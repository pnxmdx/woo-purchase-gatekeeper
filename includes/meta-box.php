<?php
add_action('add_meta_boxes', function() {
    add_meta_box(
        'wpg_meta',
        'Woo Purchase Gatekeeper',
        'wpg_meta_box',
        'page',
        'side'
    );
});

function wpg_meta_box($post) {
    $selected_products = get_post_meta($post->ID, '_wpg_required_products', true);
    if (!is_array($selected_products)) $selected_products = [];

    $products = wc_get_products(['limit' => -1]);

    echo '<label>Select Required Products:</label><br>';
    echo '<select name="wpg_required_products[]" multiple style="width:100%;height:120px;">';
    foreach ($products as $product) {
        $selected = in_array($product->get_id(), $selected_products) ? 'selected' : '';
        echo '<option value="' . esc_attr($product->get_id()) . '" ' . $selected . '>' 
             . esc_html($product->get_name()) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">Users must have purchased one of the selected products to access this page.</p>';
}

add_action('save_post', function($post_id) {
    if (isset($_POST['wpg_required_products'])) {
        $products = array_map('intval', $_POST['wpg_required_products']);
        update_post_meta($post_id, '_wpg_required_products', $products);
    } else {
        delete_post_meta($post_id, '_wpg_required_products');
    }
});
