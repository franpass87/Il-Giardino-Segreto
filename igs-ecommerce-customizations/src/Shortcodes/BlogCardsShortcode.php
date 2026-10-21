<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Shortcodes;

use WP_Query;

class BlogCardsShortcode
{
    public function register(): void
    {
        add_shortcode('igs_blog_cards', [$this, 'render']);
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function render(array|string $atts = []): string
    {
        $atts = shortcode_atts([
            'count'    => 4,
            'more'     => '',
            'category' => '',
        ], is_array($atts) ? $atts : [], 'igs_blog_cards');

        $count = absint($atts['count']);
        if ($count < 1) {
            $count = 4;
        } elseif ($count > 12) {
            $count = 12;
        }

        $more = trim((string) ($atts['more'] ?? ''));
        $moreLabel = $more !== ''
            ? sanitize_text_field($atts['more'])
            : __('Leggi l\'articolo', 'igs-ecommerce');

        $category = trim((string) ($atts['category'] ?? ''));
        $categorySlug = $category !== '' ? sanitize_title($category) : '';

        $queryArgs = [
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => $count,
            'ignore_sticky_posts' => true,
            'suppress_filters'    => false,
            'no_found_rows'       => true,
        ];
        if ($categorySlug !== '') {
            $queryArgs['category_name'] = $categorySlug;
        }

        $query = new WP_Query($queryArgs);

        if (!$query->have_posts()) {
            return '';
        }

        static $stylePrinted = false;
        $output = '';

        if (!$stylePrinted) {
            $output .= '<style>'
                . '.igs-pcards{display:grid;grid-template-columns:repeat(4,1fr);gap:22px;margin-top:34px}'
                . '@media (max-width:1000px){.igs-pcards{grid-template-columns:repeat(2,1fr)}}'
                . '@media (max-width:560px){.igs-pcards{grid-template-columns:1fr;gap:18px}}'
                . '.igs-pcard{display:flex;flex-direction:column;background:#fff;border-radius:16px;overflow:hidden;text-decoration:none!important;color:inherit;box-shadow:0 2px 10px rgba(11,87,100,.08);transition:transform .25s ease,box-shadow .25s ease}'
                . '.igs-pcard:hover{transform:translateY(-4px);box-shadow:0 12px 28px rgba(11,87,100,.16)}'
                . '.igs-pcard__img{display:block;aspect-ratio:4/3;overflow:hidden;background:#e3eeec}'
                . '.igs-pcard__img picture{display:block;width:100%;height:100%}'
                . '.igs-pcard__img img{width:100%!important;height:100%!important;object-fit:cover;display:block;margin:0!important;transition:transform .5s ease}'
                . '.igs-pcard:hover .igs-pcard__img img{transform:scale(1.04)}'
                . '.igs-pcard__body{text-align:left;display:flex;flex-direction:column;flex:1;padding:18px 20px 20px}'
                . '.igs-pcard .igs-pcard__tag{align-self:flex-start;font-size:12px;line-height:1.5!important;letter-spacing:.1em;text-transform:uppercase;color:#0b5764;background:#e3eeec;border-radius:999px;padding:3px 11px;margin-bottom:12px}'
                . '.igs-pcard h3{font-size:21px;line-height:1.25;margin:0 0 10px;padding:0;color:#333}'
                . '.igs-pcard p{font-size:15px;line-height:1.55;color:#666;margin:0 0 14px;padding:0;flex:1}'
                . '.igs-bcard__meta{display:block;font-size:13px;color:#8a8a8a;margin:0 0 12px}'
                . '.igs-pcard__more{display:inline-flex;align-items:center;gap:8px;font-weight:600;font-size:14px;color:#0b5764}'
                . '.igs-pcard__more svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;transition:transform .2s ease}'
                . '.igs-pcard:hover .igs-pcard__more svg{transform:translateX(3px)}'
                . '</style>';
            $stylePrinted = true;
        }

        $defaultCatId = (int) get_option('default_category');
        $dateFormat = str_starts_with(determine_locale(), 'it') ? 'j F Y' : 'F j, Y';
        $cards = '';

        while ($query->have_posts()) {
            $query->the_post();
            $postId = (int) get_the_ID();

            $permalink = get_permalink($postId);
            $title = get_the_title($postId);
            $titleText = wp_strip_all_tags($title);

            $thumbnail = get_the_post_thumbnail($postId, 'large', [
                'loading' => 'lazy',
                'alt'     => $titleText,
            ]);
            if (!is_string($thumbnail)) {
                $thumbnail = '';
            }

            $tagHtml = '';
            $categories = get_the_category($postId);
            if (!empty($categories) && is_array($categories)) {
                $firstCat = $categories[0];
                $catId = (int) ($firstCat->term_id ?? 0);
                $catName = trim((string) ($firstCat->name ?? ''));
                $catSlug = strtolower(trim((string) ($firstCat->slug ?? '')));
                $catLower = strtolower($catName);

                $isDefault = ($catId === $defaultCatId)
                    || in_array($catLower, ['uncategorized', 'senza categoria', 'senza-categoria', 'non categorizzato'], true)
                    || in_array($catSlug, ['uncategorized', 'senza-categoria', 'non-categorizzato'], true);

                $isFilteredCategory = $categorySlug !== '' && $catSlug === $categorySlug;

                if ($catName !== '' && !$isDefault && !$isFilteredCategory) {
                    $tagHtml = '<span class="igs-pcard__tag">' . esc_html($catName) . '</span>';
                }
            }

            $rawExcerpt = get_the_excerpt($postId);
            $cleanExcerpt = wp_strip_all_tags(strip_shortcodes($rawExcerpt));
            $excerpt = wp_trim_words($cleanExcerpt, 20, '…');

            $date = get_the_date($dateFormat, $postId);

            $cards .= '<a class="igs-pcard igs-bcard" href="' . esc_url($permalink) . '">'
                . '<span class="igs-pcard__img">' . $thumbnail . '</span>'
                . '<span class="igs-pcard__body">'
                . $tagHtml
                . '<h3>' . esc_html($titleText) . '</h3>'
                . '<p>' . esc_html($excerpt) . '</p>'
                . '<span class="igs-bcard__meta">' . esc_html($date) . '</span>'
                . '<span class="igs-pcard__more">' . esc_html($moreLabel)
                . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>'
                . '</span>'
                . '</a>';
        }

        wp_reset_postdata();

        $output .= '<div class="igs-pcards igs-bcards">' . $cards . '</div>';

        return $output;
    }
}
