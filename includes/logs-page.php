<?php
add_action('admin_menu', function(){
    add_submenu_page('woocommerce','Access Logs','Access Logs','manage_woocommerce','wpg-logs','wpg_logs_page');
});
function wpg_logs_page(){
    $logs = get_option('wpg_access_logs',[]);
    echo '<div class="wrap"><h1>Access Logs</h1><table class="widefat"><tr><th>User</th><th>Email</th><th>Page</th><th>Time</th></tr>';
    foreach(array_reverse($logs) as $log){
        echo '<tr><td>'.esc_html($log['user_id']).'</td><td>'.esc_html($log['user_email']).'</td><td>'.get_the_title($log['page_id']).'</td><td>'.esc_html($log['time']).'</td></tr>';
    }
    echo '</table></div>';
}
