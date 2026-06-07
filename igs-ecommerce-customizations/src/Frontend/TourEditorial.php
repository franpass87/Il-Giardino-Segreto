<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\CountryFlags;
use IGS\Ecommerce\Helper\Locale;
use IGS\Ecommerce\Helper\Theme;
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
        add_filter('body_class', [$this, 'bodyClass']);
    }

    /**
     * Marca le pagine col rail editoriale: il CSS usa body.igs-has-rail per nascondere
     * la barra di prenotazione in basso su desktop (il rail ha già prezzo + CTA).
     *
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    public function bodyClass(array $classes): array
    {
        if (is_product() && $this->currentIsManaged()) {
            $classes[] = 'igs-has-rail';
        }

        return $classes;
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

    /**
     * Etichetta bilingue scelta dalla lingua della pagina (Locale::isIt). Su questo
     * sito determine_locale() non distingue IT/EN, quindi gettext non basta: scegliamo
     * la stringa esplicitamente come fa il plugin per servizi e livelli.
     */
    private function t(bool $isIt, string $it, string $en): string
    {
        return $isIt ? $it : $en;
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
        // Miniatura del rail: la foto di galleria più "panoramica" (orientamento
        // orizzontale), diversa dalla copertina — così si evitano ritratti e foto di
        // gruppo. Fallback alla prima diversa dalla copertina, poi alla copertina.
        $railThumbId = (int) $coverId;
        $firstNonCover = 0;
        $bestAspect = 0.0;
        foreach ($product->get_gallery_image_ids() as $gid) {
            $gid = (int) $gid;
            if ($gid <= 0 || $gid === (int) $coverId) {
                continue;
            }
            if ($firstNonCover === 0) {
                $firstNonCover = $gid;
            }
            $meta = wp_get_attachment_metadata($gid);
            $w = is_array($meta) && !empty($meta['width']) ? (int) $meta['width'] : 0;
            $h = is_array($meta) && !empty($meta['height']) ? (int) $meta['height'] : 0;
            $aspect = ($w > 0 && $h > 0) ? $w / $h : 0.0;
            if ($aspect > $bestAspect) {
                $bestAspect = $aspect;
                $railThumbId = $gid;
            }
        }
        if ($railThumbId === (int) $coverId && $firstNonCover > 0) {
            $railThumbId = $firstNonCover;
        }
        $thumbUrl = $railThumbId ? wp_get_attachment_image_url($railThumbId, 'large') : $coverUrl;

        $programma = get_post_meta($id, '_igs_tour_programma', true);
        $programma = is_array($programma) ? $programma : [];
        $galleryIds = $this->galleryIds($product);
        $hasInfo = $this->hasInfo($id);

        // Voci di navigazione (solo sezioni presenti).
        $sections = [['id' => 'ed-viaggio', 'label' => $this->t($isIt, 'Il viaggio', 'The journey')]];
        if (!empty($programma)) {
            $sections[] = ['id' => 'ed-programma', 'label' => $this->t($isIt, 'Programma', 'Itinerary')];
        }
        if (!empty($galleryIds)) {
            $sections[] = ['id' => 'ed-galleria', 'label' => $this->t($isIt, 'Galleria', 'Gallery')];
        }
        if ($hasInfo) {
            $sections[] = ['id' => 'ed-info', 'label' => $this->t($isIt, 'Informazioni', 'Information')];
        }

        echo '<div class="igs-editorial">';
        // Safety net: senza JS gli elementi .igs-reveal resterebbero a opacity:0.
        echo '<noscript><style>.igs-editorial .igs-reveal{opacity:1 !important;transform:none !important;}</style></noscript>';

        /* ---------- RAIL ---------- */
        echo '<aside class="igs-ed-rail">';
        echo '<div class="igs-ed-rail-inner">';
        echo '<div class="igs-ed-kicker">' . esc_html__('Il Giardino Segreto · Garden Tour', 'igs-ecommerce') . '</div>';
        echo '<h1 class="igs-ed-title">' . esc_html(get_the_title()) . '</h1>';

        $where = $paese !== '' ? CountryFlags::withFlagHtml($paese) : esc_html($this->t($isIt, 'Italia', 'Italy'));
        $dateLabel = $first ? esc_html($this->formatRange($first, $isIt)) : esc_html($this->t($isIt, 'Date in definizione', 'Dates to be confirmed'));
        echo '<div class="igs-ed-where">' . $where . ' &nbsp;·&nbsp; ' . $dateLabel . '</div>';

        if ($thumbUrl) {
            echo '<div class="igs-ed-thumb" style="background-image:url(\'' . esc_url($thumbUrl) . '\')"></div>';
        }

        echo '<ul class="igs-ed-facts">';
        $duration = $this->durationLabel($first, $isIt);
        if ($duration !== '') {
            echo '<li><span>' . esc_html($this->t($isIt, 'Durata', 'Duration')) . '</span><span>' . esc_html($duration) . '</span></li>';
        }
        // "Partenza" non qui: la data è già nel sottotitolo (no doppione).
        $protagonista = trim((string) get_post_meta($id, '_protagonista_tour', true));
        if ($protagonista !== '') {
            echo '<li><span>' . esc_html($this->t($isIt, 'Protagonista', 'Highlight')) . '</span><span>' . esc_html($protagonista) . '</span></li>';
        }
        // Esclusività NON qui: i punteggi (Cultura/Passeggiata/Comfort/Esclusività)
        // sono raggruppati una sola volta in alto nel contenuto (no doppione).
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

        echo '<button type="button" class="igs-ed-book" data-igs-open-modal>' . esc_html($this->t($isIt, 'Scopri e Prenota', 'Discover & Book')) . ' →</button>';

        if (count($sections) > 1) {
            $navIco = ['ed-viaggio' => 'compass', 'ed-programma' => 'route', 'ed-galleria' => 'image', 'ed-info' => 'info'];
            echo '<nav class="igs-ed-nav" data-igs-spy aria-label="' . esc_attr($this->t($isIt, 'Sezioni del tour', 'Tour sections')) . '">';
            foreach ($sections as $i => $s) {
                $on = $i === 0 ? ' class="is-active"' : '';
                $ico = $this->edIcon($navIco[$s['id']] ?? '');
                echo '<a href="#' . esc_attr($s['id']) . '"' . $on . '>' . $ico . '<span>' . esc_html($s['label']) . '</span></a>';
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

        // Punteggi tutti insieme in alto (una sola volta).
        $this->renderLevels($id, $isIt);

        // Il viaggio: descrizione.
        echo '<section id="ed-viaggio" class="igs-ed-sec">';
        if ($excerpt !== '') {
            echo '<div class="igs-ed-lead igs-reveal">' . wp_kses_post($excerpt) . '</div>';
        }
        echo '</section>';

        // Programma.
        if (!empty($programma)) {
            echo '<section id="ed-programma" class="igs-ed-sec">';
            echo '<h2 class="igs-ed-h2">' . esc_html($this->t($isIt, 'Il programma, giorno per giorno', 'The itinerary, day by day')) . '</h2>';
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
            echo '<h2 class="igs-ed-h2">' . esc_html($this->t($isIt, 'Galleria', 'Gallery')) . '</h2>';
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
            echo '<h2 class="igs-ed-h2">' . esc_html($this->t($isIt, 'Tutto quello che devi sapere', 'Everything you need to know')) . '</h2>';
            $this->renderInfo($id, $isIt);
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

    /**
     * Icona inline (stile Lucide, 24x24, currentColor). Stringa vuota se sconosciuta.
     */
    private function edIcon(string $name): string
    {
        $paths = [
            'culture' => '<polygon points="12 2 20 7 4 7"/><line x1="6" y1="11" x2="6" y2="18"/><line x1="10" y1="11" x2="10" y2="18"/><line x1="14" y1="11" x2="14" y2="18"/><line x1="18" y1="11" x2="18" y2="18"/><line x1="3" y1="22" x2="21" y2="22"/>',
            'walk' => '<path d="M4 16v-2.38C4 11.5 2.97 10.5 3 8c.03-2.72 1.49-6 4.5-6C9.37 2 10 3.8 10 5.5c0 3.11-2 5.66-2 8.68V16a2 2 0 1 1-4 0Z"/><path d="M20 20v-2.38c0-2.12 1.03-3.12 1-5.62-.03-2.72-1.49-6-4.5-6C14.63 6 14 7.8 14 9.5c0 3.11 2 5.66 2 8.68V20a2 2 0 1 0 4 0Z"/><path d="M16 17h4"/><path d="M4 13h4"/>',
            'feather' => '<path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" y1="8" x2="2" y2="22"/><line x1="17.5" y1="15" x2="9" y2="15"/>',
            'gem' => '<path d="M6 3h12l4 6-10 13L2 9Z"/><path d="M11 3 8 9l4 13 4-13-3-6"/><path d="M2 9h20"/>',
            'compass' => '<circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/>',
            'route' => '<circle cx="6" cy="19" r="3"/><path d="M9 19h8.5a3.5 3.5 0 0 0 0-7h-11a3.5 3.5 0 0 1 0-7H15"/><circle cx="18" cy="5" r="3"/>',
            'image' => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
            'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        ];
        if (!isset($paths[$name])) {
            return '';
        }
        return '<svg class="igs-ed-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
    }

    private function renderLevels(int $id, bool $isIt): void
    {
        $levels = [
            ['k' => '_livello_culturale', 'it' => 'Cultura', 'en' => 'Culture', 'ico' => 'culture'],
            ['k' => '_livello_passeggiata', 'it' => 'Passeggiata', 'en' => 'Walking', 'ico' => 'walk'],
            ['k' => '_livello_piuma', 'it' => 'Comfort', 'en' => 'Comfort', 'ico' => 'feather'],
            ['k' => '_livello_esclusivita', 'it' => 'Esclusività', 'en' => 'Exclusivity', 'ico' => 'gem'],
        ];
        $rows = [];
        foreach ($levels as $l) {
            $v = (int) get_post_meta($id, $l['k'], true);
            if ($v >= 1 && $v <= 5) {
                $rows[] = ['label' => $isIt ? $l['it'] : $l['en'], 'v' => $v, 'ico' => $l['ico']];
            }
        }
        if (empty($rows)) {
            return;
        }
        echo '<div class="igs-ed-levels igs-reveal">';
        foreach ($rows as $r) {
            echo '<div class="igs-ed-level"><span class="igs-ed-level-l">' . $this->edIcon($r['ico']) . '<span>' . esc_html($r['label']) . '</span></span><span class="igs-ed-dots">' . $this->dots($r['v']) . '</span></div>';
        }
        echo '</div>';
    }

    private function renderInfo(int $id, bool $isIt): void
    {
        $comprende = $this->lines((string) get_post_meta($id, '_igs_tour_quota_comprende', true));
        $nonComprende = $this->lines((string) get_post_meta($id, '_igs_tour_quota_non_comprende', true));

        if (!empty($comprende) || !empty($nonComprende)) {
            echo '<div class="igs-ed-quota igs-reveal">';
            if (!empty($comprende)) {
                echo '<div class="igs-ed-quota-col"><h4>' . esc_html($this->t($isIt, 'La quota comprende', 'What\'s included')) . '</h4><ul class="igs-ed-yes">';
                foreach ($comprende as $i) {
                    echo '<li>' . esc_html($i) . '</li>';
                }
                echo '</ul></div>';
            }
            if (!empty($nonComprende)) {
                echo '<div class="igs-ed-quota-col"><h4>' . esc_html($this->t($isIt, 'La quota non comprende', 'What\'s not included')) . '</h4><ul class="igs-ed-no">';
                foreach ($nonComprende as $i) {
                    echo '<li>' . esc_html($i) . '</li>';
                }
                echo '</ul></div>';
            }
            echo '</div>';
        }

        $blocks = [
            ['k' => '_igs_tour_cosa_portare', 'label' => $this->t($isIt, 'Cosa portare in valigia', 'What to pack'), 'list' => true, 'html' => false],
            ['k' => '_igs_tour_documenti', 'label' => $this->t($isIt, 'Documenti necessari', 'Required documents'), 'list' => false, 'html' => true],
            ['k' => '_igs_tour_voli', 'label' => $this->t($isIt, 'Voli aerei consigliati', 'Recommended flights'), 'list' => false, 'html' => true],
            ['k' => '_igs_tour_info', 'label' => $this->t($isIt, 'Info generali', 'General information'), 'list' => true, 'html' => true],
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

    /** @var array<string, array<int, string>> Nomi mesi IT/EN per le date leggibili. */
    private const MONTHS = [
        'it' => [1 => 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'],
        'en' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    ];

    /**
     * Formatta una data di intervallo in forma leggibile e bilingue, comprimendo le
     * parti comuni: "3 – 6 settembre 2026", "30 settembre – 2 ottobre 2026",
     * "3 September 2026". Fallback alla stringa grezza se il parsing fallisce.
     *
     * @param array{start?: string, end?: string} $range
     */
    private function formatRange(array $range, bool $isIt): string
    {
        $start = (string) ($range['start'] ?? '');
        $end = (string) ($range['end'] ?? '');
        $s = \DateTime::createFromFormat('d/m/Y', $start);
        $e = \DateTime::createFromFormat('d/m/Y', $end);
        if (!$s) {
            return trim($start . ' – ' . $end, ' –');
        }
        $months = self::MONTHS[$isIt ? 'it' : 'en'];
        $full = static fn (\DateTime $d): string => (int) $d->format('j') . ' ' . $months[(int) $d->format('n')] . ' ' . $d->format('Y');

        if (!$e || $s->format('Y-m-d') === $e->format('Y-m-d')) {
            return $full($s);
        }
        if ($s->format('n-Y') === $e->format('n-Y')) {
            return (int) $s->format('j') . ' – ' . (int) $e->format('j') . ' ' . $months[(int) $e->format('n')] . ' ' . $e->format('Y');
        }
        if ($s->format('Y') === $e->format('Y')) {
            return (int) $s->format('j') . ' ' . $months[(int) $s->format('n')] . ' – ' . $full($e);
        }

        return $full($s) . ' – ' . $full($e);
    }

    private function durationLabel(?array $range, bool $isIt): string
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
        if ($isIt) {
            return $days . ' ' . ($days === 1 ? 'giorno' : 'giorni');
        }

        return $days . ' ' . ($days === 1 ? 'day' : 'days');
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
        $css = '
        .igs-editorial{--ed-bg:#f7f3ea;--ed-panel:#fffdf8;--ed-ink:#26241f;--ed-muted:#6f6a5d;--ed-line:#e4ddcd;--ed-accent:{{ACCENT}};--ed-accent2:{{ACCENT}};
            position:relative;left:50%;right:50%;width:100vw;margin-left:-50vw;margin-right:-50vw;background:var(--ed-bg);color:var(--ed-ink);
            display:flex;align-items:flex-start;font-family:inherit;line-height:1.65;}
        .igs-ed-serif{font-family:\'the-seasons-regular\',Georgia,serif;}
        /* RAIL */
        .igs-ed-rail{width:404px;flex:0 0 404px;align-self:stretch;background:var(--ed-panel);border-right:1px solid var(--ed-line);}
        .igs-ed-rail-inner{position:sticky;top:0;padding:48px 42px;display:flex;flex-direction:column;max-height:100vh;overflow-y:auto;overflow-x:hidden;scrollbar-width:none;-ms-overflow-style:none;}
        .igs-ed-rail-inner::-webkit-scrollbar{width:0;height:0;display:none;}
        .igs-ed-kicker{font-size:12.5px;letter-spacing:.2em;text-transform:uppercase;color:var(--ed-accent);font-weight:700;}
        .igs-ed-title{font-family:\'the-seasons-regular\',Georgia,serif;font-weight:400;font-size:42px;line-height:1.07;margin:14px 0 8px;color:var(--ed-ink);}
        .igs-ed-where{color:var(--ed-muted);font-size:16px;margin-bottom:24px;display:flex;align-items:center;flex-wrap:wrap;}
        .igs-ed-where .igs-country{display:inline-flex;align-items:center;gap:.4em;}
        .igs-ed-where .igs-flag{width:1.1em;height:auto;border-radius:2px;}
        .igs-ed-thumb{height:178px;border-radius:14px;background-size:cover;background-position:center;margin-bottom:24px;box-shadow:0 12px 26px rgba(38,36,31,.12);}
        .igs-ed-facts{list-style:none;margin:0 0 24px;padding:16px 0 0;border-top:1px solid var(--ed-line);}
        .igs-ed-facts li{display:flex;justify-content:space-between;gap:14px;padding:10px 0;border-bottom:1px solid var(--ed-line);font-size:16px;}
        .igs-ed-facts li span:first-child{color:var(--ed-muted);}
        .igs-ed-facts li span:last-child{font-weight:600;text-align:right;}
        .igs-ed-stars{color:var(--ed-accent);letter-spacing:1px;}
        .igs-ed-price{margin-bottom:8px;}
        .igs-ed-amount{font-family:\'the-seasons-regular\',Georgia,serif;font-size:34px;font-weight:700;color:var(--ed-ink);}
        .igs-ed-amount .woocommerce-Price-amount,.igs-ed-amount bdi{color:var(--ed-ink);}
        .igs-ed-install{font-size:14px;color:var(--ed-muted);margin-top:2px;}
        .igs-ed-book{margin-top:18px;border:0;cursor:pointer;background:var(--ed-accent);color:#fff;padding:17px 24px;border-radius:999px;
            font-family:inherit;font-weight:700;font-size:17px;letter-spacing:.01em;box-shadow:0 10px 24px rgba({{ACCENT_RGB}},.28);
            transition:transform .2s ease,box-shadow .25s ease,filter .2s ease;}
        .igs-ed-book:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba({{ACCENT_RGB}},.42);filter:brightness(1.06);}
        .igs-ed-book:focus-visible,.igs-ed-nav a:focus-visible,.igs-ed-gallery .igs-gallery-item:focus-visible{outline:2px solid var(--ed-accent);outline-offset:3px;border-radius:6px;}
        .igs-ed-nav{margin-top:28px;padding-top:24px;border-top:1px solid var(--ed-line);display:flex;flex-direction:column;gap:3px;}
        .igs-ed-nav a{display:flex;align-items:center;gap:9px;color:var(--ed-muted);text-decoration:none;font-size:15.5px;border-left:2px solid var(--ed-line);padding:7px 0 7px 14px;transition:color .2s,border-color .2s;}
        .igs-ed-nav a .igs-ed-ico{opacity:.85;}
        .igs-ed-nav a:hover{color:var(--ed-ink);}
        .igs-ed-nav a.is-active{color:var(--ed-ink);border-color:var(--ed-accent);font-weight:600;}
        /* CONTENT */
        .igs-ed-content{flex:1;min-width:0;padding-bottom:80px;font-size:17px;}
        .igs-ed-cover{height:470px;background-size:cover;background-position:center;}
        .igs-ed-pad{padding:48px clamp(28px,5vw,76px) 56px;max-width:940px;}
        .igs-ed-sec{scroll-margin-top:24px;}
        .igs-ed-sec+.igs-ed-sec{margin-top:8px;}
        .igs-ed-lead{font-size:23px;line-height:1.72;color:#3a372f;}
        .igs-ed-lead p{margin:0 0 .8em;}
        .igs-ed-lead>:first-child::first-letter,.igs-ed-lead::first-letter{font-family:\'the-seasons-regular\',Georgia,serif;font-size:66px;float:left;line-height:.8;margin:6px 16px 0 0;color:var(--ed-accent);}
        /* Punteggi (tutti insieme in alto, una sola volta) */
        .igs-ed-levels{display:flex;flex-wrap:wrap;gap:20px 44px;margin:0 0 38px;padding:22px 26px;background:var(--ed-panel);border:1px solid var(--ed-line);border-radius:14px;box-shadow:0 8px 22px rgba(38,36,31,.05);}
        .igs-ed-level{display:flex;flex-direction:column;gap:9px;}
        .igs-ed-level-l{display:inline-flex;align-items:center;gap:7px;font-size:12.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--ed-muted);font-weight:600;}
        .igs-ed-ico{width:15px;height:15px;flex:0 0 auto;}
        .igs-ed-level-l .igs-ed-ico{width:16px;height:16px;color:var(--ed-accent);}
        .igs-ed-dots{display:inline-flex;gap:5px;}
        .igs-ed-dot{width:9px;height:9px;border-radius:50%;background:var(--ed-line);}
        .igs-ed-dot.on{background:var(--ed-accent);}
        .igs-ed-h2{font-family:\'the-seasons-regular\',Georgia,serif;font-weight:400;font-size:33px;margin:58px 0 26px;padding-bottom:12px;border-bottom:1px solid var(--ed-line);color:var(--ed-ink);}
        /* Programma editoriale */
        .igs-ed-day{display:grid;grid-template-columns:84px 1fr;gap:24px;padding:24px 16px 24px 14px;margin:0 -16px;border-bottom:1px solid var(--ed-line);border-radius:12px;transition:background .25s ease;}
        .igs-ed-day:last-child{border-bottom:none;}
        .igs-ed-day:hover{background:var(--ed-panel);}
        .igs-ed-day-n{font-family:\'the-seasons-regular\',Georgia,serif;font-size:54px;line-height:1;color:var(--ed-accent);}
        .igs-ed-day-b h3{font-size:22px;font-weight:600;margin:4px 0 9px;color:var(--ed-ink);}
        .igs-ed-day-text{color:#4a463d;line-height:1.72;font-size:16.5px;}
        .igs-ed-day-text p{margin:0 0 .7em;}
        /* Galleria mosaico */
        .igs-ed-gallery{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;}
        .igs-ed-gallery .igs-gallery-item{display:block;aspect-ratio:4/3;border-radius:10px;overflow:hidden;box-shadow:0 8px 20px rgba(38,36,31,.10);transition:transform .3s ease,box-shadow .3s ease;}
        .igs-ed-gallery .igs-gallery-item:hover{transform:translateY(-3px);box-shadow:0 14px 30px rgba(38,36,31,.18);}
        .igs-ed-gallery .igs-gallery-item img{width:100%!important;height:100%!important;max-width:none!important;object-fit:cover!important;display:block;margin:0;}
        /* Info */
        .igs-ed-quota{display:grid;grid-template-columns:1fr 1fr;gap:34px;margin-bottom:8px;}
        .igs-ed-quota h4,.igs-ed-block h4{font-size:14.5px;letter-spacing:.04em;text-transform:uppercase;color:var(--ed-accent);margin:0 0 12px;}
        /* Un solo pallino: niente bullet nativo del tema (!important) né doppioni; il
           segno è sempre lo ::before. Vale anche per le liste HTML dentro .igs-ed-prose. */
        .igs-ed-quota ul,.igs-ed-bullets,.igs-ed-prose ul{list-style:none !important;margin:0;padding:0;}
        .igs-ed-quota li,.igs-ed-bullets li,.igs-ed-prose ul li{position:relative;list-style:none !important;padding:7px 0 7px 28px;color:#3a372f;line-height:1.6;font-size:16px;}
        .igs-ed-quota li::marker,.igs-ed-bullets li::marker,.igs-ed-prose ul li::marker{content:"";}
        .igs-ed-quota li::before,.igs-ed-bullets li::before,.igs-ed-prose ul li::before{position:absolute;left:0;top:7px;font-weight:700;content:"\2022";color:var(--ed-accent);}
        .igs-ed-yes li::before{content:"\2713";color:#5a8a3c;}
        .igs-ed-no li::before{content:"\2715";color:#c0563f;}
        .igs-ed-block{margin-top:34px;}
        .igs-ed-prose{color:#3a372f;line-height:1.72;font-size:16.5px;}
        .igs-ed-prose p{margin:0 0 .7em;}
        /* Reveal scoped (oltre alle regole globali di TourLayout) */
        .igs-editorial .igs-reveal{opacity:0;transform:translateY(22px);transition:opacity .7s ease,transform .7s cubic-bezier(.16,.84,.44,1);}
        .igs-editorial .igs-reveal.igs-in{opacity:1;transform:none;}
        @media (prefers-reduced-motion: reduce){.igs-editorial .igs-reveal{opacity:1!important;transform:none!important;}}
        /* Su desktop il rail mostra già prezzo + CTA: nascondi la barra sticky in basso. */
        @media (min-width:1025px){
            body.igs-has-rail #gs-fixed-cta{display:none !important;}
            /* Il <body> ha overflow:hidden (tema Salient / fpml-salient-enhanced) → rompe
               position:sticky del rail (lo rende uno scroll-container che non scrolla).
               overflow:clip clippa come hidden (niente scroll orizzontale dal full-bleed
               -50vw) ma NON crea uno scroll-container → il rail resta sticky. Verificato live. */
            body.single-product.igs-has-rail{overflow:clip;}
        }
        /* Responsive: rail in cima, niente sticky */
        @media (max-width: 1024px){
            .igs-editorial{flex-direction:column;}
            .igs-ed-rail{width:100%;flex:none;border-right:none;border-bottom:1px solid var(--ed-line);}
            .igs-ed-rail-inner{position:static;max-height:none;overflow:visible;padding:34px 24px;}
            .igs-ed-nav{flex-direction:row;flex-wrap:wrap;gap:6px 16px;}
            .igs-ed-nav a{border-left:none;border-bottom:2px solid var(--ed-line);padding:6px 2px;}
            .igs-ed-nav a.is-active{border-color:var(--ed-accent);}
            .igs-ed-cover{height:320px;}
            .igs-ed-gallery{grid-template-columns:repeat(2,1fr);}
        }
        @media (max-width: 560px){
            .igs-ed-pad{padding:34px 20px 44px;}
            .igs-ed-levels{gap:16px 28px;padding:18px 20px;}
            .igs-ed-quota{grid-template-columns:1fr;gap:22px;}
            .igs-ed-gallery{grid-template-columns:repeat(2,1fr);}
            .igs-ed-day{grid-template-columns:50px 1fr;gap:14px;}
            .igs-ed-day-n{font-size:40px;}
            .igs-ed-title{font-size:34px;}
            .igs-ed-lead{font-size:21px;}
        }
        ';

        return strtr($css, [
            '{{ACCENT}}' => Theme::accent(),
            '{{ACCENT_RGB}}' => Theme::accentRgb(),
        ]);
    }
}
