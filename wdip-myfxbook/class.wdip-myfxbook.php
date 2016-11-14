<?php
namespace WDIP\Plugin;

/**
 * @author: igor.popravka
 * @link https://www.upwork.com/freelancers/~010854a54a1811f970 Author Profile
 * Date: 09.11.2016
 * Time: 14:32
 */
class MyFXBook {
    const OPTIONS_GROUP = 'wdip-myfxbook-group';
    const OPTIONS_PAGE = 'wdip-myfxbook-page';

    const OPTIONS_REGISTRATION = 'registration_options';
    const OPTIONS_GENERAL = 'general_options';
    const OPTIONS_DAILYGAIN = 'dailygain_options';
    const OPTIONS_WIDGET = 'widget_options';
    const OPTIONS_DATADAILY = 'datadaily_options';

    const PAGE_HOOK = 1;
    const SHORTCODE_HOOK = 2;

    private static $instance;

    private function __construct() {
    }

    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', $this->getCallback('admin_menu'));
            add_action('admin_init', $this->getCallback('settings_init'));
        } else {
            add_action('wp_enqueue_scripts', $this->getCallback('enqueue_scripts'));
            if ($this->isShortCode()) {
                $options = get_option(self::OPTIONS_GENERAL);
                add_shortcode(trim($options['page_hook_field'], '[] '), $this->getCallback('shortcode_hook'));
            } else {
                add_filter('the_content', $this->getCallback('content_hook'));
            }
        }
    }

    public function admin_menu() {
        add_options_page(
            __('My FX Book Settings'),
            'MyFXBook',
            8,
            self::OPTIONS_PAGE,
            $this->getCallback('options_page')
        );
    }

    public function content_hook($content) {
        if (!$this->canView()) return $content;
        return $content . $this->graph_view();
    }

    public function shortcode_hook($atts = [], $content = null) {
        if (!$this->isShortCode()) return '';
        $header = '<p>&nbsp;</p>>';
        if(!empty($atts['title'])){
            $header .= "<h1>{$atts['title']}</h1>";
        }

        if(!empty($atts['description'])){
            $header .= "<h4>{$atts['description']}</h4>";
        }

        return $header . $content . $this->graph_view();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('google-charts', 'https://www.gstatic.com/charts/loader.js');
        wp_enqueue_script('wdip-myfxbook', plugins_url('/js/wdip-myfxbook.js', __FILE__), ['jquery', 'google-charts']);

        wp_localize_script('wdip-myfxbook', 'wdip_myfxbook_daily_gain', $this->localize_dailygain_graph());
        wp_localize_script('wdip-myfxbook', 'wdip_myfxbook_data_daily', $this->localize_datadaily_graph());
        wp_localize_script('wdip-myfxbook', 'wdip_myfxbook_notify', ['graph_error' => __('<p class="description"><i>Failed to get graph!</i></p>', self::OPTIONS_PAGE)]);

        wp_enqueue_style('wdip-myfxbook', plugins_url('/css/wdip-myfxbook.css', __FILE__));
    }

    public function options_page() {
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
                settings_fields(self::OPTIONS_GROUP);
                // output setting sections and their fields
                do_settings_sections(self::OPTIONS_PAGE);
                // output save settings button
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    public function settings_init() {
        $this->register_settings();

        /**
         * registration section
         */
        add_settings_section(
            'registration_section',
            __('Account Registration Data', self::OPTIONS_PAGE),
            $this->getCallback('section_content'),
            self::OPTIONS_PAGE
        );
        add_settings_field(
            'login_field',
            __('Login', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'registration_section',
            [
                'label_for' => 'login_field',
                'tag' => 'input',
                'type' => 'text',
                'description' => 'Enter your a login. It will be used only to authorization in API',
                'options_name' => self::OPTIONS_REGISTRATION
            ]
        );
        add_settings_field(
            'password_field',
            __('Password', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'registration_section',
            [
                'label_for' => 'password_field',
                'tag' => 'input',
                'type' => 'password',
                'description' => 'Enter your a password. It will be used only to authorization in API',
                'options_name' => self::OPTIONS_REGISTRATION
            ]
        );

        /**
         * general section
         */
        add_settings_section(
            'general_section',
            __('General Plugin Settings', self::OPTIONS_PAGE),
            $this->getCallback('section_content'),
            self::OPTIONS_PAGE
        );
        add_settings_field(
            'page_hook_type_field',
            __('Page hook type', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'general_section',
            [
                'label_for' => 'page_hook_type_field',
                'tag' => 'radio',
                'type' => 'radio',
                'description' => "Choose hook type where will be display the graphs.",
                'options_name' => self::OPTIONS_GENERAL,
                'default_value' => 1
            ]
        );
        add_settings_field(
            'page_hook_field',
            __('Page hook value', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'general_section',
            [
                'label_for' => 'page_hook_field',
                'tag' => 'input',
                'type' => 'text',
                'description' => "Enter full a page url (\"http://www.your-site/page\") if checked \"Page URI\" type\nOr enter the shortcode name \"my_shortcode_name\".",
                'options_name' => self::OPTIONS_GENERAL
            ]
        );

        $this->init_graph_settings();
    }

    public function registration_validation($options) {
        $options['api_session'] = null;
        $options['accounts_list'] = [];

        if (!empty($options['login_field']) && !empty($options['password_field'])) {
            $result = $this->requestAPI('login.json', [
                'email' => $options['login_field'],
                'password' => $options['password_field']
            ]);

            if (empty($result->session)) {
                add_settings_error(
                    'myfxbook-api-session-empty',
                    'myfxbook-api-session-empty',
                    __('Failed during authorization into <a href="https://www.myfxbook.com/api">https://www.myfxbook.com/api</a>', self::OPTIONS_PAGE)
                );
            } else {
                $options['api_session'] = $result->session;
                $result = $this->requestAPI('get-my-accounts.json', [
                    'session' => $options['api_session']
                ]);

                if (empty($result->accounts)) {
                    add_settings_error(
                        'myfxbook-api-accounts-empty',
                        'myfxbook-api-accounts-empty',
                        __('Failed to get accounts list from <a href="https://www.myfxbook.com/api">https://www.myfxbook.com/api</a>', self::OPTIONS_PAGE)
                    );
                } else {
                    foreach ($result->accounts as $acc) {
                        $options['accounts_list']["{$acc->name} ({$acc->accountId})"] = $acc->id;
                    }
                }
            }
        }

        return $options;
    }

    public function section_content($args) {
        $notify = [
            'registration_section' => 'Please fill following information about account registration onto <a href="https://www.myfxbook.com">https://www.myfxbook.com</a>',
            'general_section' => 'Please fill general plugin settings',
            'dailygain_section' => 'Please fill data for daily gain graph',
            'widget_section' => 'Please fill data for custom account widget',
            'datadaily_section' => 'Please fill data for data daily graph'
        ];

        ?>
        <p class="description"><?= isset($notify[$args['id']]) ? __($notify[$args['id']], self::OPTIONS_PAGE) : ''; ?></p>
        <?php
    }

    public function field_content($args) {
        $name = $args['options_name'];
        $label_for = $args['label_for'];
        $options = get_option($name);
        $value = !empty($options[$label_for]) ? $options[$label_for] : (isset($args['default_value']) ? $args['default_value'] : '');

        switch ($args['tag']) {
            case 'select':
                ?>
                <select id="<?= esc_attr($label_for); ?>"
                        name="<?= sprintf('%s[%s]', $name, esc_attr($label_for)); ?>">
                    <?php foreach ($args['options_list'] as $nm => $vl) : ?>
                        <option
                            value="<?= $vl; ?>"<?= ($value == $vl) ? 'selected' : ''; ?>><?= $nm; ?></option>
                    <?php endforeach; ?>
                </select>
                <?php
                break;
            case 'radio':
                ?>
                <input id="<?= esc_attr($label_for) . '-1'; ?>" type="<?= esc_attr($args['type']); ?>"
                       name="<?= sprintf('%s[%s]', $name, esc_attr($label_for)); ?>"
                       value="1" <?= $value == 1 ? 'checked' : ''; ?>/>
                <label for="<?= esc_attr($label_for) . '-1'; ?>"
                       style="margin-right: 60px;"><?= __('Page URI') ?></label>&nbsp;
                <input id="<?= esc_attr($label_for) . '-2'; ?>" type="<?= esc_attr($args['type']); ?>"
                       name="<?= sprintf('%s[%s]', $name, esc_attr($label_for)); ?>"
                       value="2" <?= $value == 2 ? 'checked' : ''; ?>/>
                <label for="<?= esc_attr($label_for) . '-2'; ?>"><?= __('Shortcode') ?></label>

                <?php
                break;
            case 'input':
            default:
                ?>
                <input id="<?= esc_attr($label_for); ?>" type="<?= esc_attr($args['type']); ?>"
                       name="<?= sprintf('%s[%s]', $name, esc_attr($label_for)); ?>"
                       value="<?= $value; ?>"
                >
                <?php
        }

        ?>
        <p class="description">
            <?= nl2br(__($args['description'], self::OPTIONS_PAGE)); ?>
        </p>
        <?php
    }

    private function register_settings() {
        register_setting(self::OPTIONS_GROUP, self::OPTIONS_REGISTRATION, $this->getCallback('registration_validation'));
        register_setting(self::OPTIONS_GROUP, self::OPTIONS_GENERAL);
        register_setting(self::OPTIONS_GROUP, self::OPTIONS_DAILYGAIN);
        register_setting(self::OPTIONS_GROUP, self::OPTIONS_WIDGET);
        register_setting(self::OPTIONS_GROUP, self::OPTIONS_DATADAILY);
    }

    public function settings_deactivation() {
        unregister_setting(self::OPTIONS_GROUP, self::OPTIONS_REGISTRATION);
        unregister_setting(self::OPTIONS_GROUP, self::OPTIONS_GENERAL);
        unregister_setting(self::OPTIONS_GROUP, self::OPTIONS_DAILYGAIN);
        unregister_setting(self::OPTIONS_GROUP, self::OPTIONS_WIDGET);
        unregister_setting(self::OPTIONS_GROUP, self::OPTIONS_DATADAILY);
        delete_option(self::OPTIONS_REGISTRATION);
        delete_option(self::OPTIONS_GENERAL);
        delete_option(self::OPTIONS_DAILYGAIN);
        delete_option(self::OPTIONS_WIDGET);
        delete_option(self::OPTIONS_DATADAILY);
    }

    private function init_graph_settings() {
        $options = get_option(self::OPTIONS_REGISTRATION);

        if (empty($options['accounts_list'])) return;

        /**
         * Init settings for daily gain graph
         */
        add_settings_section(
            'dailygain_section',
            __('Daily Gain Graph Settings', self::OPTIONS_PAGE),
            $this->getCallback('section_content'),
            self::OPTIONS_PAGE
        );
        add_settings_field(
            'dg_title_field',
            __('Title', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'dailygain_section',
            [
                'label_for' => 'dg_title_field',
                'tag' => 'input',
                'type' => 'text',
                'description' => 'Enter the graph title',
                'options_name' => self::OPTIONS_DAILYGAIN,
                'default_value' => 'Daily gain'
            ]
        );
        add_settings_field(
            'dg_account_field',
            __('Account', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'dailygain_section',
            [
                'label_for' => 'dg_account_field',
                'tag' => 'select',
                'description' => 'Select your an account, which will be use to built the graph',
                'options_list' => $options['accounts_list'],
                'options_name' => self::OPTIONS_DAILYGAIN
            ]
        );
        add_settings_field(
            'dg_start_field',
            __('Start date', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'dailygain_section',
            [
                'label_for' => 'dg_start_field',
                'tag' => 'input',
                'type' => 'date',
                'description' => 'Enter start date, which will use for filtering the account data',
                'options_name' => self::OPTIONS_DAILYGAIN,
                'default_value' => (new \DateTime())->modify('first day of previous month')->format('Y-m-d')
            ]
        );
        add_settings_field(
            'dg_end_field',
            __('End date', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'dailygain_section',
            [
                'label_for' => 'dg_end_field',
                'tag' => 'input',
                'type' => 'date',
                'description' => 'Enter end date, which will use for filtering the account data',
                'options_name' => self::OPTIONS_DAILYGAIN,
                'default_value' => (new \DateTime())->modify('first day of this month')->format('Y-m-d')
            ]
        );

        /**
         * Init settings for custom widget
         */
        add_settings_section(
            'widget_section',
            __('MyFXBook Custom Widget Settings', self::OPTIONS_PAGE),
            $this->getCallback('section_content'),
            self::OPTIONS_PAGE
        );
        add_settings_field(
            'wg_title_field',
            __('Title', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'widget_section',
            [
                'label_for' => 'wg_title_field',
                'tag' => 'input',
                'type' => 'text',
                'description' => 'Enter the graph title',
                'options_name' => self::OPTIONS_WIDGET,
                'default_value' => 'Custom widget'
            ]
        );
        add_settings_field(
            'wg_account_field',
            __('Account', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'widget_section',
            [
                'label_for' => 'wg_account_field',
                'tag' => 'select',
                'description' => 'Select your an account, which will be use to built the widget',
                'options_list' => $options['accounts_list'],
                'options_name' => self::OPTIONS_WIDGET
            ]
        );

        /**
         * Init settings for data daily
         */
        add_settings_section(
            'datadaily_section',
            __('Data Daily Graph Settings', self::OPTIONS_PAGE),
            $this->getCallback('section_content'),
            self::OPTIONS_PAGE
        );
        add_settings_field(
            'dd_title_field',
            __('Title', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'datadaily_section',
            [
                'label_for' => 'dd_title_field',
                'tag' => 'input',
                'type' => 'text',
                'description' => 'Enter the graph title',
                'options_name' => self::OPTIONS_DATADAILY,
                'default_value' => 'Data daily'
            ]
        );
        add_settings_field(
            'dd_account_field',
            __('Account', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'datadaily_section',
            [
                'label_for' => 'dd_account_field',
                'tag' => 'select',
                'description' => 'Select your an account, which will be use to built the graph',
                'options_list' => $options['accounts_list'],
                'options_name' => self::OPTIONS_DATADAILY
            ]
        );
        add_settings_field(
            'dd_start_field',
            __('Start date', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'datadaily_section',
            [
                'label_for' => 'dd_start_field',
                'tag' => 'input',
                'type' => 'date',
                'description' => 'Enter start date, which will use for filtering the account data',
                'options_name' => self::OPTIONS_DATADAILY,
                'default_value' => (new \DateTime())->modify('first day of previous month')->format('Y-m-d')
            ]
        );
        add_settings_field(
            'dd_end_field',
            __('End date', self::OPTIONS_PAGE),
            $this->getCallback('field_content'),
            self::OPTIONS_PAGE,
            'datadaily_section',
            [
                'label_for' => 'dd_end_field',
                'tag' => 'input',
                'type' => 'date',
                'description' => 'Enter end date, which will use for filtering the account data',
                'options_name' => self::OPTIONS_DATADAILY,
                'default_value' => (new \DateTime())->modify('first day of this month')->format('Y-m-d')
            ]
        );
    }

    public function getCallback($fun_name) {
        return [self::instance(), $fun_name];
    }

    /**
     * @param string $action
     * @param array $params
     * @return null|\stdClass
     */
    private function requestAPI($action, array $params) {
        $url = sprintf('https://www.myfxbook.com/api/%s?%s', $action, build_query($params));
        $response = wp_remote_get($url);

        if (wp_remote_retrieve_response_code($response) == 200) {
            $response = json_decode(wp_remote_retrieve_body($response));
            if (!empty($response)) return $response;
        }

        add_settings_error(
            'myfxbook-api-request-error',
            'myfxbook-api-request-error',
            __('Bad API request', self::OPTIONS_PAGE)
        );
        return null;
    }

    private function isShortCode() {
        $options = get_option(self::OPTIONS_GENERAL);
        return $options['page_hook_type_field'] == self::SHORTCODE_HOOK;
    }

    private function graph_view() {
        $op_rg = get_option(self::OPTIONS_REGISTRATION);
        $op_dg = get_option(self::OPTIONS_DAILYGAIN);
        $op_wg = get_option(self::OPTIONS_WIDGET);
        $op_dd = get_option(self::OPTIONS_DATADAILY);
        $widget = '';

        if(is_array($op_wg)){
            $widget = (!empty($op_rg['api_session']) && !empty($op_wg['wg_account_field'])) ? sprintf('<img style="user-select: none; cursor: zoom-in;" src="https://www.myfxbook.com/api/get-custom-widget.png?%s" />', build_query([
                'session' => $op_rg['api_session'],
                'id' => $op_wg['wg_account_field'],
                'bgColor' => 'ffffff',
                'chartbgc' => 'ffffff',
                'gridColor' => 'BDBDBD',
                'lineColor' => '00CB05',
                'barColor' => 'FF8D0A',
                'fontColor' => '000000',
                'bart' => 1,
                'linet' => 0,
                'title' => '',
                'titles' => 20,
            ])) : '';
        }

        ob_start();
        ?>
        <div class="wdip-myfxbook graph-wrap">
            <div class="graph">
                <h3 class="title"><?= __($op_dg['dg_title_field']); ?></h3>
                <div id="wdip-myfxbook-daily-gain"></div>
            </div>
            <div class="graph">
                <h3 class="title"><?= __($op_wg['wg_title_field']); ?></h3>
                <div id="wdip-myfxbook-custom-widget">
                    <?= $widget; ?>
                </div>
            </div>
            <div class="graph">
                <h3 class="title"><?= __($op_dd['dd_title_field']); ?></h3>
                <div id="wdip-myfxbook-data-daily"></div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    private function canView() {
        $options = get_option(self::OPTIONS_GENERAL);
        if (preg_match("/(?:http:\/\/|https:\/\/)?(.+)/i", $options['page_hook_field'], $match)) {
            return trim($match[1], '/') == trim($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '/');
        }
        return false;
    }

    private function localize_dailygain_graph() {
        $op_rg = get_option(self::OPTIONS_REGISTRATION);
        $op_dg = get_option(self::OPTIONS_DAILYGAIN);
        $table = [];

        if(is_array($op_dg)){
            $result = $this->requestAPI('get-daily-gain.json', [
                'session' => $op_rg['api_session'],
                'id' => $op_dg['dg_account_field'],
                'start' => $op_dg['dg_start_field'],
                'end' => $op_dg['dg_end_field']
            ]);

            if (!empty($result->dailyGain)) {
                foreach ($result->dailyGain as $item) {
                    $item = (array)array_shift($item);
                    if (empty($table)) {
                        $table[] = array_map('ucfirst', array_keys($item));
                    }
                    $table[] = array_values($item);
                }
            }
        }

        return $table;
    }

    private function localize_datadaily_graph() {
        $op_rg = get_option(self::OPTIONS_REGISTRATION);
        $op_dd = get_option(self::OPTIONS_DATADAILY);
        $table = [];

        if(is_array($op_dd)){
            $result = $this->requestAPI('get-data-daily.json', [
                'session' => $op_rg['api_session'],
                'id' => $op_dd['dd_account_field'],
                'start' => $op_dd['dd_start_field'],
                'end' => $op_dd['dd_end_field']
            ]);

            if (!empty($result->dataDaily)) {
                foreach ($result->dataDaily as $item) {
                    $item = (array)array_shift($item);
                    if (empty($table)) {
                        $table[] = array_map('ucfirst', ['date', 'balance', 'pips', 'lots']);
                    }
                    $table[] = [$item['date'], $item['balance'], $item['pips'], $item['lots']];
                }
            }
        }
        return $table;
    }
}