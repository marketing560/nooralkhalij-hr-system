<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

require_once NAK_HR_PLUGIN_DIR . 'includes/class-admin.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-signup-shortcode.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-login-shortcode.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-auth-gateway-shortcode.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-dashboard-shortcode.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-dashboard-careers-tab.php';
require_once NAK_HR_PLUGIN_DIR . 'includes/class-careers-shortcode.php';

class Plugin
{
    private static ?Plugin $instance = null;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function get_employee_roles(): array
    {
        return [
            'nak_employee' => __('Employee', 'nooralkhalij-hr-system'),
            'nak_sales' => __('Sales', 'nooralkhalij-hr-system'),
            'nak_retention' => __('Retention', 'nooralkhalij-hr-system'),
            'nak_qc' => __('QC', 'nooralkhalij-hr-system'),
            'nak_marketing' => __('Marketing', 'nooralkhalij-hr-system'),
            'nak_training' => __('Training', 'nooralkhalij-hr-system'),
            'nak_master' => __('Master', 'nooralkhalij-hr-system'),
        ];
    }

    public static function activate(): void
    {
        self::register_roles();
        self::create_tables();
    }

    public static function register_roles(): void
    {
        foreach (self::get_employee_roles() as $role_key => $role_label) {
            add_role(
                $role_key,
                $role_label,
                [
                    'read' => true,
                ]
            );
        }
    }

    public static function create_tables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $quiz_table = $wpdb->prefix . 'nak_quiz_questions';
        $careers_table = $wpdb->prefix . 'nak_careers';
        $applications_table = $wpdb->prefix . 'nak_career_applications';

        $quiz_sql = "CREATE TABLE {$quiz_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            role VARCHAR(50) NOT NULL,
            question VARCHAR(255) NOT NULL,
            choices JSON NOT NULL,
            answer_key INT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY unique_question (question),
            KEY idx_role (role)
        ) {$charset_collate};";

        $careers_sql = "CREATE TABLE {$careers_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            details LONGTEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_active (is_active)
        ) {$charset_collate};";

        $applications_sql = "CREATE TABLE {$applications_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            career_id BIGINT UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(100) NOT NULL,
            cv_attachment_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            cv_url TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_career_id (career_id),
            KEY idx_email (email)
        ) {$charset_collate};";

        dbDelta($quiz_sql);
        dbDelta($careers_sql);
        dbDelta($applications_sql);
    }

    private function __construct()
    {
        add_action('init', [$this, 'register_shortcodes']);
        add_action('init', [self::class, 'register_roles']);
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('after_setup_theme', [$this, 'maybe_hide_admin_bar']);
        add_action('wp_ajax_nak_hr_get_career', [self::class, 'ajax_get_career']);
        add_action('wp_ajax_nopriv_nak_hr_get_career', [self::class, 'ajax_get_career']);
        add_action('wp_ajax_nak_hr_apply_career', [self::class, 'ajax_apply_career']);
        add_action('wp_ajax_nopriv_nak_hr_apply_career', [self::class, 'ajax_apply_career']);
        add_action('wp_ajax_nak_hr_get_wiki_form', ['\\NoorAlKhalij\\HRSystem\\Dashboard_Shortcode', 'ajax_get_wiki_form']);
        add_action('wp_ajax_nak_hr_save_wiki_question', ['\\NoorAlKhalij\\HRSystem\\Dashboard_Shortcode', 'ajax_save_wiki_question']);
        add_action('wp_ajax_nak_hr_delete_wiki_question', ['\\NoorAlKhalij\\HRSystem\\Dashboard_Shortcode', 'ajax_delete_wiki_question']);
        add_action('wp_ajax_nak_hr_get_career_form', ['\\NoorAlKhalij\\HRSystem\\Dashboard_Shortcode', 'ajax_get_career_form']);
        add_action('wp_ajax_nak_hr_save_career', ['\\NoorAlKhalij\\HRSystem\\Dashboard_Shortcode', 'ajax_save_career']);
        add_action('wp_ajax_nak_hr_toggle_career', ['\\NoorAlKhalij\\HRSystem\\Dashboard_Shortcode', 'ajax_toggle_career']);
        add_action('wp_ajax_nak_hr_delete_career', ['\\NoorAlKhalij\\HRSystem\\Dashboard_Shortcode', 'ajax_delete_career']);
    }

    public function register_shortcodes(): void
    {
        Signup_Shortcode::register();
        Login_Shortcode::register();
        Auth_Gateway_Shortcode::register();
        Dashboard_Shortcode::register();
        Careers_Shortcode::register();
    }

    public function register_admin_menu(): void
    {
        Admin::register_menu();
    }

    public function enqueue_assets(): void
    {
        wp_register_style(
            'nak-hr-frontend',
            NAK_HR_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            NAK_HR_VERSION
        );

        wp_register_script(
            'nak-hr-careers',
            NAK_HR_PLUGIN_URL . 'assets/js/careers.js',
            [],
            NAK_HR_VERSION,
            true
        );
    }

    public static function ajax_get_career(): void
    {
        check_ajax_referer('nak_hr_career_modal', 'nonce');

        global $wpdb;

        $career_id = absint($_POST['career_id'] ?? 0);
        $table_name = $wpdb->prefix . 'nak_careers';
        $career = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, title, details FROM {$table_name} WHERE id = %d AND is_active = 1",
                $career_id
            )
        );

        if (!$career) {
            wp_send_json_error(['message' => __('Position not found.', 'nooralkhalij-hr-system')], 404);
        }

        ob_start();
        ?>
        <div class="nak-hr-careers-modal__content">
            <button type="button" class="nak-hr-careers-modal__close" data-careers-close>&times;</button>
            <div class="nak-hr-career-card-badge"><?php esc_html_e('Open Position', 'nooralkhalij-hr-system'); ?></div>
            <h3><?php echo esc_html($career->title); ?></h3>
            <div class="nak-hr-careers-modal__body"><?php echo wpautop(esc_html($career->details)); ?></div>

            <form class="nak-hr-careers-apply-form" data-career-apply enctype="multipart/form-data">
                <input type="hidden" name="action" value="nak_hr_apply_career">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('nak_hr_career_apply')); ?>">
                <input type="hidden" name="career_id" value="<?php echo esc_attr($career->id); ?>">

                <label>
                    <span><?php esc_html_e('Name', 'nooralkhalij-hr-system'); ?></span>
                    <input type="text" name="name" required>
                </label>

                <label>
                    <span><?php esc_html_e('Email', 'nooralkhalij-hr-system'); ?></span>
                    <input type="email" name="email" required>
                </label>

                <label>
                    <span><?php esc_html_e('Phone Number', 'nooralkhalij-hr-system'); ?></span>
                    <input type="text" name="phone" required>
                </label>

                <label>
                    <span><?php esc_html_e('CV (PDF)', 'nooralkhalij-hr-system'); ?></span>
                    <input type="file" name="cv_file" accept="application/pdf,.pdf" required>
                </label>

                <div class="nak-hr-careers-apply-feedback" data-career-feedback></div>
                <button type="submit" class="nak-hr-action-button"><?php esc_html_e('Apply', 'nooralkhalij-hr-system'); ?></button>
            </form>
        </div>
        <?php

        wp_send_json_success(['html' => (string) ob_get_clean()]);
    }

    public static function ajax_apply_career(): void
    {
        check_ajax_referer('nak_hr_career_apply', 'nonce');

        global $wpdb;

        $career_id = absint($_POST['career_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $careers_table = $wpdb->prefix . 'nak_careers';
        $applications_table = $wpdb->prefix . 'nak_career_applications';

        $career_exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$careers_table} WHERE id = %d AND is_active = 1", $career_id));

        if (!$career_exists) {
            wp_send_json_error(['message' => __('This position is no longer available.', 'nooralkhalij-hr-system')], 404);
        }

        if ($name === '' || !is_email($email) || $phone === '') {
            wp_send_json_error(['message' => __('Please complete all fields with valid information.', 'nooralkhalij-hr-system')], 422);
        }

        if (empty($_FILES['cv_file']['name'])) {
            wp_send_json_error(['message' => __('Please attach your CV as a PDF.', 'nooralkhalij-hr-system')], 422);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file = $_FILES['cv_file'];
        $file_type = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);

        if (($file_type['ext'] ?? '') !== 'pdf') {
            wp_send_json_error(['message' => __('Only PDF files are allowed for CV upload.', 'nooralkhalij-hr-system')], 422);
        }

        $attachment_id = media_handle_upload('cv_file', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()], 500);
        }

        $cv_url = wp_get_attachment_url($attachment_id) ?: '';

        $inserted = $wpdb->insert(
            $applications_table,
            [
                'career_id' => $career_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'cv_attachment_id' => $attachment_id,
                'cv_url' => $cv_url,
            ],
            ['%d', '%s', '%s', '%s', '%d', '%s']
        );

        if (!$inserted) {
            wp_send_json_error(['message' => __('Failed to save your application. Please try again.', 'nooralkhalij-hr-system')], 500);
        }

        wp_send_json_success(['message' => __('Your application was submitted successfully.', 'nooralkhalij-hr-system')]);
    }

    public function maybe_hide_admin_bar(): void
    {
        if (!is_user_logged_in() || current_user_can('manage_options')) {
            return;
        }

        $user = wp_get_current_user();
        $employee_roles = array_keys(self::get_employee_roles());

        if (!empty(array_intersect($employee_roles, (array) $user->roles))) {
            show_admin_bar(false);
        }
    }
}
