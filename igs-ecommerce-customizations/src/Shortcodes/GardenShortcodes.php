<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Shortcodes;

class GardenShortcodes
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueStyles']);
        add_shortcode('protagonista_tour', [$this, 'protagonista']);
        add_shortcode('livello_culturale', fn () => $this->barFeature('livello_culturale', 'Cultura'));
        add_shortcode('livello_passeggiata', fn () => $this->barFeature('livello_passeggiata', 'Passeggiata'));
        add_shortcode('livello_piuma', fn () => $this->barFeature('livello_piuma', 'Comfort'));
        add_shortcode('livello_esclusivita', fn () => $this->barFeature('livello_esclusivita', 'Esclusività'));
    }

    public function enqueueStyles(): void
    {
        if (!is_singular('product')) {
            return;
        }
        $css = '.garden-feature .bar-feature-item{opacity:0;animation:igs-bar-in .4s ease forwards}.garden-feature .bar-feature-item:nth-child(1){animation-delay:0}.garden-feature .bar-feature-item:nth-child(2){animation-delay:.05s}.garden-feature .bar-feature-item:nth-child(3){animation-delay:.1s}.garden-feature .bar-feature-item:nth-child(4){animation-delay:.15s}.garden-feature .bar-feature-item:nth-child(5){animation-delay:.2s}@keyframes igs-bar-in{to{opacity:1}}';
        wp_add_inline_style('woocommerce-general', $css);
    }

    public function protagonista(): string
    {
        if (!is_singular('product')) {
            return '';
        }
        $text = get_post_meta((int) get_the_ID(), '_protagonista_tour', true);
        if (!$text) {
            return '';
        }
        return '<div class="garden-feature" style="margin-bottom:12px;">'
            . '<div style="font-weight:bold; font-family:\'the-seasons-regular\'; margin-bottom:8px;">Pianta</div>'
            . '<div style="min-height:32px; display:flex; align-items:center; justify-content:center;">'
            . esc_html($text)
            . '</div></div>';
    }

    public function barFeature(string $metaKey, string $label): string
    {
        if (!is_singular('product')) {
            return '';
        }
        $value = (int) get_post_meta((int) get_the_ID(), '_' . $metaKey, true);
        if ($value < 1 || $value > 5) {
            return '';
        }

        $bars = '';
        for ($i = 1; $i <= 5; $i++) {
            $filled = $i <= $value ? '#00665e' : '#ccc';
            $bars .= '<div class="bar-feature-item" style="width:14px; height:8px; border-radius:4px; background:' . esc_attr($filled) . ';"></div>';
        }

        return '<div class="garden-feature" style="margin-bottom:12px;">'
            . '<div style="font-weight:bold; font-family:\'the-seasons-regular\'; margin-bottom:8px;">' . esc_html($label) . '</div>'
            . '<div style="min-height:32px; display:flex; justify-content:center; align-items:center; gap:4px;">' . $bars . '</div>'
            . '</div>';
    }
}
