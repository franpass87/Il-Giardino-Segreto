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
        // I contenuti del tour (galleria, programma, dettagli) non sono più tab
        // WooCommerce: vengono resi inline da TourLayout::renderTourContent() in un
        // unico contenitore coerente. I metodi render* qui restano usati da TourLayout.
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
        $hasGalleria = $this->hasGalleria($product);

        // La mappa NON viene aggiunta come tab: lo shortcode [mappa_viaggio] è già
        // inserito nel contenuto della pagina. Aggiungerla qui creerebbe un secondo
        // container con lo stesso id (errore "Map container already initialized").

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
        // Mostra la galleria SOLO se esistono vere immagini di galleria WooCommerce.
        // Con la sola immagine in evidenza (già usata nell'hero) si avrebbe un'immagine
        // singola "orfana" in fondo alla pagina.
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
        $allIds = array_values(array_unique(array_filter(array_map('absint', $allIds))));

        if (empty($allIds)) {
            return;
        }

        // Cap a 12 immagini: una griglia ordinata e una pagina non troppo lunga
        // (anche con gallerie prodotto molto numerose). Filtrabile se serve.
        $maxImages = (int) apply_filters('igs_tour_gallery_max_images', 12, $product);
        if ($maxImages > 0 && count($allIds) > $maxImages) {
            $allIds = array_slice($allIds, 0, $maxImages);
        }
        ?>
        <div class="igs-tour-galleria igs-reveal">
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

        // Timeline verticale: ogni giorno è una tappa con marker numerato; l'animazione
        // di comparsa allo scroll (igs-reveal → igs-in) è gestita da tour-experience.js.
        echo '<div class="igs-tour-programma igs-timeline">';
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
            $marker = $num > 0 ? (string) $num : '·';
            echo '<section class="igs-timeline-item igs-reveal">';
            echo '<div class="igs-timeline-marker" aria-hidden="true">' . esc_html($marker) . '</div>';
            echo '<div class="igs-timeline-body">';
            echo '<h3>' . esc_html($heading) . '</h3>';
            echo '<div class="igs-programma-content">' . wp_kses_post(wpautop($contenuto)) . '</div>';
            echo '</div>';
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

        $info = get_post_meta($id, '_igs_tour_info', true);
        $info = is_string($info) ? $info : '';

        $lbl = [
            'caratteristiche' => __('Caratteristiche del Tour', 'igs-ecommerce'),
            'cosaPortare' => __('Cosa portare in valigia', 'igs-ecommerce'),
            'documenti' => __('Documenti necessari', 'igs-ecommerce'),
            'quotaComprende' => __('La quota comprende', 'igs-ecommerce'),
            'quotaNonComprende' => __('La quota non comprende', 'igs-ecommerce'),
            'voli' => __('Voli aerei consigliati', 'igs-ecommerce'),
            'info' => __('Info generali', 'igs-ecommerce'),
        ];

        // Costruisce le voci dell'accordion (apri/chiudi in JS, prima aperta).
        $items = [];
        if (!empty($caratteristiche)) {
            $html = $this->buildCaratteristicheHtml($caratteristiche, $isIt);
            if ($html !== '') {
                $items[] = ['title' => $lbl['caratteristiche'], 'class' => 'igs-caratteristiche', 'html' => $html];
            }
        }
        if (trim($cosaPortare) !== '') {
            $items[] = ['title' => $lbl['cosaPortare'], 'class' => 'igs-cosa-portare', 'html' => $this->buildListHtml($cosaPortare)];
        }
        if (trim($documenti) !== '') {
            $items[] = ['title' => $lbl['documenti'], 'class' => 'igs-documenti', 'html' => '<div class="igs-documenti-content">' . wp_kses_post(wpautop($documenti)) . '</div>'];
        }
        if (trim($quotaComprende) !== '') {
            $items[] = ['title' => $lbl['quotaComprende'], 'class' => 'igs-quota-comprende', 'html' => $this->buildListHtml($quotaComprende)];
        }
        if (trim($quotaNonComprende) !== '') {
            $items[] = ['title' => $lbl['quotaNonComprende'], 'class' => 'igs-quota-non-comprende', 'html' => $this->buildListHtml($quotaNonComprende)];
        }
        if (trim($voli) !== '') {
            $items[] = ['title' => $lbl['voli'], 'class' => 'igs-voli', 'html' => '<div class="igs-voli-content">' . wp_kses_post(wpautop($voli)) . '</div>'];
        }
        if (trim($info) !== '') {
            $items[] = ['title' => $lbl['info'], 'class' => 'igs-info-generali', 'html' => $this->buildListHtml($info, true)];
        }

        if (empty($items)) {
            return;
        }

        echo '<div class="igs-tour-dettagli igs-accordion igs-reveal">';
        $chevron = '<svg class="igs-acc-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
        foreach ($items as $i => $it) {
            $open = $i === 0;
            echo '<div class="igs-acc-item ' . esc_attr($it['class']) . ($open ? ' is-open' : '') . '">';
            echo '<button type="button" class="igs-acc-head" aria-expanded="' . ($open ? 'true' : 'false') . '">';
            echo '<span class="igs-acc-title">' . esc_html($it['title']) . '</span>' . $chevron;
            echo '</button>';
            echo '<div class="igs-acc-panel"><div class="igs-acc-panel-inner">' . $it['html'] . '</div></div>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Costruisce l'HTML (già escapato) delle card caratteristiche libere
     * (_igs_tour_caratteristiche) per il pannello accordion.
     *
     * @param array<int, array<string, mixed>> $caratteristiche
     */
    private function buildCaratteristicheHtml(array $caratteristiche, bool $isIt): string
    {
        $out = '<div class="igs-caratteristiche-cards">';
        $rendered = 0;
        foreach ($caratteristiche as $c) {
            $title = $isIt ? ($c['it'] ?? $c['en'] ?? '') : ($c['en'] ?? $c['it'] ?? '');
            if ($title === '') {
                continue;
            }
            $subtitle = $isIt ? ($c['subtitle_it'] ?? $c['subtitle_en'] ?? '') : ($c['subtitle_en'] ?? $c['subtitle_it'] ?? '');
            $icon = $c['icon'] ?? '🌱';
            $iconImageId = isset($c['icon_image']) ? absint($c['icon_image']) : 0;
            $rating = isset($c['rating']) ? (int) $c['rating'] : 0;
            $out .= '<div class="igs-caratteristica-card">';
            $out .= '<div class="igs-car-icon">';
            if ($iconImageId > 0) {
                $img = wp_get_attachment_image($iconImageId, 'thumbnail', false, ['style' => 'width:100%;height:100%;object-fit:contain;']);
                $out .= $img !== '' ? wp_kses_post($img) : esc_html((string) $icon);
            } else {
                $out .= esc_html((string) $icon);
            }
            $out .= '</div>';
            $out .= '<div class="igs-car-title">' . esc_html((string) $title) . '</div>';
            if ($subtitle !== '') {
                $out .= '<div class="igs-car-subtitle">' . esc_html((string) $subtitle) . '</div>';
            }
            if ($rating > 0) {
                $out .= '<div class="igs-car-rating">';
                for ($i = 1; $i <= 5; $i++) {
                    $fill = $i <= $rating ? 'var(--igs-brand)' : '#e2e8f0';
                    $out .= '<span style="background:' . esc_attr($fill) . ';"></span>';
                }
                $out .= '</div>';
            }
            $out .= '</div>';
            $rendered++;
        }
        $out .= '</div>';

        return $rendered > 0 ? $out : '';
    }

    /** Costruisce una lista <ul> (già escapata) dalle righe di testo. */
    private function buildListHtml(string $text, bool $allowHtml = false): string
    {
        $items = $this->linesToArray($text);
        if (empty($items)) {
            return '';
        }
        $out = '<ul>';
        foreach ($items as $item) {
            $out .= '<li>' . ($allowHtml ? wp_kses_post($item) : esc_html($item)) . '</li>';
        }

        return $out . '</ul>';
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
