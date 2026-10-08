<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use WC_Product;
use IGS\Ecommerce\Helper\Locale;

class PriceDisplay
{
    public function register(): void
    {
        add_filter('woocommerce_get_price_html', [$this, 'filterPriceHtml'], 100, 2);
    }

    public function filterPriceHtml(string $price, WC_Product $product): string
    {
        // Prefisso/etichette in base alla lingua del percorso (IT default, /en/ ecc. = target),
        // non via gettext: il .mo non viene caricato per la lingua corrente su questo sito.
        $isIt = Locale::isIt();

        // "da" anche sui prodotti a prezzo unico: nelle griglie una card senza prefisso
        // accanto a due che ce l'hanno sembra un errore. Il prezzo e comunque di partenza,
        // le quote variano per camera singola, supplementi e periodo.
        // Span dedicato: senza, il prefisso ereditava i 25px di .price contro i 19px
        // dell importo e risultava piu grande del prezzo stesso.
        $prefix = '<span class="igs-price-from">' . ($isIt ? 'da' : 'from') . '</span> ';

        if ($product->is_type('variable')) {
            $minPrice = $product->get_variation_price('min', true);

            // Sul percorso /en/ le varianti vengono filtrate per lingua e il minimo torna
            // vuoto: finche la cache dei prezzi reggeva non si vedeva, ma al primo
            // svuotamento i tour restavano senza prezzo. Il prezzo del prodotto padre,
            // che WooCommerce tiene allineato al minimo delle varianti, e la rete.
            if (!is_numeric($minPrice) || (float) $minPrice <= 0) {
                $minPrice = $product->get_price();
            }

            if (is_numeric($minPrice) && (float) $minPrice > 0) {
                return $prefix . wc_price((float) $minPrice, ['decimals' => 0]);
            }
            return '<span class="no-price"></span>';
        }

        $val = $product->get_price();
        if (is_numeric($val) && (float) $val > 0) {
            return $prefix . wc_price((float) $val, ['decimals' => 0]);
        }

        $soon = $isIt ? 'info in arrivo' : 'coming soon';
        return '<span class="no-price">' . esc_html($soon) . '</span>';
    }
}
