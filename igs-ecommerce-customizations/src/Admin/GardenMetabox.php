<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Admin;

class GardenMetabox
{
    private const FIELDS = [
        'protagonista_tour' => ['label' => 'Pianta', 'type' => 'text'],
        'livello_culturale' => ['label' => 'Cultura (1–5)', 'type' => 'number'],
        'livello_passeggiata' => ['label' => 'Passeggiata (1–5)', 'type' => 'number'],
        'livello_piuma' => ['label' => 'Comfort (1–5)', 'type' => 'number'],
        'livello_esclusivita' => ['label' => 'Esclusività (1–5)', 'type' => 'number'],
    ];

    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'addMetabox']);
        add_action('save_post', [$this, 'save']);
    }

    public function addMetabox(): void
    {
        add_meta_box(
            'garden_details_meta',
            'Dettagli Garden Tour',
            [$this, 'render'],
            'product',
            'normal',
            'high'
        );
    }

    public function render(\WP_Post $post): void
    {
        foreach (self::FIELDS as $key => $field) {
            $value = get_post_meta($post->ID, '_' . $key, true);
            echo '<p><label style="width:180px; display:inline-block;">' . esc_html($field['label']) . ':</label>';
            echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" ';
            if ($field['type'] === 'number') {
                echo 'min="1" max="5" ';
            }
            echo 'style="width:200px;" /></p>';
        }
    }

    public function save(int $postId): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        foreach (self::FIELDS as $key => $field) {
            if (!isset($_POST[$key])) {
                continue;
            }
            $value = sanitize_text_field(wp_unslash($_POST[$key]));
            if ($field['type'] === 'number' && $value !== '') {
                $num = absint($value);
                $value = (string) max(1, min(5, $num ?: 1));
            }
            update_post_meta($postId, '_' . $key, $value);
        }
    }
}
