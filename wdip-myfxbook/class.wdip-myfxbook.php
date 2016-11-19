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
        if (!isset($attr['method']) || !isset($attr['id'])) return $content;

        $chart = isset($attr['chart']) ? ucfirst($attr['chart']) . 'Chart' : 'LineChart';
        $id = md5("{$attr['method']}-{$attr['id']}-{$chart}");
        $options = ['hAxis' => ['format' => 'MMM, yy',], 'vAxis' => []];
        foreach (['title', 'height', 'width', 'bgcolor', 'fontsize', 'gridcolor'] as $nm) {
            if (isset($attr[$nm])) {
                switch ($nm) {
                    case 'bgcolor':
                        $options['backgroundColor'] = $attr[$nm];
                        break;
                    case 'gridcolor':
                        $options['hAxis']['gridlines'] = ['color' => $attr[$nm]];
                        $options['vAxis']['gridlines'] = ['color' => $attr[$nm]];
                        break;
                    default:
                        $options[$nm] = $attr[$nm];
                }
            }
        }

        if ($attr['chart'] == 'column') {
            $options['isStacked'] = true;
            $options['legend'] = ['position' => 'top', 'maxLines' => 2];
        }

        $style = '';
        foreach (['height', 'width', 'bgcolor'] as $nm) {
            if (isset($attr[$nm])) {
                switch ($nm) {
                    case 'bgcolor':
                        $style .= "background-color:{$attr[$nm]}; ";
                        break;
                    default:
                        $style .= "{$nm}:{$attr[$nm]}px; ";
                }
            }
        }

        ob_start();

        echo $content;

        switch ($attr['method']) {
            case 'get-daily-gain':
            case 'get-data-daily':
                $data = $attr['method'] == 'get-daily-gain' ? $this->getDataDailyGain($attr['id']) : $this->getDataDaily($attr['id']);
                ?>
                <div id="<?= $id; ?>" class="wdip-myfxbook" style="<?= $style; ?>">
                    <div id="filter-<?= $id; ?>" class="filter"></div>
                    <div id="chart-<?= $id; ?>"></div>
                </div>
                <script>
                    /* <![CDATA[ */
                    if (typeof wdip_myfxbook_options == 'undefined') {
                        var wdip_myfxbook_options = [];
                    }

                    wdip_myfxbook_options.push({
                        chart: "<?= $chart; ?>",
                        id: "<?= $id; ?>",
                        data: function ($) {
                            var dt = <?= json_encode($data); ?>;
                            $(dt.rows).each(function (i, r) {
                                r.c[0].v = new Date(r.c[0].v);
                                dt.rows[i] = r;
                            });
                            return dt;
                        },
                        options: <?= json_encode($options); ?>
                    });
                    /* ]]> */
                </script>
                <?php

                break;
            case 'get-custom-widget':
                $op_rg = get_option(self::OPTIONS_NAME);

                echo (!empty($op_rg['api_session']) && !empty($attr['id'])) ? sprintf('<img style="user-select: none; cursor: zoom-in;" src="https://www.myfxbook.com/api/get-custom-widget.png?%s" />', build_query([
                    'session' => $op_rg['api_session'],
                    'id' => $attr['id'],
                    'width' => isset($attr['width']) ? intval($attr['width']) : null,
                    'height' => isset($attr['height']) ? intval($attr['height']) : null,
                    'bgColor' => isset($attr['bgcolor']) ? trim($attr['bgcolor'], '#') : 'ffffff',
                    'chartbgc' => isset($attr['bgcolor']) ? trim($attr['bgcolor'], '#') : 'ffffff',
                    'gridColor' => isset($attr['gridcolor']) ? trim($attr['gridcolor'], '#') : 'BDBDBD',
                    'lineColor' => '00CB05',
                    'barColor' => 'FF8D0A',
                    'fontColor' => '000000',
                    'bart' => 1,
                    'linet' => 0,
                    'title' => isset($attr['title']) ? $attr['title'] : '',
                    'titles' => 20,
                ])) : '';

        }

        return ob_get_clean();
    }

    public function initEnqueueScripts() {
        wp_enqueue_script('google-charts', 'https://www.gstatic.com/charts/loader.js');
        wp_enqueue_script('wdip-myfxbook', plugins_url('/js/wdip-myfxbook.js', __FILE__), ['jquery', 'google-charts']);
        wp_enqueue_style('wdip-myfxbook', plugins_url('/css/wdip-myfxbook.css', __FILE__));
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
                            <label for="method-list"><span>*</span> Method:</label>
                            <select name="method" id="method-list" class="attr-field">
                                <option value="get-daily-gain">Get Daily Gain</option>
                                <option value="get-custom-widget">Get Custom widget</option>
                                <option value="get-data-daily">Get Data Daily</option>
                            </select>
                        </p>
                        <p>
                            <label for="chart-list">Chart type:</label>
                            <select name="chart" id="chart-list" class="attr-field">
                                <option value="line">Line</option>
                                <option value="column">Column</option>
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
                            <label for="fontsize">Font size:</label>
                            <input name="fontsize" id="fontsize" type="text" value="" class="attr-field"/>
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

        add_settings_error(
            'myfxbook-api-request-error',
            'myfxbook-api-request-error',
            __('Bad API request', self::OPTIONS_PAGE)
        );
        return null;
    }

    private function getDataDailyGain($id) {
        static $table = [];

        if (!isset($table[$id])) {
            $table[$id] = [];
            $op_rg = get_option(self::OPTIONS_NAME);
            $result = $this->requestAPI('get-daily-gain.json', [
                'session' => $op_rg['api_session'],
                'id' => $id,
                'start' => '0-0-0',
                'end' => (new \DateTime())->format('Y-m-d')
            ]);

            $table[$id] = [
                'cols' => [
                    ['id' => 'date', 'label' => 'Date', 'type' => 'date'],
                    ['id' => 'value', 'label' => 'Value', 'type' => 'number'],
                    ['id' => 'profit', 'label' => 'Profit', 'type' => 'number'],
                ],
                'rows' => []
            ];

            if (!empty($result->dailyGain)) {
                $daily_gain = array_map(function ($i) {
                    return $i[0];
                }, $result->dailyGain);

                foreach ($daily_gain as $item) {
                    $table[$id]['rows'][] = [
                        'c' => [
                            ['v' => $item->date],
                            ['v' => floatval($item->value)],
                            ['v' => floatval($item->profit)]
                        ]
                    ];
                }
            }
        }

        return $table[$id];
    }

    private function getDataDaily($id) {
        static $table = [];

        if (!isset($table[$id])) {
            $table[$id] = [];
            $op_rg = get_option(self::OPTIONS_NAME);
            $result = $this->requestAPI('get-data-daily.json', [
                'session' => $op_rg['api_session'],
                'id' => $id,
                'start' => '0-0-0',
                'end' => (new \DateTime())->format('Y-m-d')
            ]);

            $table[$id] = [
                'cols' => [
                    ['id' => 'date', 'label' => 'Date', 'type' => 'date'],
                    ['id' => 'balance', 'label' => 'Balance', 'type' => 'number'],
                    ['id' => 'profit', 'label' => 'Profit', 'type' => 'number'],
                    ['id' => 'pips', 'label' => 'Pips', 'type' => 'number'],
                    ['id' => 'lots', 'label' => 'Lots', 'type' => 'number']
                ],
                'rows' => []
            ];

            if (!empty($result->dataDaily)) {
                $daily_gain = array_map(function ($i) {
                    return $i[0];
                }, $result->dataDaily);

                foreach ($daily_gain as $item) {
                    $table[$id]['rows'][] = [
                        'c' => [
                            ['v' => $item->date],
                            ['v' => floatval($item->balance)],
                            ['v' => floatval($item->profit)],
                            ['v' => floatval($item->pips)],
                            ['v' => floatval($item->lots)]
                        ]
                    ];
                }
            }
        }

        return $table[$id];
    }

    private function isSessionSet() {
        $options = get_option(self::OPTIONS_NAME);
        return !empty($options['api_session']);
    }
}