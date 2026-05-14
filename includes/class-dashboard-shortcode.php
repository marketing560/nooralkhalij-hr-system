<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Shortcode
{
    private const SHORTCODE = 'nak_hr_dashboard';

    public static function register(): void
    {
        add_shortcode(self::SHORTCODE, [self::class, 'render']);
    }

    public static function render(): string
    {
        wp_enqueue_style('nak-hr-frontend');
        wp_enqueue_script('nak-hr-careers');

        if (!is_user_logged_in()) {
            wp_safe_redirect(home_url('/login'));
            exit;
        }

        $user = wp_get_current_user();
        $is_master = in_array('nak_master', (array) $user->roles, true);
        $role_labels = array_map('translate_user_role', $user->roles);
        $logout_url = wp_logout_url(get_permalink() ?: home_url('/'));
        $sections = [
            'general-info' => __('General Info', 'nooralkhalij-hr-system'),
            'leaves-vacations' => __('Leaves and Vacations', 'nooralkhalij-hr-system'),
            'infinity-wiki' => __('Infinity Wiki', 'nooralkhalij-hr-system'),
            'suggestions-more' => __('Suggestions and More', 'nooralkhalij-hr-system'),
        ];

        if ($is_master) {
            $sections['careers'] = __('Careers', 'nooralkhalij-hr-system');
        }

        $current_section = sanitize_key($_GET['nak_section'] ?? 'general-info');

        if (!array_key_exists($current_section, $sections)) {
            $current_section = 'general-info';
        }

        ob_start();
        ?>
        <div class="nak-hr-auth-shell">
            <div class="nak-hr-auth-card nak-hr-dashboard-layout">
                <aside class="nak-hr-dashboard-sidebar">
                    <div class="nak-hr-dashboard-sidebar-head">
                        <h2><?php esc_html_e('Dashboard', 'nooralkhalij-hr-system'); ?></h2>
                        <p><?php echo esc_html($user->display_name ?: $user->user_login); ?></p>
                    </div>

                    <nav class="nak-hr-dashboard-nav">
                        <?php foreach ($sections as $section_key => $section_label) : ?>
                            <a
                                class="nak-hr-dashboard-nav-link <?php echo $current_section === $section_key ? 'is-active' : ''; ?>"
                                href="<?php echo esc_url(add_query_arg('nak_section', $section_key, get_permalink() ?: '')); ?>"
                            >
                                <?php echo esc_html($section_label); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <a class="nak-hr-action-button nak-hr-action-button--secondary" href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Sign out', 'nooralkhalij-hr-system'); ?></a>
                </aside>

                <div class="nak-hr-dashboard-content">
                    <div class="nak-hr-auth-copy">
                        <h2><?php echo esc_html($sections[$current_section]); ?></h2>
                        <p><?php echo esc_html(sprintf(__('Welcome, %s.', 'nooralkhalij-hr-system'), $user->display_name ?: $user->user_login)); ?></p>
                    </div>

                    <div class="nak-hr-dashboard-panel">
                        <?php if ($current_section === 'general-info') : ?>
                            <?php require NAK_HR_PLUGIN_DIR . 'includes/dashboard-tabs/general-info.php'; ?>
                        <?php elseif ($current_section === 'leaves-vacations') : ?>
                            <?php require NAK_HR_PLUGIN_DIR . 'includes/dashboard-tabs/leaves-vacations.php'; ?>
                        <?php elseif ($current_section === 'infinity-wiki') : ?>
                            <?php Dashboard_Infinity_Wiki_Tab::render(); ?>
                        <?php elseif ($current_section === 'suggestions-more') : ?>
                            <?php require NAK_HR_PLUGIN_DIR . 'includes/dashboard-tabs/suggestions-more.php'; ?>
                        <?php elseif ($current_section === 'careers' && $is_master) : ?>
                            <?php Dashboard_Careers_Tab::render(); ?>
                        <?php else : ?>
                            <div class="nak-hr-dashboard-empty">
                                <h3><?php echo esc_html($sections[$current_section]); ?></h3>
                                <p><?php esc_html_e('This section is ready and currently blank.', 'nooralkhalij-hr-system'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

}
