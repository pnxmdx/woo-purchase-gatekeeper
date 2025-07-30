<?php
/**
 * Plugin Name: Woo Purchase Gatekeeper 2.4
 * Description: Restrict access to pages until purchase. Supports per-page rules, subscriptions, Access Denied page, Elementor-safe, and logging.
 * Version: 2.4
 * Author: PnxMdx
 */

if (!defined('ABSPATH')) exit;

function wpg_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Woo Purchase Gatekeeper</strong> requires WooCommerce to be installed and active.</p></div>';
        });
        return;
    }

    define('WPG_PLUGIN_DIR', plugin_dir_path(__FILE__));

    foreach (['settings-page.php', 'meta-box.php', 'logs-page.php'] as $file) {
        $path = WPG_PLUGIN_DIR . 'includes/' . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }

    if (!is_admin()) {
        add_action('template_redirect', 'wpg_restriction_logic');
    }
}
add_action('plugins_loaded', 'wpg_init', 20);

function wpg_restriction_logic() {
    if (!function_exists('wc_get_orders')) return;

    if (isset($_GET['elementor-preview']) || (function_exists('is_elementor') && is_elementor())) {
        return;
    }

    $restricted_page_id = get_option('wpg_restricted_page', false);
    $redirect_url       = get_option('wpg_redirect_url', home_url());
    $mode               = get_option('wpg_restriction_mode', 'any');
    $deny_mode          = get_option('wpg_deny_mode', 'redirect');
    $deny_message       = get_option('wpg_deny_message', 'Access Denied.');
    $denied_page        = get_option('wpg_denied_page', false);

    if (!$restricted_page_id && $mode !== 'per-page') return;
    if (!is_page()) return;

    global $post;
    if (!$post) return;
    $page_id = $post->ID;

    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink($page_id)));
        exit;
    }

    $user_id   = get_current_user_id();
    $has_access = false;

    $per_page_products = get_post_meta($page_id, '_wpg_required_products', true);
    if (!is_array($per_page_products)) $per_page_products = [];

    if ($mode === 'per-page' && !empty($per_page_products)) {
        $has_access = wpg_user_has_purchased_any_product($user_id, $per_page_products);

    } elseif ($mode === 'specific' && !empty($restricted_page_id)) {
        $products   = array_map('intval', explode(',', get_option('wpg_required_product_ids', '')));
        $has_access = wpg_user_has_purchased_any_product($user_id, $products);

    } elseif ($mode === 'any') {
        $has_access = wpg_user_has_purchased_anything($user_id);
    }

    if (!$has_access) {
        do_action('wpg_access_denied', $user_id, $page_id);

        if ($deny_mode === 'redirect') {
            if ($redirect_url && $redirect_url !== get_permalink($page_id)) {
                wp_redirect($redirect_url);
                exit;
            } elseif ($denied_page) {
                wp_redirect(get_permalink($denied_page));
                exit;
            } else {
                wp_die(wp_kses_post($deny_message), 'Access Denied');
