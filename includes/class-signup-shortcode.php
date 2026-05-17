<?php

namespace NoorAlKhalij\HRSystem;

if (!defined('ABSPATH')) {
    exit;
}

class Signup_Shortcode
{
    private const SHORTCODE = 'nak_hr_signup';
    private const ALLOWED_DOMAIN = 'infinityecn.com';

    public static function register(): void
    {
        add_shortcode(self::SHORTCODE, [self::class, 'render']);
    }

    public static function render(): string
    {
        wp_enqueue_style('nak-hr-frontend');

        $messages = self::handle_submission();
        $defaults = [
            'first_name' => '',
            'last_name' => '',
            'email' => '',
            'username' => '',
            'role' => '',
        ];

        if (!empty($_POST['nak_hr_signup']) && is_array($_POST['nak_hr_signup'])) {
            $defaults = wp_parse_args(wp_unslash($_POST['nak_hr_signup']), $defaults);
        }

        ob_start();
        ?>
        <div class="nak-hr-auth-shell">
            <div class="nak-hr-auth-card">
                <div class="nak-hr-auth-copy">
                    <h2><?php esc_html_e('Create account', 'nooralkhalij-hr-system'); ?></h2>
                    <p><?php esc_html_e('Use your company email to join the employee platform and access upcoming HR tools.', 'nooralkhalij-hr-system'); ?></p>
                </div>

                <?php foreach ($messages as $message) : ?>
                    <div class="nak-hr-alert nak-hr-alert--<?php echo esc_attr($message['type']); ?>">
                        <?php echo esc_html($message['text']); ?>
                    </div>
                <?php endforeach; ?>

                <form method="post" class="nak-hr-auth-form" novalidate>
                    <?php wp_nonce_field('nak_hr_signup_action', 'nak_hr_signup_nonce'); ?>

                    <div class="nak-hr-grid">
                        <label>
                            <span><?php esc_html_e('First name', 'nooralkhalij-hr-system'); ?></span>
                            <input type="text" name="nak_hr_signup[first_name]" value="<?php echo esc_attr($defaults['first_name']); ?>" required>
                        </label>

                        <label>
                            <span><?php esc_html_e('Last name', 'nooralkhalij-hr-system'); ?></span>
                            <input type="text" name="nak_hr_signup[last_name]" value="<?php echo esc_attr($defaults['last_name']); ?>" required>
                        </label>
                    </div>

                    <label>
                        <span><?php esc_html_e('Username', 'nooralkhalij-hr-system'); ?></span>
                        <input type="text" name="nak_hr_signup[username]" value="<?php echo esc_attr($defaults['username']); ?>" required>
                    </label>

                    <label>
                        <span><?php esc_html_e('Company email', 'nooralkhalij-hr-system'); ?></span>
                        <input type="email" name="nak_hr_signup[email]" value="<?php echo esc_attr($defaults['email']); ?>" placeholder="name@infinityecn.com" pattern=".+@infinityecn\.com$" required>
                    </label>

                    <label>
                        <span><?php esc_html_e('Department', 'nooralkhalij-hr-system'); ?></span>
                        <select name="nak_hr_signup[role]" required>
                            <option value=""><?php esc_html_e('Select department', 'nooralkhalij-hr-system'); ?></option>
                            <?php foreach (Plugin::get_employee_roles() as $role_key => $role_label) : ?>
                                <?php if (in_array($role_key, ['nak_employee', 'nak_master'], true)) { continue; } ?>
                                <option value="<?php echo esc_attr($role_key); ?>" <?php selected($defaults['role'], $role_key, false); ?>><?php echo esc_html($role_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="nak-hr-grid">
                        <label>
                            <span><?php esc_html_e('Password', 'nooralkhalij-hr-system'); ?></span>
                            <input type="password" name="nak_hr_signup[password]" required>
                        </label>

                        <label>
                            <span><?php esc_html_e('Confirm password', 'nooralkhalij-hr-system'); ?></span>
                            <input type="password" name="nak_hr_signup[password_confirm]" required>
                        </label>
                    </div>

                    <button type="submit"><?php esc_html_e('Create account', 'nooralkhalij-hr-system'); ?></button>
                </form>
            </div>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    private static function handle_submission(): array
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return [];
        }

        if (empty($_POST['nak_hr_signup_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nak_hr_signup_nonce'])), 'nak_hr_signup_action')) {
            return [];
        }

        $data = isset($_POST['nak_hr_signup']) && is_array($_POST['nak_hr_signup'])
            ? wp_unslash($_POST['nak_hr_signup'])
            : [];

        $first_name = sanitize_text_field($data['first_name'] ?? '');
        $last_name = sanitize_text_field($data['last_name'] ?? '');
        $username = sanitize_user($data['username'] ?? '');
        $email = sanitize_email($data['email'] ?? '');
        $password = (string) ($data['password'] ?? '');
        $password_confirm = (string) ($data['password_confirm'] ?? '');
        $role = sanitize_key($data['role'] ?? '');

        $errors = [];

        if ($first_name === '' || $last_name === '' || $username === '' || $email === '' || $password === '' || $password_confirm === '' || $role === '') {
            $errors[] = __('Please complete all required fields.', 'nooralkhalij-hr-system');
        }

        if ($email && !is_email($email)) {
            $errors[] = __('Please enter a valid email address.', 'nooralkhalij-hr-system');
        }

        if ($email && !self::is_allowed_email_domain($email)) {
            $errors[] = __('Registration is limited to @infinityecn.com email addresses.', 'nooralkhalij-hr-system');
        }

        if ($password !== $password_confirm) {
            $errors[] = __('Passwords do not match.', 'nooralkhalij-hr-system');
        }

        if ($username && username_exists($username)) {
            $errors[] = __('That username is already taken.', 'nooralkhalij-hr-system');
        }

        if ($email && email_exists($email)) {
            $errors[] = __('An account with that email already exists.', 'nooralkhalij-hr-system');
        }

        $allowed_roles = Plugin::get_employee_roles();
        unset($allowed_roles['nak_employee'], $allowed_roles['nak_master']);

        if ($role !== '' && !array_key_exists($role, $allowed_roles)) {
            $errors[] = __('Please choose a valid department.', 'nooralkhalij-hr-system');
        }

        if (!empty($errors)) {
            return array_map(
                static fn(string $error): array => [
                    'type' => 'error',
                    'text' => $error,
                ],
                $errors
            );
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return [[
                'type' => 'error',
                'text' => $user_id->get_error_message(),
            ]];
        }

        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
            'role' => $role,
        ]);

        update_user_meta($user_id, 'nak_email_activated', 0);
        delete_user_meta($user_id, 'nak_email_verification_code');
        delete_user_meta($user_id, 'nak_email_verification_sent_at');
        delete_user_meta($user_id, 'nak_email_verification_expires_at');

        return [[
            'type' => 'success',
            'text' => __('Your account has been created successfully. You can now log in.', 'nooralkhalij-hr-system'),
        ]];
    }

    private static function is_allowed_email_domain(string $email): bool
    {
        $domain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));

        return $domain === self::ALLOWED_DOMAIN;
    }
}
