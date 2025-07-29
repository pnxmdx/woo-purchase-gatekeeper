<?php
/**
 * Plugin Name: Woo Purchase Gatekeeper 2.0
 * Description: Restrict access to pages until purchase. Supports per-page rules, redirect or on-page message, and logging.
 * Version: 2.0
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

    $restricted_page_id = get_option('wpg_restricted_page');
    $redirect_url = get_option('wpg_redirect_url', home_url());
    $mode = get_option('wpg_restriction_mode', 'any');
    $deny_mode = get_option('wpg_deny_mode', 'redirect');
    $deny_message = get_option('wpg_deny_message', 'Access Denied.');

    if (!is_page()) return;

    global $post;
    $page_id = $post->ID;

    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(get_permalink($page_id)));
        exit;
    }

    $user_id = get_current_user_id();
    $has_access = false;

    // Per-page override
    $per_page_products = get_post_meta($page_id, '_wpg_required_products', true);

    if ($mode === 'per-page' && !empty($per_page_products)) {
        $products = array_map('intval', explode(',', $per_page_products));
        $has_access = wpg_user_has_purchased_any_product($user_id, $products);
    } elseif ($mode === 'specific') {
        $products = array_map('intval', explode(',', get_option('wpg_required_product_ids', '')));
        $has_access = wpg_user_has_purchased_any_product($user_id, $products);
    } else {
        $has_access = wpg_user_has_purchased_anything($user_id);
    }

    if (!$has_access) {
        do_action('wpg_access_denied', $user_id, $page_id);
        if ($deny_mode === 'redirect') {
            wp_redirect($redirect_url);
            exit;
        } else {
            wp_die(wp_kses_post($deny_message), 'Access Denied');
        }
    }
});

function wpg_user_has_purchased_anything($user_id) {
    $orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('completed','processing'),
        'limit' => 1,
        'return' => 'ids'
    ));
    return !empty($orders);
}

function wpg_user_has_purchased_any_product($user_id, $product_ids) {
    if (empty($product_ids)) return false;
    $orders = wc_get_orders(array(
        'customer_id' => $user_id,
        'status' => array('completed','processing'),
        'limit' => -1,
        'return' => 'ids'
    ));
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

// Shortcodes
add_shortcode('show_if_purchased_anything', function($atts,$content=null){
    if (!is_user_logged_in()) return '';
    if (wpg_user_has_purchased_anything(get_current_user_id())) return do_shortcode($content);
    return '';
});

add_shortcode('show_if_purchased_any', function($atts,$content=null){
    if (!is_user_logged_in()) return '';
    $atts = shortcode_atts(['products'=>''],$atts);
    $products = array_map('intval',explode(',',$atts['products']));
    if (wpg_user_has_purchased_any_product(get_current_user_id(), $products)) return do_shortcode($content);
    return '';
});

// Logging
add_action('wpg_access_denied', function($user_id,$page_id){
    $logs = get_option('wpg_access_logs',[]);
    $logs[] = [
        'user_id'=>$user_id,
        'user_email'=>wp_get_current_user()->user_email,
        'page_id'=>$page_id,
        'time'=>current_time('mysql')
    ];
    update_option('wpg_access_logs',$logs);
},10,2);
