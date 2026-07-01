<?php
/**
 * Plugin Name: BOB-LIFT Multilingual Sync
 * Description: BOB-LIFT theme option language storage, sync, translation and stale status controller.
 * Version: 0.1.0
 * Author: BOB-LIFT
 * Text Domain: boblift-multilingual-sync
 * Requires Plugins: polylang
 */

if (!defined('ABSPATH')) {
    exit;
}

const BOBLIFT_MLS_SETTINGS_OPTION = 'boblift_mls_settings';
const BOBLIFT_MLS_STATE_OPTION = 'boblift_mls_state';

require_once __DIR__ . '/includes/core.php';
require_once __DIR__ . '/includes/jobs.php';
require_once __DIR__ . '/includes/admin.php';
require_once __DIR__ . '/includes/cli.php';

register_activation_hook(__FILE__, 'boblift_mls_install_jobs_schema');
