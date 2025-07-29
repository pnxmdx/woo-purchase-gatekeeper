<?php
/**
 * Plugin Name: Woo Purchase Gatekeeper 2.3
 * Description: Restrict access to pages until purchase. Elementor-safe, per-page rules, redirect/message, and logging.
 * Version: 2.3
 * Author: pnxmdx
 */

if (!defined('ABSPATH')) exit;

function wpg_init() {
    // ✅ Only run if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Woo Purchase Gatekeeper</strong> requires WooCommerce to be installed and active.</p></div>';
        });
        return;
    }

    define('WPG_PLUGIN_DIR', plugin_dir_path(__FILE__));

    // ✅ Safely include required files
    foreach (['settings-page.php', 'meta-box.php', 'logs-page.php'] as $file) {
        $path = WPG_PLUGIN_DIR . 'includes/' . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }

    // ✅ Only load restriction logic on frontend
    if (!is_admin()) {
        add_action('template_redirect', 'wpg_restriction_logic');
    }
}
add_action('plugins_loaded', 'wpg_init', 20);

function wpg_restriction_logic() {
    if (!function_exists('wc_get_orders')) return;

    // Elementor safe
    if (isset($_GET['elementor-preview']) || (function_exists('is_elementor') && is_elementor())) {
        return;
    }

    $restricted_page_id = get_option('wpg_restricted_page', false);
    $redirect_url       = get_option('wpg_redirect_url', home_url());
    $mode               = get_option('wpg_restriction_mode', 'any');
    $deny_mode          = get_option('wpg_deny_mode', 'redirect');
    $deny_message       = get_option('wpg_deny_message', 'Access Denied.');

    // ✅ Do nothing if no restriction is configured
    if (!$restricted_page_id && $mode !== 'per-page') return;

    if (!is_page()) return;

    global $post;
    if (!$post) return;
    $page_id = $post->ID;

    // Login check
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink($page_id)));
        exit;
    }

    $user_id = get_current_user_id();
    $has_access = false;

    // Per-page product rule
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

    // Deny access
    if (!$has_access) {
        do_action('wpg_access_denied', $user_id, $page_id);

        if ($deny_mode === 'redirect') {
            // ✅ Prevent redirect loop
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
}

// ✅ Helpers
function wpg_user_has_purchased_anything($user_id) {
    if (!function_exists('wc_get_orders')) return false;
    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => ['completed','processing'],
        'limit'       => 1,
        'return'      => 'ids'
    ]);
    return !empty($orders);
}

function wpg_user_has_purchased_any_product($user_id, $product_ids) {
    if (!function_exists('wc_get_orders')) return false;
    if (empty($product_ids)) return false;

    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'status'      => ['completed','processing'],
        'limit'       => -1,
        'return'      => 'ids'
    ]);

    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item) {
            if (in_array((int)$item->get_product_id(), $product_ids)) {
                return true;
            }
        }
    }
    return false;
}

// ✅ Shortcodes
add_shortcode('show_if_purchased_anything', function($atts,$content=null){
    if (!is_user_logged_in()) return '';
    if (wpg_user_has_purchased_anything(get_current_user_id())) return do_shortcode($content);
    return '';
});

add_shortcode('show_if_purchased_any', function($atts,$content=null){
    if (!is_user_logged_in()) return '';
    $atts     = shortcode_atts(['products'=>''],$atts);
    $products = array_map('intval', explode(',', $atts['products']));
    if (wpg_user_has_purchased_any_product(get_current_user_id(), $products)) return do_shortcode($content);
    return '';
});

// ✅ Logging
add_action('wpg_access_denied', function($user_id,$page_id){
    $logs = get_option('wpg_access_logs',[]);
    $logs[] = [
        'user_id'    => $user_id,
        'user_email' => wp_get_current_user()->user_email,
        'page_id'    => $page_id,
        'time'       => current_time('mysql')
    ];
    update_option('wpg_access_logs',$logs);
},10,2);
