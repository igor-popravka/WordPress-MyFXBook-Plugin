<?php
/**
 * @author: igor.popravka
 * @link: https://www.upwork.com/freelancers/~010854a54a1811f970 Author Profile
 * Date: 03.11.2016
 * Time: 16:58
 */

$wdip_myfxbook_error_graph = __('<p class="description"><i>Failed to get graph!</i></p>', 'myfxbook');

/**
 * Uninstall plugin functions
 */
function wip_myfxbook_deactivation() {
    unregister_setting('myfxbook', 'wdip_myfxbook_options');
    delete_option('wdip_myfxbook_options');
}

/**
 * Init Admin settings page
 */
function wdip_myfxbook_options_page() {
    add_options_page('My FX Book Settings', 'MyFXBook', 8, 'myfxbook', 'wdip_myfxbook_options_page_view');
}

function wdip_myfxbook_options_page_view() {
    // check user capabilities
    if (!current_user_can('manage_options')) return;

    ?>
    <div class="wrap">
        <h1><?= esc_html(get_admin_page_title()); ?></h1>
        <p>
        <div><span style="margin-right: 20px;">Author Name:</span><i>Igor P.</i></div>
        <div>
            <span style="margin-right: 20px;">Author Page:</span>
            <i><a href="https://www.upwork.com/freelancers/~010854a54a1811f970">https://www.upwork.com/freelancers/~010854a54a1811f970</a></i>
        </div>
        </p>
        <form action="options.php" method="post">
            <?php
            // output security fields
            settings_fields('myfxbook');
            // output setting sections and their fields
            do_settings_sections('myfxbook');
            // output save settings button
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

function wdip_myfxbook_settings_init() {
    register_setting('myfxbook', 'wdip_myfxbook_options', 'wdip_myfxbook_validate');
    // check registration data
    wdip_myfxbook_check_registration_data();
    // init api session
    wdip_myfxbook_init_session();

    // register a new section
    add_settings_section(
        'wdip_myfxbook_section_account',
        __('Account Registration Data', 'myfxbook'),
        'wdip_myfxbook_section_cb',
        'myfxbook'
    );
    add_settings_section(
        'wdip_myfxbook_section_global',
        __('General Plugin Settings', 'myfxbook'),
        'wdip_myfxbook_section_cb',
        'myfxbook'
    );
    // register a new fields
    add_settings_field(
        'wdip_myfxbook_field_login',
        __('Login', 'myfxbook'),
        'wdip_myfxbook_field_input_cb',
        'myfxbook',
        'wdip_myfxbook_section_account',
        [
            'label_for' => 'wdip_myfxbook_field_login',
            'class' => 'wdip_myfxbook_row',
            'type' => 'text',
            'description' => 'Enter your a login. It will be used only to authorization in API'
        ]
    );
    add_settings_field(
        'wdip_myfxbook_field_password',
        __('Password', 'myfxbook'),
        'wdip_myfxbook_field_input_cb',
        'myfxbook',
        'wdip_myfxbook_section_account',
        [
            'label_for' => 'wdip_myfxbook_field_password',
            'class' => 'wdip_myfxbook_row',
            'type' => 'password',
            'description' => 'Enter your a password. It will be used only to authorization in API'
        ]
    );
    add_settings_field(
        'wdip_myfxbook_field_rendering_page',
        __('Destination page url', 'myfxbook'),
        'wdip_myfxbook_field_input_cb',
        'myfxbook',
        'wdip_myfxbook_section_global',
        [
            'label_for' => 'wdip_myfxbook_field_rendering_page',
            'class' => 'wdip_myfxbook_row',
            'type' => 'text',
            'description' => 'Enter url of a destination page where will be display all graphs'
        ]
    );
    // init graph settings
    wdip_myfxbook_graph_settings_init();
}

function wdip_myfxbook_check_registration_data() {
    $options = get_option('wdip_myfxbook_options');

    if (empty($options['wdip_myfxbook_field_login']) && empty($options['wdip_myfxbook_field_password'])) {
        $options['wdip_myfxbook_session'] = null;
        $options['wdip_myfxbook_accounts'] = null;
        update_option('wdip_myfxbook_options', $options);
    }
}

function wdip_myfxbook_init_session() {
    $options = get_option('wdip_myfxbook_options');
    $canInitSession = (
        empty($options['wdip_myfxbook_session']) &&
        !empty($options['wdip_myfxbook_field_login']) &&
        !empty($options['wdip_myfxbook_field_password'])
    );

    if (!$canInitSession) return;

    $result = wdip_myfxbook_api_client('login.json', [
        'email' => $options['wdip_myfxbook_field_login'],
        'password' => $options['wdip_myfxbook_field_password']
    ]);
    $options['wdip_myfxbook_session'] = isset($result->session) ? $result->session : null;

    if (!empty($options['wdip_myfxbook_session'])) {
        add_settings_error('wdip_myfxbook_success_session', 'wdip_myfxbook_success_session', __('Authorization was successful at <a href="https://www.myfxbook.com/api">https://www.myfxbook.com/api</a>', 'myfxbook'), 'updated');

        $options['wdip_myfxbook_accounts'] = [];
        $result = wdip_myfxbook_api_client('get-my-accounts.json', [
            'session' => $options['wdip_myfxbook_session']
        ]);
        if (!empty($result->accounts)) {
            foreach ($result->accounts as $acc) {
                $options['wdip_myfxbook_accounts']["{$acc->name} ({$acc->accountId})"] = $acc->id;
            }
        }
    } else {
        add_settings_error('wdip_myfxbook_empty_session', 'wdip_myfxbook_empty_session', __('Failed during authorization into <a href="https://www.myfxbook.com/api">https://www.myfxbook.com/api</a>', 'myfxbook'));
    }

    update_option('wdip_myfxbook_options', $options);
}

function wdip_myfxbook_graph_settings_init() {
    $options = get_option('wdip_myfxbook_options');

    if (empty($options['wdip_myfxbook_accounts'])) return;

    /**
     * Init settings for daily gain graph
     */
    add_settings_section(
        'wdip_myfxbook_section_daily_gain',
        __('Daily Gain Graph Settings', 'myfxbook'),
        'wdip_myfxbook_section_cb',
        'myfxbook'
    );
    add_settings_field(
        'wdip_myfxbook_field_daily_gain_account',
        __('Account', 'myfxbook'),
        'wdip_myfxbook_field_select_cb',
        'myfxbook',
        'wdip_myfxbook_section_daily_gain',
        [
            'label_for' => 'wdip_myfxbook_field_daily_gain_account',
            'class' => 'wdip_myfxbook_row',
            'description' => 'Select your an account, which will be built the graph',
            'options' => $options['wdip_myfxbook_accounts']
        ]
    );
    add_settings_field(
        'wdip_myfxbook_field_daily_gain_start',
        __('Start date', 'myfxbook'),
        'wdip_myfxbook_field_input_cb',
        'myfxbook',
        'wdip_myfxbook_section_daily_gain',
        [
            'label_for' => 'wdip_myfxbook_field_daily_gain_start',
            'class' => 'wdip_myfxbook_row',
            'type' => 'date',
            'description' => 'Enter start date, which will use for filtering the account data'
        ]
    );
    add_settings_field(
        'wdip_myfxbook_field_daily_gain_end',
        __('End date', 'myfxbook'),
        'wdip_myfxbook_field_input_cb',
        'myfxbook',
        'wdip_myfxbook_section_daily_gain',
        [
            'label_for' => 'wdip_myfxbook_field_daily_gain_end',
            'class' => 'wdip_myfxbook_row',
            'type' => 'date',
            'description' => 'Enter end date, which will use for filtering the account data'
        ]
    );

    /**
     * Init settings for custom widget
     */
    add_settings_section(
        'wdip_myfxbook_section_widget',
        __('MyFXBook Custom Widget Settings', 'myfxbook'),
        'wdip_myfxbook_section_cb',
        'myfxbook'
    );
    add_settings_field(
        'wdip_myfxbook_field_widget_account',
        __('Account', 'myfxbook'),
        'wdip_myfxbook_field_select_cb',
        'myfxbook',
        'wdip_myfxbook_section_widget',
        [
            'label_for' => 'wdip_myfxbook_field_widget_account',
            'class' => 'wdip_myfxbook_row',
            'description' => 'Select your an account, which will be built the graph',
            'options' => $options['wdip_myfxbook_accounts']
        ]
    );

    /**
     * Init settings for data daily
     */
    add_settings_section(
        'wdip_myfxbook_section_data_daily',
        __('Data Daily Graph Settings', 'myfxbook'),
        'wdip_myfxbook_section_cb',
        'myfxbook'
    );
    add_settings_field(
        'wdip_myfxbook_field_data_daily_account',
        __('Account', 'myfxbook'),
        'wdip_myfxbook_field_select_cb',
        'myfxbook',
        'wdip_myfxbook_section_data_daily',
        [
            'label_for' => 'wdip_myfxbook_field_data_daily_account',
            'class' => 'wdip_myfxbook_row',
            'description' => 'Select your an account, which will be built the graph',
            'options' => $options['wdip_myfxbook_accounts']
        ]
    );
    add_settings_field(
        'wdip_myfxbook_field_data_daily_start',
        __('Start date', 'myfxbook'),
        'wdip_myfxbook_field_input_cb',
        'myfxbook',
        'wdip_myfxbook_section_data_daily',
        [
            'label_for' => 'wdip_myfxbook_field_data_daily_start',
            'class' => 'wdip_myfxbook_row',
            'type' => 'date',
            'description' => 'Enter start date, which will use for filtering the account data'
        ]
    );
    add_settings_field(
        'wdip_myfxbook_field_data_daily_end',
        __('End date', 'myfxbook'),
        'wdip_myfxbook_field_input_cb',
        'myfxbook',
        'wdip_myfxbook_section_data_daily',
        [
            'label_for' => 'wdip_myfxbook_field_data_daily_end',
            'class' => 'wdip_myfxbook_row',
            'type' => 'date',
            'description' => 'Enter end date, which will use for filtering the account data'
        ]
    );
}

function wdip_myfxbook_section_cb($args) {
    $notify = [
        'wdip_myfxbook_section_account' => 'Please fill following information about account registration onto <a href="https://www.myfxbook.com">https://www.myfxbook.com</a>',
        'wdip_myfxbook_section_global' => 'Please fill general plugin settings',
        'wdip_myfxbook_section_daily_gain' => 'Please fill data for daily gain graph',
        'wdip_myfxbook_section_widget' => 'Please fill data for custom account widget',
        'wdip_myfxbook_section_data_daily' => 'Please fill data for data daily graph'
    ];

    ?>
    <p class="description"><?= isset($notify[$args['id']]) ? __($notify[$args['id']], 'myfxbook') : ''; ?></p>
    <?php
}

function wdip_myfxbook_field_input_cb($args) {
    $options = get_option('wdip_myfxbook_options');
    ?>
    <input id="<?= esc_attr($args['label_for']); ?>" type="<?= esc_attr($args['type']); ?>"
           name="wdip_myfxbook_options[<?= esc_attr($args['label_for']); ?>]"
           value="<?= isset($options[$args['label_for']]) ? $options[$args['label_for']] : ''; ?>"
    >
    <p class="description">
        <?= esc_html__($args['description'], 'myfxbook'); ?>
    </p>
    <?php
}

function wdip_myfxbook_field_select_cb($args) {
    $options = get_option('wdip_myfxbook_options');
    ?>
    <select id="<?= esc_attr($args['label_for']); ?>"
            name="wdip_myfxbook_options[<?= esc_attr($args['label_for']); ?>]">
        <?php foreach ($args['options'] as $name => $value) : ?>
            <option
                value="<?= $value; ?>"<?= (isset($options[$args['label_for']]) && $options[$args['label_for']] == $value) ? 'selected' : ''; ?>><?= $name; ?></option>
        <?php endforeach; ?>
    </select>
    <p class="description">
        <?= esc_html__($args['description'], 'myfxbook'); ?>
    </p>
    <?php
}

function wdip_myfxbook_admin_scripts($hook) {
    if (strpos($hook, 'myfxbook') === false) return;
    wp_enqueue_script('wdip-myfxbook-admin-script',
        plugins_url('/js/wdip-myfxbook-admin.js', __FILE__),
        array('jquery')
    );
}

/**
 * @param string $content
 * @return string
 */
function wdip_myfxbook_view_graph($content) {
    if (!wdip_myfxbook_can_view()) return $content;

    ob_start();
    echo $content;
    ?>
    <div class="wdip-myfxbook graph-wrap">
        <h3>MyFXBook Daily Gain graph</h3>
        <div id="wdip-myfxbook-daily-gain" class="graph"></div>

        <h3>MyFXBook Custom Widget</h3>
        <div id="wdip-myfxbook-custom-widget" class="graph">
            <?= wdip_myfxbook_get_widget(); ?>
        </div>

        <h3>MyFXBook Data Daily graph</h3>
        <div id="wdip-myfxbook-data-daily" class="graph"></div>
    </div>
    <?php

    return ob_get_clean();
}

function wdip_myfxbook_scripts() {
    if (!wdip_myfxbook_can_view()) return;

    global $wdip_myfxbook_error_graph;
    wp_enqueue_script('google-charts', 'https://www.gstatic.com/charts/loader.js');
    wp_enqueue_script('wdip-myfxbook', plugins_url('/js/wdip-myfxbook.js', __FILE__), ['jquery', 'google-charts']);
    wp_localize_script('wdip-myfxbook', 'wdip_myfxbook_daily_gain', wdip_myfxbook_get_daily_gain_graph());
    wp_localize_script('wdip-myfxbook', 'wdip_myfxbook_data_daily', wdip_myfxbook_get_data_daily_graph());
    wp_localize_script('wdip-myfxbook', 'wdip_myfxbook_notify', ['graph_error' => $wdip_myfxbook_error_graph]);
}

/**
 * @param string $action
 * @param array $params
 * @return null|\stdClass
 */
function wdip_myfxbook_api_client($action, array $params) {
    $url = sprintf('https://www.myfxbook.com/api/%s?%s', $action, build_query($params));
    $response = wp_remote_get($url);

    if (wp_remote_retrieve_response_code($response) == 200) {
        $response = wp_remote_retrieve_body($response);
        return json_decode($response);
    }
    return null;
}

function wdip_myfxbook_can_view() {
    $options = get_option('wdip_myfxbook_options');
    $ref_query = parse_url($options['wdip_myfxbook_field_rendering_page'], PHP_URL_QUERY);
    $client_query = $_SERVER['QUERY_STRING'];

    return strpos($client_query, $ref_query) !== false;
}

function wdip_myfxbook_get_widget() {
    global $wdip_myfxbook_error_graph;
    $options = get_option('wdip_myfxbook_options');

    if (empty($options['wdip_myfxbook_session']) || empty($options['wdip_myfxbook_field_widget_account'])) return $wdip_myfxbook_error_graph;

    return sprintf('<img style="user-select: none; cursor: zoom-in;" src="https://www.myfxbook.com/api/get-custom-widget.png?%s" />', build_query([
        'session' => $options['wdip_myfxbook_session'],
        'id' => $options['wdip_myfxbook_field_widget_account'],
        'width' => 450,
        'height' => 250,
        'bgColor' => '000000',
        'chartbgc' => '474747',
        'gridColor' => 'BDBDBD',
        'lineColor' => '00CB05',
        'barColor' => 'FF8D0A',
        'fontColor' => 'FFFFFF',
        'bart' => 1,
        'linet' => 0,
        'title' => '',
        'titles' => 20,
    ]));
}

function wdip_myfxbook_get_daily_gain_graph() {
    $options = get_option('wdip_myfxbook_options');
    $data = [
        'session' => 'wdip_myfxbook_session',
        'id' => 'wdip_myfxbook_field_daily_gain_account',
        'start' => 'wdip_myfxbook_field_daily_gain_start',
        'end' => 'wdip_myfxbook_field_daily_gain_end'
    ];

    foreach ($data as &$val) {
        if (empty($options[$val])) return [];
        $val = $options[$val];
    }

    $daily_gain = wdip_myfxbook_api_client('get-daily-gain.json', $data);

    $table = [];
    foreach ($daily_gain->dailyGain as $item) {
        $item = (array)array_shift($item);
        if (empty($table)) {
            $table[] = array_map('ucfirst', array_keys($item));
        }
        $table[] = array_values($item);
    }

    return $table;
}

function wdip_myfxbook_get_data_daily_graph() {
    $options = get_option('wdip_myfxbook_options');
    $data = [
        'session' => 'wdip_myfxbook_session',
        'id' => 'wdip_myfxbook_field_data_daily_account',
        'start' => 'wdip_myfxbook_field_data_daily_start',
        'end' => 'wdip_myfxbook_field_data_daily_end'
    ];

    foreach ($data as &$val) {
        if (empty($options[$val])) return [];
        $val = $options[$val];
    }

    $data_daily = wdip_myfxbook_api_client('get-data-daily.json', $data);

    $table = [];
    foreach ($data_daily->dataDaily as $item) {
        $item = (array)array_shift($item);
        if (empty($table)) {
            $table[] = array_map('ucfirst', array_keys($item));
        }
        $table[] = array_values($item);
    }

    return $table;
}

function wdip_myfxbook_validate($fields) {
    $def_value = [
        'wdip_myfxbook_field_daily_gain_start' => (new DateTime())->modify('first day of previous month')->format('Y-m-d'),
        'wdip_myfxbook_field_daily_gain_end' => (new DateTime())->modify('first day of this month')->format('Y-m-d'),
        'wdip_myfxbook_field_data_daily_start' => (new DateTime())->modify('first day of previous month')->format('Y-m-d'),
        'wdip_myfxbook_field_data_daily_end' => (new DateTime())->modify('first day of this month')->format('Y-m-d')
    ];

    foreach ($def_value as $nm => $vl) {
        if (isset($fields[$nm]) && empty($fields[$nm])) {
            $fields[$nm] = $vl;
        }
    }

    return $fields;
}