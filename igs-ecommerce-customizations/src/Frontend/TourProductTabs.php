<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use WC_Product;

/**
 * Tab e rendering per il contenuto strutturato tour (Programma, Quota, Documenti, Voli).
 */
class TourProductTabs
{
    public function register(): void
    {
        add_filter('woocommerce_product_tabs', [$this, 'addTabs'], 15);
    }

    /** @param array<string, array{title: string, callback: callable, priority: int}> $tabs */
    public function addTabs(array $tabs): array
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return $tabs;
        }

        $id = $product->get_id();
        $programma = get_post_meta($id, '_igs_tour_programma', true);
        $hasProgramma = is_array($programma) && !empty($programma);

        $hasInfo = $this->hasStructuredInfo($id);
        $hasMappa = $this->hasMappa($id);
        $hasGalleria = $this->hasGalleria($product);

        if ($hasMappa) {
            $tabs['igs_mappa'] = [
                'title' => __('Mappa', 'igs-ecommerce'),
                'callback' => [$this, 'renderMappa'],
                'priority' => 10,
            ];
        }

        if ($hasGalleria) {
            $tabs['igs_galleria'] = [
                'title' => __('Galleria', 'igs-ecommerce'),
                'callback' => [$this, 'renderGalleria'],
                'priority' => 11,
            ];
        }

        if ($hasProgramma) {
            $tabs['igs_programma'] = [
                'title' => __('Programma del Tour', 'igs-ecommerce'),
                'callback' => [$this, 'renderProgramma'],
                'priority' => 12,
            ];
        }

        if ($hasInfo) {
            $tabs['igs_dettagli_viaggio'] = [
                'title' => __('Tutto quello che devi sapere', 'igs-ecommerce'),
                'callback' => [$this, 'renderDettagliViaggio'],
                'priority' => 14,
            ];
        }

        return $tabs;
    }

    private function hasStructuredInfo(int $productId): bool
    {
        $fields = [
            '_igs_tour_cosa_portare',
            '_igs_tour_documenti',
            '_igs_tour_quota_comprende',
            '_igs_tour_quota_non_comprende',
            '_igs_tour_voli',
        ];
        foreach ($fields as $key) {
            $v = get_post_meta($productId, $key, true);
            if (is_string($v) && trim($v) !== '') {
                return true;
            }
        }
        $car = get_post_meta($productId, '_igs_tour_caratteristiche', true);
        return is_array($car) && !empty($car);
    }

    private function hasMappa(int $productId): bool
    {
        $tappe = get_post_meta($productId, '_mappa_tappe', true);
        return is_array($tappe) && !empty($tappe);
    }

    private function hasGalleria(WC_Product $product): bool
    {
        if ($product->get_image_id()) {
            return true;
        }
        $galleryIds = $product->get_gallery_image_ids();
        return !empty($galleryIds);
    }

    public function renderMappa(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }
        echo do_shortcode('[mappa_viaggio id="' . (int) $product->get_id() . '"]');
    }

    public function renderGalleria(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $mainId = $product->get_image_id();
        $galleryIds = $product->get_gallery_image_ids();
        $allIds = $mainId ? array_merge([$mainId], $galleryIds) : $galleryIds;
        $allIds = array_unique(array_filter(array_map('absint', $allIds)));

        if (empty($allIds)) {
            return;
        }
        ?>
        <div class="igs-tour-galleria">
        <?php
        foreach ($allIds as $attachId) {
            $full = wp_get_attachment_image_url($attachId, 'large');
            $thumb = wp_get_attachment_image_url($attachId, 'medium');
            $alt = get_post_meta($attachId, '_wp_attachment_image_alt', true);
            if (!$thumb) {
                $thumb = $full;
            }
            if ($full && $thumb) {
                echo '<a href="' . esc_url($full) . '" class="igs-gallery-item" target="_blank" rel="noopener">';
                echo '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($alt ?: get_the_title($product->get_id())) . '" loading="lazy">';
                echo '</a>';
            }
        }
        ?>
        </div>
        <?php
    }

    public function renderProgramma(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $programma = get_post_meta($product->get_id(), '_igs_tour_programma', true);
        if (!is_array($programma) || empty($programma)) {
            return;
        }

        echo '<div class="igs-tour-programma">';
        foreach ($programma as $day) {
            $num = (int) ($day['num'] ?? 0);
            $titolo = $day['titolo'] ?? '';
            $contenuto = $day['contenuto'] ?? '';
            if ($titolo === '' && $contenuto === '') {
                continue;
            }
            if ($num > 0 && $titolo !== '') {
                $heading = sprintf(
                    /* translators: 1: day number, 2: day title */
                    __('Giorno %1$d - %2$s', 'igs-ecommerce'),
                    $num,
                    $titolo
                );
            } elseif ($num > 0) {
                $heading = sprintf(
                    /* translators: %d: day number */
                    __('Giorno %d', 'igs-ecommerce'),
                    $num
                );
            } else {
                $heading = $titolo;
            }
            echo '<section class="igs-programma-day">';
            echo '<h3>' . esc_html($heading) . '</h3>';
            echo '<div class="igs-programma-content">' . wp_kses_post($contenuto) . '</div>';
            echo '</section>';
        }
        echo '</div>';
    }

    public function renderDettagliViaggio(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $isIt = \IGS\Ecommerce\Helper\Locale::isIt();
        $id = $product->get_id();

        $caratteristiche = get_post_meta($id, '_igs_tour_caratteristiche', true);
        $caratteristiche = is_array($caratteristiche) ? $caratteristiche : [];

        $cosaPortare = get_post_meta($id, '_igs_tour_cosa_portare', true);
        $documenti = get_post_meta($id, '_igs_tour_documenti', true);
        $quotaComprende = get_post_meta($id, '_igs_tour_quota_comprende', true);
        $quotaNonComprende = get_post_meta($id, '_igs_tour_quota_non_comprende', true);
        $voli = get_post_meta($id, '_igs_tour_voli', true);

        $lbl = [
            'caratteristiche' => __('Caratteristiche del Tour', 'igs-ecommerce'),
            'cosaPortare' => __('Cosa portare in valigia', 'igs-ecommerce'),
            'documenti' => __('Documenti necessari', 'igs-ecommerce'),
            'quotaComprende' => __('La quota comprende', 'igs-ecommerce'),
            'quotaNonComprende' => __('La quota non comprende', 'igs-ecommerce'),
            'voli' => __('Voli aerei consigliati', 'igs-ecommerce'),
        ];

        echo '<div class="igs-tour-dettagli">';

        if (!empty($caratteristiche)) {
            echo '<section class="igs-caratteristiche">';
            echo '<h3>' . esc_html($lbl['caratteristiche']) . '</h3>';
            echo '<div class="igs-caratteristiche-cards">';
            foreach ($caratteristiche as $c) {
                $title = $isIt ? ($c['it'] ?? $c['en'] ?? '') : ($c['en'] ?? $c['it'] ?? '');
                $subtitle = $isIt ? ($c['subtitle_it'] ?? $c['subtitle_en'] ?? '') : ($c['subtitle_en'] ?? $c['subtitle_it'] ?? '');
                $icon = $c['icon'] ?? '🌱';
                $iconImageId = isset($c['icon_image']) ? absint($c['icon_image']) : 0;
                $rating = isset($c['rating']) ? (int) $c['rating'] : 0;
                if ($title === '') {
                    continue;
                }
                echo '<div class="igs-caratteristica-card">';
                echo '<div class="igs-car-icon">';
                if ($iconImageId > 0) {
                    $img = wp_get_attachment_image($iconImageId, 'thumbnail', false, ['style' => 'width:100%;height:100%;object-fit:contain;']);
                    if ($img !== '') {
                        echo wp_kses_post($img);
                    } else {
                        echo esc_html($icon);
                    }
                } else {
                    echo esc_html($icon);
                }
                echo '</div>';
                echo '<div class="igs-car-title">' . esc_html($title) . '</div>';
                if ($subtitle !== '') {
                    echo '<div class="igs-car-subtitle">' . esc_html($subtitle) . '</div>';
                }
                if ($rating > 0) {
                    echo '<div class="igs-car-rating">';
                    for ($i = 1; $i <= 5; $i++) {
                        $fill = $i <= $rating ? 'var(--igs-brand)' : '#e2e8f0';
                        echo '<span style="background:' . esc_attr($fill) . ';"></span>';
                    }
                    echo '</div>';
                }
                echo '</div>';
            }
            echo '</div></section>';
        }

        if ($cosaPortare !== '') {
            $items = $this->linesToArray($cosaPortare);
            echo '<section class="igs-cosa-portare">';
            echo '<h3>' . esc_html($lbl['cosaPortare']) . '</h3>';
            echo '<ul>';
            foreach ($items as $item) {
                echo '<li>' . esc_html($item) . '</li>';
            }
            echo '</ul></section>';
        }

        if ($documenti !== '') {
            echo '<section class="igs-documenti">';
            echo '<h3>' . esc_html($lbl['documenti']) . '</h3>';
            echo '<div class="igs-documenti-content">' . wp_kses_post(wpautop($documenti)) . '</div></section>';
        }

        if ($quotaComprende !== '') {
            $items = $this->linesToArray($quotaComprende);
            echo '<section class="igs-quota-comprende">';
            echo '<h3>' . esc_html($lbl['quotaComprende']) . '</h3>';
            echo '<ul>';
            foreach ($items as $item) {
                echo '<li>' . esc_html($item) . '</li>';
            }
            echo '</ul></section>';
        }

        if ($quotaNonComprende !== '') {
            $items = $this->linesToArray($quotaNonComprende);
            echo '<section class="igs-quota-non-comprende">';
            echo '<h3>' . esc_html($lbl['quotaNonComprende']) . '</h3>';
            echo '<ul>';
            foreach ($items as $item) {
                echo '<li>' . esc_html($item) . '</li>';
            }
            echo '</ul></section>';
        }

        if ($voli !== '') {
            echo '<section class="igs-voli">';
            echo '<h3>' . esc_html($lbl['voli']) . '</h3>';
            echo '<div class="igs-voli-content">' . wp_kses_post(wpautop($voli)) . '</div></section>';
        }

        echo '</div>';
    }

    /** @return list<string> */
    private function linesToArray(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $result = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $result[] = $line;
            }
        }
        return $result;
    }
}
