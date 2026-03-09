<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\CountryFlags;
use IGS\Ecommerce\Helper\Locale;
use IGS\Ecommerce\Helper\TrustBadges;
use WC_Product;

class TourLayout
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('woocommerce_before_single_product_summary', [$this, 'render'], 1);
    }

    public function enqueueStyles(): void
    {
        if (!is_product()) {
            return;
        }
        $css = '
            :root {
                --igs-brand: #0b5764;
                --igs-brand-light: #0e6b7a;
                --igs-brand-bg: rgba(11,87,100,0.08);
                --igs-brand-bg-strong: rgba(11,87,100,0.14);
                --igs-text: #2d3748;
                --igs-text-muted: #64748b;
                --igs-border: #e2e8f0;
                --igs-shadow: 0 4px 20px rgba(0,0,0,0.06);
                --igs-shadow-hover: 0 8px 30px rgba(0,0,0,0.1);
                --igs-radius: 12px;
                --igs-radius-sm: 8px;
            }
            .custom-hero {
                position: relative;
                left: 50%; right: 50%;
                width: 100vw;
                margin-left: -50vw;
                margin-right: -50vw;
                height: 65vh;
                min-height: 380px;
                background-size: cover;
                background-position: center;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .custom-hero::before {
                content: "";
                position: absolute; top:0; left:0;
                width:100%; height:100%;
                background: linear-gradient(180deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.45) 100%);
            }
            .custom-hero-content {
                position: relative; z-index:1;
                text-align:center; color:#fff; padding:0 24px;
                max-width: 900px;
            }
            .custom-hero-content h1 {
                font-size: clamp(32px, 5vw, 52px);
                line-height: 1.15;
                margin-bottom: 0.5em;
                color: #fff;
                text-shadow: 0 2px 20px rgba(0,0,0,0.3);
                letter-spacing: -0.02em;
            }
            .custom-hero-content .country,
            .custom-hero-content .dates {
                font-size: clamp(18px, 2.2vw, 24px);
                margin-bottom: 0.35em;
                background: rgba(11,87,100,0.85);
                border-radius: var(--igs-radius-sm);
                padding: 10px 20px;
                font-weight: 600;
                display: inline-block;
                letter-spacing: 0.02em;
            }
            .custom-hero-content ul,
            .custom-hero-content ol { list-style: none; margin: 0; padding: 0; }
            .custom-hero-content li { list-style: none; }
            .custom-hero-content li::marker,
            .custom-hero-content li::before { content: none; }
            .custom-tour-wrapper { max-width: 1200px; margin: 48px auto; padding: 0 24px; }
            .custom-tour-columns { display: flex; flex-wrap: nowrap; gap: 48px; align-items: flex-start; }
            .custom-tour-desc {
                flex: 2; min-width: 0;
                font-size: 1.1em; line-height: 1.75;
                color: var(--igs-text);
            }
            .custom-tour-desc p { margin-bottom: 1em; }
            .custom-tour-sidebar {
                flex: 1;
                min-width: 280px;
                max-width: 360px;
                background: linear-gradient(165deg, var(--igs-brand-bg) 0%, var(--igs-brand-bg-strong) 100%);
                border-radius: var(--igs-radius);
                box-shadow: var(--igs-shadow);
                padding: 40px 32px;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                border: 1px solid var(--igs-border);
            }
            .custom-tour-sidebar .price {
                font-size: 2rem;
                font-weight: 700;
                margin-bottom: 8px;
                color: var(--igs-brand);
            }
            .custom-tour-sidebar .installment {
                font-size: 0.9rem;
                color: var(--igs-text-muted);
                margin-bottom: 24px;
                font-weight: 600;
            }
            .custom-tour-sidebar .duration {
                font-size: 1.1rem;
                margin-bottom: 20px;
                color: var(--igs-text);
            }
            .custom-tour-sidebar .tour-services {
                display: flex;
                flex-direction: column;
                gap: 10px;
                margin: 8px 0 20px;
                width: 100%;
                text-align: left;
            }
            .custom-tour-sidebar .tour-services span {
                font-size: 0.95rem;
                color: var(--igs-text);
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 6px 0;
                border-bottom: 1px solid var(--igs-border);
            }
            .custom-tour-sidebar .tour-services span:last-child { border-bottom: none; }
            .custom-tour-sidebar .sidebar-section-title {
                font-size: 1rem;
                font-weight: 700;
                color: var(--igs-text);
                margin-bottom: 10px;
                text-align: center;
                letter-spacing: 0.02em;
            }
            .custom-tour-sidebar .country-band {
                background: var(--igs-brand);
                padding: 14px 40px;
                border-radius: var(--igs-radius-sm);
                margin: 24px -32px -40px -32px;
                font-size: 0.95rem;
                font-weight: 700;
                color: #fff;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }
            .custom-hero.igs-hero-lazy { background-color: #e5e7eb; }
            #gs-open-modal {
                font-size: 1.8rem !important;
                font-family: \'the-seasons-regular\' !important;
                background-color: #8fb159 !important;
                text-shadow: 2px 2px 8px black !important;
            }
            body[data-header-resize="1"] .container-wrap,
            body[data-header-resize="1"] .project-title { margin-top: 0; padding-top: 0; }
            body[data-slide-out-widget-area-style=slide-out-from-right] .nectar-social.fixed,
            .nectar-social.fixed { display: none; }
            .single-product .nectar-prod-wrap,
            .single-product .product[data-gallery-style=left_thumb_sticky] .nectar-sticky-prod-wrap,
            .woocommerce .product[data-gallery-style=left_thumb_sticky][data-tab-pos*=fullwidth]>.summary.entry-summary,
            .woocommerce .product[data-gallery-style=left_thumb_sticky][data-tab-pos=in_sidebar] .single-product-summary>div.summary,
            .woocommerce div.product[data-gallery-style=left_thumb_sticky] div.images .woocommerce-product-gallery__image:nth-child(n+2) {
                float: none; width: 100%; display: none;
            }
            .woocommerce div.product .woocommerce-tabs[data-tab-style=fullwidth_stacked] { padding-top: 0; margin-top: 0; }
            .woocommerce #ajax-content-wrap .woocommerce-tabs[data-tab-style=fullwidth_stacked] #tab-reviews>#reviews { padding-top: 40px; display: none; }
            #tab-additional_information { display: none !important; }
            div[data-style="default"] .toggle .toggle-title a { border-radius: 10px; font-weight: bold; }
            .nectar-fancy-box[data-style=default] .inner *,
            .nectar-fancy-box[data-style=hover_desc] .inner *,
            .nectar-fancy-box[data-style=parallax_hover] .inner * {
                text-shadow: 2px 2px 10px black;
                font-size: 35px;
                line-height: 40px;
            }
            .toggle > .toggle-title a i {
                position: absolute;
                left: 13px;
                top: 50%;
                transform: translateY(-50%);
                background-color: transparent;
                color: #888;
                width: 18px;
                height: 16px;
                line-height: 18px;
                font-size: 20px;
                transition: all .2s linear;
            }
            .bottom_controls {
                background-color: rgba(0,0,0,.03);
                padding: 40px 0;
                margin-top: 40px;
                position: relative;
                z-index: 1;
                display: none;
            }
            .gs-form-group > label {
                font-family: \'foundersgrotesk\' !important;
                font-size: 22px !important;
            }
            .igs-trust-badges {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 12px 20px;
                padding: 20px 24px;
                background: linear-gradient(135deg, var(--igs-brand) 0%, var(--igs-brand-light) 100%);
                position: relative;
                left: 50%;
                right: 50%;
                width: 100vw;
                margin-left: -50vw;
                margin-right: -50vw;
            }
            .igs-trust-badge {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 10px 18px;
                background: rgba(255,255,255,0.97);
                border-radius: 999px;
                font-size: 0.9rem;
                font-weight: 600;
                color: var(--igs-brand);
                box-shadow: 0 2px 12px rgba(0,0,0,0.08);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .igs-trust-badge:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
            .igs-trust-badge span.igs-badge-icon { font-size: 1.15em; }
            .igs-tour-programma { padding: 8px 0; }
            .igs-programma-day {
                margin-bottom: 32px;
                padding-bottom: 32px;
                border-bottom: 1px solid var(--igs-border);
            }
            .igs-programma-day:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
            .igs-programma-day h3 {
                font-size: 1.2rem;
                color: var(--igs-brand);
                margin-bottom: 14px;
                font-weight: 700;
            }
            .igs-programma-content { color: var(--igs-text); line-height: 1.7; }
            .igs-programma-content p { margin-bottom: 1em; }
            .igs-tour-dettagli { padding: 8px 0; }
            .igs-tour-dettagli section { margin-bottom: 36px; }
            .igs-tour-dettagli section:last-child { margin-bottom: 0; }
            .igs-tour-dettagli h3 {
                font-size: 1.15rem;
                color: var(--igs-brand);
                margin-bottom: 14px;
                font-weight: 700;
                padding-bottom: 8px;
                border-bottom: 2px solid var(--igs-brand-bg-strong);
            }
            .igs-caratteristiche-cards {
                display: flex;
                flex-wrap: wrap;
                gap: 20px;
                justify-content: center;
                padding: 8px 0;
            }
            .igs-caratteristica-card {
                background: #fff;
                border-radius: var(--igs-radius);
                padding: 28px 24px;
                min-width: 150px;
                max-width: 200px;
                text-align: center;
                box-shadow: var(--igs-shadow);
                border: 1px solid var(--igs-border);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .igs-caratteristica-card:hover {
                transform: translateY(-4px);
                box-shadow: var(--igs-shadow-hover);
            }
            .igs-car-icon {
                width: 60px; height: 60px;
                margin: 0 auto 14px;
                border-radius: 50%;
                background: var(--igs-brand);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 28px;
                overflow: hidden;
            }
            .igs-car-icon img { width: 100%; height: 100%; object-fit: contain; padding: 12px; }
            .igs-car-title { font-weight: 700; font-size: 1rem; margin-bottom: 4px; color: var(--igs-text); }
            .igs-car-subtitle { font-size: 0.85rem; color: var(--igs-text-muted); margin-bottom: 10px; }
            .igs-car-rating { display: flex; gap: 5px; justify-content: center; }
            .igs-car-rating span { width: 8px; height: 8px; border-radius: 50%; }
            .igs-tour-dettagli ul { margin: 0 0 0 20px; padding: 0; }
            .igs-tour-dettagli li { margin-bottom: 8px; color: var(--igs-text); line-height: 1.6; }
            .igs-documenti-content,
            .igs-voli-content { color: var(--igs-text); line-height: 1.7; }
            .igs-documenti-content p,
            .igs-voli-content p { margin-bottom: 0.8em; }
            .mappa-viaggio-wrapper { margin: 24px 0; border-radius: var(--igs-radius); overflow: hidden; box-shadow: var(--igs-shadow); }
            .igs-tour-galleria {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 16px;
                padding: 16px 0;
            }
            .igs-gallery-item {
                display: block;
                aspect-ratio: 4/3;
                border-radius: var(--igs-radius-sm);
                overflow: hidden;
                box-shadow: var(--igs-shadow);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .igs-gallery-item:hover {
                transform: scale(1.02);
                box-shadow: var(--igs-shadow-hover);
            }
            .igs-gallery-item img { width: 100%; height: 100%; object-fit: cover; }
            .woocommerce .igs-tour-programma .igs-programma-day,
            .woocommerce .igs-tour-dettagli,
            .woocommerce .igs-tour-galleria { max-width: 900px; }
            @media (max-width: 768px) {
                .custom-hero { height: 50vh; min-height: 300px; }
                .custom-tour-columns { flex-direction: column; }
                .custom-tour-sidebar { max-width: none; }
                .igs-trust-badges { padding: 16px; gap: 10px; }
                .igs-trust-badge { font-size: 0.85rem; padding: 8px 14px; }
                .igs-caratteristica-card { min-width: 130px; max-width: 160px; padding: 20px 16px; }
                .igs-tour-galleria { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            }
            @media (min-width: 769px) { .custom-hero { height: 65vh; } }
        ';
        wp_add_inline_style('woocommerce-general', $css);
    }

    public function render(): void
    {
        if (!is_product()) {
            return;
        }

        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $isIt = Locale::isIt();
        $id = $product->get_id();
        $ranges = get_post_meta($id, '_date_ranges', true);
        $paese = get_post_meta($id, '_paese_tour', true);
        $excerpt = apply_filters('woocommerce_short_description', $product->get_short_description());

        $imgId = $product->get_image_id();
        $imgUrl = $imgId ? wp_get_attachment_image_url($imgId, 'full') : wc_placeholder_img_src();

        echo '<div class="custom-hero igs-hero-lazy" data-bg="' . esc_attr($imgUrl) . '">';
        echo '<div class="custom-hero-content">';
        echo '<h1>' . esc_html(get_the_title()) . '</h1>';
        $countryDisplay = $paese ? CountryFlags::withFlag($paese) : __('Paese non specificato', 'igs-ecommerce');
        echo '<div class="country">' . esc_html($countryDisplay) . '</div>';
        if (is_array($ranges) && count($ranges) > 0) {
            echo '<div class="dates">' . esc_html($ranges[0]['start']) . ' → ' . esc_html($ranges[0]['end']) . '</div>';
        } else {
            echo '<div class="dates">' . esc_html__('Date non disponibili', 'igs-ecommerce') . '</div>';
        }
        echo '</div></div>';

        $trustBadges = TrustBadges::getForProduct($product);
        if (!empty($trustBadges)) {
            echo '<div class="igs-trust-badges">';
            foreach ($trustBadges as $badge) {
                $rawText = $isIt ? ($badge['it'] ?? $badge['en']) : ($badge['en'] ?? $badge['it']);
                $text = __($rawText, 'igs-ecommerce');
                $icon = $badge['icon'] ?? '';
                echo '<span class="igs-trust-badge">';
                if ($icon !== '') {
                    echo '<span class="igs-badge-icon" aria-hidden="true">' . esc_html($icon) . '</span>';
                }
                echo '<span>' . esc_html($text) . '</span></span>';
            }
            echo '</div>';
        }

        echo '<div class="custom-tour-wrapper">';
        echo '<div class="custom-tour-columns">';
        echo '<div class="custom-tour-desc">' . wp_kses_post($excerpt) . '</div>';
        echo '<div class="custom-tour-sidebar">';

        $priceHtml = apply_filters('woocommerce_get_price_html', '', $product);
        echo '<div class="price">' . $priceHtml . '</div>';

        $sidebarTitleIt = get_post_meta($id, '_igs_sidebar_title_it', true);
        $sidebarTitleEn = get_post_meta($id, '_igs_sidebar_title_en', true);
        $installmentIt = get_post_meta($id, '_igs_installment_text_it', true);
        $installmentEn = get_post_meta($id, '_igs_installment_text_en', true);
        $sidebarTitle = $isIt ? ($sidebarTitleIt ?: $sidebarTitleEn) : ($sidebarTitleEn ?: $sidebarTitleIt);
        $installmentText = $isIt ? ($installmentIt ?: __('Pagamento a rate disponibile', 'igs-ecommerce')) : ($installmentEn ?: __('Installment payment available', 'igs-ecommerce'));
        if ($sidebarTitle !== '') {
            echo '<div class="sidebar-section-title">' . esc_html($sidebarTitle) . '</div>';
        }
        echo '<div class="installment">' . esc_html($installmentText) . '</div>';

        if (is_array($ranges) && count($ranges) > 0) {
            $d1 = \DateTime::createFromFormat('d/m/Y', $ranges[0]['start']);
            $d2 = \DateTime::createFromFormat('d/m/Y', $ranges[0]['end']);
            if ($d1 && $d2 && $d2 >= $d1) {
                $days = $d1->diff($d2)->days + 1;
                $label = sprintf(
                    /* translators: %d: number of days */
                    _n('%d giorno', '%d giorni', $days, 'igs-ecommerce'),
                    $days
                );
                echo '<div class="duration"><strong>' . esc_html($label) . '</strong></div>';
            }
        }

        $defaultServices = [
            ['icon' => '🪷', 'it' => __('Ingressi ai siti e giardini', 'igs-ecommerce'), 'en' => __('Entrance to sites and gardens', 'igs-ecommerce')],
            ['icon' => '🏨', 'it' => __('Pernottamento incluso', 'igs-ecommerce'), 'en' => __('Overnight stay included', 'igs-ecommerce')],
            ['icon' => '🚌', 'it' => __('Trasferimenti in loco', 'igs-ecommerce'), 'en' => __('Local transfers', 'igs-ecommerce')],
            ['icon' => '🍽️', 'it' => __('Pasti da itinerario', 'igs-ecommerce'), 'en' => __('Meals as per itinerary', 'igs-ecommerce')],
            ['icon' => '🗺️', 'it' => __('Guida locale', 'igs-ecommerce'), 'en' => __('Local guide', 'igs-ecommerce')],
        ];
        $customServices = get_post_meta($id, '_igs_tour_services', true);
        $services = is_array($customServices) && !empty($customServices) ? $customServices : $defaultServices;
        $services = apply_filters('igs_tour_services', $services, $product);
        echo '<div class="tour-services">';
        foreach ((array) $services as $s) {
            if (is_string($s)) {
                echo '<span>' . esc_html($s) . '</span>';
                continue;
            }
            $text = $isIt ? ($s['it'] ?? $s['en'] ?? '') : ($s['en'] ?? $s['it'] ?? '');
            $icon = $s['icon'] ?? '';
            if ($text !== '') {
                echo '<span>' . esc_html($icon . ' ' . $text) . '</span>';
            }
        }
        echo '</div>';

        $countryBandDisplay = $paese ? CountryFlags::withFlag($paese) : __('Paese non specificato', 'igs-ecommerce');
        echo '<div class="country-band">' . esc_html($countryBandDisplay) . '</div>';
        echo '</div></div></div>';
        $this->renderHeroLazyScript();
    }

    private function renderHeroLazyScript(): void
    {
        ?>
        <script>
        (function(){
            var el = document.querySelector('.igs-hero-lazy');
            if(!el || !el.dataset.bg) return;
            if('IntersectionObserver' in window){
                var io = new IntersectionObserver(function(entries){
                    if(entries[0].isIntersecting){
                        el.style.backgroundImage = 'url(' + el.dataset.bg + ')';
                        el.classList.remove('igs-hero-lazy');
                        io.disconnect();
                    }
                }, { rootMargin: '50px' });
                io.observe(el);
            } else { el.style.backgroundImage = 'url(' + el.dataset.bg + ')'; el.classList.remove('igs-hero-lazy'); }
        })();
        </script>
        <?php
    }
}
