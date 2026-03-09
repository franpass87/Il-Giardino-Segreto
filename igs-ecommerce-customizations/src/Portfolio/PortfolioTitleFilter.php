<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Portfolio;

class PortfolioTitleFilter
{
    private const PARTNER_LOGO_URL = 'https://www.italiangardentour.com/wp-content/uploads/2025/07/mob_LOGO_grandigiardiniitaliani_1c.png';

    public function register(): void
    {
        add_filter('the_title', [$this, 'filterTitle'], 10, 2);
    }

    public function filterTitle(string $title, int $postId): string
    {
        if (is_admin() || get_post_type($postId) !== 'portfolio') {
            return $title;
        }

        $dataPartenza = get_post_meta($postId, '_data_partenza', true);
        $dataArrivo = get_post_meta($postId, '_data_arrivo', true);

        if (empty($dataPartenza) || empty($dataArrivo)) {
            return $title;
        }

        $tsPartenza = strtotime($dataPartenza);
        $tsArrivo = strtotime($dataArrivo);
        if ($tsPartenza === false || $tsArrivo === false) {
            return $title;
        }

        $dataPartenzaFmt = date_i18n('d M Y', $tsPartenza);
        $dataArrivoFmt = date_i18n('d M Y', $tsArrivo);

        $cssClass = is_singular('portfolio') ? 'tour-date-single' : 'tour-date-loop';
        $dateHtml = '<div class="' . esc_attr($cssClass) . '">' . esc_html($dataPartenzaFmt) . ' → ' . esc_html($dataArrivoFmt) . '</div>';

        $logoHtml = '';
        if (!is_singular('portfolio') && has_term('tour-in-partnership', 'portfolio_category', $postId)) {
            $logoHtml = '<div class="partner-logo-loop"><img src="' . esc_url(self::PARTNER_LOGO_URL) . '" width="100" height="100" alt="Partner: Grandi Giardini Italiani"></div>';
        }

        return $title . $dateHtml . $logoHtml;
    }
}
