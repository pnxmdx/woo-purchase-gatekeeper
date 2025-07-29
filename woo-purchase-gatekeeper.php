<?php
/**
 * Plugin Name: Woo Purchase Gatekeeper 2.1
 * Description: Restrict access to pages until purchase. Supports per-page rules, redirect or on-page message, Elementor-safe, and logging.
 * Version: 2.1
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

define('WPG_PLUGIN_DIR', plugin_dir_path(__FILE__));

require_once WPG_PLUGIN_DIR . 'includes/settings-page.php';
require_once WPG_PLUGIN_DIR . 'includes/meta-box.php';
require_once WPG_PLUGIN_DIR . 'includes/logs-page.php';

// Restrict access
add_action('template_redirect', function() {
    if (!function_exists('is_woocommerce')) return;

    // Elementor safe: skip restriction in edit/preview mode
    if (isset($_GET['elementor-preview']) || (function_exists('is_elementor') && is_elementor())) {
        return;
    }

    $restricted_page_id = get_option('wpg_restricted_page');
    $redirect_url       = get_option('wpg_redirect_url', home_url());
    $mode               = get_option('wpg_restriction_mode', 'any');
    $deny_mode          = get_option('wpg_deny_mode', 'redirect');
    $deny_message       = get_option('wpg_deny_message', 'Access Denied.');

    // ✅ Prevent restrictions when no page is configured
    if (!$restricted_page_id && $mode !== 'per-page') {
        return;
    }

    if (!is_page()) return;

    global $post;
    $page_id = $post->ID;

    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink($page_id)));
        exit;
    }

    $user_id   = get_current_user_id();
    $has_access = false;

    // Per-page override
    $per_page_products = get_post_meta($page_id, '_wpg_required_products', true);

    if ($mode === 'per-page' && !empty($per_page_products)) {
        $products   = array_map('intval', explode(',', $per_page_products));
        $has_access = wpg_user_has_purchased_any_product($user_id, $products);

    } elseif ($mode === 'specific' && !empty($restricted_page_id)) {
        $products   = array_map('intval', explode(',', get_option('wpg_required_product_ids', '')));
        $has_access = wpg_user_has_purchased_any_product($user_id, $products);

    } elseif ($mode === 'any') {
        $has_access = wpg_user_has_purchased_anything($user_id);
    }

    if (!$has_access) {
        do_action('wpg_access_denied', $user_id, $page_id);

        if ($deny_mode === 'redirect') {
            // ✅ Avoid redirect loop
            if ($redirect_url && $redirect_url !== get_permalink($page_id)) {
                wp_redirect($redirect_url);
                exit;
            } else {
                wp_die(wp_kses_post($deny_message), 'Access Denied');
            }
        } else {
            wp_die(wp_kses_post($deny_message), 'Access Denied');
        }
    }
});

function wpg_user_has_purchased_anything($user_id) {
    $orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status'      => array('completed','processing'),
        'limit'       => 1,
        'return'      => 'ids'
    ));
    return !empty($orders);
}

function wpg_user_has_purchased_any_p_
