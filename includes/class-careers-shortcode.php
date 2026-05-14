<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

class Careers_Shortcode
{
    private const SHORTCODE = 'nak_hr_careers';

    public static function register(): void
    {
        add_shortcode(self::SHORTCODE, [self::class, 'render']);
    }

    public static function render(): string
    {
        global $wpdb;

        wp_enqueue_style('nak-hr-frontend');
        wp_enqueue_script('nak-hr-careers');

        $table_name = $wpdb->prefix . 'nak_careers';
        $per_page = 9;
        $current_page = max(1, absint($_GET['careers_paged'] ?? 1));
        $offset = ($current_page - 1) * $per_page;
        $ajax_url = admin_url('admin-ajax.php');
        $modal_nonce = wp_create_nonce('nak_hr_career_modal');

        $total_items = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE is_active = 1");
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, title, details FROM {$table_name} WHERE is_active = 1 ORDER BY id DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
        $total_pages = max(1, (int) ceil($total_items / $per_page));

        ob_start();
        ?>
        <section class="nak-hr-careers-shell">
            <div class="nak-hr-careers-hero">
                <span class="nak-hr-careers-eyebrow"><?php esc_html_e('Noor Al Khalij', 'nooralkhalij-hr-system'); ?></span>
                <h2><?php esc_html_e('CAREER', 'nooralkhalij-hr-system'); ?></h2>
                <p><?php esc_html_e('Explore open opportunities and find the role that fits your next move.', 'nooralkhalij-hr-system'); ?></p>
            </div>

            <?php if (empty($items)) : ?>
                <div class="nak-hr-careers-empty">
                    <h3><?php esc_html_e('No open positions right now', 'nooralkhalij-hr-system'); ?></h3>
                    <p><?php esc_html_e('Please check back later for new opportunities.', 'nooralkhalij-hr-system'); ?></p>
                </div>
            <?php else : ?>
                <div class="nak-hr-careers-grid">
                    <?php foreach ($items as $item) : ?>
                        <article
                            class="nak-hr-career-card nak-hr-career-card--clickable"
                            data-career-id="<?php echo esc_attr($item->id); ?>"
                            data-ajax-url="<?php echo esc_url($ajax_url); ?>"
                            data-nonce="<?php echo esc_attr($modal_nonce); ?>"
                            tabindex="0"
                            role="button"
                            aria-label="<?php echo esc_attr(sprintf(__('View details for %s', 'nooralkhalij-hr-system'), $item->title)); ?>"
                        >
                            <div class="nak-hr-career-card-badge"><?php esc_html_e('Open Position', 'nooralkhalij-hr-system'); ?></div>
                            <h3><?php echo esc_html($item->title); ?></h3>
                            <p><?php echo esc_html(wp_html_excerpt((string) $item->details, 140, '...')); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="nak-hr-careers-pagination">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('careers_paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Previous', 'nooralkhalij-hr-system'),
                        'next_text' => __('Next &raquo;', 'nooralkhalij-hr-system'),
                        'total' => $total_pages,
                        'current' => $current_page,
                        'type' => 'plain',
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </section>
        <?php

        return (string) ob_get_clean();
    }
}
