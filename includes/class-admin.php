<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    public static function register_menu(): void
    {
        add_menu_page(
            __('HR System', 'nooralkhalij-hr-system'),
            __('HR System', 'nooralkhalij-hr-system'),
            'manage_options',
            'nak-hr-system',
            [self::class, 'render_shortcode_page'],
            'dashicons-groups',
            58
        );

        add_submenu_page(
            'nak-hr-system',
            __('Employees', 'nooralkhalij-hr-system'),
            __('Employees', 'nooralkhalij-hr-system'),
            'manage_options',
            'nak-hr-employees',
            [self::class, 'render_employees_page']
        );
    }

    public static function render_user_profile_fields(\WP_User $user): void
    {
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }

        $email_activated = (int) get_user_meta($user->ID, 'nak_email_activated', true) === 1;
        ?>
        <h2><?php esc_html_e('Noor Al Khalij HR Settings', 'nooralkhalij-hr-system'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="nak_email_activated"><?php esc_html_e('Email activated', 'nooralkhalij-hr-system'); ?></label></th>
                <td>
                    <label for="nak_email_activated">
                        <input
                            type="checkbox"
                            name="nak_email_activated"
                            id="nak_email_activated"
                            value="1"
                            <?php checked($email_activated); ?>
                        >
                        <?php esc_html_e('Mark this user as email verified.', 'nooralkhalij-hr-system'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Uncheck to require email verification again.', 'nooralkhalij-hr-system'); ?></p>
                    <?php wp_nonce_field('nak_hr_save_user_profile', 'nak_hr_user_profile_nonce'); ?>
                </td>
            </tr>
        </table>
        <?php
    }

    public static function save_user_profile_fields(int $user_id): void
    {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        if (
            empty($_POST['nak_hr_user_profile_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nak_hr_user_profile_nonce'])), 'nak_hr_save_user_profile')
        ) {
            return;
        }

        $is_email_activated = isset($_POST['nak_email_activated']) ? 1 : 0;
        update_user_meta($user_id, 'nak_email_activated', $is_email_activated);

        if ($is_email_activated === 1) {
            delete_user_meta($user_id, 'nak_email_verification_code');
            delete_user_meta($user_id, 'nak_email_verification_sent_at');
            delete_user_meta($user_id, 'nak_email_verification_expires_at');
        }
    }

    public static function render_shortcode_page(): void
    {
        $shortcodes = [
            [
                'tag' => '[nak_hr_signup]',
                'description' => __('Displays the company signup form for creating new users with @infinityecn.com emails only.', 'nooralkhalij-hr-system'),
            ],
            [
                'tag' => '[nak_hr_login]',
                'description' => __('Displays the employee login form for HR system users.', 'nooralkhalij-hr-system'),
            ],
            [
                'tag' => '[nak_hr_auth]',
                'description' => __('Displays login/signup when logged out, and dashboard/sign out when logged in.', 'nooralkhalij-hr-system'),
            ],
            [
                'tag' => '[nak_hr_auth variant="menu"]',
                'description' => __('Displays a compact menu button that links to login or My Account.', 'nooralkhalij-hr-system'),
            ],
            [
                'tag' => '[nak_hr_dashboard]',
                'description' => __('Displays the logged-in user dashboard only.', 'nooralkhalij-hr-system'),
            ],
            [
                'tag' => '[nak_hr_careers]',
                'description' => __('Displays active career positions as public cards with server-side pagination.', 'nooralkhalij-hr-system'),
            ],
        ];

        if (
            strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
            && isset($_POST['nak_hr_settings_nonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nak_hr_settings_nonce'])), 'nak_hr_save_settings')
        ) {
            $questions_per_popup = max(1, min(10, absint($_POST['nak_hr_questions_per_popup'] ?? 1)));
            $quiz_interval_minutes = max(0, absint($_POST['nak_hr_quiz_interval_minutes'] ?? 0));
            $session_expiry_minutes = max(0, absint($_POST['nak_hr_session_expiry_minutes'] ?? 0));
            update_option('nak_hr_questions_per_popup', $questions_per_popup);
            update_option('nak_hr_quiz_interval_minutes', $quiz_interval_minutes);
            update_option('nak_hr_session_expiry_minutes', $session_expiry_minutes);
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'nooralkhalij-hr-system') . '</p></div>';
        }

        $questions_per_popup = (int) get_option('nak_hr_questions_per_popup', 1);
        $quiz_interval_minutes = (int) get_option('nak_hr_quiz_interval_minutes', 0);
        $session_expiry_minutes = (int) get_option('nak_hr_session_expiry_minutes', 0);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Noor Al Khalij HR System', 'nooralkhalij-hr-system'); ?></h1>
            <p><?php esc_html_e('Use these shortcodes on any page to render plugin features.', 'nooralkhalij-hr-system'); ?></p>
            <p>
                <strong><?php esc_html_e('Available employee roles:', 'nooralkhalij-hr-system'); ?></strong>
                <?php foreach (Plugin::get_employee_roles() as $role_key => $role_label) : ?>
                    <code style="margin-right: 8px;"><?php echo esc_html($role_key); ?></code>
                <?php endforeach; ?>
            </p>

            <form method="post" style="max-width: 520px; margin: 20px 0 28px; padding: 20px; background: #fff; border: 1px solid #dcdcde; border-radius: 8px;">
                <?php wp_nonce_field('nak_hr_save_settings', 'nak_hr_settings_nonce'); ?>
                <h2 style="margin-top: 0;"><?php esc_html_e('Quiz Popup Settings', 'nooralkhalij-hr-system'); ?></h2>
                <p><?php esc_html_e('Choose how many questions should appear in the employee quiz popup.', 'nooralkhalij-hr-system'); ?></p>
                <label for="nak_hr_questions_per_popup" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php esc_html_e('Questions per popup', 'nooralkhalij-hr-system'); ?></label>
                <input
                    type="number"
                    min="1"
                    max="10"
                    id="nak_hr_questions_per_popup"
                    name="nak_hr_questions_per_popup"
                    value="<?php echo esc_attr((string) $questions_per_popup); ?>"
                    style="width: 120px; margin-bottom: 12px;"
                >

                <label for="nak_hr_quiz_interval_minutes" style="display: block; margin: 8px 0; font-weight: 600;"><?php esc_html_e('Time between quiz popups (minutes)', 'nooralkhalij-hr-system'); ?></label>
                <input
                    type="number"
                    min="0"
                    id="nak_hr_quiz_interval_minutes"
                    name="nak_hr_quiz_interval_minutes"
                    value="<?php echo esc_attr((string) $quiz_interval_minutes); ?>"
                    style="width: 120px; margin-bottom: 12px;"
                >
                <p style="margin-top: 0; color: #50575e;"><?php esc_html_e('0 means show on every login decision.', 'nooralkhalij-hr-system'); ?></p>

                <label for="nak_hr_session_expiry_minutes" style="display: block; margin: 8px 0; font-weight: 600;"><?php esc_html_e('Session expiry (minutes)', 'nooralkhalij-hr-system'); ?></label>
                <input
                    type="number"
                    min="0"
                    id="nak_hr_session_expiry_minutes"
                    name="nak_hr_session_expiry_minutes"
                    value="<?php echo esc_attr((string) $session_expiry_minutes); ?>"
                    style="width: 120px; margin-bottom: 12px;"
                >
                <p style="margin-top: 0; color: #50575e;"><?php esc_html_e('0 means use the default WordPress session duration.', 'nooralkhalij-hr-system'); ?></p>
                <div>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'nooralkhalij-hr-system'); ?></button>
                </div>
            </form>

            <table class="widefat striped" style="max-width: 980px; margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Shortcode', 'nooralkhalij-hr-system'); ?></th>
                        <th><?php esc_html_e('Description', 'nooralkhalij-hr-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($shortcodes as $shortcode) : ?>
                        <tr>
                            <td><code><?php echo esc_html($shortcode['tag']); ?></code></td>
                            <td><?php echo esc_html($shortcode['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_employees_page(): void
    {
        $per_page = 20;
        $current_page = max(1, absint($_GET['paged'] ?? 1));
        $offset = ($current_page - 1) * $per_page;
        $search = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));

        $role_keys = array_keys(Plugin::get_employee_roles());
        $query_args = [
            'meta_query' => [
                [
                    'key' => $GLOBALS['wpdb']->get_blog_prefix() . 'capabilities',
                    'value' => '"nak_',
                    'compare' => 'LIKE',
                ],
            ],
            'orderby' => 'registered',
            'order' => 'DESC',
            'number' => $per_page,
            'offset' => $offset,
            'count_total' => true,
        ];

        if ($search !== '') {
            $query_args['search'] = '*' . $search . '*';
            $query_args['search_columns'] = ['user_email', 'display_name', 'user_login'];
        }

        $query = new \WP_User_Query($query_args);

        $employees = $query->get_results();
        $total_users = (int) $query->get_total();
        $total_pages = max(1, (int) ceil($total_users / $per_page));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Employees', 'nooralkhalij-hr-system'); ?></h1>
            <p><?php esc_html_e('Users created for the HR system with employee department roles.', 'nooralkhalij-hr-system'); ?></p>

            <form method="get" style="margin: 16px 0 20px; display: flex; gap: 8px; align-items: center; max-width: 520px;">
                <input type="hidden" name="page" value="nak-hr-employees">
                <input
                    type="search"
                    name="s"
                    value="<?php echo esc_attr($search); ?>"
                    placeholder="<?php esc_attr_e('Search by name or email', 'nooralkhalij-hr-system'); ?>"
                    style="width: 100%; max-width: 360px;"
                >
                <button type="submit" class="button button-primary"><?php esc_html_e('Search', 'nooralkhalij-hr-system'); ?></button>
                <?php if ($search !== '') : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=nak-hr-employees')); ?>" class="button"><?php esc_html_e('Clear', 'nooralkhalij-hr-system'); ?></a>
                <?php endif; ?>
            </form>

            <table class="widefat striped" style="max-width: 1100px; margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'nooralkhalij-hr-system'); ?></th>
                        <th><?php esc_html_e('Username', 'nooralkhalij-hr-system'); ?></th>
                        <th><?php esc_html_e('Email', 'nooralkhalij-hr-system'); ?></th>
                        <th><?php esc_html_e('Role', 'nooralkhalij-hr-system'); ?></th>
                        <th><?php esc_html_e('Registered', 'nooralkhalij-hr-system'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)) : ?>
                        <tr>
                            <td colspan="5">
                                <?php echo esc_html($search !== '' ? __('No employees matched your search.', 'nooralkhalij-hr-system') : __('No employees found yet.', 'nooralkhalij-hr-system')); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($employees as $employee) : ?>
                            <?php
                            $edit_link = get_edit_user_link($employee->ID);
                            $delete_link = current_user_can('delete_user', $employee->ID)
                                ? wp_nonce_url(
                                    admin_url('users.php?action=delete&user=' . $employee->ID),
                                    'bulk-users'
                                )
                                : '';
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($employee->display_name ?: trim($employee->first_name . ' ' . $employee->last_name)); ?></strong>
                                    <div class="row-actions">
                                        <?php if ($edit_link) : ?>
                                            <span class="edit"><a href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit', 'nooralkhalij-hr-system'); ?></a></span>
                                        <?php endif; ?>
                                        <?php if ($edit_link && $delete_link) : ?>
                                            <span> | </span>
                                        <?php endif; ?>
                                        <?php if ($delete_link) : ?>
                                            <span class="trash"><a href="<?php echo esc_url($delete_link); ?>"><?php esc_html_e('Delete', 'nooralkhalij-hr-system'); ?></a></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo esc_html($employee->user_login); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($employee->user_email); ?>"><?php echo esc_html($employee->user_email); ?></a></td>
                                <td><?php echo esc_html(implode(', ', array_map('translate_user_role', $employee->roles))); ?></td>
                                <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $employee->user_registered)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php
            echo paginate_links([
                'base' => add_query_arg([
                    'paged' => '%#%',
                    's' => $search,
                ]),
                'format' => '',
                'prev_text' => __('&laquo; Previous', 'nooralkhalij-hr-system'),
                'next_text' => __('Next &raquo;', 'nooralkhalij-hr-system'),
                'total' => $total_pages,
                'current' => $current_page,
                'type' => 'plain',
            ]);
            ?>
        </div>
        <?php
    }
}
