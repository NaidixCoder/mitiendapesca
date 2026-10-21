<?php
namespace App\Services\Admin\Variants;

use App\Repositories\Admin\AdminVariantReadRepository;
use PDO;

final class VariantReadService
{
    private AdminVariantReadRepository $repo;

    public function __construct(PDO $db)
    {
        $this->repo = new AdminVariantReadRepository($db);
    }

    public function getData(int $productId, array $opts = []): array
    {
        $data = $this->repo->fetchForProduct($productId, $opts);
        return $this->decorateForView($data);
    }

    private function decorateForView(array $data): array
    {
        $variants = is_array($data['variants'] ?? null) ? $data['variants'] : [];
        $pAttrs = is_array($data['variant_attributes'] ?? null) ? $data['variant_attributes'] : [];
        $valuesByVariant = is_array($data['values_by_variant'] ?? null) ? $data['values_by_variant'] : [];
        $selectedByAttr = is_array($data['selected_by_attribute'] ?? null) ? $data['selected_by_attribute'] : [];
        $obsoleteIds = is_array($data['obsolete_ids'] ?? null) ? $data['obsolete_ids'] : [];
        $variantTagCatalog = is_array($data['variant_tag_catalog'] ?? null) ? $data['variant_tag_catalog'] : [];
        $variantTagsByVariant = is_array($data['variant_tags_by_variant'] ?? null) ? $data['variant_tags_by_variant'] : [];

        $requiredAttrIds = [];
        foreach ($pAttrs as $pa) {
            $aid = (int)($pa['attribute_id'] ?? $pa['id'] ?? 0);
            if ($aid <= 0) {
                continue;
            }
            $isReq = (int)($pa['is_required'] ?? 1) === 1;
            $isVariantAxis = (int)($pa['is_variant_axis'] ?? 0) === 1;
            if ($isReq && $isVariantAxis) {
                $requiredAttrIds[] = $aid;
            }
        }
        $requiredAttrIds = array_values(array_unique($requiredAttrIds));

        $incompleteIds = [];
        if (!empty($requiredAttrIds) && !empty($variants)) {
            foreach ($variants as $variant) {
                $vid = (int)($variant['id'] ?? 0);
                if ($vid <= 0) {
                    continue;
                }
                $active = (int)($variant['is_active'] ?? 1) === 1;
                if (!$active) {
                    continue;
                }
                $vals = $valuesByVariant[$vid] ?? [];
                $present = [];
                foreach ((array)$vals as $vv) {
                    $aid = (int)($vv['attribute_id'] ?? 0);
                    if ($aid > 0) {
                        $present[$aid] = true;
                    }
                }
                foreach ($requiredAttrIds as $rid) {
                    if (empty($present[$rid])) {
                        $incompleteIds[] = $vid;
                        break;
                    }
                }
            }
        }
        $incompleteIds = array_values(array_unique(array_map('intval', $incompleteIds)));

        $compactByVariant = [];
        $variantTagNamesById = [];
        foreach ($variantTagCatalog as $group) {
            foreach ((array)($group['tags'] ?? []) as $tag) {
                $tagId = (int)($tag['id'] ?? 0);
                if ($tagId > 0) {
                    $variantTagNamesById[$tagId] = (string)($tag['name'] ?? '');
                }
            }
        }
        $variantTagSummaryByVariant = [];
        foreach ($variants as $variant) {
            $vid = (int)($variant['id'] ?? 0);
            if ($vid <= 0) {
                continue;
            }
            $compactByVariant[$vid] = $this->compactVariantLabel($vid, $pAttrs, $valuesByVariant);
            $selectedTagIds = array_values(array_unique(array_map('intval', (array)($variantTagsByVariant[$vid] ?? []))));
            $labels = [];
            foreach ($selectedTagIds as $tagId) {
                $name = trim((string)($variantTagNamesById[$tagId] ?? ''));
                if ($name !== '') {
                    $labels[] = $name;
                }
            }
            $variantTagSummaryByVariant[$vid] = [
                'count' => count($selectedTagIds),
                'labels' => $labels,
                'text' => implode(', ', $labels),
            ];
        }

        $anySelected = false;
        foreach ($selectedByAttr as $arr) {
            if (is_array($arr) && count($arr) > 0) {
                $anySelected = true;
                break;
            }
        }

        $ivaRate = (float)($data['product_iva_rate'] ?? 0);
        $ivaFactor = ($ivaRate > 1.0) ? ($ivaRate / 100.0) : $ivaRate;
        if ($ivaFactor < 0) {
            $ivaFactor = 0.0;
        }
        $ivaPct = $ivaFactor * 100.0;

        $marginRaw = $data['product_margin_pct'] ?? null;
        $marginDisplay = '';
        if ($marginRaw !== null && $marginRaw !== '') {
            $mNum = (float)$marginRaw;
            $mFactor = ($mNum > 2.0) ? ($mNum / 100.0) : $mNum;
            if ($mFactor < 0) {
                $mFactor = 0.0;
            }
            $marginDisplay = rtrim(rtrim(number_format($mFactor * 100.0, 2, '.', ''), '0'), '.');
        }

        $data['has_variants'] = !empty($variants);
        $data['required_attr_ids'] = $requiredAttrIds;
        $data['incomplete_ids'] = $incompleteIds;
        $data['has_incomplete'] = !empty($incompleteIds);
        $data['has_obsolete'] = !empty($obsoleteIds);
        $data['obsolete_ids_csv'] = implode(',', array_map('intval', $obsoleteIds));
        $data['incomplete_ids_csv'] = implode(',', $incompleteIds);
        $data['compact_label_by_variant'] = $compactByVariant;
        $data['variant_tag_summary_by_variant'] = $variantTagSummaryByVariant;
        $data['any_selected'] = $anySelected;
        $data['product_iva_factor'] = $ivaFactor;
        $data['product_iva_pct'] = $ivaPct;
        $data['product_margin_display_pct'] = $marginDisplay;

        return $data;
    }

    private function compactVariantLabel(int $variantId, array $productAttributes, array $valuesByVariant): string
    {
        $vals = $valuesByVariant[$variantId] ?? [];
        $map = [];
        foreach ((array)$vals as $v) {
            $aid = (int)($v['attribute_id'] ?? 0);
            if ($aid > 0) {
                $map[$aid] = (string)($v['value'] ?? '');
            }
        }

        $parts = [];
        foreach ($productAttributes as $pa) {
            $aid = (int)($pa['attribute_id'] ?? $pa['id'] ?? 0);
            if ($aid <= 0) {
                continue;
            }
            $value = trim((string)($map[$aid] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        if (empty($parts) && !empty($vals)) {
            foreach ((array)$vals as $v) {
                $value = trim((string)($v['value'] ?? ''));
                if ($value !== '') {
                    $parts[] = $value;
                }
            }
        }

        return implode(' - ', $parts);
    }
}
