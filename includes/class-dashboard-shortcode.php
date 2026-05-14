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
                            <?php self::render_infinity_wiki(); ?>
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

    private static function render_infinity_wiki(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nak_quiz_questions';
        $user = wp_get_current_user();
        $is_master = in_array('nak_master', (array) $user->roles, true);
        $per_page = 10;
        $current_page = max(1, absint($_GET['wiki_paged'] ?? 1));
        $offset = ($current_page - 1) * $per_page;
        $search = sanitize_text_field(wp_unslash($_GET['wiki_search'] ?? ''));
        $allowed_roles = $is_master
            ? array_keys(Plugin::get_employee_roles())
            : array_values(array_intersect(array_keys(Plugin::get_employee_roles()), (array) $user->roles));

        if (empty($allowed_roles)) {
            echo '<div class="nak-hr-dashboard-empty"><h3>' . esc_html__('No role access found', 'nooralkhalij-hr-system') . '</h3><p>' . esc_html__('There are no Infinity Wiki questions available for your account role.', 'nooralkhalij-hr-system') . '</p></div>';
            return;
        }

        $role_placeholders = implode(', ', array_fill(0, count($allowed_roles), '%s'));
        $where_sql = "role IN ({$role_placeholders})";
        $params = $allowed_roles;

        if ($search !== '') {
            $where_sql .= ' AND question LIKE %s';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $count_sql = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_sql}";
        $items_sql = "SELECT id, question, answer_key, choices, role FROM {$table_name} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
        $total_items = (int) $wpdb->get_var($wpdb->prepare($count_sql, ...$params));
        $items = $wpdb->get_results($wpdb->prepare($items_sql, ...array_merge($params, [$per_page, $offset])));
        $total_pages = max(1, (int) ceil($total_items / $per_page));
        $ajax_url = admin_url('admin-ajax.php');
        $modal_nonce = wp_create_nonce('nak_hr_wiki_modal');
        require NAK_HR_PLUGIN_DIR . 'includes/dashboard-tabs/infinity-wiki.php';
    }

    public static function ajax_get_wiki_form(): void
    {
        check_ajax_referer('nak_hr_wiki_modal', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'nooralkhalij-hr-system')], 403);
        }

        $user = wp_get_current_user();

        if (!in_array('nak_master', (array) $user->roles, true)) {
            wp_send_json_error(['message' => __('You are not allowed to manage questions.', 'nooralkhalij-hr-system')], 403);
        }

        global $wpdb;

        $question_id = absint($_POST['question_id'] ?? 0);
        $editing_item = null;
        $table_name = $wpdb->prefix . 'nak_quiz_questions';

        if ($question_id > 0) {
            $editing_item = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, role, question, choices, answer_key FROM {$table_name} WHERE id = %d",
                    $question_id
                )
            );
        }

        ob_start();
        self::render_wiki_form($editing_item);
        wp_send_json_success(['html' => (string) ob_get_clean()]);
    }

    public static function ajax_save_wiki_question(): void
    {
        check_ajax_referer('nak_hr_wiki_modal', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'nooralkhalij-hr-system')], 403);
        }

        $user = wp_get_current_user();

        if (!in_array('nak_master', (array) $user->roles, true)) {
            wp_send_json_error(['message' => __('You are not allowed to manage questions.', 'nooralkhalij-hr-system')], 403);
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'nak_quiz_questions';
        $question_id = absint($_POST['question_id'] ?? 0);
        $role = sanitize_key($_POST['role'] ?? '');
        $question = sanitize_text_field($_POST['question'] ?? '');
        $answer_key = absint($_POST['answer_key'] ?? 0);
        $choices_input = isset($_POST['choices']) && is_array($_POST['choices']) ? wp_unslash($_POST['choices']) : [];
        $choices = [];

        foreach ($choices_input as $choice) {
            $choice = sanitize_text_field($choice);

            if ($choice !== '') {
                $choices[] = $choice;
            }
        }

        $allowed_roles = Plugin::get_employee_roles();
        $errors = [];

        if (!array_key_exists($role, $allowed_roles)) {
            $errors[] = __('Please choose a valid role.', 'nooralkhalij-hr-system');
        }

        if ($question === '') {
            $errors[] = __('Question text is required.', 'nooralkhalij-hr-system');
        }

        if (count($choices) < 2) {
            $errors[] = __('Please provide at least two choices.', 'nooralkhalij-hr-system');
        }

        if (!isset($choices[$answer_key])) {
            $errors[] = __('Please choose a valid answer.', 'nooralkhalij-hr-system');
        }

        if (!empty($errors)) {
            wp_send_json_error(['message' => implode(' ', $errors)], 422);
        }

        $data = [
            'role' => $role,
            'question' => $question,
            'choices' => wp_json_encode(array_values($choices)),
            'answer_key' => $answer_key,
        ];

        if ($question_id > 0) {
            $updated = $wpdb->update($table_name, $data, ['id' => $question_id], ['%s', '%s', '%s', '%d'], ['%d']);

            if ($updated === false) {
                wp_send_json_error(['message' => __('Failed to update the question.', 'nooralkhalij-hr-system')], 500);
            }

            wp_send_json_success(['message' => __('Question updated successfully.', 'nooralkhalij-hr-system')]);
        }

        $inserted = $wpdb->insert($table_name, $data, ['%s', '%s', '%s', '%d']);

        if (!$inserted) {
            wp_send_json_error(['message' => __('Failed to add the question.', 'nooralkhalij-hr-system')], 500);
        }

        wp_send_json_success(['message' => __('Question added successfully.', 'nooralkhalij-hr-system')]);
    }

    public static function ajax_delete_wiki_question(): void
    {
        check_ajax_referer('nak_hr_wiki_modal', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'nooralkhalij-hr-system')], 403);
        }

        $user = wp_get_current_user();

        if (!in_array('nak_master', (array) $user->roles, true)) {
            wp_send_json_error(['message' => __('You are not allowed to manage questions.', 'nooralkhalij-hr-system')], 403);
        }

        global $wpdb;

        $question_id = absint($_POST['question_id'] ?? 0);
        $table_name = $wpdb->prefix . 'nak_quiz_questions';
        $deleted = $question_id > 0 ? $wpdb->delete($table_name, ['id' => $question_id], ['%d']) : false;

        if (!$deleted) {
            wp_send_json_error(['message' => __('Failed to delete the question.', 'nooralkhalij-hr-system')], 500);
        }

        wp_send_json_success(['message' => __('Question deleted successfully.', 'nooralkhalij-hr-system')]);
    }

    private static function render_wiki_form(?object $editing_item): void
    {
        $defaults = [
            'question_id' => $editing_item->id ?? 0,
            'role' => $editing_item->role ?? '',
            'question' => $editing_item->question ?? '',
            'choices' => ['', '', '', ''],
            'answer_key' => isset($editing_item->answer_key) ? (int) $editing_item->answer_key : 0,
        ];

        if ($editing_item && !empty($editing_item->choices)) {
            $decoded_choices = json_decode((string) $editing_item->choices, true);

            if (is_array($decoded_choices)) {
                $defaults['choices'] = array_pad(array_values($decoded_choices), 4, '');
            }
        }
        ?>
        <div class="nak-hr-careers-modal__content nak-hr-wiki-modal-content">
            <button type="button" class="nak-hr-careers-modal__close" data-careers-close>&times;</button>
            <h3><?php echo esc_html($editing_item ? __('Edit Question', 'nooralkhalij-hr-system') : __('Add Question', 'nooralkhalij-hr-system')); ?></h3>

            <form class="nak-hr-auth-form" data-wiki-form>
                <input type="hidden" name="action" value="nak_hr_save_wiki_question">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('nak_hr_wiki_modal')); ?>">
                <input type="hidden" name="question_id" value="<?php echo esc_attr((string) $defaults['question_id']); ?>">

                <label>
                    <span><?php esc_html_e('Role', 'nooralkhalij-hr-system'); ?></span>
                    <select name="role" required>
                        <option value=""><?php esc_html_e('Select role', 'nooralkhalij-hr-system'); ?></option>
                        <?php foreach (Plugin::get_employee_roles() as $role_key => $role_label) : ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php selected($defaults['role'], $role_key, false); ?>><?php echo esc_html($role_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span><?php esc_html_e('Question', 'nooralkhalij-hr-system'); ?></span>
                    <input type="text" name="question" value="<?php echo esc_attr((string) $defaults['question']); ?>" required>
                </label>

                <div class="nak-hr-grid">
                    <?php foreach ($defaults['choices'] as $index => $choice) : ?>
                        <label>
                            <span><?php echo esc_html(sprintf(__('Choice %d', 'nooralkhalij-hr-system'), $index + 1)); ?></span>
                            <input type="text" name="choices[]" value="<?php echo esc_attr($choice); ?>" <?php echo $index < 2 ? 'required' : ''; ?>>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label>
                    <span><?php esc_html_e('Correct answer', 'nooralkhalij-hr-system'); ?></span>
                    <select name="answer_key" required>
                        <?php foreach ($defaults['choices'] as $index => $choice) : ?>
                            <option value="<?php echo esc_attr((string) $index); ?>" <?php selected((int) $defaults['answer_key'], $index, false); ?>><?php echo esc_html(sprintf(__('Choice %d', 'nooralkhalij-hr-system'), $index + 1)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="nak-hr-careers-apply-feedback" data-wiki-feedback></div>
                <div class="nak-hr-modal-actions">
                    <button type="submit"><?php echo esc_html($editing_item ? __('Update Question', 'nooralkhalij-hr-system') : __('Save Question', 'nooralkhalij-hr-system')); ?></button>
                    <button type="button" class="nak-hr-action-button nak-hr-action-button--secondary" data-careers-close><?php esc_html_e('Cancel', 'nooralkhalij-hr-system'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

}
