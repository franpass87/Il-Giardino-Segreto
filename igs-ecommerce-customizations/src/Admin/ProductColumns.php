<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Admin;

use IGS\Ecommerce\Helper\Locale;

class ProductColumns
{
    public function register(): void
    {
        add_filter('manage_edit-product_columns', [$this, 'addColumn']);
        add_action('manage_product_posts_custom_column', [$this, 'renderColumn'], 10, 2);
        add_action('admin_head', [$this, 'enqueueStyles']);
    }

    public function addColumn(array $columns): array
    {
        $new = [];
        $label = Locale::isIt() ? 'Date tour' : 'Tour dates';

        foreach ($columns as $key => $val) {
            $new[$key] = $val;
            if ($key === 'name') {
                $new['tour_dates'] = $label;
            }
        }
        return $new;
    }

    public function renderColumn(string $column, int $postId): void
    {
        if ($column !== 'tour_dates') {
            return;
        }

        $ranges = get_post_meta($postId, '_date_ranges', true);
        if (is_array($ranges) && !empty($ranges[0]['start'])) {
            echo '<span class="tour-date-cell">'
                . esc_html($ranges[0]['start'])
                . ' → '
                . esc_html($ranges[0]['end'] ?? '')
                . '</span>';
        } else {
            echo '—';
        }
    }

    public function enqueueStyles(): void
    {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'edit-product') {
            return;
        }
        ?>
        <style>
          .widefat .column-tour_dates { white-space: nowrap; width: 140px; }
          .widefat .column-tour_dates .tour-date-cell { display: inline-block; min-width: 120px; overflow: hidden; text-overflow: ellipsis; }
        </style>
        <?php
    }
}
