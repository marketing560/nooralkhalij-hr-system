<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Careers_Tab
{
    public static function render(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'nak_careers';
        $messages = self::handle_mutations();
        $edit_id = absint($_GET['career_edit'] ?? 0);
        $show_form = isset($_GET['career_form']) || $edit_id > 0;
        $editing_item = null;

        foreach ($messages as $message) {
            echo '<div class="nak-hr-alert nak-hr-alert--' . esc_attr($message['type']) . '">' . esc_html($message['text']) . '</div>';
        }

        if ($edit_id > 0) {
            $editing_item = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, title, details, is_active FROM {$table_name} WHERE id = %d",
                    $edit_id
                )
            );
        }

        if ($show_form) {
            self::render_form($editing_item);
        }

        $items = $wpdb->get_results("SELECT id, title, details, is_active FROM {$table_name} ORDER BY id DESC");
        require NAK_HR_PLUGIN_DIR . 'includes/dashboard-tabs/careers.php';
    }

    private static function handle_mutations(): array
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

            return [[
                'type' => $deleted ? 'success' : 'error',
                'text' => $deleted ? __('Position deleted successfully.', 'nooralkhalij-hr-system') : __('Failed to delete the position.', 'nooralkhalij-hr-system'),
            ]];
        }

        if ($action === 'toggle' && $career_id > 0) {
            $current = $wpdb->get_var($wpdb->prepare("SELECT is_active FROM {$table_name} WHERE id = %d", $career_id));
            $updated = $wpdb->update($table_name, ['is_active' => $current ? 0 : 1], ['id' => $career_id], ['%d'], ['%d']);

            return [[
                'type' => $updated !== false ? 'success' : 'error',
                'text' => $updated !== false ? __('Position status updated.', 'nooralkhalij-hr-system') : __('Failed to update the position status.', 'nooralkhalij-hr-system'),
            ]];
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

            return [[
                'type' => $updated !== false ? 'success' : 'error',
                'text' => $updated !== false ? __('Position updated successfully.', 'nooralkhalij-hr-system') : __('Failed to update the position.', 'nooralkhalij-hr-system'),
            ]];
        }

        $inserted = $wpdb->insert($table_name, $data, ['%s', '%s', '%d']);

        return [[
            'type' => $inserted ? 'success' : 'error',
            'text' => $inserted ? __('Position added successfully.', 'nooralkhalij-hr-system') : __('Failed to add the position.', 'nooralkhalij-hr-system'),
        ]];
    }

    private static function render_form(?object $editing_item): void
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
                    <h3><?php echo esc_html($editing_item ? __('Edit Position', 'nooralkhalij-hr-system') : __('Add Position', 'nooralkhalij-hr-system')); ?></h3>
                    <a href="<?php echo esc_url(add_query_arg('nak_section', 'careers', get_permalink() ?: '')); ?>">&times;</a>
                </div>

                <form method="post" class="nak-hr-auth-form">
                    <?php wp_nonce_field('nak_hr_career_action', 'nak_hr_career_nonce'); ?>
                    <input type="hidden" name="career_action" value="save">
                    <input type="hidden" name="career_id" value="<?php echo esc_attr((string) $defaults['career_id']); ?>">

                    <label>
                        <span><?php esc_html_e('Position Title', 'nooralkhalij-hr-system'); ?></span>
                        <input type="text" name="title" value="<?php echo esc_attr((string) $defaults['title']); ?>" required>
                    </label>

                    <label>
                        <span><?php esc_html_e('Details', 'nooralkhalij-hr-system'); ?></span>
                        <textarea name="details" rows="6" required><?php echo esc_textarea((string) $defaults['details']); ?></textarea>
                    </label>

                    <label class="nak-hr-checkbox-row">
                        <input type="checkbox" name="is_active" value="1" <?php checked((int) $defaults['is_active'], 1); ?>>
                        <span><?php esc_html_e('Active position', 'nooralkhalij-hr-system'); ?></span>
                    </label>

                    <div class="nak-hr-modal-actions">
                        <button type="submit"><?php echo esc_html($editing_item ? __('Update Position', 'nooralkhalij-hr-system') : __('Save Position', 'nooralkhalij-hr-system')); ?></button>
                        <a class="nak-hr-action-button nak-hr-action-button--secondary" href="<?php echo esc_url(add_query_arg('nak_section', 'careers', get_permalink() ?: '')); ?>"><?php esc_html_e('Cancel', 'nooralkhalij-hr-system'); ?></a>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}
