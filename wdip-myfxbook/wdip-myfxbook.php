<?php
/**
 * @Plugin Name: MyFXBook Plugin
 * @Description: Сustom MyFXBook Plugin, which builds charts/graphs using data from  API <a href="https://www.myfxbook.com/api">https://www.myfxbook.com/api</a>
 * @Version:     0.1
 * @Author:      Web Developer Igor P.
 * @Author URI:  https://www.upwork.com/freelancers/~010854a54a1811f970
 */

require __DIR__ . '/wdip-functions.php';

add_action('admin_menu', 'wdip_myfxbook_options_page');
add_action('admin_init', 'wdip_myfxbook_settings_init');

add_filter('the_content', 'wdip_myfxbook_view_graph');

add_action('wp_enqueue_scripts', 'wdip_myfxbook_scripts');
add_action( 'admin_enqueue_scripts', 'wdip_myfxbook_admin_scripts' );

register_deactivation_hook( __FILE__, 'wip_myfxbook_deactivation' );