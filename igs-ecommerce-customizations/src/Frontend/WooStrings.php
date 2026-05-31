<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

/**
 * Forza in italiano alcune stringhe core di WooCommerce sulle pagine in lingua
 * italiana, quando il locale a runtime non le applica (su questo sito alcune
 * stringhe WC restano in inglese anche con il .mo it_IT presente).
 *
 * Rilevamento via URL: su /en/ (ecc.) lascia l'inglese; altrove (IT default) forza l'italiano.
 * Oltre al filtro gettext generico, usa il filtro WC dedicato per la "add to cart description"
 * dei prodotti variabili (lo screen-reader "This product has multiple variants...").
 */
class WooStrings
{
    /** @var array<string, string> */
    private const IT = [
        'This product has multiple variants. The options may be chosen on the product page'
            => 'Questo prodotto ha più varianti. Le opzioni si possono scegliere nella pagina del prodotto',
        'This product has multiple variants. The options may be chosen on the product page.'
            => 'Questo prodotto ha più varianti. Le opzioni si possono scegliere nella pagina del prodotto.',
    ];

    public function register(): void
    {
        add_filter('gettext', [$this, 'filter'], 20, 3);
        // Filtro dedicato WooCommerce per la descrizione "add to cart" dei prodotti variabili.
        add_filter('woocommerce_product_add_to_cart_description', [$this, 'filterAddToCartDescription'], 20, 1);
    }

    /**
     * È un percorso in lingua target (es. /en/)? In tal caso NON tradurre (resta inglese).
     */
    private function isTargetLangPath(): bool
    {
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        return $path !== '' && (bool) preg_match('#^/(en|de|fr|es|pt|nl|pl|ru|zh|ja|ar)(/|$)#i', $path);
    }

    /**
     * @param string $translated
     * @param string $text
     * @param string $domain
     * @return string
     */
    public function filter($translated, $text, $domain)
    {
        if ($domain !== 'woocommerce' || is_admin()) {
            return $translated;
        }
        if (!isset(self::IT[$text]) || $this->isTargetLangPath()) {
            return $translated;
        }
        return self::IT[$text];
    }

    /**
     * @param string $description
     * @return string
     */
    public function filterAddToCartDescription($description)
    {
        if (is_admin() || $this->isTargetLangPath()) {
            return $description;
        }
        if (isset(self::IT[$description])) {
            return self::IT[$description];
        }
        // Match difensivo anche se il testo differisce di poco dalla mappa.
        if (stripos((string) $description, 'multiple variants') !== false) {
            return 'Questo prodotto ha più varianti. Le opzioni si possono scegliere nella pagina del prodotto';
        }
        return $description;
    }
}
