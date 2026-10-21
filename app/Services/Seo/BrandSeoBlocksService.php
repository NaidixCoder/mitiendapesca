<?php
namespace App\Services\Seo;

use App\Repositories\Seo\BrandSeoBlocksRepository;
use PDO;

final class BrandSeoBlocksService
{
    private BrandSeoBlocksRepository $repo;

    public function __construct(private PDO $db)
    {
        $this->repo = new BrandSeoBlocksRepository($db);
    }

    /**
     * @return array{bullets: array<int,string>, specs: array<int,array{label:string,value:string}>, faq: array<int,array{q:string,a:string}>}
     */
    public function getForBrand(int $brandId): array
    {
        $row = $this->repo->getByBrandId($brandId);

        $decode = static function($v): array {
            if (!$v) return [];
            if (is_array($v)) return $v;
            $arr = json_decode((string)$v, true);
            return is_array($arr) ? $arr : [];
        };

        $bullets = $decode($row['bullets_json'] ?? null);
        $specs   = $decode($row['specs_json'] ?? null);
        $faq     = $decode($row['faq_json'] ?? null);

        // Normalización defensiva
        $bullets = array_values(array_filter(array_map('strval', $bullets), fn($s) => trim($s) !== ''));

        $specsOut = [];
        foreach ($specs as $it) {
            if (!is_array($it)) continue;
            $label = trim((string)($it['label'] ?? ''));
            $value = trim((string)($it['value'] ?? ''));
            if ($label === '' || $value === '') continue;
            $specsOut[] = ['label' => $label, 'value' => $value];
        }

        $faqOut = [];
        foreach ($faq as $it) {
            if (!is_array($it)) continue;
            $q = trim((string)($it['q'] ?? ''));
            $a = trim((string)($it['a'] ?? ''));
            if ($q === '' || $a === '') continue;
            $faqOut[] = ['q' => $q, 'a' => $a];
        }

        return ['bullets' => $bullets, 'specs' => $specsOut, 'faq' => $faqOut];
    }

    /**
     * Guarda solo filas completas (el resto se descarta).
     */
    public function saveForBrand(int $brandId, array $bullets, array $specs, array $faq): void
    {
        $bullets = $this->sanitizeBullets($bullets);
        $specs   = $this->sanitizeSpecs($specs);
        $faq     = $this->sanitizeFaq($faq);

        if (!$bullets && !$specs && !$faq) {
            $this->repo->deleteByBrandId($brandId);
            return;
        }

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        $bulletsJson = $bullets ? json_encode($bullets, $flags) : null;
        $specsJson   = $specs   ? json_encode($specs, $flags)   : null;
        $faqJson     = $faq     ? json_encode($faq, $flags)     : null;

        $this->repo->upsert($brandId, $bulletsJson, $specsJson, $faqJson);
    }

    private function sanitizeBullets(array $bullets): array
    {
        $out = [];
        foreach ($bullets as $b) {
            $s = trim((string)$b);
            if ($s === '') continue;
            $s = mb_substr($s, 0, 140);
            $out[] = $s;
            if (count($out) >= 20) break;
        }
        return $out;
    }

    /**
     * @param array<int,array{label:string,value:string}> $specs
     */
    private function sanitizeSpecs(array $specs): array
    {
        $out = [];
        foreach ($specs as $it) {
            if (!is_array($it)) continue;
            $label = trim((string)($it['label'] ?? ''));
            $value = trim((string)($it['value'] ?? ''));
            if ($label === '' || $value === '') continue;
            $label = mb_substr($label, 0, 80);
            $value = mb_substr($value, 0, 200);
            $out[] = ['label' => $label, 'value' => $value];
            if (count($out) >= 20) break;
        }
        return $out;
    }

    /**
     * @param array<int,array{q:string,a:string}> $faq
     */
    private function sanitizeFaq(array $faq): array
    {
        $out = [];
        foreach ($faq as $it) {
            if (!is_array($it)) continue;
            $q = trim((string)($it['q'] ?? ''));
            $a = trim((string)($it['a'] ?? ''));
            if ($q === '' || $a === '') continue;
            $q = mb_substr($q, 0, 160);
            $a = mb_substr($a, 0, 600);
            $out[] = ['q' => $q, 'a' => $a];
            if (count($out) >= 20) break;
        }
        return $out;
    }
}
