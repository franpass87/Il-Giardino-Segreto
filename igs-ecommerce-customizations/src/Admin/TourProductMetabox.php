<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Admin;

use IGS\Ecommerce\Helper\Locale;
use IGS\Ecommerce\Helper\TrustBadges;

class TourProductMetabox
{
    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts'], 10, 1);
        add_action('woocommerce_product_options_general_product_data', [$this, 'render']);
        add_action('woocommerce_process_product_meta', [$this, 'save']);
    }

    public function enqueueScripts(string $hook): void
    {
        if (!in_array($hook, ['post-new.php', 'post.php'], true)) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product') {
            return;
        }

        $isIt = Locale::isIt();
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        $i18n = [
            'departure' => __('Partenza', 'igs-ecommerce'),
            'return' => __('Ritorno', 'igs-ecommerce'),
            'add' => __('Aggiungi', 'igs-ecommerce'),
            'remove' => __('Rimuovi', 'igs-ecommerce'),
            'tourDates' => __('Date del tour', 'igs-ecommerce'),
            'itPlaceholder' => __('Ingressi ai siti e giardini', 'igs-ecommerce'),
            'enPlaceholder' => __('Entrance to sites and gardens', 'igs-ecommerce'),
            'giorno' => __('Giorno', 'igs-ecommerce'),
            'titolo' => __('Titolo', 'igs-ecommerce'),
        ];
        wp_add_inline_script(
            'jquery-ui-datepicker',
            'window.giTourI18n = ' . wp_json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
            'before'
        );

        wp_add_inline_script('jquery-ui-datepicker', $this->getDatepickerJs(), 'after');
    }

    public function render(): void
    {
        $isIt = Locale::isIt();
        $saved = get_post_meta((int) get_the_ID(), '_date_ranges', true);

        echo '<div id="date_ranges_wrapper" class="options_group">';
        echo '<p class="form-field"><label>' . esc_html__('Date del tour', 'igs-ecommerce') . '</label> ';
        echo '<button type="button" class="button add-date-range">' . esc_html__('Aggiungi', 'igs-ecommerce') . '</button></p>';
        echo '<div id="date_ranges_list">';

        if (is_array($saved)) {
            foreach ($saved as $r) {
                printf(
                    '<div class="date-range-row" style="margin-bottom:10px;">
                        <input type="text" name="date_ranges[start][]" class="date-field" value="%s" placeholder="%s" style="width:120px;margin-right:5px;">
                        <input type="text" name="date_ranges[end][]" class="date-field" value="%s" placeholder="%s" style="width:120px;margin-right:5px;">
                        <button type="button" class="button remove-date-range">%s</button>
                    </div>',
                    esc_attr($r['start'] ?? ''),
                    esc_attr__('Partenza', 'igs-ecommerce'),
                    esc_attr($r['end'] ?? ''),
                    esc_attr__('Ritorno', 'igs-ecommerce'),
                    esc_html__('Rimuovi', 'igs-ecommerce')
                );
            }
        }

        echo '</div></div>';

        woocommerce_wp_text_input([
            'id' => '_paese_tour',
            'label' => __('Paese del tour', 'igs-ecommerce'),
            'placeholder' => __('Es. Italia', 'igs-ecommerce'),
            'desc_tip' => true,
            'description' => __('Inserisci il paese del tour', 'igs-ecommerce'),
        ]);

        $savedBadges = get_post_meta((int) get_the_ID(), '_igs_trust_badges', true);
        $savedBadges = is_array($savedBadges) ? $savedBadges : [];
        $allBadges = TrustBadges::getAll();
        if (!empty($allBadges)) {
            echo '<div class="options_group igs-trust-badges-metabox">';
            echo '<p class="form-field"><strong>' . esc_html__('Badge di fiducia', 'igs-ecommerce') . '</strong></p>';
            echo '<p class="description" style="margin-bottom:12px;">' . esc_html__('Seleziona i badge da mostrare sopra la pagina prodotto.', 'igs-ecommerce') . '</p>';
            echo '<div class="igs-trust-badges-checkboxes" style="display:flex;flex-wrap:wrap;gap:12px 24px;">';
            foreach ($allBadges as $id => $badge) {
                $label = $isIt ? ($badge['it'] ?? $badge['en']) : ($badge['en'] ?? $badge['it']);
                $checked = in_array($id, $savedBadges, true);
                printf(
                    '<label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="igs_trust_badges[]" value="%s" %s> %s %s</label>',
                    esc_attr($id),
                    $checked ? 'checked' : '',
                    esc_html($badge['icon'] ?? ''),
                    esc_html($label)
                );
            }
            echo '</div></div>';
        }

        $this->renderSidebarSection();
    }

    private function renderSidebarSection(): void
    {
        $isIt = Locale::isIt();
        $pid = (int) get_the_ID();
        $titleIt = get_post_meta($pid, '_igs_sidebar_title_it', true);
        $titleEn = get_post_meta($pid, '_igs_sidebar_title_en', true);
        $installmentIt = get_post_meta($pid, '_igs_installment_text_it', true);
        $installmentEn = get_post_meta($pid, '_igs_installment_text_en', true);
        $services = get_post_meta($pid, '_igs_tour_services', true);
        $services = is_array($services) ? $services : [];

        echo '<div class="options_group igs-sidebar-metabox" style="border-top:1px solid #ddd;padding-top:12px;">';
        echo '<p class="form-field"><strong>' . esc_html__('Info sidebar (sotto il prezzo)', 'igs-ecommerce') . '</strong></p>';

        echo '<p class="form-field"><label>' . esc_html__('Titolo sezione', 'igs-ecommerce') . '</label></p>';
        echo '<p style="margin-bottom:8px;"><input type="text" name="igs_sidebar_title_it" value="' . esc_attr($titleIt) . '" placeholder="' . esc_attr__('Es. info in arrivo', 'igs-ecommerce') . '" class="regular-text"> (IT)</p>';
        echo '<p style="margin-bottom:12px;"><input type="text" name="igs_sidebar_title_en" value="' . esc_attr($titleEn) . '" placeholder="' . esc_attr__('Es. Info coming soon', 'igs-ecommerce') . '" class="regular-text"> (EN)</p>';

        echo '<p class="form-field"><label>' . esc_html__('Testo pagamento a rate', 'igs-ecommerce') . '</label></p>';
        echo '<p style="margin-bottom:8px;"><input type="text" name="igs_installment_text_it" value="' . esc_attr($installmentIt) . '" placeholder="' . esc_attr__('Pagamento a rate disponibile', 'igs-ecommerce') . '" class="large-text"> (IT)</p>';
        echo '<p style="margin-bottom:12px;"><input type="text" name="igs_installment_text_en" value="' . esc_attr($installmentEn) . '" placeholder="' . esc_attr__('Installment payment available', 'igs-ecommerce') . '" class="large-text"> (EN)</p>';

        echo '<p class="form-field"><label>' . esc_html__('Servizi inclusi', 'igs-ecommerce') . '</label>';
        echo ' <button type="button" class="button add-tour-service">' . esc_html__('Aggiungi', 'igs-ecommerce') . '</button></p>';
        echo '<p class="description" style="margin-bottom:8px;">' . esc_html__('Icona (emoji) + testo IT e EN. Lascia vuoto per usare i servizi predefiniti.', 'igs-ecommerce') . '</p>';
        echo '<div id="igs_tour_services_list" style="margin-bottom:12px;">';
        foreach ($services as $s) {
            $icon = $s['icon'] ?? '';
            $it = $s['it'] ?? '';
            $en = $s['en'] ?? '';
            echo '<div class="tour-service-row" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap;">';
            echo '<input type="text" name="igs_tour_services[icon][]" value="' . esc_attr($icon) . '" placeholder="🪷" style="width:50px;text-align:center">';
            echo '<input type="text" name="igs_tour_services[it][]" value="' . esc_attr($it) . '" placeholder="' . esc_attr__('Ingressi ai siti e giardini', 'igs-ecommerce') . '" style="flex:1;min-width:140px">';
            echo '<input type="text" name="igs_tour_services[en][]" value="' . esc_attr($en) . '" placeholder="' . esc_attr__('Entrance to sites and gardens', 'igs-ecommerce') . '" style="flex:1;min-width:140px">';
            echo '<button type="button" class="button remove-tour-service">' . esc_html__('Rimuovi', 'igs-ecommerce') . '</button>';
            echo '</div>';
        }
        echo '</div></div>';

        $this->renderTourStructureSection();
    }

    private function renderTourStructureSection(): void
    {
        $isIt = Locale::isIt();
        $pid = (int) get_the_ID();
        $programma = get_post_meta($pid, '_igs_tour_programma', true);
        $programma = is_array($programma) ? $programma : [];
        $caratteristiche = get_post_meta($pid, '_igs_tour_caratteristiche', true);
        $caratteristiche = is_array($caratteristiche) ? $caratteristiche : [];
        $cosaPortare = get_post_meta($pid, '_igs_tour_cosa_portare', true);
        $documenti = get_post_meta($pid, '_igs_tour_documenti', true);
        $quotaComprende = get_post_meta($pid, '_igs_tour_quota_comprende', true);
        $quotaNonComprende = get_post_meta($pid, '_igs_tour_quota_non_comprende', true);
        $voli = get_post_meta($pid, '_igs_tour_voli', true);

        $lbl = [
            'title' => __('Struttura contenuto tour', 'igs-ecommerce'),
            'subtitle' => __('Come su italiangardentour.com (Canarie, Azzorre, ecc.)', 'igs-ecommerce'),
            'programma' => __('Programma del Tour', 'igs-ecommerce'),
            'addDay' => __('Aggiungi giorno', 'igs-ecommerce'),
            'giorno' => __('Giorno', 'igs-ecommerce'),
            'titolo' => __('Titolo', 'igs-ecommerce'),
            'caratteristiche' => __('Caratteristiche del Tour', 'igs-ecommerce'),
            'addCar' => __('Aggiungi caratteristica', 'igs-ecommerce'),
            'cosaPortare' => __('Cosa portare in valigia', 'igs-ecommerce'),
            'cosaPortareHelp' => __('Una voce per riga', 'igs-ecommerce'),
            'documenti' => __('Documenti necessari', 'igs-ecommerce'),
            'quotaComprende' => __('La quota comprende', 'igs-ecommerce'),
            'quotaComprendeHelp' => __('Una voce per riga', 'igs-ecommerce'),
            'quotaNonComprende' => __('La quota non comprende', 'igs-ecommerce'),
            'quotaNonComprendeHelp' => __('Una voce per riga', 'igs-ecommerce'),
            'voli' => __('Voli aerei consigliati', 'igs-ecommerce'),
        ];

        echo '<div class="options_group igs-tour-structure-metabox" style="border-top:1px solid #ddd;padding-top:16px;margin-top:16px;">';
        echo '<p class="form-field"><strong>' . esc_html($lbl['title']) . '</strong></p>';
        echo '<p class="description" style="margin-bottom:16px;">' . esc_html($lbl['subtitle']) . '</p>';

        echo '<details open style="margin-bottom:16px;"><summary style="cursor:pointer;font-weight:600;">' . esc_html($lbl['programma']) . '</summary>';
        echo '<p><button type="button" class="button add-programma-day">' . esc_html($lbl['addDay']) . '</button></p>';
        echo '<div id="igs_programma_list" style="margin-top:12px;">';
        foreach ($programma as $p) {
            $num = $p['num'] ?? '';
            $titolo = $p['titolo'] ?? '';
            $contenuto = $p['contenuto'] ?? '';
            echo '<div class="programma-day-row" style="border:1px solid #ddd;padding:12px;margin-bottom:12px;border-radius:6px;">';
            echo '<div style="display:flex;gap:8px;margin-bottom:8px;"><input type="number" name="igs_programma[num][]" value="' . esc_attr($num) . '" placeholder="' . esc_attr($lbl['giorno']) . '" min="1" style="width:70px">';
            echo '<input type="text" name="igs_programma[titolo][]" value="' . esc_attr($titolo) . '" placeholder="' . esc_attr($lbl['titolo']) . '" class="large-text" style="flex:1"></div>';
            echo '<textarea name="igs_programma[contenuto][]" rows="4" class="large-text" style="width:100%">' . esc_textarea($contenuto) . '</textarea>';
            echo '<button type="button" class="button remove-programma-day" style="margin-top:6px">' . esc_html__('Rimuovi', 'igs-ecommerce') . '</button></div>';
        }
        echo '</div></details>';

        echo '<details style="margin-bottom:16px;"><summary style="cursor:pointer;font-weight:600;">' . esc_html($lbl['caratteristiche']) . '</summary>';
        echo '<p class="description" style="margin-bottom:8px;">' . esc_html__('Icona: emoji oppure ID immagine da Media. Titolo, sottotitolo, rating 1-5.', 'igs-ecommerce') . '</p>';
        echo '<p><button type="button" class="button add-caratteristica">' . esc_html($lbl['addCar']) . '</button></p>';
        echo '<div id="igs_caratteristiche_list" style="margin-top:12px;">';
        foreach ($caratteristiche as $c) {
            $it = $c['it'] ?? '';
            $en = $c['en'] ?? '';
            $subIt = $c['subtitle_it'] ?? '';
            $subEn = $c['subtitle_en'] ?? '';
            $icon = $c['icon'] ?? '🌱';
            $iconImage = isset($c['icon_image']) ? absint($c['icon_image']) : 0;
            $rating = isset($c['rating']) ? (int) $c['rating'] : 0;
            echo '<div class="caratteristica-row" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;border-radius:6px;display:grid;grid-template-columns:50px 70px 1fr 1fr 1fr 1fr 60px auto;gap:8px;align-items:center;">';
            echo '<input type="text" name="igs_caratteristiche[icon][]" value="' . esc_attr($icon) . '" placeholder="🌱" style="width:50px;text-align:center" title="Emoji">';
            echo '<div class="igs-icon-image-cell"><input type="number" name="igs_caratteristiche[icon_image][]" value="' . ($iconImage ?: '') . '" placeholder="ID" min="0" style="width:60px" title="' . esc_attr__('ID immagine (0 = usa emoji)', 'igs-ecommerce') . '"></div>';
            echo '<input type="text" name="igs_caratteristiche[it][]" value="' . esc_attr($it) . '" placeholder="' . esc_attr__('Titolo IT', 'igs-ecommerce') . '">';
            echo '<input type="text" name="igs_caratteristiche[en][]" value="' . esc_attr($en) . '" placeholder="' . esc_attr__('Titolo EN', 'igs-ecommerce') . '">';
            echo '<input type="text" name="igs_caratteristiche[subtitle_it][]" value="' . esc_attr($subIt) . '" placeholder="' . esc_attr__('Sottot. IT', 'igs-ecommerce') . '">';
            echo '<input type="text" name="igs_caratteristiche[subtitle_en][]" value="' . esc_attr($subEn) . '" placeholder="' . esc_attr__('Sottot. EN', 'igs-ecommerce') . '">';
            echo '<select name="igs_caratteristiche[rating][]"><option value="0"' . ($rating === 0 ? ' selected' : '') . '>-</option>';
            for ($r = 1; $r <= 5; $r++) {
                echo '<option value="' . $r . '"' . ($rating === $r ? ' selected' : '') . '>' . $r . '</option>';
            }
            echo '</select>';
            echo '<button type="button" class="button remove-caratteristica">×</button></div>';
        }
        echo '</div></details>';

        echo '<p class="form-field"><label>' . esc_html($lbl['cosaPortare']) . '</label><span class="description">' . esc_html($lbl['cosaPortareHelp']) . '</span></p>';
        echo '<textarea name="igs_tour_cosa_portare" rows="4" class="large-text">' . esc_textarea($cosaPortare) . '</textarea>';

        echo '<p class="form-field" style="margin-top:12px;"><label>' . esc_html($lbl['documenti']) . '</label></p>';
        echo '<textarea name="igs_tour_documenti" rows="5" class="large-text">' . esc_textarea($documenti) . '</textarea>';

        echo '<p class="form-field" style="margin-top:12px;"><label>' . esc_html($lbl['quotaComprende']) . '</label><span class="description">' . esc_html($lbl['quotaComprendeHelp']) . '</span></p>';
        echo '<textarea name="igs_tour_quota_comprende" rows="6" class="large-text">' . esc_textarea($quotaComprende) . '</textarea>';

        echo '<p class="form-field" style="margin-top:12px;"><label>' . esc_html($lbl['quotaNonComprende']) . '</label><span class="description">' . esc_html($lbl['quotaNonComprendeHelp']) . '</span></p>';
        echo '<textarea name="igs_tour_quota_non_comprende" rows="6" class="large-text">' . esc_textarea($quotaNonComprende) . '</textarea>';

        echo '<p class="form-field" style="margin-top:12px;"><label>' . esc_html($lbl['voli']) . '</label></p>';
        echo '<textarea name="igs_tour_voli" rows="4" class="large-text">' . esc_textarea($voli) . '</textarea>';

        echo '</div>';
    }

    public function save(int $postId): void
    {
        if (!isset($_POST['date_ranges']['start']) || !is_array($_POST['date_ranges']['start'])) {
            delete_post_meta($postId, '_date_ranges');
        } else {
            $ranges = [];
            $startArr = isset($_POST['date_ranges']['start']) && is_array($_POST['date_ranges']['start']) ? wp_unslash($_POST['date_ranges']['start']) : [];
            $endArr = isset($_POST['date_ranges']['end']) && is_array($_POST['date_ranges']['end']) ? wp_unslash($_POST['date_ranges']['end']) : [];
            foreach ($startArr as $i => $start) {
                $end = $endArr[$i] ?? '';
                $start = trim(sanitize_text_field($start));
                $end = trim(sanitize_text_field($end));
                $startNorm = $this->normalizeDate($start);
                $endNorm = $this->normalizeDate($end);
                if ($startNorm && $endNorm) {
                    $ranges[] = ['start' => $startNorm, 'end' => $endNorm];
                }
            }
            update_post_meta($postId, '_date_ranges', $ranges);
        }

        if (isset($_POST['_paese_tour'])) {
            update_post_meta($postId, '_paese_tour', sanitize_text_field(wp_unslash($_POST['_paese_tour'])));
        }

        $validIds = array_keys(TrustBadges::getAll());
        if (isset($_POST['igs_trust_badges']) && is_array($_POST['igs_trust_badges'])) {
            $selected = array_intersect(array_map('sanitize_key', wp_unslash($_POST['igs_trust_badges'])), $validIds);
            update_post_meta($postId, '_igs_trust_badges', array_values($selected));
        } else {
            update_post_meta($postId, '_igs_trust_badges', []);
        }

        if (isset($_POST['igs_sidebar_title_it'])) {
            update_post_meta($postId, '_igs_sidebar_title_it', sanitize_text_field(wp_unslash($_POST['igs_sidebar_title_it'])));
        }
        if (isset($_POST['igs_sidebar_title_en'])) {
            update_post_meta($postId, '_igs_sidebar_title_en', sanitize_text_field(wp_unslash($_POST['igs_sidebar_title_en'])));
        }
        if (isset($_POST['igs_installment_text_it'])) {
            update_post_meta($postId, '_igs_installment_text_it', sanitize_text_field(wp_unslash($_POST['igs_installment_text_it'])));
        }
        if (isset($_POST['igs_installment_text_en'])) {
            update_post_meta($postId, '_igs_installment_text_en', sanitize_text_field(wp_unslash($_POST['igs_installment_text_en'])));
        }

        if (isset($_POST['igs_tour_services']['icon']) && is_array($_POST['igs_tour_services']['icon'])) {
            $services = [];
            $icons = wp_unslash($_POST['igs_tour_services']['icon'] ?? []);
            $its = wp_unslash($_POST['igs_tour_services']['it'] ?? []);
            $ens = wp_unslash($_POST['igs_tour_services']['en'] ?? []);
            foreach ($icons as $i => $icon) {
                $it = isset($its[$i]) ? sanitize_text_field($its[$i]) : '';
                $en = isset($ens[$i]) ? sanitize_text_field($ens[$i]) : '';
                $icon = sanitize_text_field($icon);
                if ($it !== '' || $en !== '') {
                    $services[] = ['icon' => $icon, 'it' => $it, 'en' => $en];
                }
            }
            update_post_meta($postId, '_igs_tour_services', $services);
        } else {
            update_post_meta($postId, '_igs_tour_services', []);
        }

        if (isset($_POST['igs_programma']['num']) && is_array($_POST['igs_programma']['num'])) {
            $programma = [];
            $nums = wp_unslash($_POST['igs_programma']['num'] ?? []);
            $titoli = wp_unslash($_POST['igs_programma']['titolo'] ?? []);
            $contenuti = wp_unslash($_POST['igs_programma']['contenuto'] ?? []);
            foreach ($nums as $i => $num) {
                $titolo = isset($titoli[$i]) ? sanitize_text_field($titoli[$i]) : '';
                $contenuto = isset($contenuti[$i]) ? wp_kses_post($contenuti[$i]) : '';
                if ($titolo !== '' || $contenuto !== '') {
                    $programma[] = [
                        'num' => absint($num) ?: ($i + 1),
                        'titolo' => $titolo,
                        'contenuto' => $contenuto,
                    ];
                }
            }
            update_post_meta($postId, '_igs_tour_programma', $programma);
        } else {
            update_post_meta($postId, '_igs_tour_programma', []);
        }

        if (isset($_POST['igs_caratteristiche']['it']) && is_array($_POST['igs_caratteristiche']['it'])) {
            $caratteristiche = [];
            $its = wp_unslash($_POST['igs_caratteristiche']['it'] ?? []);
            $ens = wp_unslash($_POST['igs_caratteristiche']['en'] ?? []);
            $subIts = wp_unslash($_POST['igs_caratteristiche']['subtitle_it'] ?? []);
            $subEns = wp_unslash($_POST['igs_caratteristiche']['subtitle_en'] ?? []);
            $icons = wp_unslash($_POST['igs_caratteristiche']['icon'] ?? []);
            $iconImages = wp_unslash($_POST['igs_caratteristiche']['icon_image'] ?? []);
            $ratings = wp_unslash($_POST['igs_caratteristiche']['rating'] ?? []);
            foreach ($its as $i => $it) {
                $en = isset($ens[$i]) ? sanitize_text_field($ens[$i]) : '';
                $it = sanitize_text_field($it);
                if ($it !== '' || $en !== '') {
                    $rating = isset($ratings[$i]) ? max(0, min(5, (int) $ratings[$i])) : 0;
                    $iconImg = isset($iconImages[$i]) ? absint($iconImages[$i]) : 0;
                    $caratteristiche[] = [
                        'it' => $it,
                        'en' => $en,
                        'subtitle_it' => isset($subIts[$i]) ? sanitize_text_field($subIts[$i]) : '',
                        'subtitle_en' => isset($subEns[$i]) ? sanitize_text_field($subEns[$i]) : '',
                        'icon' => isset($icons[$i]) ? sanitize_text_field($icons[$i]) : '',
                        'icon_image' => $iconImg,
                        'rating' => $rating,
                    ];
                }
            }
            update_post_meta($postId, '_igs_tour_caratteristiche', $caratteristiche);
        } else {
            update_post_meta($postId, '_igs_tour_caratteristiche', []);
        }

        $textFields = [
            'igs_tour_cosa_portare' => '_igs_tour_cosa_portare',
            'igs_tour_documenti' => '_igs_tour_documenti',
            'igs_tour_quota_comprende' => '_igs_tour_quota_comprende',
            'igs_tour_quota_non_comprende' => '_igs_tour_quota_non_comprende',
            'igs_tour_voli' => '_igs_tour_voli',
        ];
        foreach ($textFields as $postKey => $metaKey) {
            if (isset($_POST[$postKey])) {
                update_post_meta($postId, $metaKey, wp_kses_post(wp_unslash($_POST[$postKey])));
            }
        }
    }

    private function normalizeDate(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $raw)) {
            $d = \DateTime::createFromFormat('d/m/Y', $raw);
            return $d ? $d->format('d/m/Y') : '';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $d = \DateTime::createFromFormat('Y-m-d', $raw);
            return $d ? $d->format('d/m/Y') : '';
        }
        try {
            $d = new \DateTime($raw);
            return $d->format('d/m/Y');
        } catch (\Exception) {
            return '';
        }
    }

    private function getDatepickerJs(): string
    {
        return <<<'JS'
jQuery(function($){
  function initDatepicker(ctx){
    ctx.find('.date-field').datepicker({ dateFormat:'dd/mm/yy', minDate:0 });
  }
  initDatepicker($('#date_ranges_list'));

  $(document).on('click','.add-date-range',function(){
    var row = $(
      '<div class="date-range-row" style="margin-bottom:10px;">'+
        '<input type="text" name="date_ranges[start][]" class="date-field" placeholder="'+ (window.giTourI18n?.departure || 'Departure') +'" style="width:120px;margin-right:5px;">'+
        '<input type="text" name="date_ranges[end][]" class="date-field" placeholder="'+ (window.giTourI18n?.return || 'Return') +'" style="width:120px;margin-right:5px;">'+
        '<button type="button" class="button remove-date-range">'+ (window.giTourI18n?.remove || 'Remove') +'</button>'+
      '</div>'
    );
    $('#date_ranges_list').append(row);
    initDatepicker(row);
  });

  $(document).on('click','.remove-date-range',function(){
    $(this).closest('.date-range-row').remove();
  });

  $(document).on('click','.add-tour-service',function(){
    var row = '<div class="tour-service-row" style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap;">'+
      '<input type="text" name="igs_tour_services[icon][]" placeholder="🪷" style="width:50px;text-align:center">'+
      '<input type="text" name="igs_tour_services[it][]" placeholder="'+(window.giTourI18n?.itPlaceholder||'IT')+'" style="flex:1;min-width:140px">'+
      '<input type="text" name="igs_tour_services[en][]" placeholder="'+(window.giTourI18n?.enPlaceholder||'EN')+'" style="flex:1;min-width:140px">'+
      '<button type="button" class="button remove-tour-service">'+(window.giTourI18n?.remove||'Remove')+'</button>'+
    '</div>';
    $('#igs_tour_services_list').append(row);
  });
  $(document).on('click','.remove-tour-service',function(){
    $(this).closest('.tour-service-row').remove();
  });

  $(document).on('click','.add-programma-day',function(){
    var row = '<div class="programma-day-row" style="border:1px solid #ddd;padding:12px;margin-bottom:12px;border-radius:6px;">'+
      '<div style="display:flex;gap:8px;margin-bottom:8px;"><input type="number" name="igs_programma[num][]" placeholder="'+(window.giTourI18n?.giorno||'Day')+'" min="1" style="width:70px">'+
      '<input type="text" name="igs_programma[titolo][]" placeholder="'+(window.giTourI18n?.titolo||'Title')+'" class="large-text" style="flex:1"></div>'+
      '<textarea name="igs_programma[contenuto][]" rows="4" class="large-text" style="width:100%"></textarea>'+
      '<button type="button" class="button remove-programma-day" style="margin-top:6px">'+(window.giTourI18n?.remove||'Remove')+'</button></div>';
    $('#igs_programma_list').append(row);
  });
  $(document).on('click','.remove-programma-day',function(){ $(this).closest('.programma-day-row').remove(); });

  $(document).on('click','.add-caratteristica',function(){
    var row = '<div class="caratteristica-row" style="border:1px solid #ddd;padding:10px;margin-bottom:10px;border-radius:6px;display:grid;grid-template-columns:50px 70px 1fr 1fr 1fr 1fr 60px auto;gap:8px;align-items:center;">'+
      '<input type="text" name="igs_caratteristiche[icon][]" placeholder="🌱" style="width:50px;text-align:center">'+
      '<input type="number" name="igs_caratteristiche[icon_image][]" placeholder="ID" min="0" style="width:60px">'+
      '<input type="text" name="igs_caratteristiche[it][]" placeholder="Titolo IT">'+
      '<input type="text" name="igs_caratteristiche[en][]" placeholder="Titolo EN">'+
      '<input type="text" name="igs_caratteristiche[subtitle_it][]" placeholder="Sottot. IT">'+
      '<input type="text" name="igs_caratteristiche[subtitle_en][]" placeholder="Sottot. EN">'+
      '<select name="igs_caratteristiche[rating][]"><option value="0">-</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option></select>'+
      '<button type="button" class="button remove-caratteristica">×</button></div>';
    $('#igs_caratteristiche_list').append(row);
  });
  $(document).on('click','.remove-caratteristica',function(){ $(this).closest('.caratteristica-row').remove(); });
});
JS;
    }
}
