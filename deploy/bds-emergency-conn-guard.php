<?php
/**
 * Plugin Name: BDS Emergency Conn Guard
 * Version: 1.1.0
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!defined('DISABLE_WP_CRON')) {
    define('DISABLE_WP_CRON', true);
}
add_filter('action_scheduler_allow_async_request_runner', '__return_false');

// Do not erase the host cron's queue event during ordinary page requests.
if ((defined('DOING_CRON') && DOING_CRON) || (defined('WP_CLI') && WP_CLI)) {
    return;
}
add_filter('action_scheduler_queue_runner_batch_size', '__return_zero');
add_filter('action_scheduler_queue_runner_concurrent_batches', '__return_zero');
