<?php
namespace App\Services\Catalog;

final class EditorialBlockViewModelService
{
    /**
     * @param array<string,mixed> $entity
     * @param array<string,mixed> $blocks
     * @return array<string,mixed>|null
     */
    public function build(string $title, array $entity, array $blocks): ?array
    {
        $seoText = trim((string)($entity['seo_text'] ?? ''));
        $imageUrl = $this->resolveImageUrl($entity);
        $bullets = $this->normalizeBullets($blocks['bullets'] ?? []);
        $specs = $this->normalizeSpecs($blocks['specs'] ?? []);
        $faq = $this->normalizeFaq($blocks['faq'] ?? []);

        if ($seoText === '' && $imageUrl === '' && $bullets === [] && $specs === [] && $faq === []) {
            return null;
        }

        return [
            'title' => trim($title) !== '' ? trim($title) : 'Informacion adicional',
            'seoText' => $seoText,
            'imageUrl' => $imageUrl,
            'imageAlt' => trim((string)($entity['name'] ?? '')) !== ''
                ? ('Portada de ' . trim((string)($entity['name'] ?? '')))
                : 'Imagen editorial',
            'bullets' => $bullets,
            'specs' => $specs,
            'faq' => $faq,
        ];
    }

    /**
     * @param array<string,mixed> $entity
     */
    private function resolveImageUrl(array $entity): string
    {
        $imageUrl = trim((string)($entity['cover_image'] ?? ''));
        if ($imageUrl === '') {
            return '';
        }

        if (!preg_match('~^https?://~i', $imageUrl)) {
            return base_url(ltrim($imageUrl, '/'));
        }

        return $imageUrl;
    }

    /**
     * @param mixed $raw
     * @return array<int,string>
     */
    private function normalizeBullets($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $rows = [];
        foreach ($raw as $item) {
            $value = trim((string)$item);
            if ($value !== '') {
                $rows[] = $value;
            }
        }

        return $rows;
    }

    /**
     * @param mixed $raw
     * @return array<int,array{label:string,value:string}>
     */
    private function normalizeSpecs($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $rows = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string)($item['label'] ?? ''));
            $value = trim((string)($item['value'] ?? ''));
            if ($label === '' || $value === '') {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $rows;
    }

    /**
     * @param mixed $raw
     * @return array<int,array{q:string,a:string}>
     */
    private function normalizeFaq($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $rows = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $question = trim((string)($item['q'] ?? ''));
            $answer = trim((string)($item['a'] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }

            $rows[] = [
                'q' => $question,
                'a' => $answer,
            ];
        }

        return $rows;
    }
}
