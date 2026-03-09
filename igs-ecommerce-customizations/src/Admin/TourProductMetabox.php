<?php

declare(strict_types=1);

namespace IGS\Ecommerce\Admin;

use IGS\Ecommerce\Helper\Locale;

class TourProductMetabox
{
    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts'], 10, 1);
        add_action('woocommerce_product_options_general_product_data', [$this, 'render']);
        add_action('woocommerce_process_product_meta', [$this, 'save']);
    }

    public function enqueueScripts(string $hook): void
    {
        if (!in_array($hook, ['post-new.php', 'post.php'], true)) {
            return;
        }
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'product') {
            return;
        }

        $isIt = Locale::isIt();
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

        $i18n = [
            'departure' => $isIt ? 'Partenza' : 'Departure',
            'return' => $isIt ? 'Ritorno' : 'Return',
            'add' => $isIt ? 'Aggiungi' : 'Add',
            'remove' => $isIt ? 'Rimuovi' : 'Remove',
            'tourDates' => $isIt ? 'Date del tour' : 'Tour dates',
        ];
        wp_add_inline_script(
            'jquery-ui-datepicker',
            'window.giTourI18n = ' . wp_json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';',
            'before'
        );

        wp_add_inline_script('jquery-ui-datepicker', $this->getDatepickerJs(), 'after');
    }

    public function render(): void
    {
        $isIt = Locale::isIt();
        $saved = get_post_meta((int) get_the_ID(), '_date_ranges', true);

        echo '<div id="date_ranges_wrapper" class="options_group">';
        echo '<p class="form-field"><label>' . esc_html($isIt ? 'Date del tour' : 'Tour dates') . '</label> ';
        echo '<button type="button" class="button add-date-range">' . esc_html($isIt ? 'Aggiungi' : 'Add') . '</button></p>';
        echo '<div id="date_ranges_list">';

        if (is_array($saved)) {
            foreach ($saved as $r) {
                printf(
                    '<div class="date-range-row" style="margin-bottom:10px;">
                        <input type="text" name="date_ranges[start][]" class="date-field" value="%s" placeholder="%s" style="width:120px;margin-right:5px;">
                        <input type="text" name="date_ranges[end][]" class="date-field" value="%s" placeholder="%s" style="width:120px;margin-right:5px;">
                        <button type="button" class="button remove-date-range">%s</button>
                    </div>',
                    esc_attr($r['start'] ?? ''),
                    esc_attr($isIt ? 'Partenza' : 'Departure'),
                    esc_attr($r['end'] ?? ''),
                    esc_attr($isIt ? 'Ritorno' : 'Return'),
                    esc_html($isIt ? 'Rimuovi' : 'Remove')
                );
            }
        }

        echo '</div></div>';

        woocommerce_wp_text_input([
            'id' => '_paese_tour',
            'label' => $isIt ? 'Paese del tour' : 'Tour country',
            'placeholder' => $isIt ? 'Es. Italia' : 'e.g. Italy',
            'desc_tip' => true,
            'description' => $isIt ? 'Inserisci il paese del tour' : 'Enter the tour country',
        ]);
    }

    public function save(int $postId): void
    {
        if (!isset($_POST['date_ranges']['start']) || !is_array($_POST['date_ranges']['start'])) {
            delete_post_meta($postId, '_date_ranges');
        } else {
            $ranges = [];
            $startArr = isset($_POST['date_ranges']['start']) && is_array($_POST['date_ranges']['start']) ? wp_unslash($_POST['date_ranges']['start']) : [];
            $endArr = isset($_POST['date_ranges']['end']) && is_array($_POST['date_ranges']['end']) ? wp_unslash($_POST['date_ranges']['end']) : [];
            foreach ($startArr as $i => $start) {
                $end = $endArr[$i] ?? '';
                $start = sanitize_text_field($start);
                $end = sanitize_text_field($end);
                if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $start) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $end)) {
                    $ranges[] = [
                        'start' => $start,
                        'end' => $end,
                    ];
                }
            }
            update_post_meta($postId, '_date_ranges', $ranges);
        }

        if (isset($_POST['_paese_tour'])) {
            update_post_meta($postId, '_paese_tour', sanitize_text_field(wp_unslash($_POST['_paese_tour'])));
        }
    }

    private function getDatepickerJs(): string
    {
        return <<<'JS'
jQuery(function($){
  function initDatepicker(ctx){
    ctx.find('.date-field').datepicker({ dateFormat:'dd/mm/yy', minDate:0 });
  }
  initDatepicker($('#date_ranges_list'));

  $(document).on('click','.add-date-range',function(){
    var row = $(
      '<div class="date-range-row" style="margin-bottom:10px;">'+
        '<input type="text" name="date_ranges[start][]" class="date-field" placeholder="'+ (window.giTourI18n?.departure || 'Departure') +'" style="width:120px;margin-right:5px;">'+
        '<input type="text" name="date_ranges[end][]" class="date-field" placeholder="'+ (window.giTourI18n?.return || 'Return') +'" style="width:120px;margin-right:5px;">'+
        '<button type="button" class="button remove-date-range">'+ (window.giTourI18n?.remove || 'Remove') +'</button>'+
      '</div>'
    );
    $('#date_ranges_list').append(row);
    initDatepicker(row);
  });

  $(document).on('click','.remove-date-range',function(){
    $(this).closest('.date-range-row').remove();
  });
});
JS;
    }
}
