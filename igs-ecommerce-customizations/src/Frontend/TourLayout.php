<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\Locale;
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
            .custom-hero {
                position: relative;
                left: 50%; right: 50%;
                width: 100vw;
                margin-left: -50vw; margin-right: -50vw;
                height: 50vh;
                background-size: cover;
                background-position: center;
                display: flex; align-items: center; justify-content: center;
            }
            .custom-hero::before {
                content: "";
                position: absolute; top:0; left:0;
                width:100%; height:100%;
                background: rgba(0,0,0,0.3);
            }
            .custom-hero-content {
                position: relative; z-index:1;
                text-align:center; color:#fff; padding:0 20px;
            }
            .custom-hero-content h1 { font-size:3em; margin-bottom:.3em; }
            .custom-hero-content .country,
            .custom-hero-content .dates { font-size:1.2em; margin-bottom:.2em; }
            .custom-tour-wrapper { max-width:1200px; margin:40px auto; padding:0 20px; }
            .custom-tour-columns { display:flex; flex-wrap:nowrap; gap:40px; }
            .custom-tour-desc { flex:2; min-width:0; font-size:1.1em; line-height:1.6; }
            .custom-tour-sidebar {
                flex:1; min-width:0;
                background:#fff; border-radius:12px;
                box-shadow:0 4px 12px rgba(0,0,0,0.1);
                padding:20px; display:flex; flex-direction:column; align-items:center;
            }
            .custom-tour-sidebar .price { font-size:2em; font-weight:bold; margin-bottom:10px; }
            .custom-tour-sidebar .installment { font-size:.95em; color:#777; margin-bottom:20px; }
            .custom-tour-sidebar .duration { font-size:1.1em; margin-bottom:20px; }
            .custom-tour-sidebar .tour-services { display:flex; flex-direction:column; gap:0.2em; margin:0.2em 0; }
            .custom-tour-sidebar .tour-services span { font-size:0.95em; color:#555; font-weight:bold; display:flex; align-items:center; gap:0.4em; }
            .custom-tour-sidebar .country-band {
                background:#f0f0f0; padding:10px;
                border-radius:0 0 12px 12px;
                margin:-20px -20px 0; font-weight:bold;
            }
            @media (min-width:769px) { .custom-hero { height:70vh; } }
            @media (max-width:768px) { .custom-tour-columns { flex-direction:column; } }
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

        echo '<div class="custom-hero" style="background-image:url(' . esc_url($imgUrl) . ')">';
        echo '<div class="custom-hero-content">';
        echo '<h1>' . esc_html(get_the_title()) . '</h1>';
        echo '<div class="country">' . ($paese ? esc_html($paese) : ($isIt ? 'Paese non specificato' : 'Country not specified')) . '</div>';
        if (is_array($ranges) && count($ranges) > 0) {
            echo '<div class="dates">' . esc_html($ranges[0]['start']) . ' → ' . esc_html($ranges[0]['end']) . '</div>';
        } else {
            echo '<div class="dates">' . ($isIt ? 'Date non disponibili' : 'Dates not available') . '</div>';
        }
        echo '</div></div>';

        echo '<div class="custom-tour-wrapper">';
        echo '<div class="custom-tour-columns">';
        echo '<div class="custom-tour-desc">' . wp_kses_post($excerpt) . '</div>';
        echo '<div class="custom-tour-sidebar">';

        $priceHtml = apply_filters('woocommerce_get_price_html', '', $product);
        echo '<div class="price">' . $priceHtml . '</div>';
        echo '<div class="installment">' . ($isIt ? 'Pagamento a rate disponibile' : 'Installment payment available') . '</div>';

        if (is_array($ranges) && count($ranges) > 0) {
            $d1 = \DateTime::createFromFormat('d/m/Y', $ranges[0]['start']);
            $d2 = \DateTime::createFromFormat('d/m/Y', $ranges[0]['end']);
            if ($d1 && $d2 && $d2 >= $d1) {
                $days = $d1->diff($d2)->days + 1;
                $label = $isIt
                    ? ($days === 1 ? '1 giorno' : sprintf('%d giorni', $days))
                    : ($days === 1 ? '1 day' : sprintf('%d days', $days));
                echo '<div class="duration"><strong>' . esc_html($label) . '</strong></div>';
            }
        }

        echo '<div class="tour-services">';
        echo '<span>🪷 ' . ($isIt ? 'Ingressi ai siti e giardini' : 'Entrance to sites and gardens') . '</span>';
        echo '<span>🏨 ' . ($isIt ? 'Pernottamento incluso' : 'Overnight stay included') . '</span>';
        echo '<span>🚌 ' . ($isIt ? 'Trasferimenti in loco' : 'Local transfers') . '</span>';
        echo '<span>🍽️ ' . ($isIt ? 'Pasti da itinerario' : 'Meals as per itinerary') . '</span>';
        echo '<span>🗺️ ' . ($isIt ? 'Guida locale' : 'Local guide') . '</span>';
        echo '</div>';

        echo '<div class="country-band">' . ($paese ? esc_html($paese) : ($isIt ? 'Paese non specificato' : 'Country not specified')) . '</div>';
        echo '</div></div></div>';
    }
}
