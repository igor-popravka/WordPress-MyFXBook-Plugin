<?php
/**
 * @Plugin Name: MyFXBook Plugin
 * @Description: MyFXBook Plugin, which builds charts/graphs using the data from  API <a href="https://www.myfxbook.com/api">https://www.myfxbook.com/api</a>
 * @Version:     1.6.1
 * @Author:      Web Developer Igor P.
 * @Author URI:  https://www.upwork.com/freelancers/~010854a54a1811f970
 */

require __DIR__ . '/class.wdip-myfxbook.php';

WDIP_MyFXBook_Plugin::instance()->init();
register_deactivation_hook(__FILE__, WDIP_MyFXBook_Plugin::instance()->getCallback('delSettings'));