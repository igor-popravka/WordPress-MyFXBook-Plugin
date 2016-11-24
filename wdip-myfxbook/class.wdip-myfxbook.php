<?php

/**
 * @author: igor.popravka
 * @link https://www.upwork.com/freelancers/~010854a54a1811f970 Author Profile
 * Date: 09.11.2016
 * Time: 14:32
 */
class WDIP_MyFXBook_Plugin {
    const OPTIONS_GROUP = 'wdip-myfxbook-group';
    const OPTIONS_PAGE = 'wdip-myfxbook-page';
    const OPTIONS_NAME = 'options_name';
    const SHORT_CODE_NAME = 'myfxbook';

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
            add_action('admin_menu', $this->getCallback('initAdminMenu'));
            add_action('admin_init', $this->getCallback('initSettings'));
            add_action('admin_enqueue_scripts', $this->getCallback('initAdminEnqueueScripts'));
        } else {
            add_action('wp_enqueue_scripts', $this->getCallback('initEnqueueScripts'));
            add_shortcode(self::SHORT_CODE_NAME, $this->getCallback('applyShortCode'));
        }
    }

    public function initAdminMenu() {
        add_options_page(
            __('My FX Book Settings'),
            'MyFXBook',
            8,
            self::OPTIONS_PAGE,
            $this->getCallback('renderOptionsPage')
        );
    }

    public function applyShortCode($attr = [], $content = null) {
        if (!isset($attr['type']) || !isset($attr['id'])) return $content;

        $id = md5("{$attr['type']}-{$attr['id']}");
        $options = [
            'type' => $attr['type'],
            'data' => [],
            'title' => !empty($attr['title']) ? $attr['title'] : null,
            'height' => !empty($attr['height']) ? $attr['height'] : null,
            'width' => !empty($attr['width']) ? $attr['width'] : null,
            'bgcolor' => !empty($attr['bgcolor']) ? $attr['bgcolor'] : null,
            'gridcolor' => !empty($attr['gridcolor']) ? $attr['gridcolor'] : null,
            'filter' => !empty($attr['filter']) ? $attr['filter'] : 0
        ];

        ob_start();

        echo $content;

        if ($attr['type'] == 'get-daily-gain') {
            $options['data'] = $this->getDataDailyGain($attr['id']);
        } else if ($attr['type'] == 'get-data-daily') {
            $options['data'] = $this->getDataDaily($attr['id']);
        }

        ?>
        <div id="<?= $id; ?>" class="wdip-myfxbook-chart"></div>
        <script>
            /* <![CDATA[ */
            if (typeof WDIPMyFxBook == 'undefined') {
                var WDIPMyFxBook = new (function () {
                    var options = [];
                    return {
                        add: function (id, opt) {
                            options.push({
                                id: id,
                                opt: opt
                            });
                        },
                        each: function (callbak) {
                            for (var i in options) {
                                var o = options[i];
                                callbak(o.id, o.opt);
                            }
                        }
                    }
                })();
            }
            WDIPMyFxBook.add("<?= $id; ?>", <?= json_encode($options); ?>);
            /* ]]> */
        </script>
        <?php

        return ob_get_clean();
    }

    public function initEnqueueScripts() {
        wp_enqueue_script('highcharts', 'http://code.highcharts.com/highcharts.js');
        wp_enqueue_script('wdip-myfxbook-chats', plugins_url('/js/wdip-myfxbook.chats.js', __FILE__), ['jquery', 'jquery-ui-slider', 'highcharts']);

        wp_enqueue_style('jquery-ui-slider-css', '//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        wp_enqueue_style('wdip-myfxbook-css', plugins_url('/css/wdip-myfxbook.css', __FILE__));
    }

    public function initAdminEnqueueScripts() {
        wp_enqueue_script('wdip-myfxbook', plugins_url('/js/wdip-myfxbook.admin.js', __FILE__), ['jquery']);
        wp_enqueue_style('wdip-myfxbook', plugins_url('/css/wdip-myfxbook.css', __FILE__));
    }

    public function renderOptionsPage() {
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
            <?
            $options = get_option(self::OPTIONS_NAME);
            $acc_list = $options['accounts_list'];

            if ($this->isSessionSet()):
                ?>
                <h1>SortCode Generator</h1>
                <div class="generation-fields">
                    <fieldset>
                        <legend>Attributes</legend>
                        <p>
                            <label for="account-list"><span>*</span> Account:</label>
                            <select name="id" id="account-list" class="attr-field">
                                <? foreach ($acc_list as $title => $value) : ?>
                                    <option value="<?= $value; ?>"><?= $title; ?></option>
                                <? endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <label for="type-list"><span>*</span> Type:</label>
                            <select name="type" id="type-list" class="attr-field">
                                <option value="get-daily-gain">Get Daily Gain</option>
                                <option value="get-data-daily">Get Data Daily</option>
                            </select>
                        </p>
                        <p>
                            <label for="title">Title:</label>
                            <input name="title" id="title" type="text" value="" class="attr-field"/>
                        </p>
                        <p>
                            <label for="height">Height:</label>
                            <input name="height" id="height" type="text" value="" class="attr-field"/>
                        </p>
                        <p>
                            <label for="width">Width:</label>
                            <input name="width" id="width" type="text" value="" class="attr-field"/>
                        </p>
                        <p>
                            <label for="bgcolor">Background color:</label>
                            <input name="bgcolor" id="bgcolor" type="text" value="" class="attr-field"/>
                        </p>
                        <p>
                            <label for="gridcolor">Grid color:</label>
                            <input name="gridcolor" id="gridColor" type="text" value="" class="attr-field"/>
                        </p>
                        <p>
                            <label for="filter">Use filter:</label>
                            <input name="filter" id="filter" type="checkbox" value="1" checked class="attr-field"/>
                        </p>
                    </fieldset>
                </div>
                <div class="generation-result">
                    <fieldset>
                        <legend>Result</legend>
                        <p>
                            <textarea id="result"></textarea>
                        </p>
                    </fieldset>
                </div>
                <div class="generation-action wp-core-ui">
                    <p>
                        <button class="button button-secondary">Generate</button>
                    <hr/>
                    </p>
                </div>
            <? endif; ?>
        </div>
        <?php
    }

    public function initSettings() {
        register_setting(
            self::OPTIONS_GROUP,
            self::OPTIONS_NAME,
            $this->getCallback('validOptionsData')
        );

        $section_code = 'options_sections';

        /**
         * registration section
         */
        add_settings_section(
            $section_code,
            __('Account Registration Data', self::OPTIONS_PAGE),
            $this->getCallback('renderOptionsNotify'),
            self::OPTIONS_PAGE
        );
        add_settings_field(
            'login_field',
            __('Login', self::OPTIONS_PAGE),
            $this->getCallback('renderOptionsField'),
            self::OPTIONS_PAGE,
            $section_code,
            [
                'label_for' => 'login_field',
                'tag' => 'input',
                'type' => 'text',
                'description' => 'Enter your a login. It will be used only to authorization in API',
                'options_name' => self::OPTIONS_NAME
            ]
        );
        add_settings_field(
            'password_field',
            __('Password', self::OPTIONS_PAGE),
            $this->getCallback('renderOptionsField'),
            self::OPTIONS_PAGE,
            $section_code,
            [
                'label_for' => 'password_field',
                'tag' => 'input',
                'type' => 'password',
                'description' => 'Enter your a password. It will be used only to authorization in API',
                'options_name' => self::OPTIONS_NAME
            ]
        );
    }

    public function validOptionsData($options) {
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

    public function renderOptionsNotify($args) {
        $notify = __('Please fill following information about account registration onto <a href="https://www.myfxbook.com">https://www.myfxbook.com</a>', self::OPTIONS_PAGE);
        ?>
        <p class="description"><?= $notify; ?></p>
        <?php
    }

    public function renderOptionsField($args) {
        $name = $args['options_name'];
        $label_for = $args['label_for'];
        $options = get_option($name);
        $value = !empty($options[$label_for]) ? $options[$label_for] : (isset($args['default_value']) ? $args['default_value'] : '');

        ?>
        <input id="<?= esc_attr($label_for); ?>" type="<?= esc_attr($args['type']); ?>"
               name="<?= sprintf('%s[%s]', $name, esc_attr($label_for)); ?>"
               value="<?= $value; ?>"
        <div>
            <p class="description">
                <?= nl2br(__($args['description'], self::OPTIONS_PAGE)); ?>
            </p>
        </div>
        <?php
    }

    public function delSettings() {
        unregister_setting(self::OPTIONS_GROUP, self::OPTIONS_NAME);
        delete_option(self::OPTIONS_NAME);
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

        return null;
    }

    private function getDataDailyGain($id) {
        $op_rg = get_option(self::OPTIONS_NAME);
        $result = $this->requestAPI('get-daily-gain.json', [
            'session' => $op_rg['api_session'],
            'id' => $id,
            'start' => '0-0-0',
            'end' => (new \DateTime())->format('Y-m-d')
        ]);

        $table = [];

        if (!empty($result->dailyGain)) {
            $daily_gain = array_map(function ($i) {
                return $i[0];
            }, $result->dailyGain);

            $group_data = [];
            foreach ($daily_gain as $item) {
                $date = (new \DateTime())->createFromFormat('m/d/Y', $item->date);
                $group_name = $date->format('m/1/Y');
                if (!isset($group_data[$group_name])) {
                    $group_data[$group_name] = [];
                }

                $group_data[$group_name][] = floatval($item->profit);
            }

            foreach ($group_data as $x => $y) {
                $table[] = [
                    'x' => $x,
                    'y' => round(array_sum($y) / count($y), 2)
                ];
            }
        }

        return $table;
    }

    private function getDataDaily($id) {
        $op_rg = get_option(self::OPTIONS_NAME);
        $result = $this->requestAPI('get-data-daily.json', [
            'session' => $op_rg['api_session'],
            'id' => $id,
            'start' => '0-0-0',
            'end' => (new \DateTime())->format('Y-m-d')
        ]);

        $table = [];

        if (!empty($result->dataDaily)) {
            $daily_gain = array_map(function ($i) {
                return $i[0];
            }, $result->dataDaily);

            $group_data = [];
            foreach ($daily_gain as $item) {
                $date = (new \DateTime())->createFromFormat('m/d/Y', $item->date);
                $group_name = $date->format('m/1/Y');
                if (!isset($group_data[$group_name])) {
                    $group_data[$group_name] = [];
                }

                $group_data[$group_name][] = floatval($item->profit);
            }

            foreach ($group_data as $x => $y) {
                $table[] = [
                    'x' => $x,
                    'y' => round(array_sum($y) / count($y), 2)
                ];
            }
        }

        return $table;
    }

    private function isSessionSet() {
        $options = get_option(self::OPTIONS_NAME);
        return !empty($options['api_session']);
    }
}