<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\CountryFlags;
use IGS\Ecommerce\Helper\Locale;
use WC_Product;

/**
 * Layout "Editoriale a rail fisso" per la scheda tour (forma scelta per i garden tour).
 *
 * Colonna sinistra sticky con titolo, dati, prezzo e CTA sempre visibili; colonna
 * destra con copertina, racconto, programma numerato, galleria a mosaico e info.
 * HTML pulito generato da zero: nessun riassunto/tab WooCommerce, nessun WPBakery.
 *
 * Reso dal template content-single-tour.php solo per i tour gestiti. La prenotazione
 * resta il modal di BookingModal, aperto dal bottone del rail (data-igs-open-modal).
 */
class TourEditorial
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles'], 20);
    }

    public function enqueueStyles(): void
    {
        if (!is_product() || !$this->currentIsManaged()) {
            return;
        }
        wp_add_inline_style('woocommerce-general', $this->css());
    }

    private function currentIsManaged(): bool
    {
        $id = get_queried_object_id();
        if (!$id) {
            return false;
        }
        $programma = get_post_meta($id, '_igs_tour_programma', true);

        return is_array($programma) && !empty($programma);
    }

    public function render(WC_Product $product): void
    {
        $isIt = Locale::isIt();
        $id = $product->get_id();

        $paese = (string) get_post_meta($id, '_paese_tour', true);
        $ranges = get_post_meta($id, '_date_ranges', true);
        $first = is_array($ranges) && !empty($ranges) ? $ranges[0] : null;
        $excerpt = apply_filters('woocommerce_short_description', $product->get_short_description());

        $coverId = $product->get_image_id();
        $coverUrl = $coverId ? wp_get_attachment_image_url($coverId, 'full') : wc_placeholder_img_src();
        $thumbUrl = $coverId ? wp_get_attachment_image_url($coverId, 'large') : $coverUrl;

        $programma = get_post_meta($id, '_igs_tour_programma', true);
        $programma = is_array($programma) ? $programma : [];
        $galleryIds = $this->galleryIds($product);
        $hasInfo = $this->hasInfo($id);

        // Voci di navigazione (solo sezioni presenti).
        $sections = [['id' => 'ed-viaggio', 'label' => __('Il viaggio', 'igs-ecommerce')]];
        if (!empty($programma)) {
            $sections[] = ['id' => 'ed-programma', 'label' => __('Programma', 'igs-ecommerce')];
        }
        if (!empty($galleryIds)) {
            $sections[] = ['id' => 'ed-galleria', 'label' => __('Galleria', 'igs-ecommerce')];
        }
        if ($hasInfo) {
            $sections[] = ['id' => 'ed-info', 'label' => __('Informazioni', 'igs-ecommerce')];
        }

        echo '<div class="igs-editorial">';

        /* ---------- RAIL ---------- */
        echo '<aside class="igs-ed-rail">';
        echo '<div class="igs-ed-rail-inner">';
        echo '<div class="igs-ed-kicker">' . esc_html__('Il Giardino Segreto · Garden Tour', 'igs-ecommerce') . '</div>';
        echo '<h1 class="igs-ed-title">' . esc_html(get_the_title()) . '</h1>';

        $where = $paese !== '' ? CountryFlags::withFlagHtml($paese) : esc_html__('Italia', 'igs-ecommerce');
        $dateLabel = $first ? esc_html($first['start'] . ' – ' . $first['end']) : esc_html__('Date in definizione', 'igs-ecommerce');
        echo '<div class="igs-ed-where">' . $where . ' &nbsp;·&nbsp; ' . $dateLabel . '</div>';

        if ($thumbUrl) {
            echo '<div class="igs-ed-thumb" style="background-image:url(\'' . esc_url($thumbUrl) . '\')"></div>';
        }

        echo '<ul class="igs-ed-facts">';
        $duration = $this->durationLabel($first);
        if ($duration !== '') {
            echo '<li><span>' . esc_html__('Durata', 'igs-ecommerce') . '</span><span>' . esc_html($duration) . '</span></li>';
        }
        if ($first) {
            echo '<li><span>' . esc_html__('Partenza', 'igs-ecommerce') . '</span><span>' . esc_html($first['start']) . '</span></li>';
        }
        $protagonista = trim((string) get_post_meta($id, '_protagonista_tour', true));
        if ($protagonista !== '') {
            echo '<li><span>' . esc_html__('Protagonista', 'igs-ecommerce') . '</span><span>' . esc_html($protagonista) . '</span></li>';
        }
        $escl = (int) get_post_meta($id, '_livello_esclusivita', true);
        if ($escl >= 1) {
            echo '<li><span>' . esc_html__('Esclusività', 'igs-ecommerce') . '</span><span class="igs-ed-stars">' . $this->stars($escl) . '</span></li>';
        }
        echo '</ul>';

        $priceHtml = apply_filters('woocommerce_get_price_html', '', $product);
        $installIt = (string) get_post_meta($id, '_igs_installment_text_it', true);
        $installEn = (string) get_post_meta($id, '_igs_installment_text_en', true);
        $install = $isIt ? ($installIt !== '' ? $installIt : __('Pagamento a rate disponibile', 'igs-ecommerce')) : ($installEn !== '' ? $installEn : __('Installment payment available', 'igs-ecommerce'));
        echo '<div class="igs-ed-price">';
        if ($priceHtml !== '') {
            echo '<div class="igs-ed-amount">' . $priceHtml . '</div>';
        }
        echo '<div class="igs-ed-install">' . esc_html($install) . '</div>';
        echo '</div>';

        echo '<button type="button" class="igs-ed-book" data-igs-open-modal>' . esc_html__('Scopri e Prenota', 'igs-ecommerce') . ' →</button>';

        if (count($sections) > 1) {
            echo '<nav class="igs-ed-nav" data-igs-spy aria-label="' . esc_attr__('Sezioni del tour', 'igs-ecommerce') . '">';
            foreach ($sections as $i => $s) {
                $on = $i === 0 ? ' class="is-active"' : '';
                echo '<a href="#' . esc_attr($s['id']) . '"' . $on . '>' . esc_html($s['label']) . '</a>';
            }
            echo '</nav>';
        }
        echo '</div></aside>';

        /* ---------- CONTENT ---------- */
        echo '<main class="igs-ed-content">';

        if ($coverUrl) {
            echo '<div class="igs-ed-cover igs-reveal" style="background-image:url(\'' . esc_url($coverUrl) . '\')"></div>';
        }

        echo '<div class="igs-ed-pad">';

        // Il viaggio: lead + caratteristiche.
        echo '<section id="ed-viaggio" class="igs-ed-sec">';
        if ($excerpt !== '') {
            echo '<div class="igs-ed-lead igs-reveal">' . wp_kses_post($excerpt) . '</div>';
        }
        $this->renderLevels($id, $isIt);
        echo '</section>';

        // Programma.
        if (!empty($programma)) {
            echo '<section id="ed-programma" class="igs-ed-sec">';
            echo '<h2 class="igs-ed-h2">' . esc_html__('Il programma, giorno per giorno', 'igs-ecommerce') . '</h2>';
            foreach ($programma as $day) {
                $num = (int) ($day['num'] ?? 0);
                $titolo = (string) ($day['titolo'] ?? '');
                $contenuto = (string) ($day['contenuto'] ?? '');
                if ($titolo === '' && $contenuto === '') {
                    continue;
                }
                echo '<article class="igs-ed-day igs-reveal">';
                echo '<div class="igs-ed-day-n">' . esc_html($num > 0 ? (string) $num : '·') . '</div>';
                echo '<div class="igs-ed-day-b">';
                if ($titolo !== '') {
                    echo '<h3>' . esc_html($titolo) . '</h3>';
                }
                if ($contenuto !== '') {
                    echo '<div class="igs-ed-day-text">' . wp_kses_post(wpautop($contenuto)) . '</div>';
                }
                echo '</div></article>';
            }
            echo '</section>';
        }

        // Galleria (mosaico, con lightbox via tour-experience.js).
        if (!empty($galleryIds)) {
            echo '<section id="ed-galleria" class="igs-ed-sec">';
            echo '<h2 class="igs-ed-h2">' . esc_html__('Galleria', 'igs-ecommerce') . '</h2>';
            echo '<div class="igs-ed-gallery igs-reveal">';
            foreach ($galleryIds as $aid) {
                $full = wp_get_attachment_image_url($aid, 'large');
                $thumb = wp_get_attachment_image_url($aid, 'medium_large') ?: $full;
                $alt = (string) get_post_meta($aid, '_wp_attachment_image_alt', true);
                if ($full) {
                    echo '<a href="' . esc_url($full) . '" class="igs-gallery-item" target="_blank" rel="noopener">';
                    echo '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($alt !== '' ? $alt : get_the_title()) . '" loading="lazy">';
                    echo '</a>';
                }
            }
            echo '</div></section>';
        }

        // Informazioni.
        if ($hasInfo) {
            echo '<section id="ed-info" class="igs-ed-sec">';
            echo '<h2 class="igs-ed-h2">' . esc_html__('Tutto quello che devi sapere', 'igs-ecommerce') . '</h2>';
            $this->renderInfo($id);
            echo '</section>';
        }

        echo '</div></main></div>';
    }

    /** @return list<int> */
    private function galleryIds(WC_Product $product): array
    {
        $mainId = $product->get_image_id();
        $ids = $product->get_gallery_image_ids();
        $all = $mainId ? array_merge([$mainId], $ids) : $ids;
        $all = array_values(array_unique(array_filter(array_map('absint', $all))));
        $max = (int) apply_filters('igs_tour_gallery_max_images', 12, $product);
        if ($max > 0 && count($all) > $max) {
            $all = array_slice($all, 0, $max);
        }

        return $all;
    }

    private function hasInfo(int $id): bool
    {
        foreach (['_igs_tour_quota_comprende', '_igs_tour_quota_non_comprende', '_igs_tour_cosa_portare', '_igs_tour_documenti', '_igs_tour_voli', '_igs_tour_info'] as $k) {
            $v = get_post_meta($id, $k, true);
            if (is_string($v) && trim($v) !== '') {
                return true;
            }
        }

        return false;
    }

    private function renderLevels(int $id, bool $isIt): void
    {
        $levels = [
            ['k' => '_livello_culturale', 'it' => 'Cultura', 'en' => 'Culture'],
            ['k' => '_livello_passeggiata', 'it' => 'Passeggiata', 'en' => 'Walking'],
            ['k' => '_livello_piuma', 'it' => 'Comfort', 'en' => 'Comfort'],
            ['k' => '_livello_esclusivita', 'it' => 'Esclusività', 'en' => 'Exclusivity'],
        ];
        $rows = [];
        foreach ($levels as $l) {
            $v = (int) get_post_meta($id, $l['k'], true);
            if ($v >= 1 && $v <= 5) {
                $rows[] = ['label' => $isIt ? $l['it'] : $l['en'], 'v' => $v];
            }
        }
        if (empty($rows)) {
            return;
        }
        echo '<div class="igs-ed-levels igs-reveal">';
        foreach ($rows as $r) {
            echo '<div class="igs-ed-level"><span class="igs-ed-level-l">' . esc_html($r['label']) . '</span><span class="igs-ed-dots">' . $this->dots($r['v']) . '</span></div>';
        }
        echo '</div>';
    }

    private function renderInfo(int $id): void
    {
        $comprende = $this->lines((string) get_post_meta($id, '_igs_tour_quota_comprende', true));
        $nonComprende = $this->lines((string) get_post_meta($id, '_igs_tour_quota_non_comprende', true));

        if (!empty($comprende) || !empty($nonComprende)) {
            echo '<div class="igs-ed-quota igs-reveal">';
            if (!empty($comprende)) {
                echo '<div class="igs-ed-quota-col"><h4>' . esc_html__('La quota comprende', 'igs-ecommerce') . '</h4><ul class="igs-ed-yes">';
                foreach ($comprende as $i) {
                    echo '<li>' . esc_html($i) . '</li>';
                }
                echo '</ul></div>';
            }
            if (!empty($nonComprende)) {
                echo '<div class="igs-ed-quota-col"><h4>' . esc_html__('La quota non comprende', 'igs-ecommerce') . '</h4><ul class="igs-ed-no">';
                foreach ($nonComprende as $i) {
                    echo '<li>' . esc_html($i) . '</li>';
                }
                echo '</ul></div>';
            }
            echo '</div>';
        }

        $blocks = [
            ['k' => '_igs_tour_cosa_portare', 'label' => __('Cosa portare in valigia', 'igs-ecommerce'), 'list' => true, 'html' => false],
            ['k' => '_igs_tour_documenti', 'label' => __('Documenti necessari', 'igs-ecommerce'), 'list' => false, 'html' => true],
            ['k' => '_igs_tour_voli', 'label' => __('Voli aerei consigliati', 'igs-ecommerce'), 'list' => false, 'html' => true],
            ['k' => '_igs_tour_info', 'label' => __('Info generali', 'igs-ecommerce'), 'list' => true, 'html' => true],
        ];
        foreach ($blocks as $b) {
            $raw = (string) get_post_meta($id, $b['k'], true);
            if (trim($raw) === '') {
                continue;
            }
            echo '<div class="igs-ed-block igs-reveal"><h4>' . esc_html($b['label']) . '</h4>';
            if ($b['list']) {
                echo '<ul class="igs-ed-bullets">';
                foreach ($this->lines($raw) as $i) {
                    echo '<li>' . ($b['html'] ? wp_kses_post($i) : esc_html($i)) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<div class="igs-ed-prose">' . wp_kses_post(wpautop($raw)) . '</div>';
            }
            echo '</div>';
        }
    }

    /** @return list<string> */
    private function lines(string $text): array
    {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($text)) ?: [] as $l) {
            $l = trim($l);
            if ($l !== '') {
                $out[] = $l;
            }
        }

        return $out;
    }

    private function durationLabel(?array $range): string
    {
        if (!$range) {
            return '';
        }
        $d1 = \DateTime::createFromFormat('d/m/Y', (string) ($range['start'] ?? ''));
        $d2 = \DateTime::createFromFormat('d/m/Y', (string) ($range['end'] ?? ''));
        if (!$d1 || !$d2 || $d2 < $d1) {
            return '';
        }
        $days = $d1->diff($d2)->days + 1;

        return sprintf(_n('%d giorno', '%d giorni', $days, 'igs-ecommerce'), $days);
    }

    private function dots(int $n): string
    {
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $out .= '<i class="igs-ed-dot' . ($i <= $n ? ' on' : '') . '"></i>';
        }

        return $out;
    }

    private function stars(int $n): string
    {
        $n = max(0, min(5, $n));

        return esc_html(str_repeat('★', $n) . str_repeat('☆', 5 - $n));
    }

    private function css(): string
    {
        return '
        .igs-editorial{--ed-bg:#f7f3ea;--ed-panel:#fffdf8;--ed-ink:#26241f;--ed-muted:#7a7466;--ed-line:#e4ddcd;--ed-accent:#b5532f;--ed-accent2:#7d7a45;
            position:relative;left:50%;right:50%;width:100vw;margin-left:-50vw;margin-right:-50vw;background:var(--ed-bg);color:var(--ed-ink);
            display:flex;align-items:flex-start;font-family:inherit;line-height:1.65;}
        .igs-ed-serif{font-family:\'the-seasons-regular\',Georgia,serif;}
        /* RAIL */
        .igs-ed-rail{width:400px;flex:0 0 400px;align-self:stretch;background:var(--ed-panel);border-right:1px solid var(--ed-line);}
        .igs-ed-rail-inner{position:sticky;top:0;padding:46px 40px;display:flex;flex-direction:column;max-height:100vh;overflow:auto;}
        .igs-ed-kicker{font-size:12px;letter-spacing:.2em;text-transform:uppercase;color:var(--ed-accent);font-weight:700;}
        .igs-ed-title{font-family:\'the-seasons-regular\',Georgia,serif;font-weight:400;font-size:38px;line-height:1.08;margin:14px 0 8px;color:var(--ed-ink);}
        .igs-ed-where{color:var(--ed-muted);font-size:15px;margin-bottom:22px;display:flex;align-items:center;flex-wrap:wrap;}
        .igs-ed-where .igs-country{display:inline-flex;align-items:center;gap:.4em;}
        .igs-ed-where .igs-flag{width:1.1em;height:auto;border-radius:2px;}
        .igs-ed-thumb{height:172px;border-radius:14px;background-size:cover;background-position:center;margin-bottom:22px;box-shadow:0 12px 26px rgba(38,36,31,.12);}
        .igs-ed-facts{list-style:none;margin:0 0 22px;padding:16px 0 0;border-top:1px solid var(--ed-line);}
        .igs-ed-facts li{display:flex;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid var(--ed-line);font-size:15px;}
        .igs-ed-facts li span:first-child{color:var(--ed-muted);}
        .igs-ed-facts li span:last-child{font-weight:600;text-align:right;}
        .igs-ed-stars{color:var(--ed-accent2);letter-spacing:1px;}
        .igs-ed-price{margin-bottom:8px;}
        .igs-ed-amount{font-family:\'the-seasons-regular\',Georgia,serif;font-size:30px;font-weight:700;color:var(--ed-ink);}
        .igs-ed-amount .woocommerce-Price-amount,.igs-ed-amount bdi{color:var(--ed-ink);}
        .igs-ed-install{font-size:13.5px;color:var(--ed-muted);margin-top:2px;}
        .igs-ed-book{margin-top:16px;border:0;cursor:pointer;background:var(--ed-accent);color:#fff;padding:16px 22px;border-radius:999px;
            font-family:inherit;font-weight:700;font-size:16.5px;letter-spacing:.01em;box-shadow:0 10px 24px rgba(181,83,47,.28);
            transition:transform .2s ease,box-shadow .25s ease,filter .2s ease;}
        .igs-ed-book:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(181,83,47,.4);filter:brightness(1.04);}
        .igs-ed-nav{margin-top:26px;padding-top:22px;border-top:1px solid var(--ed-line);display:flex;flex-direction:column;gap:3px;}
        .igs-ed-nav a{color:var(--ed-muted);text-decoration:none;font-size:14.5px;border-left:2px solid var(--ed-line);padding:6px 0 6px 14px;transition:color .2s,border-color .2s;}
        .igs-ed-nav a:hover{color:var(--ed-ink);}
        .igs-ed-nav a.is-active{color:var(--ed-ink);border-color:var(--ed-accent);font-weight:600;}
        /* CONTENT */
        .igs-ed-content{flex:1;min-width:0;padding-bottom:80px;}
        .igs-ed-cover{height:460px;background-size:cover;background-position:center;}
        .igs-ed-pad{padding:54px clamp(28px,5vw,72px);max-width:920px;}
        .igs-ed-sec{scroll-margin-top:24px;}
        .igs-ed-sec+.igs-ed-sec{margin-top:8px;}
        .igs-ed-lead{font-size:21px;line-height:1.7;color:#3a372f;}
        .igs-ed-lead p{margin:0 0 .8em;}
        .igs-ed-lead>:first-child::first-letter,.igs-ed-lead::first-letter{font-family:\'the-seasons-regular\',Georgia,serif;font-size:62px;float:left;line-height:.8;margin:6px 14px 0 0;color:var(--ed-accent);}
        .igs-ed-levels{display:flex;flex-wrap:wrap;gap:10px 26px;margin-top:26px;padding-top:22px;border-top:1px solid var(--ed-line);}
        .igs-ed-level{display:flex;align-items:center;gap:10px;}
        .igs-ed-level-l{font-size:14px;color:var(--ed-muted);}
        .igs-ed-dots{display:inline-flex;gap:4px;}
        .igs-ed-dot{width:8px;height:8px;border-radius:50%;background:var(--ed-line);}
        .igs-ed-dot.on{background:var(--ed-accent2);}
        .igs-ed-h2{font-family:\'the-seasons-regular\',Georgia,serif;font-weight:400;font-size:30px;margin:56px 0 24px;padding-bottom:12px;border-bottom:1px solid var(--ed-line);color:var(--ed-ink);}
        /* Programma editoriale */
        .igs-ed-day{display:grid;grid-template-columns:84px 1fr;gap:22px;padding:22px 0;border-bottom:1px solid var(--ed-line);}
        .igs-ed-day:last-child{border-bottom:none;}
        .igs-ed-day-n{font-family:\'the-seasons-regular\',Georgia,serif;font-size:52px;line-height:1;color:var(--ed-accent2);}
        .igs-ed-day-b h3{font-size:20px;font-weight:600;margin:4px 0 8px;color:var(--ed-ink);}
        .igs-ed-day-text{color:var(--ed-muted);line-height:1.7;}
        .igs-ed-day-text p{margin:0 0 .7em;}
        /* Galleria mosaico */
        .igs-ed-gallery{columns:3;column-gap:14px;}
        .igs-ed-gallery .igs-gallery-item{display:block;break-inside:avoid;margin-bottom:14px;border-radius:10px;overflow:hidden;box-shadow:0 8px 20px rgba(38,36,31,.10);transition:transform .3s ease,box-shadow .3s ease;}
        .igs-ed-gallery .igs-gallery-item:hover{transform:translateY(-3px);box-shadow:0 14px 30px rgba(38,36,31,.18);}
        .igs-ed-gallery .igs-gallery-item img{width:100%!important;height:auto!important;max-width:none!important;display:block;margin:0;}
        /* Info */
        .igs-ed-quota{display:grid;grid-template-columns:1fr 1fr;gap:34px;margin-bottom:8px;}
        .igs-ed-quota h4,.igs-ed-block h4{font-size:14px;letter-spacing:.04em;text-transform:uppercase;color:var(--ed-accent);margin:0 0 12px;}
        .igs-ed-quota ul,.igs-ed-bullets{list-style:none;margin:0;padding:0;}
        .igs-ed-quota li,.igs-ed-bullets li{position:relative;padding:6px 0 6px 26px;color:#3a372f;line-height:1.55;}
        .igs-ed-quota li::before,.igs-ed-bullets li::before{position:absolute;left:0;top:6px;font-weight:700;}
        .igs-ed-yes li::before{content:"\2713";color:var(--ed-accent2);}
        .igs-ed-no li::before{content:"\2715";color:var(--ed-accent);}
        .igs-ed-bullets li::before{content:"\2022";color:var(--ed-accent2);}
        .igs-ed-block{margin-top:34px;}
        .igs-ed-prose{color:#3a372f;line-height:1.7;}
        .igs-ed-prose p{margin:0 0 .7em;}
        /* Reveal scoped (oltre alle regole globali di TourLayout) */
        .igs-editorial .igs-reveal{opacity:0;transform:translateY(22px);transition:opacity .7s ease,transform .7s cubic-bezier(.16,.84,.44,1);}
        .igs-editorial .igs-reveal.igs-in{opacity:1;transform:none;}
        @media (prefers-reduced-motion: reduce){.igs-editorial .igs-reveal{opacity:1!important;transform:none!important;}}
        /* Responsive: rail in cima, niente sticky */
        @media (max-width: 1024px){
            .igs-editorial{flex-direction:column;}
            .igs-ed-rail{width:100%;flex:none;border-right:none;border-bottom:1px solid var(--ed-line);}
            .igs-ed-rail-inner{position:static;max-height:none;overflow:visible;padding:34px 24px;}
            .igs-ed-nav{flex-direction:row;flex-wrap:wrap;gap:6px 16px;}
            .igs-ed-nav a{border-left:none;border-bottom:2px solid var(--ed-line);padding:6px 2px;}
            .igs-ed-nav a.is-active{border-color:var(--ed-accent);}
            .igs-ed-cover{height:320px;}
            .igs-ed-gallery{columns:2;}
        }
        @media (max-width: 560px){
            .igs-ed-pad{padding:36px 20px;}
            .igs-ed-quota{grid-template-columns:1fr;gap:22px;}
            .igs-ed-gallery{columns:1;}
            .igs-ed-day{grid-template-columns:54px 1fr;gap:14px;}
            .igs-ed-day-n{font-size:38px;}
            .igs-ed-title{font-size:32px;}
        }
        ';
    }
}
