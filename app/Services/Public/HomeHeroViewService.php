<?php
namespace App\Services\Public;

final class HomeHeroViewService
{
    /**
     * @param array<int,array<string,mixed>> $slides
     * @return array<int,array<string,string>>
     */
    public function build(array $slides): array
    {
        $out = [];
        foreach ($slides as $slide) {
            if (!is_array($slide)) continue;
            $desktop = $this->toWebAssetUrl((string)($slide['image_desktop'] ?? ''));
            if ($desktop === '') continue;

            $target = trim((string)($slide['target_url'] ?? ''));
            if ($target === '') $target = base_url('productos');

            $out[] = [
                'title' => trim((string)($slide['title'] ?? '')),
                'alt_text' => trim((string)($slide['alt_text'] ?? '')) ?: 'Slide hero',
                'image_desktop' => $desktop,
                'image_mobile' => $this->toWebAssetUrl((string)($slide['image_mobile'] ?? '')),
                'target_url' => $target,
            ];
        }

        if (!empty($out)) return $out;

        return [
            [
                'title' => 'Slide 1',
                'alt_text' => 'Equipo de pesca en primer plano',
                'image_desktop' => asset('img/hero/hero-pesca-01.png'),
                'image_mobile' => '',
                'target_url' => base_url('productos'),
            ],
            [
                'title' => 'Slide 2',
                'alt_text' => 'Caja con accesorios de pesca',
                'image_desktop' => asset('img/hero/hero-pesca-01a.jpg'),
                'image_mobile' => '',
                'target_url' => base_url('productos'),
            ],
            [
                'title' => 'Slide 3',
                'alt_text' => 'Paisaje de pesca con cana',
                'image_desktop' => asset('img/hero/hero-pesca-02.jpg'),
                'image_mobile' => '',
                'target_url' => base_url('productos'),
            ],
        ];
    }

    private function toWebAssetUrl(string $value): string
    {
        $s = trim($value);
        if ($s === '') return '';
        $s = str_replace('\\', '/', $s);
        if (preg_match('~^https?://~i', $s)) return $s;

        $posPublic = stripos($s, '/public_html/');
        if ($posPublic !== false) {
            $s = substr($s, $posPublic + strlen('/public_html/'));
        }

        if (str_starts_with($s, 'assets/')) return url('/' . ltrim($s, '/'));
        if (str_starts_with($s, 'img/')) return asset($s);
        if (str_starts_with($s, '/assets/')) return url($s);
        if (str_starts_with($s, '/img/')) return url('/assets' . $s);
        return url('/assets/' . ltrim($s, '/'));
    }
}

