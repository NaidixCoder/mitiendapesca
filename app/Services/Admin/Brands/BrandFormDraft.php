<?php
namespace App\Services\Admin\Brands;

/** Datos del formulario para el único GET posterior a una validación fallida. */
final class BrandFormDraft
{
    private const KEY = '_admin_brand_form_draft';
    private const FIELDS = [
        'name', 'slug', 'sort', 'cover_image', 'meta_title', 'meta_description', 'seo_text',
        'seo_index', 'seo_follow', 'seo_twitter_card', 'seo_canonical_override',
        'seo_og_title', 'seo_og_description', 'seo_og_image', 'seo_schema_override_jsonld',
        'seo_bullets', 'seo_specs_label', 'seo_specs_value', 'seo_faq_q', 'seo_faq_a',
    ];

    public static function remember(int $id, array $post): void
    {
        ensure_session();
        $values = [];
        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $post)) continue;
            $value = $post[$field];
            if (is_string($value)) {
                // Conservá el texto SEO/JSON-LD completo tras errores de DB o validación.
                $max = in_array($field, ['seo_text', 'seo_schema_override_jsonld'], true) ? 100000 : 15000;
                $values[$field] = mb_substr($value, 0, $max);
            } elseif (is_array($value)) {
                $values[$field] = array_slice(array_map(
                    static fn($v) => is_string($v) ? mb_substr($v, 0, 3000) : '',
                    array_values($value)
                ), 0, 25);
            }
        }
        $_SESSION[self::KEY] = ['id' => $id, 'values' => $values];
    }

    public static function consume(int $id): array
    {
        ensure_session();
        $saved = $_SESSION[self::KEY] ?? null;
        unset($_SESSION[self::KEY]);
        return is_array($saved) && ($saved['id'] ?? null) === $id
            && is_array($saved['values'] ?? null) ? $saved['values'] : [];
    }

    public static function overlay(array &$brand, array &$settings, array &$blocks, array $old): void
    {
        if (!$old) return;
        foreach (['name', 'slug', 'sort', 'cover_image', 'meta_title', 'meta_description', 'seo_text'] as $field) {
            if (is_string($old[$field] ?? null)) $brand[$field] = $old[$field];
        }
        $index = ($old['seo_index'] ?? '') === 'noindex' ? 'noindex' : 'index';
        $follow = ($old['seo_follow'] ?? '') === 'nofollow' ? 'nofollow' : 'follow';
        $settings['robots'] = "$index,$follow";
        foreach ([
            'seo_canonical_override'=>'canonical_override', 'seo_og_title'=>'og_title',
            'seo_og_description'=>'og_description', 'seo_og_image'=>'og_image',
            'seo_twitter_card'=>'twitter_card', 'seo_schema_override_jsonld'=>'schema_override_jsonld',
        ] as $field=>$key) {
            if (is_string($old[$field] ?? null)) $settings[$key] = $old[$field];
        }
        if (array_key_exists('seo_bullets', $old)) $blocks['bullets'] = $old['seo_bullets'];
        if (array_key_exists('seo_specs_label', $old) || array_key_exists('seo_specs_value', $old)) {
            $blocks['specs'] = [];
            foreach (($old['seo_specs_label'] ?? []) as $i=>$label) {
                $blocks['specs'][] = ['label'=>$label,'value'=>$old['seo_specs_value'][$i] ?? ''];
            }
        }
        if (array_key_exists('seo_faq_q', $old) || array_key_exists('seo_faq_a', $old)) {
            $blocks['faq'] = [];
            foreach (($old['seo_faq_q'] ?? []) as $i=>$q) {
                $blocks['faq'][] = ['q'=>$q,'a'=>$old['seo_faq_a'][$i] ?? ''];
            }
        }
    }
}
