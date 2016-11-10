<?php
/**
 * @Plugin Name: MyFXBook Plugin
 * @Description: Сustom MyFXBook Plugin, which builds charts/graphs using data from  API <a href="https://www.myfxbook.com/api">https://www.myfxbook.com/api</a>
 * @Version:     1.0
 * @Author:      Web Developer Igor P.
 * @Author URI:  https://www.upwork.com/freelancers/~010854a54a1811f970
 */

require __DIR__ . '/class.wdip-myfxbook.php';

use WDIP\Plugin\MyFXBook;

MyFXBook::instance()->init();
register_deactivation_hook(__FILE__, MyFXBook::instance()->getCallback('settings_deactivation'));