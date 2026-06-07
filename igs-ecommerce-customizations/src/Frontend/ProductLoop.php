<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\CountryFlags;
use IGS\Ecommerce\Helper\Locale;
use IGS\Ecommerce\Helper\Theme;
use WC_Product;

class ProductLoop
{
    public function register(): void
    {
        add_action('init', [$this, 'removeAddToCart']);
        add_filter('woocommerce_loop_add_to_cart_link', '__return_empty_string', 10);
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('woocommerce_before_shop_loop_item_title', [$this, 'renderCardFlag'], 5);
        add_action('woocommerce_after_shop_loop_item_title', [$this, 'renderLoopMeta'], 15);
        add_action('woocommerce_after_shop_loop_item', [$this, 'renderFullCardLink'], 20);
    }

    public function removeAddToCart(): void
    {
        remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    }

    public function enqueueStyles(): void
    {
        $shopPages = is_shop() || is_product_taxonomy() || is_product_category() || is_product_tag() || is_product();

        $cssShop = $shopPages ? '
            .woocommerce ul.products li.product .woocommerce-loop-product__title {
                line-height: 1.4em; min-height: calc(1.4em * 3); margin-bottom: 0.5em; overflow: visible;
            }
            /* Mobile: una sola colonna a piena larghezza per le card tour, così non
               si accavallano nel 2-col stretto e confuso del telefono. */
            @media (max-width: 600px) {
                .woocommerce ul.products { display: block !important; }
                .woocommerce ul.products li.product {
                    width: 100% !important; max-width: 480px; float: none !important;
                    clear: both !important; margin: 0 auto 22px !important; display: block !important;
                }
                .woocommerce ul.products li.product .woocommerce-loop-product__title {
                    font-size: 22px; min-height: 0;
                }
            }
        ' : '';

        $accent = Theme::accent();
        $accentRgb = Theme::accentRgb();
        $cssGlobal = '
            /* ===== Card tour (carosello home + shop) — restyling editoriale ===== */
            .woocommerce ul.products { margin-bottom: 1.5em; align-items: stretch; }
            .woocommerce ul.products li.product {
                position: relative; cursor: pointer; overflow: hidden;
                display: flex; flex-direction: column;
                background: #fff; border: 1px solid #e7e2d6; border-radius: 16px;
                box-shadow: 0 8px 24px rgba(20,40,35,.07);
                transition: box-shadow .3s ease, transform .3s ease;
            }
            .woocommerce ul.products li.product:hover {
                box-shadow: 0 16px 40px rgba({{RGB}},.16);
                transform: translateY(-5px);
            }
            .woocommerce ul.products li.product a { display: block; }
            /* immagine: bordo superiore, zoom morbido su hover */
            .woocommerce ul.products li.product a img {
                border-radius: 0 !important; margin: 0 !important; display: block; width: 100%;
                transition: transform .55s cubic-bezier(.16,.84,.44,1);
            }
            .woocommerce ul.products li.product:hover a img { transform: scale(1.05); }
            .woocommerce ul.products li.product .full-card-link {
                position: absolute; top: 0; left: 0; width: 100%; height: 100%;
                z-index: 12; text-indent: -9999px;
            }
            /* Bandiera/paese sovrapposta in alto a sinistra sull immagine */
            .loop-tour-flag {
                position: absolute; top: 12px; left: 12px; z-index: 11;
                display: inline-flex; align-items: center; gap: .4em;
                background: rgba(255,253,248,.94); -webkit-backdrop-filter: blur(4px); backdrop-filter: blur(4px);
                padding: 6px 13px; border-radius: 999px;
                font-size: 13px; font-weight: 700; letter-spacing: .01em; color: {{ACCENT}};
                box-shadow: 0 4px 14px rgba(0,0,0,.16);
            }
            .loop-tour-flag .igs-country { display:inline-flex; align-items:center; gap:.45em; line-height:1; }
            .loop-tour-flag .igs-flag { width:1.15em; height:auto; border-radius:2px; box-shadow:0 0 0 1px rgba(0,0,0,.12); }
            /* Titolo serif coerente con le pagine tour */
            .woocommerce ul.products li.product .woocommerce-loop-product__title {
                text-align: center; font-family: \'the-seasons-regular\', Georgia, serif;
                font-weight: 400; font-size: 22px; line-height: 1.22; color: #22302a;
                padding: 16px 18px 4px; overflow: visible;
                /* Riserva 3 righe: così titoli da 1 a 3 righe restano alti uguale e
                   prezzo/date/durata si allineano su tutte le card (home + shop). */
                min-height: calc(1.22em * 3 + 16px);
            }
            /* Prezzo elegante in accent */
            .woocommerce ul.products li.product .price { text-align: center; margin: 2px 0 0; }
            .woocommerce ul.products li.product .price,
            .woocommerce ul.products li.product .price .amount,
            .woocommerce ul.products li.product .price bdi,
            .woocommerce ul.products li.product .price ins .amount {
                font-size: 19px; line-height: 1.3; font-weight: 700; color: {{ACCENT}};
            }
            /* Date leggibili */
            .loop-tour-dates {
                text-align: center; font-size: 14px; font-weight: 600; color: #6f6a5d;
                margin: 8px 0 0; letter-spacing: .01em;
            }
            /* Riga chip: durata (accent tenue). margin-top:auto la tiene in fondo alla
               card, allineata su tutte (le card hanno la stessa altezza con align-items:stretch). */
            .loop-tour-meta { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; padding: 12px 14px 18px; margin-top: auto; }
            .loop-chip {
                display: inline-flex; align-items: center; gap: .45em;
                font-size: 13px; font-weight: 600; padding: 6px 13px; border-radius: 999px;
            }
            .loop-chip svg { width: 14px; height: 14px; }
            .loop-chip--days { background: rgba({{RGB}},.10); color: {{ACCENT}}; }
            /* ===== Override strutturali tema Salient (mantenuti) ===== */
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.classic,
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.text_on_hover {
                box-shadow: 0 8px 24px rgba(20,40,35,.07); background: #fff;
            }
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.classic .price,
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.classic .woocommerce-loop-product__title {
                padding-left: 18px; padding-right: 18px; text-align: center;
                font-family: \'the-seasons-regular\', Georgia, serif;
            }
            .woocommerce ul.products[data-product-style]:not([data-n-desktop-columns=default]) li.product,
            .woocommerce ul.products[data-product-style]:not([data-n-desktop-small-columns=default]) li.product,
            .woocommerce ul.products[data-product-style]:not([data-n-phone-columns=default]) li.product,
            .woocommerce ul.products[data-product-style]:not([data-n-tablet-columns=default]) li.product {
                float: none !important; clear: none !important;
            }
            .woocommerce .woocommerce-result-count { display: none !important; }
            .flickity-page-dots { display: none; }
            /* Mobile: card a colonna singola → niente riserva 3 righe (allineamento non serve). */
            @media (max-width: 600px) {
                .woocommerce ul.products li.product .woocommerce-loop-product__title { min-height: 0; }
            }
        ';

        $css = trim($cssShop . strtr($cssGlobal, ['{{ACCENT}}' => $accent, '{{RGB}}' => $accentRgb]));
        if ($css !== '') {
            wp_add_inline_style('woocommerce-general', $css);
        }
    }

    /** Bandiera + paese sovrapposti in alto a sinistra sull'immagine della card. */
    public function renderCardFlag(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }
        $paese = get_post_meta($product->get_id(), '_paese_tour', true);
        if (empty($paese)) {
            return;
        }
        echo '<span class="loop-tour-flag">' . CountryFlags::withFlagHtml($paese) . '</span>';
    }

    public function renderLoopMeta(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }

        $isIt = Locale::isIt();
        $ranges = get_post_meta($product->get_id(), '_date_ranges', true);

        if (is_array($ranges) && !empty($ranges)) {
            $r = $ranges[0];
            $start = isset($r['start']) ? \DateTime::createFromFormat('d/m/Y', $r['start']) : null;
            $end = isset($r['end']) ? \DateTime::createFromFormat('d/m/Y', $r['end']) : null;

            if ($start && $end && $end >= $start) {
                echo '<div class="loop-tour-dates">' . esc_html($this->formatRangeCompact($start, $end, $isIt)) . '</div>';
                $days = $start->diff($end)->days + 1;
                $label = $isIt
                    ? $days . ' ' . ($days === 1 ? 'giorno' : 'giorni')
                    : $days . ' ' . ($days === 1 ? 'day' : 'days');
                echo '<div class="loop-tour-meta"><span class="loop-chip loop-chip--days">' . $this->clockIcon() . esc_html($label) . '</span></div>';

                return;
            }
        }

        echo '<div class="loop-tour-dates">' . esc_html($isIt ? 'Date in via di definizione' : 'Dates to be confirmed') . '</div>';
    }

    /** Intervallo date compatto e bilingue: "9 – 15 set 2026" / "9 – 15 Sep 2026". */
    private function formatRangeCompact(\DateTime $s, \DateTime $e, bool $isIt): string
    {
        $m = $isIt
            ? [1 => 'gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic']
            : [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $full = static fn (\DateTime $d): string => (int) $d->format('j') . ' ' . $m[(int) $d->format('n')] . ' ' . $d->format('Y');
        if ($s->format('Y-m-d') === $e->format('Y-m-d')) {
            return $full($s);
        }
        if ($s->format('n-Y') === $e->format('n-Y')) {
            return (int) $s->format('j') . ' – ' . (int) $e->format('j') . ' ' . $m[(int) $e->format('n')] . ' ' . $e->format('Y');
        }
        if ($s->format('Y') === $e->format('Y')) {
            return (int) $s->format('j') . ' ' . $m[(int) $s->format('n')] . ' – ' . $full($e);
        }

        return $full($s) . ' – ' . $full($e);
    }

    private function clockIcon(): string
    {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>';
    }

    public function renderFullCardLink(): void
    {
        global $product;
        if (!$product instanceof WC_Product) {
            return;
        }
        $url = esc_url(get_permalink($product->get_id()));
        $title = esc_attr(get_the_title($product->get_id()));
        $label = apply_filters('igs_loop_vai_al_tour_label', __('Vai al tour', 'igs-ecommerce'), $product);
        echo '<a href="' . $url . '" class="full-card-link" aria-label="' . $title . '">' . esc_html($label) . '</a>';
    }
}
