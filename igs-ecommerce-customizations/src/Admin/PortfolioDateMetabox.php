<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Admin;

class PortfolioDateMetabox
{
    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetabox']);
        add_action('save_post', [$this, 'save']);
    }

    public function addMetabox(): void
    {
        add_meta_box(
            'date_tour_meta',
            'Date del Tour',
            [$this, 'render'],
            'portfolio',
            'side',
            'default'
        );
    }

    public function render(\WP_Post $post): void
    {
        $dataPartenza = get_post_meta($post->ID, '_data_partenza', true);
        $dataArrivo = get_post_meta($post->ID, '_data_arrivo', true);
        echo '<label for="data_partenza">Data Partenza:</label><br>';
        echo '<input type="date" name="data_partenza" id="data_partenza" value="' . esc_attr($dataPartenza) . '"><br><br>';
        echo '<label for="data_arrivo">Data Arrivo:</label><br>';
        echo '<input type="date" name="data_arrivo" id="data_arrivo" value="' . esc_attr($dataArrivo) . '">';
    }

    public function save(int $postId): void
    {
        if (isset($_POST['data_partenza'])) {
            update_post_meta($postId, '_data_partenza', sanitize_text_field(wp_unslash($_POST['data_partenza'])));
        }
        if (isset($_POST['data_arrivo'])) {
            update_post_meta($postId, '_data_arrivo', sanitize_text_field(wp_unslash($_POST['data_arrivo'])));
        }
    }
}
