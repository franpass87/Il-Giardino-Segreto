<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Frontend;

use WP;

/**
 * Invia header Cache-Control sulle pagine HTML del front-end così l'edge cache di
 * Aruba (cache CONDIVISA a livello hosting) non memorizzi più l'HTML e non serva
 * versioni vecchie dopo un deploy: niente più "warming" manuale di home/shop.
 *
 * Nota: questo riguarda solo i documenti HTML (richieste che passano da PHP/WordPress).
 * Gli asset statici (CSS/JS/immagini) sono serviti direttamente dal webserver, NON
 * passano da qui e mantengono la loro cache lunga. La page cache di FP Performance
 * continua a servire l'HTML velocemente dall'origin.
 */
class CacheHeaders
{
    public function register(): void
    {
        add_action('send_headers', [$this, 'sendHtmlHeaders']);
    }

    public function sendHtmlHeaders(?WP $wp = null): void
    {
        unset($wp);

        if (is_admin() || is_user_logged_in()) {
            return;
        }
        if ((defined('DOING_AJAX') && DOING_AJAX)
            || (defined('REST_REQUEST') && REST_REQUEST)
            || (defined('DOING_CRON') && DOING_CRON)
            || (defined('WP_CLI') && WP_CLI)
        ) {
            return;
        }
        $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';
        if ($method !== 'GET' && $method !== 'HEAD') {
            return;
        }
        // Pagine WooCommerce dinamiche: lasciamo gli header già impostati da WooCommerce.
        if (function_exists('is_cart') && (is_cart() || is_checkout() || (function_exists('is_account_page') && is_account_page()))) {
            return;
        }
        if (headers_sent()) {
            return;
        }

        // private        -> le cache condivise (edge Aruba) NON memorizzano l'HTML
        // no-cache/revalidate -> il browser rivalida (niente versioni vecchie)
        header('Cache-Control: no-cache, private, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
}
