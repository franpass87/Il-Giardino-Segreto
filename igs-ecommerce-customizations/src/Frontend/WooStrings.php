<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use IGS\Ecommerce\Helper\Locale;

/**
 * Forza in italiano alcune stringhe core di WooCommerce sulle pagine in lingua
 * italiana, quando il locale a runtime non le applica (su questo sito alcune
 * stringhe WC restano in inglese anche con il .mo it_IT presente).
 *
 * Agisce solo su pagine IT (Locale::isIt) e dominio 'woocommerce'; su /en/ lascia
 * l'inglese. Mappa puntuale (nessun impatto su altre stringhe).
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
        if (!isset(self::IT[$text])) {
            return $translated;
        }
        if (!Locale::isIt()) {
            return $translated;
        }
        return self::IT[$text];
    }
}
