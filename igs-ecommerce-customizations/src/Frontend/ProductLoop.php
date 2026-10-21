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
            /* Mobile: una sola colonna a piena larghezza per le card tour, così non
               si accavallano nel 2-col stretto e confuso del telefono. */
            @media (max-width: 600px) {
                .woocommerce ul.products { display: block !important; }
                .woocommerce ul.products li.product {
                    width: 100% !important; max-width: 480px; float: none !important;
                    clear: both !important; margin: 0 auto 22px !important;
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
            .woocommerce ul.products li.product{position:relative;cursor:pointer;overflow:hidden;display:grid !important;grid-template-columns:1fr auto;grid-template-rows:auto auto 1fr auto;column-gap:0;background:#fff;border:1px solid #e4ebe9;border-radius:18px;box-shadow:0 1px 2px rgba(20,40,35,.04),0 12px 30px -14px rgba(20,40,35,.22);transition:box-shadow .3s ease,transform .3s ease}
            .woocommerce ul.products li.product:hover{box-shadow:0 2px 4px rgba(20,40,35,.05),0 22px 44px -16px rgba({{RGB}},.35);transform:translateY(-4px)}
            .woocommerce ul.products li.product .product-wrap{grid-column:1/-1;grid-row:1;overflow:hidden;margin:0}
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
            .woocommerce ul.products li.product .loop-tour-dates{grid-column:1/-1;grid-row:2;text-align:left;margin:0;padding:18px 22px 0;font-size:12.5px;font-weight:700;letter-spacing:.09em;text-transform:uppercase;color:{{ACCENT}};line-height:1.3}
            .woocommerce ul.products li.product .woocommerce-loop-product__title{grid-column:1/-1;grid-row:3;align-self:start;text-align:left;font-family:\'the-seasons-regular\',Georgia,serif;font-weight:400;font-size:23px;line-height:1.2;color:#22302a;padding:6px 22px 18px;margin:0;min-height:0;overflow:visible}
            .woocommerce ul.products li.product .price{grid-column:1;grid-row:4;align-self:stretch;display:flex;align-items:baseline;gap:.3em;text-align:left;margin:0;padding:14px 0 16px 22px;border-top:1px solid #edf1f0}
            .woocommerce ul.products li.product .price,
            .woocommerce ul.products li.product .price .amount,
            .woocommerce ul.products li.product .price bdi,
            .woocommerce ul.products li.product .price ins .amount {
                font-size: 19px; line-height: 1.3; font-weight: 700; color: {{ACCENT}};
            }
            .woocommerce ul.products li.product .price .igs-price-from{font-size:13px;font-weight:500;color:#7a8682;letter-spacing:.02em}
            .woocommerce ul.products li.product .loop-tour-meta{grid-column:2;grid-row:4;display:flex;align-items:center;justify-content:flex-end;margin:0;padding:14px 22px 16px 12px;border-top:1px solid #edf1f0}
            .loop-chip{display:inline-flex;align-items:center;gap:.4em;font-size:13.5px;font-weight:600;padding:6px 12px;border-radius:999px;line-height:1}
            .loop-chip svg{width:15px;height:15px;flex:0 0 auto;margin:0}
            .loop-chip--days{background:rgba({{RGB}},.09);color:{{ACCENT}}}
            .loop-tour-flag{position:absolute;top:14px;left:14px;z-index:11;display:inline-flex;align-items:center;gap:7px;padding:6px 11px 6px 6px;border-radius:999px;background:rgba(255,255,255,.95);-webkit-backdrop-filter:blur(4px);backdrop-filter:blur(4px);font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#22302a;line-height:1;box-shadow:0 2px 10px rgba(0,0,0,.18)}
            .loop-tour-flag .igs-country{display:inline-flex;align-items:center;gap:7px;line-height:1}
            .loop-tour-flag .igs-flag{width:18px !important;height:18px !important;border-radius:50% !important;object-fit:cover;margin:0 !important;align-self:center;box-shadow:0 0 0 1px rgba(0,0,0,.1)}
            .loop-tour-flag .igs-country-name{display:block;line-height:1;text-box:trim-both cap alphabetic}
            @supports not (text-box:trim-both cap alphabetic){.loop-tour-flag .igs-country-name{position:relative;top:.19em}}
            /* ===== Override strutturali tema Salient (mantenuti) ===== */
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.classic,
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.text_on_hover {
                box-shadow: 0 1px 2px rgba(20,40,35,.04), 0 12px 30px -14px rgba(20,40,35,.22); background: #fff;
            }
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.classic .price,
            body .woocommerce .nectar-woo-flickity[data-item-shadow="1"] li.product.classic .woocommerce-loop-product__title {
                text-align: left;
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
        echo '<div class="loop-tour-meta"></div>';
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
