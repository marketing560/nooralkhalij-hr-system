<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Shortcode
{
    private const SHORTCODE = 'nak_hr_dashboard';
    private const LEVEL_SIZE = 5;

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
        $quiz_ajax_url = admin_url('admin-ajax.php');
        $quiz_nonce = wp_create_nonce('nak_hr_quiz_popup');
        $should_show_quiz_popup = (int) get_user_meta($user->ID, 'nak_should_show_quiz_popup', true) === 1;
        $progress = self::get_user_quiz_progress($user->ID);
        $sections = [
            'general-info' => __('General Info', 'nooralkhalij-hr-system'),
            'leaves-vacations' => __('Leaves and Vacations', 'nooralkhalij-hr-system'),
            'infinity-wiki' => __('Infinity Wiki', 'nooralkhalij-hr-system'),
            'suggestions-more' => __('Suggestions and More', 'nooralkhalij-hr-system'),
        ];

        if ($is_master) {
            $sections['careers'] = __('Careers', 'nooralkhalij-hr-system');
            $sections['employees'] = __('Employees', 'nooralkhalij-hr-system');
        }

        $current_section = sanitize_key($_GET['nak_section'] ?? 'general-info');

        if (!array_key_exists($current_section, $sections)) {
            $current_section = 'general-info';
        }

        ob_start();
        ?>
        <div class="nak-hr-auth-shell" <?php if ($should_show_quiz_popup): ?>data-quiz-popup-root data-ajax-url="<?php echo esc_url($quiz_ajax_url); ?>" data-nonce="<?php echo esc_attr($quiz_nonce); ?>"<?php endif; ?>>
            <div class="nak-hr-auth-card nak-hr-dashboard-layout">
                <aside class="nak-hr-dashboard-sidebar">
                    <div class="nak-hr-dashboard-sidebar-head">
                        <h2><?php esc_html_e('Dashboard', 'nooralkhalij-hr-system'); ?></h2>
                        <p><?php echo esc_html($user->display_name ?: $user->user_login); ?></p>
                        <div class="nak-hr-sidebar-progress">
                            <div class="nak-hr-sidebar-progress__meta">
                                <span><?php echo esc_html(sprintf(__('Level %d', 'nooralkhalij-hr-system'), $progress['level'])); ?></span>
                                <span><?php echo esc_html(sprintf(__('%d%%', 'nooralkhalij-hr-system'), $progress['level_progress_percent'])); ?></span>
                            </div>
                            <div class="nak-hr-sidebar-progress__track">
                                <div class="nak-hr-sidebar-progress__fill" style="width: <?php echo esc_attr((string) $progress['level_progress_percent']); ?>%;"></div>
                            </div>
                        </div>
                    </div>

                    <nav class="nak-hr-dashboard-nav">
                        <?php foreach ($sections as $section_key => $section_label): ?>
                            <a class="nak-hr-dashboard-nav-link <?php echo $current_section === $section_key ? 'is-active' : ''; ?>"
                                href="<?php echo esc_url(add_query_arg('nak_section', $section_key, get_permalink() ?: '')); ?>">
                                <?php echo esc_html($section_label); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <a class="nak-hr-action-button nak-hr-action-button--secondary"
                        href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Sign out', 'nooralkhalij-hr-system'); ?></a>
                </aside>

                <div class="nak-hr-dashboard-content">
                    <div class="nak-hr-auth-copy">
                        <h2><?php echo esc_html($sections[$current_section]); ?></h2>
                        <p><?php echo esc_html(sprintf(__('Welcome, %s.', 'nooralkhalij-hr-system'), $user->display_name ?: $user->user_login)); ?>
                        </p>
                    </div>

                    <div class="nak-hr-dashboard-panel">
                        <?php if ($current_section === 'general-info'): ?>
                            <div class="nak-hr-overview-grid">
                                <div class="nak-hr-level-card">
                                    <div class="nak-hr-level-ring" style="--progress: <?php echo esc_attr((string) $progress['level_progress_percent']); ?>;">
                                        <div class="nak-hr-level-ring__inner">
                                            <span class="nak-hr-level-ring__eyebrow"><?php esc_html_e('Level', 'nooralkhalij-hr-system'); ?></span>
                                            <strong><?php echo esc_html((string) $progress['level']); ?></strong>
                                        </div>
                                    </div>
                                    <p><?php echo esc_html(sprintf(__('Progress to next level: %d%%', 'nooralkhalij-hr-system'), $progress['level_progress_percent'])); ?></p>
                                </div>

                                <ul class="nak-hr-dashboard-list">
                                    <li><strong><?php esc_html_e('Name:', 'nooralkhalij-hr-system'); ?></strong>
                                        <?php echo esc_html($user->display_name ?: $user->user_login); ?></li>
                                    <li><strong><?php esc_html_e('Email:', 'nooralkhalij-hr-system'); ?></strong>
                                        <?php echo esc_html($user->user_email); ?></li>
                                    <li><strong><?php esc_html_e('Role:', 'nooralkhalij-hr-system'); ?></strong>
                                        <?php echo esc_html(implode(', ', $role_labels)); ?></li>
                                </ul>
                            </div>
                        <?php elseif ($current_section === 'infinity-wiki'): ?>
                            <?php self::render_infinity_wiki(); ?>
                        <?php elseif ($current_section === 'careers' && $is_master): ?>
                            <?php self::render_careers(); ?>
                        <?php elseif ($current_section === 'employees' && $is_master): ?>
                            <?php self::render_employees(); ?>
                        <?php else: ?>
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

    private static function get_user_quiz_progress(int $user_id): array
    {
        $questions_shown = (int) get_user_meta($user_id, 'nak_questions_shown_count', true);
        $questions_correct = (int) get_user_meta($user_id, 'nak_questions_correct_count', true);
        $level = (int) floor($questions_correct / self::LEVEL_SIZE) + 1;
        $level_base = max(0, ($level - 1) * self::LEVEL_SIZE);
        $correct_in_level = max(0, $questions_correct - $level_base);
        $level_progress_percent = (int) min(100, round(($correct_in_level / self::LEVEL_SIZE) * 100));

        return [
            'questions_shown' => $questions_shown,
            'questions_correct' => $questions_correct,
            'level' => $level,
            'level_progress_percent' => $level_progress_percent,
        ];
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
        ?>
        <div class="nak-hr-wiki-toolbar">
            <form method="get" class="nak-hr-inline-search">
                <input type="hidden" name="nak_section" value="infinity-wiki">
                <input type="search" name="wiki_search" value="<?php echo esc_attr($search); ?>"
                    placeholder="<?php esc_attr_e('Search question text', 'nooralkhalij-hr-system'); ?>">
                <button type="submit"
                    class="nak-hr-action-button"><?php esc_html_e('Search', 'nooralkhalij-hr-system'); ?></button>
                <?php if ($search !== ''): ?>
                    <a class="nak-hr-action-button nak-hr-action-button--secondary"
                        href="<?php echo esc_url(add_query_arg('nak_section', 'infinity-wiki', get_permalink() ?: '')); ?>"><?php esc_html_e('Clear', 'nooralkhalij-hr-system'); ?></a>
                <?php endif; ?>
            </form>

            <?php if ($is_master): ?>
                <button type="button" class="nak-hr-action-button" data-wiki-open data-ajax-url="<?php echo esc_url($ajax_url); ?>"
                    data-nonce="<?php echo esc_attr($modal_nonce); ?>">
                    <?php esc_html_e('Add Question', 'nooralkhalij-hr-system'); ?>
                </button>
            <?php endif; ?>
        </div>

        <?php if (empty($items)): ?>
            <div class="nak-hr-dashboard-empty">
                <h3><?php esc_html_e('No questions found', 'nooralkhalij-hr-system'); ?></h3>
                <p><?php echo esc_html($search !== '' ? __('No questions matched your search.', 'nooralkhalij-hr-system') : __('There are no questions yet.', 'nooralkhalij-hr-system')); ?>
                </p>
            </div>
        <?php else: ?>
            <div class="nak-hr-wiki-list" data-wiki-list>
                <?php foreach ($items as $item): ?>
                    <?php
                    $choices = json_decode((string) $item->choices, true);
                    $answer = '';

                    if (is_array($choices) && isset($choices[(int) $item->answer_key])) {
                        $answer = (string) $choices[(int) $item->answer_key];
                    }
                    ?>
                    <div class="nak-hr-wiki-item" data-question-id="<?php echo esc_attr($item->id); ?>">
                        <div class="nak-hr-wiki-item-head">
                            <div class="nak-hr-wiki-meta">
                                <?php echo esc_html(Plugin::get_employee_roles()[$item->role] ?? $item->role); ?></div>
                            <?php if ($is_master): ?>
                                <div class="nak-hr-wiki-actions">
                                    <button type="button" class="nak-hr-link-button" data-wiki-open
                                        data-question-id="<?php echo esc_attr($item->id); ?>"
                                        data-ajax-url="<?php echo esc_url($ajax_url); ?>"
                                        data-nonce="<?php echo esc_attr($modal_nonce); ?>">
                                        <?php esc_html_e('Edit', 'nooralkhalij-hr-system'); ?>
                                    </button>
                                    <button type="button" class="nak-hr-link-button nak-hr-link-button--danger" data-wiki-delete
                                        data-question-id="<?php echo esc_attr($item->id); ?>"
                                        data-ajax-url="<?php echo esc_url($ajax_url); ?>"
                                        data-nonce="<?php echo esc_attr($modal_nonce); ?>">
                                        <?php esc_html_e('Delete', 'nooralkhalij-hr-system'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo esc_html($item->question); ?></h3>
                        <p><strong><?php esc_html_e('Answer:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html($answer); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php
            echo paginate_links([
                'base' => add_query_arg([
                    'nak_section' => 'infinity-wiki',
                    'wiki_search' => $search,
                    'wiki_paged' => '%#%',
                ], get_permalink() ?: ''),
                'format' => '',
                'prev_text' => __('&laquo; Previous', 'nooralkhalij-hr-system'),
                'next_text' => __('Next &raquo;', 'nooralkhalij-hr-system'),
                'total' => $total_pages,
                'current' => $current_page,
                'type' => 'plain',
            ]);
        ?>
        <?php endif; ?>
    <?php
    }

    public static function ajax_get_quiz_popup(): void
    {
        check_ajax_referer('nak_hr_quiz_popup', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'nooralkhalij-hr-system')], 403);
        }

        global $wpdb;

        $user = wp_get_current_user();
        $is_master = in_array('nak_master', (array) $user->roles, true);
        $allowed_roles = $is_master
            ? array_keys(Plugin::get_employee_roles())
            : array_values(array_intersect(array_keys(Plugin::get_employee_roles()), (array) $user->roles));

        if (empty($allowed_roles)) {
            wp_send_json_error(['message' => __('No quiz questions are available for your role.', 'nooralkhalij-hr-system')], 404);
        }

        $table_name = $wpdb->prefix . 'nak_quiz_questions';
        $questions_per_popup = max(1, min(10, (int) get_option('nak_hr_questions_per_popup', 1)));
        $last_question_id = (int) get_user_meta($user->ID, 'nak_last_question_id', true);
        $placeholders = implode(', ', array_fill(0, count($allowed_roles), '%s'));
        $params = $allowed_roles;
        $sql = "SELECT id, role, question, choices, answer_key FROM {$table_name} WHERE role IN ({$placeholders})";

        if ($last_question_id > 0) {
            $sql .= ' AND id != %d';
            $params[] = $last_question_id;
        }

        $sql .= ' ORDER BY RAND() LIMIT %d';
        $params[] = $questions_per_popup;

        $questions = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        if (empty($questions) && $last_question_id > 0) {
            $fallback_params = $allowed_roles;
            $fallback_sql = "SELECT id, role, question, choices, answer_key FROM {$table_name} WHERE role IN ({$placeholders}) ORDER BY RAND() LIMIT %d";
            $fallback_params[] = $questions_per_popup;
            $questions = $wpdb->get_results($wpdb->prepare($fallback_sql, ...$fallback_params));
        }

        if (empty($questions)) {
            wp_send_json_error(['message' => __('No quiz questions were found.', 'nooralkhalij-hr-system')], 404);
        }

        update_user_meta($user->ID, 'nak_questions_shown_count', (int) get_user_meta($user->ID, 'nak_questions_shown_count', true) + count($questions));
        update_user_meta($user->ID, 'nak_last_question_id', (int) end($questions)->id);
        update_user_meta($user->ID, 'nak_last_question_shown_at', current_time('mysql'));
        update_user_meta($user->ID, 'nak_should_show_quiz_popup', 0);

        ob_start();
        ?>
        <div class="nak-hr-careers-modal__content nak-hr-wiki-modal-content">
            <button type="button" class="nak-hr-careers-modal__close" data-careers-close>&times;</button>
            <h3><?php esc_html_e('Infinity Quiz', 'nooralkhalij-hr-system'); ?></h3>
            <form class="nak-hr-auth-form" data-quiz-popup-form>
                <input type="hidden" name="action" value="nak_hr_submit_quiz_popup">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('nak_hr_quiz_popup')); ?>">
                <?php foreach ($questions as $index => $question): ?>
                    <?php $choices = json_decode((string) $question->choices, true); ?>
                    <div class="nak-hr-wiki-item">
                        <h3><?php echo esc_html(sprintf(__('Question %d', 'nooralkhalij-hr-system'), $index + 1)); ?></h3>
                        <p><?php echo esc_html($question->question); ?></p>
                        <input type="hidden" name="questions[<?php echo esc_attr((string) $index); ?>][id]" value="<?php echo esc_attr((string) $question->id); ?>">
                        <div class="nak-hr-grid">
                            <?php if (is_array($choices)): ?>
                                <?php foreach ($choices as $choice_index => $choice): ?>
                                    <label class="nak-hr-checkbox-row">
                                        <input type="radio" name="questions[<?php echo esc_attr((string) $index); ?>][answer]" value="<?php echo esc_attr((string) $choice_index); ?>" required>
                                        <span><?php echo esc_html((string) $choice); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="nak-hr-careers-apply-feedback" data-quiz-popup-feedback></div>
                <div class="nak-hr-modal-actions">
                    <button type="submit"><?php esc_html_e('Submit Answers', 'nooralkhalij-hr-system'); ?></button>
                    <button type="button" class="nak-hr-action-button nak-hr-action-button--secondary" data-careers-close><?php esc_html_e('Close', 'nooralkhalij-hr-system'); ?></button>
                </div>
            </form>
        </div>
        <?php

        wp_send_json_success(['html' => (string) ob_get_clean()]);
    }

    public static function ajax_submit_quiz_popup(): void
    {
        check_ajax_referer('nak_hr_quiz_popup', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'nooralkhalij-hr-system')], 403);
        }

        global $wpdb;

        $user = wp_get_current_user();
        $submitted_questions = isset($_POST['questions']) && is_array($_POST['questions']) ? wp_unslash($_POST['questions']) : [];

        if (empty($submitted_questions)) {
            wp_send_json_error(['message' => __('No answers were submitted.', 'nooralkhalij-hr-system')], 422);
        }

        $question_ids = [];
        $submitted_answers = [];

        foreach ($submitted_questions as $submitted_question) {
            $question_id = absint($submitted_question['id'] ?? 0);
            $answer = isset($submitted_question['answer']) ? absint($submitted_question['answer']) : null;

            if ($question_id > 0 && $answer !== null) {
                $question_ids[] = $question_id;
                $submitted_answers[$question_id] = $answer;
            }
        }

        if (empty($question_ids)) {
            wp_send_json_error(['message' => __('Please answer all questions.', 'nooralkhalij-hr-system')], 422);
        }

        $placeholders = implode(', ', array_fill(0, count($question_ids), '%d'));
        $table_name = $wpdb->prefix . 'nak_quiz_questions';
        $questions = $wpdb->get_results($wpdb->prepare("SELECT id, answer_key FROM {$table_name} WHERE id IN ({$placeholders})", ...$question_ids));

        $correct_count = 0;
        foreach ($questions as $question) {
            if (isset($submitted_answers[(int) $question->id]) && (int) $submitted_answers[(int) $question->id] === (int) $question->answer_key) {
                $correct_count++;
            }
        }

        update_user_meta($user->ID, 'nak_questions_correct_count', (int) get_user_meta($user->ID, 'nak_questions_correct_count', true) + $correct_count);

        wp_send_json_success([
            'message' => sprintf(
                __('You answered %1$d out of %2$d questions correctly.', 'nooralkhalij-hr-system'),
                $correct_count,
                count($question_ids)
            ),
        ]);
    }

    private static function render_employees(): void
    {
        $per_page = 20;
        $current_page = max(1, absint($_GET['employees_paged'] ?? 1));
        $offset = ($current_page - 1) * $per_page;
        $search = sanitize_text_field(wp_unslash($_GET['employees_search'] ?? ''));

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
        <div class="nak-hr-wiki-toolbar">
            <form method="get" class="nak-hr-inline-search">
                <input type="hidden" name="nak_section" value="employees">
                <input type="search" name="employees_search" value="<?php echo esc_attr($search); ?>"
                    placeholder="<?php esc_attr_e('Search by name or email', 'nooralkhalij-hr-system'); ?>">
                <button type="submit"
                    class="nak-hr-action-button"><?php esc_html_e('Search', 'nooralkhalij-hr-system'); ?></button>
                <?php if ($search !== ''): ?>
                    <a class="nak-hr-action-button nak-hr-action-button--secondary"
                        href="<?php echo esc_url(add_query_arg('nak_section', 'employees', get_permalink() ?: '')); ?>"><?php esc_html_e('Clear', 'nooralkhalij-hr-system'); ?></a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (empty($employees)): ?>
            <div class="nak-hr-dashboard-empty">
                <h3><?php esc_html_e('No employees found', 'nooralkhalij-hr-system'); ?></h3>
                <p><?php echo esc_html($search !== '' ? __('No employees matched your search.', 'nooralkhalij-hr-system') : __('No employees found yet.', 'nooralkhalij-hr-system')); ?></p>
            </div>
        <?php else: ?>
            <div class="nak-hr-wiki-list">
                <?php foreach ($employees as $employee): ?>
                    <?php
                    $display_name = $employee->display_name ?: trim($employee->first_name . ' ' . $employee->last_name);
                    $employee_progress = self::get_user_quiz_progress((int) $employee->ID);
                    ?>
                    <div class="nak-hr-wiki-item">
                        <div class="nak-hr-wiki-item-head">
                            <div class="nak-hr-wiki-meta"><?php echo esc_html(implode(', ', array_map('translate_user_role', $employee->roles))); ?></div>
                            <div class="nak-hr-wiki-actions">
                                <button
                                    type="button"
                                    class="nak-hr-link-button"
                                    data-employee-form-open
                                    data-employee-id="<?php echo esc_attr($employee->ID); ?>"
                                    data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                                    data-nonce="<?php echo esc_attr(wp_create_nonce('nak_hr_employee_modal')); ?>"
                                ><?php esc_html_e('Edit', 'nooralkhalij-hr-system'); ?></button>
                            </div>
                        </div>
                        <h3><?php echo esc_html($display_name ?: $employee->user_login); ?></h3>
                        <p><strong><?php esc_html_e('Username:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html($employee->user_login); ?></p>
                        <p><strong><?php esc_html_e('Email:', 'nooralkhalij-hr-system'); ?></strong> <a href="mailto:<?php echo esc_attr($employee->user_email); ?>"><?php echo esc_html($employee->user_email); ?></a></p>
                        <p><strong><?php esc_html_e('Registered:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $employee->user_registered)); ?></p>
                        <p><strong><?php esc_html_e('Level:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html((string) $employee_progress['level']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php
            echo paginate_links([
                'base' => add_query_arg([
                    'nak_section' => 'employees',
                    'employees_search' => $search,
                    'employees_paged' => '%#%',
                ], get_permalink() ?: ''),
                'format' => '',
                'prev_text' => __('&laquo; Previous', 'nooralkhalij-hr-system'),
                'next_text' => __('Next &raquo;', 'nooralkhalij-hr-system'),
                'total' => $total_pages,
                'current' => $current_page,
                'type' => 'plain',
            ]);
            ?>
        <?php endif; ?>
    <?php
    }

    private static function render_careers(): void
    {
        global $wpdb;

        $positions_table = $wpdb->prefix . 'nak_careers';
        $applications_table = $wpdb->prefix . 'nak_career_applications';
        $messages = self::handle_careers_mutations();
        $edit_id = absint($_GET['career_edit'] ?? 0);
        $show_form = isset($_GET['career_form']) || $edit_id > 0;
        $editing_item = null;
        $current_tab = sanitize_key($_GET['career_tab'] ?? 'positions');
        $allowed_tabs = [
            'positions' => __('Positions', 'nooralkhalij-hr-system'),
            'applications' => __('Submitted Applications', 'nooralkhalij-hr-system'),
        ];

        if (!array_key_exists($current_tab, $allowed_tabs)) {
            $current_tab = 'positions';
        }

        foreach ($messages as $message) {
            echo '<div class="nak-hr-alert nak-hr-alert--' . esc_attr($message['type']) . '">' . esc_html($message['text']) . '</div>';
        }

        if ($edit_id > 0) {
            $editing_item = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, title, details, is_active FROM {$positions_table} WHERE id = %d",
                    $edit_id
                )
            );
        }

        if ($show_form && $current_tab === 'positions') {
            self::render_career_form($editing_item);
        }

        ?>
        <div class="nak-hr-wiki-toolbar">
            <div class="nak-hr-dashboard-subnav">
                <?php foreach ($allowed_tabs as $tab_key => $tab_label): ?>
                    <a class="nak-hr-dashboard-nav-link <?php echo $current_tab === $tab_key ? 'is-active' : ''; ?>"
                        href="<?php echo esc_url(add_query_arg([
                            'nak_section' => 'careers',
                            'career_tab' => $tab_key,
                        ], get_permalink() ?: '')); ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($current_tab === 'positions'): ?>
                <button
                    type="button"
                    class="nak-hr-action-button"
                    data-career-form-open
                    data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                    data-nonce="<?php echo esc_attr(wp_create_nonce('nak_hr_career_modal')); ?>"
                ><?php esc_html_e('Add Position', 'nooralkhalij-hr-system'); ?></button>
            <?php endif; ?>
        </div>

        <?php if ($current_tab === 'applications'): ?>
            <?php
            $per_page = 10;
            $applications_page = max(1, absint($_GET['applications_paged'] ?? 1));
            $applications_offset = ($applications_page - 1) * $per_page;
            $applications_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$applications_table}");
            $applications = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT applications.id, applications.name, applications.email, applications.phone, applications.cv_url, applications.created_at, careers.title AS career_title
                    FROM {$applications_table} applications
                    LEFT JOIN {$positions_table} careers ON careers.id = applications.career_id
                    ORDER BY applications.id DESC
                    LIMIT %d OFFSET %d",
                    $per_page,
                    $applications_offset
                )
            );
            $applications_total_pages = max(1, (int) ceil($applications_total / $per_page));
            ?>

            <?php if (empty($applications)): ?>
                <div class="nak-hr-dashboard-empty">
                    <h3><?php esc_html_e('No applications found', 'nooralkhalij-hr-system'); ?></h3>
                    <p><?php esc_html_e('No one has submitted a career application yet.', 'nooralkhalij-hr-system'); ?></p>
                </div>
            <?php else: ?>
                <div class="nak-hr-wiki-list">
                    <?php foreach ($applications as $application): ?>
                        <div class="nak-hr-wiki-item">
                            <div class="nak-hr-wiki-item-head">
                                <div class="nak-hr-wiki-meta">
                                    <?php echo esc_html($application->career_title ?: __('Unknown Position', 'nooralkhalij-hr-system')); ?>
                                </div>
                            </div>
                            <h3><?php echo esc_html($application->name); ?></h3>
                            <p><strong><?php esc_html_e('Email:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html($application->email); ?></p>
                            <p><strong><?php esc_html_e('Phone:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html($application->phone); ?></p>
                            <p><strong><?php esc_html_e('Submitted:', 'nooralkhalij-hr-system'); ?></strong> <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $application->created_at)); ?></p>
                            <?php if (!empty($application->cv_url)): ?>
                                <p><a href="<?php echo esc_url($application->cv_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View CV', 'nooralkhalij-hr-system'); ?></a></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php
                echo paginate_links([
                    'base' => add_query_arg([
                        'nak_section' => 'careers',
                        'career_tab' => 'applications',
                        'applications_paged' => '%#%',
                    ], get_permalink() ?: ''),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous', 'nooralkhalij-hr-system'),
                    'next_text' => __('Next &raquo;', 'nooralkhalij-hr-system'),
                    'total' => $applications_total_pages,
                    'current' => $applications_page,
                    'type' => 'plain',
                ]);
                ?>
            <?php endif; ?>
        <?php else: ?>
            <?php
            $per_page = 10;
            $positions_page = max(1, absint($_GET['positions_paged'] ?? 1));
            $positions_offset = ($positions_page - 1) * $per_page;
            $positions_total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$positions_table}");
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, title, details, is_active FROM {$positions_table} ORDER BY id DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $positions_offset
                )
            );
            $positions_total_pages = max(1, (int) ceil($positions_total / $per_page));
            ?>

            <?php if (empty($items)): ?>
                <div class="nak-hr-dashboard-empty">
                    <h3><?php esc_html_e('No positions found', 'nooralkhalij-hr-system'); ?></h3>
                    <p><?php esc_html_e('There are no career positions yet.', 'nooralkhalij-hr-system'); ?></p>
                </div>
            <?php else: ?>
                <div class="nak-hr-wiki-list">
                    <?php foreach ($items as $item): ?>
                        <div class="nak-hr-wiki-item">
                            <div class="nak-hr-wiki-item-head">
                                <div class="nak-hr-wiki-meta <?php echo $item->is_active ? '' : 'nak-hr-wiki-meta--inactive'; ?>">
                                    <?php echo esc_html($item->is_active ? __('Active', 'nooralkhalij-hr-system') : __('Inactive', 'nooralkhalij-hr-system')); ?>
                                </div>
                                <div class="nak-hr-wiki-actions">
                                    <button
                                        type="button"
                                        class="nak-hr-link-button"
                                        data-career-form-open
                                        data-career-id="<?php echo esc_attr($item->id); ?>"
                                        data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
                                        data-nonce="<?php echo esc_attr(wp_create_nonce('nak_hr_career_modal')); ?>"
                                    ><?php esc_html_e('Edit', 'nooralkhalij-hr-system'); ?></button>
                                    <form method="post">
                                        <?php wp_nonce_field('nak_hr_career_action', 'nak_hr_career_nonce'); ?>
                                        <input type="hidden" name="career_action" value="toggle">
                                        <input type="hidden" name="career_id" value="<?php echo esc_attr($item->id); ?>">
                                        <button type="submit"
                                            class="nak-hr-link-button"><?php echo esc_html($item->is_active ? __('Deactivate', 'nooralkhalij-hr-system') : __('Activate', 'nooralkhalij-hr-system')); ?></button>
                                    </form>
                                    <form method="post"
                                        onsubmit="return confirm('<?php echo esc_js(__('Delete this position?', 'nooralkhalij-hr-system')); ?>');">
                                        <?php wp_nonce_field('nak_hr_career_action', 'nak_hr_career_nonce'); ?>
                                        <input type="hidden" name="career_action" value="delete">
                                        <input type="hidden" name="career_id" value="<?php echo esc_attr($item->id); ?>">
                                        <button type="submit"
                                            class="nak-hr-link-button nak-hr-link-button--danger"><?php esc_html_e('Delete', 'nooralkhalij-hr-system'); ?></button>
                                    </form>
                                </div>
                            </div>
                            <h3><?php echo esc_html($item->title); ?></h3>
                            <p><?php echo esc_html(wp_trim_words((string) $item->details, 30)); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php
                echo paginate_links([
                    'base' => add_query_arg([
                        'nak_section' => 'careers',
                        'career_tab' => 'positions',
                        'positions_paged' => '%#%',
                    ], get_permalink() ?: ''),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous', 'nooralkhalij-hr-system'),
                    'next_text' => __('Next &raquo;', 'nooralkhalij-hr-system'),
                    'total' => $positions_total_pages,
                    'current' => $positions_page,
                    'type' => 'plain',
                ]);
                ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php
    }

    public static function ajax_get_employee_form(): void
    {
        check_ajax_referer('nak_hr_employee_modal', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'nooralkhalij-hr-system')], 403);
        }

        $user = wp_get_current_user();

        if (!in_array('nak_master', (array) $user->roles, true)) {
            wp_send_json_error(['message' => __('You are not allowed to manage employees.', 'nooralkhalij-hr-system')], 403);
        }

        $employee_id = absint($_POST['employee_id'] ?? 0);
        $employee = $employee_id > 0 ? get_user_by('id', $employee_id) : false;

        if (!$employee) {
            wp_send_json_error(['message' => __('Employee not found.', 'nooralkhalij-hr-system')], 404);
        }

        ob_start();
        self::render_employee_form($employee);
        wp_send_json_success(['html' => (string) ob_get_clean()]);
    }

    public static function ajax_save_employee(): void
    {
        check_ajax_referer('nak_hr_employee_modal', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'nooralkhalij-hr-system')], 403);
        }

        $user = wp_get_current_user();

        if (!in_array('nak_master', (array) $user->roles, true)) {
            wp_send_json_error(['message' => __('You are not allowed to manage employees.', 'nooralkhalij-hr-system')], 403);
        }

        $employee_id = absint($_POST['employee_id'] ?? 0);
        $employee = $employee_id > 0 ? get_user_by('id', $employee_id) : false;

        if (!$employee) {
            wp_send_json_error(['message' => __('Employee not found.', 'nooralkhalij-hr-system')], 404);
        }

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $username = sanitize_user($_POST['username'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $role = sanitize_key($_POST['role'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $display_name = trim($first_name . ' ' . $last_name);
        $allowed_roles = Plugin::get_employee_roles();
        unset($allowed_roles['nak_employee']);
        $errors = [];

        if ($first_name === '' || $last_name === '' || $username === '' || $email === '' || $role === '') {
            $errors[] = __('Please complete all required fields.', 'nooralkhalij-hr-system');
        }

        if ($email !== '' && !is_email($email)) {
            $errors[] = __('Please enter a valid email address.', 'nooralkhalij-hr-system');
        }

        if ($username !== '' && username_exists($username) && $username !== $employee->user_login) {
            $errors[] = __('That username is already taken.', 'nooralkhalij-hr-system');
        }

        $existing_email = $email !== '' ? email_exists($email) : false;
        if ($existing_email && (int) $existing_email !== (int) $employee->ID) {
            $errors[] = __('An account with that email already exists.', 'nooralkhalij-hr-system');
        }

        if ($role !== '' && !array_key_exists($role, $allowed_roles)) {
            $errors[] = __('Please choose a valid department.', 'nooralkhalij-hr-system');
        }

        if (!empty($errors)) {
            wp_send_json_error(['message' => implode(' ', $errors)], 422);
        }

        $update_data = [
            'ID' => $employee->ID,
            'user_login' => $username,
            'user_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $display_name !== '' ? $display_name : $username,
            'role' => $role,
        ];

        if ($password !== '') {
            $update_data['user_pass'] = $password;
        }

        $updated = wp_update_user($update_data);

        if (is_wp_error($updated)) {
            wp_send_json_error(['message' => $updated->get_error_message()], 500);
        }

        wp_send_json_success(['message' => __('Employee updated successfully.', 'nooralkhalij-hr-system')]);
    }

    public static function ajax_get_career_form(): void
    {
        check_ajax_referer('nak_hr_career_modal', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'nooralkhalij-hr-system')], 403);
        }

        $user = wp_get_current_user();

        if (!in_array('nak_master', (array) $user->roles, true)) {
            wp_send_json_error(['message' => __('You are not allowed to manage positions.', 'nooralkhalij-hr-system')], 403);
        }

        global $wpdb;

        $career_id = absint($_POST['career_id'] ?? 0);
        $editing_item = null;
        $table_name = $wpdb->prefix . 'nak_careers';

        if ($career_id > 0) {
            $editing_item = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, title, details, is_active FROM {$table_name} WHERE id = %d",
                    $career_id
                )
            );
        }

        ob_start();
        self::render_career_form($editing_item);
        wp_send_json_success(['html' => (string) ob_get_clean()]);
    }

    public static function ajax_save_career(): void
    {
        check_ajax_referer('nak_hr_career_modal', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'nooralkhalij-hr-system')], 403);
        }

        $user = wp_get_current_user();

        if (!in_array('nak_master', (array) $user->roles, true)) {
            wp_send_json_error(['message' => __('You are not allowed to manage positions.', 'nooralkhalij-hr-system')], 403);
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'nak_careers';
        $career_id = absint($_POST['career_id'] ?? 0);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $details = sanitize_textarea_field($_POST['details'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $errors = [];

        if ($title === '') {
            $errors[] = __('Position title is required.', 'nooralkhalij-hr-system');
        }

        if ($details === '') {
            $errors[] = __('Position details are required.', 'nooralkhalij-hr-system');
        }

        if (!empty($errors)) {
            wp_send_json_error(['message' => implode(' ', $errors)], 422);
        }

        $data = [
            'title' => $title,
            'details' => $details,
            'is_active' => $is_active,
        ];

        if ($career_id > 0) {
            $updated = $wpdb->update($table_name, $data, ['id' => $career_id], ['%s', '%s', '%d'], ['%d']);

            if ($updated === false) {
                wp_send_json_error(['message' => __('Failed to update the position.', 'nooralkhalij-hr-system')], 500);
            }

            wp_send_json_success(['message' => __('Position updated successfully.', 'nooralkhalij-hr-system')]);
        }

        $inserted = $wpdb->insert($table_name, $data, ['%s', '%s', '%d']);

        if (!$inserted) {
            wp_send_json_error(['message' => __('Failed to add the position.', 'nooralkhalij-hr-system')], 500);
        }

        wp_send_json_success(['message' => __('Position added successfully.', 'nooralkhalij-hr-system')]);
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

            wp_send_json_error([
                'message' => __('Failed to add the question.', 'nooralkhalij-hr-system'),
                'database_error' => $wpdb->last_error,
                'last_query' => $wpdb->last_query,
            ], 500);
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

    private static function handle_careers_mutations(): array
    {
        global $wpdb;

        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return [];
        }

        if (empty($_POST['nak_hr_career_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nak_hr_career_nonce'])), 'nak_hr_career_action')) {
            return [];
        }

        $table_name = $wpdb->prefix . 'nak_careers';
        $action = sanitize_key($_POST['career_action'] ?? '');
        $career_id = absint($_POST['career_id'] ?? 0);

        if ($action === 'delete' && $career_id > 0) {
            $deleted = $wpdb->delete($table_name, ['id' => $career_id], ['%d']);

            return [
                [
                    'type' => $deleted ? 'success' : 'error',
                    'text' => $deleted ? __('Position deleted successfully.', 'nooralkhalij-hr-system') : __('Failed to delete the position.', 'nooralkhalij-hr-system'),
                ]
            ];
        }

        if ($action === 'toggle' && $career_id > 0) {
            $current = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM {$table_name} WHERE id = %d", $career_id));
            $updated = $wpdb->update($table_name, ['is_active' => $current ? 0 : 1], ['id' => $career_id], ['%d'], ['%d']);

            return [
                [
                    'type' => $updated !== false ? 'success' : 'error',
                    'text' => $updated !== false ? __('Position status updated.', 'nooralkhalij-hr-system') : __('Failed to update the position status.', 'nooralkhalij-hr-system'),
                ]
            ];
        }

        if ($action !== 'save') {
            return [];
        }

        $title = sanitize_text_field($_POST['title'] ?? '');
        $details = sanitize_textarea_field($_POST['details'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $errors = [];

        if ($title === '') {
            $errors[] = __('Position title is required.', 'nooralkhalij-hr-system');
        }

        if ($details === '') {
            $errors[] = __('Position details are required.', 'nooralkhalij-hr-system');
        }

        if (!empty($errors)) {
            return array_map(
                static fn(string $error): array => ['type' => 'error', 'text' => $error],
                $errors
            );
        }

        $data = [
            'title' => $title,
            'details' => $details,
            'is_active' => $is_active,
        ];

        if ($career_id > 0) {
            $updated = $wpdb->update($table_name, $data, ['id' => $career_id], ['%s', '%s', '%d'], ['%d']);

            return [
                [
                    'type' => $updated !== false ? 'success' : 'error',
                    'text' => $updated !== false ? __('Position updated successfully.', 'nooralkhalij-hr-system') : __('Failed to update the position.', 'nooralkhalij-hr-system'),
                ]
            ];
        }

        $inserted = $wpdb->insert($table_name, $data, ['%s', '%s', '%d']);

        return [
            [
                'type' => $inserted ? 'success' : 'error',
                'text' => $inserted ? __('Position added successfully.', 'nooralkhalij-hr-system') : __('Failed to add the position.', 'nooralkhalij-hr-system'),
            ]
        ];
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
            <h3><?php echo esc_html($editing_item ? __('Edit Question', 'nooralkhalij-hr-system') : __('Add Question', 'nooralkhalij-hr-system')); ?>
            </h3>

            <form class="nak-hr-auth-form" data-wiki-form>
                <input type="hidden" name="action" value="nak_hr_save_wiki_question">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('nak_hr_wiki_modal')); ?>">
                <input type="hidden" name="question_id" value="<?php echo esc_attr((string) $defaults['question_id']); ?>">

                <label>
                    <span><?php esc_html_e('Role', 'nooralkhalij-hr-system'); ?></span>
                    <select name="role" required>
                        <option value=""><?php esc_html_e('Select role', 'nooralkhalij-hr-system'); ?></option>
                        <?php foreach (Plugin::get_employee_roles() as $role_key => $role_label): ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php selected($defaults['role'], $role_key, false); ?>><?php echo esc_html($role_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span><?php esc_html_e('Question', 'nooralkhalij-hr-system'); ?></span>
                    <input type="text" name="question" value="<?php echo esc_attr((string) $defaults['question']); ?>" required>
                </label>

                <div class="nak-hr-grid">
                    <?php foreach ($defaults['choices'] as $index => $choice): ?>
                        <label>
                            <span><?php echo esc_html(sprintf(__('Choice %d', 'nooralkhalij-hr-system'), $index + 1)); ?></span>
                            <input type="text" name="choices[]" value="<?php echo esc_attr($choice); ?>" <?php echo $index < 2 ? 'required' : ''; ?>>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label>
                    <span><?php esc_html_e('Correct answer', 'nooralkhalij-hr-system'); ?></span>
                    <select name="answer_key" required>
                        <?php foreach ($defaults['choices'] as $index => $choice): ?>
                            <option value="<?php echo esc_attr((string) $index); ?>" <?php selected((int) $defaults['answer_key'], $index, false); ?>>
                                <?php echo esc_html(sprintf(__('Choice %d', 'nooralkhalij-hr-system'), $index + 1)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="nak-hr-careers-apply-feedback" data-wiki-feedback></div>
                <div class="nak-hr-modal-actions">
                    <button
                        type="submit"><?php echo esc_html($editing_item ? __('Update Question', 'nooralkhalij-hr-system') : __('Save Question', 'nooralkhalij-hr-system')); ?></button>
                    <button type="button" class="nak-hr-action-button nak-hr-action-button--secondary"
                        data-careers-close><?php esc_html_e('Cancel', 'nooralkhalij-hr-system'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    private static function render_employee_form(\WP_User $employee): void
    {
        $current_role = '';

        foreach ((array) $employee->roles as $role) {
            if (str_starts_with($role, 'nak_') && $role !== 'nak_employee') {
                $current_role = $role;
                break;
            }
        }
        ?>
        <div class="nak-hr-careers-modal__content nak-hr-wiki-modal-content">
            <button type="button" class="nak-hr-careers-modal__close" data-careers-close>&times;</button>
            <h3><?php esc_html_e('Edit Employee', 'nooralkhalij-hr-system'); ?></h3>

            <form class="nak-hr-auth-form" data-employee-manage-form>
                <input type="hidden" name="action" value="nak_hr_save_employee">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('nak_hr_employee_modal')); ?>">
                <input type="hidden" name="employee_id" value="<?php echo esc_attr((string) $employee->ID); ?>">

                <div class="nak-hr-grid">
                    <label>
                        <span><?php esc_html_e('First name', 'nooralkhalij-hr-system'); ?></span>
                        <input type="text" name="first_name" value="<?php echo esc_attr((string) $employee->first_name); ?>" required>
                    </label>

                    <label>
                        <span><?php esc_html_e('Last name', 'nooralkhalij-hr-system'); ?></span>
                        <input type="text" name="last_name" value="<?php echo esc_attr((string) $employee->last_name); ?>" required>
                    </label>
                </div>

                <label>
                    <span><?php esc_html_e('Username', 'nooralkhalij-hr-system'); ?></span>
                    <input type="text" name="username" value="<?php echo esc_attr($employee->user_login); ?>" required>
                </label>

                <label>
                    <span><?php esc_html_e('Email', 'nooralkhalij-hr-system'); ?></span>
                    <input type="email" name="email" value="<?php echo esc_attr($employee->user_email); ?>" required>
                </label>

                <label>
                    <span><?php esc_html_e('Department', 'nooralkhalij-hr-system'); ?></span>
                    <select name="role" required>
                        <option value=""><?php esc_html_e('Select department', 'nooralkhalij-hr-system'); ?></option>
                        <?php foreach (Plugin::get_employee_roles() as $role_key => $role_label) : ?>
                            <?php if ($role_key === 'nak_employee') { continue; } ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php selected($current_role, $role_key, false); ?>><?php echo esc_html($role_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <span><?php esc_html_e('New password', 'nooralkhalij-hr-system'); ?></span>
                    <input type="password" name="password" placeholder="<?php esc_attr_e('Leave blank to keep current password', 'nooralkhalij-hr-system'); ?>">
                </label>

                <div class="nak-hr-careers-apply-feedback" data-employee-manage-feedback></div>
                <div class="nak-hr-modal-actions">
                    <button type="submit"><?php esc_html_e('Save Employee', 'nooralkhalij-hr-system'); ?></button>
                    <button type="button" class="nak-hr-action-button nak-hr-action-button--secondary" data-careers-close><?php esc_html_e('Cancel', 'nooralkhalij-hr-system'); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    private static function render_career_form(?object $editing_item): void
    {
        $defaults = [
            'career_id' => $editing_item->id ?? 0,
            'title' => $editing_item->title ?? '',
            'details' => $editing_item->details ?? '',
            'is_active' => isset($editing_item->is_active) ? (int) $editing_item->is_active : 1,
        ];
        ?>
        <div class="nak-hr-modal-backdrop">
            <div class="nak-hr-modal-card">
                <div class="nak-hr-modal-head">
                    <h3><?php echo esc_html($editing_item ? __('Edit Position', 'nooralkhalij-hr-system') : __('Add Position', 'nooralkhalij-hr-system')); ?>
                    </h3>
                    <a href="<?php echo esc_url(add_query_arg('nak_section', 'careers', get_permalink() ?: '')); ?>">&times;</a>
                </div>

                <form method="post" class="nak-hr-auth-form" data-career-manage-form>
                    <input type="hidden" name="action" value="nak_hr_save_career">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('nak_hr_career_modal')); ?>">
                    <input type="hidden" name="career_id" value="<?php echo esc_attr((string) $defaults['career_id']); ?>">

                    <label>
                        <span><?php esc_html_e('Position Title', 'nooralkhalij-hr-system'); ?></span>
                        <input type="text" name="title" value="<?php echo esc_attr((string) $defaults['title']); ?>" required>
                    </label>

                    <label>
                        <span><?php esc_html_e('Details', 'nooralkhalij-hr-system'); ?></span>
                        <textarea name="details" rows="6"
                            required><?php echo esc_textarea((string) $defaults['details']); ?></textarea>
                    </label>

                    <label class="nak-hr-checkbox-row">
                        <input type="checkbox" name="is_active" value="1" <?php checked((int) $defaults['is_active'], 1); ?>>
                        <span><?php esc_html_e('Active position', 'nooralkhalij-hr-system'); ?></span>
                    </label>

                    <div class="nak-hr-careers-apply-feedback" data-career-manage-feedback></div>
                    <div class="nak-hr-modal-actions">
                        <button
                            type="submit"><?php echo esc_html($editing_item ? __('Update Position', 'nooralkhalij-hr-system') : __('Save Position', 'nooralkhalij-hr-system')); ?></button>
                        <button type="button" class="nak-hr-action-button nak-hr-action-button--secondary"
                            data-careers-close><?php esc_html_e('Cancel', 'nooralkhalij-hr-system'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}
