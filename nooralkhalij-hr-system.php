<?php
/**
 * Plugin Name: Noor Al Khalij HR System
 * Plugin URI: https://github.com/marketing560/nooralkhalij-hr-system
 * Description: HR system plugin with company-restricted signup, quizzes, and careers tools.
 * Version: 0.1.0
 * Author: Noor Al Khalij
 * Text Domain: nooralkhalij-hr-system
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NAK_HR_VERSION', '0.1.0');
define('NAK_HR_PLUGIN_FILE', __FILE__);
define('NAK_HR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NAK_HR_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once NAK_HR_PLUGIN_DIR . 'includes/class-plugin.php';

function nak_hr_boot_plugin(): void
{
    \NoorAlKhalij\HRSystem\Plugin::instance();
}

nak_hr_boot_plugin();
