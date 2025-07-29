<?php
add_action('add_meta_boxes', function(){
    add_meta_box('wpg_meta','Purchase Gatekeeper','wpg_meta_box','page','side');
});
function wpg_meta_box($post){
    $value = get_post_meta($post->ID,'_wpg_required_products',true);
    echo '<label>Required Product IDs</label>';
    echo '<input type="text" name="wpg_required_products" value="'.esc_attr($value).'" style="width:100%" />';
    echo '<p class="description">Comma separated product IDs for this page</p>';
}
add_action('save_post', function($post_id){
    if (array_key_exists('wpg_required_products',$_POST)) {
        update_post_meta($post_id,'_wpg_required_products',sanitize_text_field($_POST['wpg_required_products']));
    }
});
