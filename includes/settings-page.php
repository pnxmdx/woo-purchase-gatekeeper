<?php
add_action('admin_menu', function(){
    add_submenu_page('woocommerce','Purchase Access Settings','Purchase Access','manage_woocommerce','wpg-settings','wpg_settings_page');
});
add_action('admin_init', function(){
    register_setting('wpg_settings_group','wpg_restricted_page');
    register_setting('wpg_settings_group','wpg_required_product_ids');
    register_setting('wpg_settings_group','wpg_redirect_url');
    register_setting('wpg_settings_group','wpg_restriction_mode');
    register_setting('wpg_settings_group','wpg_deny_mode');
    register_setting('wpg_settings_group','wpg_deny_message');
});
function wpg_settings_page(){ ?>
<div class="wrap">
<h1>Woo Purchase Gatekeeper Settings</h1>
<form method="post" action="options.php">
<?php settings_fields('wpg_settings_group'); ?>
<table class="form-table">
<tr>
<th>Restriction Mode</th>
<td>
<select name="wpg_restriction_mode">
<option value="any" <?php selected(get_option('wpg_restriction_mode'),'any');?>>Any Purchase</option>
<option value="specific" <?php selected(get_option('wpg_restriction_mode'),'specific');?>>Specific Product(s)</option>
<option value="per-page" <?php selected(get_option('wpg_restriction_mode'),'per-page');?>>Per Page Rules</option>
</select>
</td>
</tr>
<tr>
<th>Restricted Page (if global)</th>
<td><?php wp_dropdown_pages(['name'=>'wpg_restricted_page','selected'=>get_option('wpg_restricted_page'),'show_option_none'=>'-- Select --']); ?></td>
</tr>
<tr>
<th>Required Product IDs</th>
<td><input type="text" name="wpg_required_product_ids" value="<?php echo esc_attr(get_option('wpg_required_product_ids')); ?>" /><p class="description">Comma separated IDs</p></td>
</tr>
<tr>
<th>Deny Action</th>
<td>
<select name="wpg_deny_mode">
<option value="redirect" <?php selected(get_option('wpg_deny_mode'),'redirect');?>>Redirect</option>
<option value="message" <?php selected(get_option('wpg_deny_mode'),'message');?>>Show Message</option>
</select>
</td>
</tr>
<tr>
<th>Redirect URL</th>
<td><input type="text" name="wpg_redirect_url" value="<?php echo esc_url(get_option('wpg_redirect_url',home_url())); ?>" size="60" /></td>
</tr>
<tr>
<th>Access Denied Message</th>
<td><textarea name="wpg_deny_message" rows="5" cols="60"><?php echo esc_textarea(get_option('wpg_deny_message','Access Denied.')); ?></textarea></td>
</tr>
</table>
<?php submit_button(); ?>
</form>
</div>
<?php } ?>
