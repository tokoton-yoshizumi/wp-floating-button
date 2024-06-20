<?php
/*
Plugin Name: WP Floating Button
Plugin URI: https://wpzen.jp/
Description: Adds a floating button to the bottom of the site.
Version: 1.1.4
Author: YOSHIZUMI LLC
Author URI: http://yoshizumi.tech
*/


function floating_button_sanitize_settings($inputs)
{
    error_log('Received settings: ' . print_r($inputs, true));  // デバッグ情報をログに出力

    $new_input = [];
    foreach ($inputs as $key => $value) {
        if (strpos($key, 'image_id_') === 0) {
            // 画像IDのフィールドを検証し、空の場合はデータベース更新用に空文字をセットする
            $new_input[$key] = empty($value) ? '' : intval($value);
        } elseif (strpos($key, 'display_on_') === 0) {
            $new_input[$key] = ($value === '1') ? '1' : '0';
        } elseif (strpos($key, 'use_banner_') === 0) {
            // バナーの使用状況を検証し、チェックされていれば '1'、そうでなければ '0' をセットする
            $new_input[$key] = (!empty($value) && $value === '1') ? '1' : '0';
        } else {
            $new_input[$key] = sanitize_text_field($value);
        }
    }
    return $new_input;
}
register_setting('floatingButton', 'floating_button_settings', 'floating_button_sanitize_settings');



// メタボックスを追加
function floating_button_add_meta_box()
{

    add_meta_box(
        'floating_button_meta_box',           // ID of the meta box
        __('WP Floating Button', 'floating-button'), // Title of the meta box
        'floating_button_meta_box_html',      // Callback function to output the content
        ['post', 'page'],                     // Post types
        'side',                               // Context
        'default'                             // Priority
    );
}

// メタボックスのHTMLコンテンツ
function floating_button_meta_box_html($post)
{
    $license_status = get_option('wp_floating_button_license_status', 'invalid');
    $value = get_post_meta($post->ID, '_floating_button_display', true);
    $disabled = ($license_status !== 'valid') ? 'disabled' : '';

?>
    <p><label for="floating_button_field"><?php esc_html_e('ボタンの表示', 'floating-button'); ?></label> <?php if ($license_status !== 'valid') : ?>
            <a href="https://yoshizumi.tech" class="premium-link" target="_blank">🔒Pro限定</a>
    </p>
<?php endif; ?>
<select name="floating_button_field" id="floating_button_field" class="postbox" <?php echo $disabled; ?>>
    <option value="yes" <?php selected($value, 'yes'); ?><?php echo $disabled; ?>><?php esc_html_e('表示', 'floating-button'); ?></option>
    <option value="no" <?php selected($value, 'no'); ?><?php echo $disabled; ?>><?php esc_html_e('非表示', 'floating-button'); ?></option>
</select>

<?php
}

// メタデータの保存
function floating_button_save_postdata($post_id)
{
    if (array_key_exists('floating_button_field', $_POST)) {
        update_post_meta(
            $post_id,
            '_floating_button_display',
            $_POST['floating_button_field']
        );
    }
}
// フックを設定
add_action('add_meta_boxes', 'floating_button_add_meta_box');
add_action('save_post', 'floating_button_save_postdata');

function add_floating_button_scripts()
{
    global $post;
    if ($post !== null) {
        $display = get_post_meta($post->ID, '_floating_button_display', true);
    } else {
        $display = '1';
    }

    if ($display !== 'no') {
        wp_enqueue_script('font-awesome-kit', 'https://kit.fontawesome.com/6ceb81141d.js', array(), null, true);
        wp_enqueue_style('floating-button-css', plugins_url('/css/style.css', __FILE__));
        wp_enqueue_script('floating-button-js', plugins_url('/js/script.js', __FILE__), array('jquery'), null, true);

        $options = get_option('floating_button_settings');
        $containerBgColor = $options['container_bg_color'] ?? '#FFFFFF';
        $closeButton = !empty($options['close_button']) ? '1' : '0';
        $numButtons = $options['columns'] ?? '1';
        $design = $options['design'] ?? 'default';
        $displayOnMobile = $options['display_on_mobile'] ?? '1';
        $displayOnTablet = $options['display_on_tablet'] ?? '1';
        $displayOnDesktop = $options['display_on_desktop'] ?? '1';

        $presets = get_button_color_presets();

        $data = [
            'columns' => $numButtons,
            'numButtons' => $numButtons,
            'containerBgColor' => $containerBgColor,
            'closeButton' => $closeButton,
            'microcopy' => $options['microcopy'] ?? '',
            'microcopyPosition' => $options['microcopy_position'] ?? 'left',
            'design' => $design,
            'displayOnMobile' => $displayOnMobile,
            'displayOnTablet' => $displayOnTablet,
            'displayOnDesktop' => $displayOnDesktop,
        ];

        for ($i = 1; $i <= $numButtons; $i++) {
            $use_banner = $options["use_banner_$i"] ?? '0';
            $image_id = $use_banner === '1' ? ($options["image_id_$i"] ?? '') : '';
            $image_url = $image_id ? wp_get_attachment_url($image_id) : '';
            $preset_key = $options["preset_$i"] ?? 'default';
            $preset_colors = $presets[$preset_key] ?? ['bg_color' => '#FFFFFF', 'text_color' => '#000000'];

            $button_text = $options["text_$i"] ?? 'クリックしてください';
            $link_url = $options["link_url_$i"] ?? 'https://yoshizumi.tech';

            // 特定ページ用の設定
            $page_ids_specific = $options["page_ids_$i"] ?? '';
            $text_specific = $options["text_specific_$i"] ?? '';
            $url_specific = $options["url_specific_$i"] ?? '';
            $visibility_specific = $options["visibility_specific_$i"] ?? '1';

            // 特定ページ用の設定がない場合は通常のリンク先URLを使用
            if (in_array($post->ID, array_map('trim', explode(',', $page_ids_specific))) && !empty($url_specific)) {
                $link_url = $url_specific;
            }

            if (in_array($post->ID, array_map('trim', explode(',', $page_ids_specific)))) {
                if (!empty($text_specific)) {
                    $button_text = $text_specific;
                }
                if ($visibility_specific === '0') {
                    continue;  // ボタンを表示しない場合
                }
            }

            $button_data = [
                'linkUrl' => $link_url,
                'text' => $button_text,
                'textColor' => $options["text_color_$i"] ?? $preset_colors['text_color'],
                'bgColor' => $options["bg_color_$i"] ?? $preset_colors['bg_color'],
                'icon' => $options["icon_$i"] ?? '',
                'imageUrl' => $image_url,
            ];
            $data['buttons'][] = $button_data;
        }

        wp_localize_script('floating-button-js', 'FloatingButton', $data);
    }
}
add_action('wp_enqueue_scripts', 'add_floating_button_scripts');

function floating_button_admin_scripts($hook_suffix)
{
    wp_enqueue_script('jquery');
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('floating-button-color-picker', plugins_url('/js/admin-color-picker.js', __FILE__), array('jquery', 'wp-color-picker'), null, true);
    wp_enqueue_script('font-awesome-kit', 'https://kit.fontawesome.com/6ceb81141d.js', array(), null, true);

    wp_enqueue_media();

    if ('toplevel_page_floating-button-settings' === $hook_suffix) {
        wp_enqueue_style('floating-button-admin-style', plugins_url('/css/admin-style.css', __FILE__));
        wp_enqueue_script('floating-button-admin-js', plugins_url('/js/admin-script.js', __FILE__), array('jquery'), null, true);

        $options = get_option('floating_button_settings', []);
        $numButtons = $options['columns'] ?? 1;
        $license_status = get_option('wp_floating_button_license_status', 'invalid');

        $buttonPresets = [];
        $buttonUrls = [];
        $buttonVisibilities = [];
        for ($i = 1; $i <= $numButtons; $i++) {
            $presetKey = "preset_{$i}";
            $buttonPresets[$presetKey] = $options[$presetKey] ?? 'default_preset';

            $urlKey = "url_specific_{$i}";
            $buttonUrls[$urlKey] = $options[$urlKey] ?? '';

            $visibilityKey = "visibility_specific_{$i}";
            $buttonVisibilities[$visibilityKey] = $options[$visibilityKey] ?? '1';
        }

        wp_localize_script('floating-button-admin-js', 'ButtonSettings', array(
            'buttonCount' => $numButtons,
            'licenseStatus' => $license_status,
            'presets' => $buttonPresets,
            'urls' => $buttonUrls,
            'visibilities' => $buttonVisibilities
        ));
    }
}
add_action('admin_enqueue_scripts', 'floating_button_admin_scripts');


function floating_button_admin_metabox_styles($hook_suffix)
{
    if ('post.php' == $hook_suffix || 'post-new.php' == $hook_suffix) {
        wp_enqueue_style('floating-button-admin-css', plugins_url('/css/admin-metabox-style.css', __FILE__));
    }
}
add_action('admin_enqueue_scripts', 'floating_button_admin_metabox_styles');


function get_button_color_presets()
{
    return [
        'sunny_gold' => ['label' => __('サニーゴールド', 'floating-button'), 'bg_color' => '#FFD700', 'text_color' => '#4B4B4B'],
        'flame_red' => ['label' => __('フレイムレッド', 'floating-button'), 'bg_color' => '#FF4136', 'text_color' => '#FFFFFF'],
        'citrus_orange' => ['label' => __('シトラスオレンジ', 'floating-button'), 'bg_color' => '#FFA500', 'text_color' => '#4B4B4B'],
        'custom_color' => ['label' => __('カスタムカラー', 'floating-button'), 'bg_color' => '', 'text_color' => '']
    ];
}



function floating_button_menu()
{
    add_menu_page('WP Floating Button', 'WP Floating Button', 'manage_options', 'floating-button-settings', 'floating_button_settings_page', null, 99);
}

add_action('admin_menu', 'floating_button_menu');


function floating_button_settings_page()
{
    $options = get_option('floating_button_settings', []);
    $license_status = get_option('wp_floating_button_license_status', 'invalid');


    // echo '<pre>';
    // var_dump($options); // オプション値を出力
    // echo '</pre>';

?>
    <div class="wrap">
        <div class="floating-button-plugin-header">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="floating-button-plugin-header-options">
                <div class="license-forms">
                    <p class="license-forms-title">Pro版を購入された方は認証してください</p>
                    <?php if ($license_status === 'valid') : ?>
                        <div class="license-revoke-form">
                            <span class="premium-badge"><i class="fas fa-check"></i>ライセンス認証済</span>
                            <!-- <form id="license-revoke" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="revoke_license">
                                <?php wp_nonce_field('revoke_license_action', 'revoke_license_nonce'); ?>
                                <input type="submit" name="swell_license_revoke" class="button-secondary" value="認証解除" />
                            </form> -->
                        </div>
                    <?php else : ?>
                        <div class="license-check-form">
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <input type="hidden" name="action" value="verify_license">
                                <?php wp_nonce_field('verify_license_action', 'verify_license_nonce'); ?>
                                <input type="text" name="license_key" placeholder="ライセンスキーを入力" />
                                <input type="submit" value="認証" class="button button-primary">
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- <div>
                    <form id="reset-settings-form" class="mt-20" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="reset_floating_button_settings">
                        <?php wp_nonce_field('floating_button_reset_settings'); ?>
                        <input type="submit" value="初期設定に戻す" class="button">

                    </form>
                </div> -->
            </div>
        </div>

        <div class="floating-button-plugin-settings-wrap">
            <form id="floating-button-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_floating_button_settings">
                <?php wp_nonce_field('floating_button_save_settings'); ?>


                <h3>ボタン設定</h3>

                <!-- ボタンの個数 -->
                <p>
                    <label for="floating_button_columns"><?php _e('ボタンの個数', 'floating-button'); ?></label>
                    <select id="floating_button_columns" name="floating_button_settings[columns]">
                        <?php
                        for ($i = 1; $i <= 3; $i++) {
                            echo '<option value="' . $i . '"' . selected($options['columns'] ?? '', $i, false) . '>' . $i . '個</option>';
                        }
                        ?>
                    </select>

                </p>

                <!-- ボタンの形状 -->
                <p>
                    <label for="floating_button_design"><?php _e('ボタンの形状', 'floating-button'); ?></label>
                    <select id="floating_button_design" name="floating_button_settings[design]">
                        <option value="default" <?php selected($options['design'] ?? '', 'default'); ?>><?php _e('デフォルト', 'floating-button'); ?></option>
                        <option value="rounded" <?php selected($options['design'] ?? '', 'rounded'); ?>><?php _e('角丸', 'floating-button'); ?></option>
                        <option value="outline" <?php selected($options['design'] ?? '', 'outline'); ?>><?php _e('アウトライン', 'floating-button'); ?></option>
                    </select>

                </p>

                <div class="button-settings-container">
                    <?php for ($i = 1; $i <= 3; $i++) :
                        $use_banner_key = "use_banner_$i";
                        $use_banner = (isset($options[$use_banner_key]) && $options[$use_banner_key] === '1') ? 'checked' : '';
                        $image_id_key = "image_id_$i";
                        $image_id = isset($options[$image_id_key]) ? $options[$image_id_key] : '';
                        $image_url = wp_get_attachment_url($image_id);

                    ?>
                        <div class="floating-button-settings-group floating-button-field-<?php echo $i; ?>">
                            <h4><?php printf(__('ボタン %d 設定', 'floating-button'), $i); ?></h4>

                            <p>
                                <label for="floating_button_link_url_<?php echo $i; ?>"><?php _e('リンク先 URL', 'floating-button'); ?></label>
                                <input type="url" id="floating_button_link_url_<?php echo $i; ?>" name="floating_button_settings[link_url_<?php echo $i; ?>]" value="<?php echo esc_attr($options['link_url_' . $i] ?? 'https://yoshizumi.tech'); ?>" placeholder="<?php _e('URL を入力', 'floating-button'); ?>">
                            </p>
                            <div class="use-banner">
                                <p>
                                    <label for="use_banner_<?php echo $i; ?>"><?php _e('バナー画像を使用する', 'floating-button'); ?></label>
                                    <input type="hidden" name="floating_button_settings[use_banner_<?php echo $i; ?>]" value="0">
                                    <input type="checkbox" id="use_banner_<?php echo $i; ?>" name="floating_button_settings[use_banner_<?php echo $i; ?>]" value="1" <?php echo $use_banner; ?>>
                                </p>
                                <div class="image-upload-settings" style="<?php echo empty($use_banner) ? 'display: none;' : ''; ?>">
                                    <div>
                                        <input type="hidden" id="floating_button_image_id_<?php echo $i; ?>" name="floating_button_settings[image_id_<?php echo $i; ?>]" value="<?php echo esc_attr($image_id); ?>">
                                        <button type="button" id="upload_image_button_<?php echo $i; ?>" class="button"><?php _e('画像を選択', 'floating-button'); ?></button>
                                    </div>
                                </div>
                            </div>
                            <div class="image-upload-settings" style="<?php echo empty($use_banner) ? 'display: none;' : ''; ?>">
                                <div id="image_preview_<?php echo $i; ?>" class="image-preview">
                                    <?php if ($image_url) : ?>
                                        <img src="<?php echo esc_url($image_url); ?>">
                                        <button type="button" id="remove_image_button_<?php echo $i; ?>" class="remove-image-button">×</button>
                                    <?php endif; ?>
                                </div>
                                <p class="recommended-size">(推奨バナーサイズ: 幅500px 高さ100px)</p>
                            </div>
                            <div class="button-text-settings" style="<?php echo !empty($use_banner) ? 'display: none;' : ''; ?>">
                                <p>
                                    <label><?php _e('アイコン', 'floating-button'); ?></label>
                                <div class="floating-button-icons">
                                    <div>
                                        <input type="radio" id="icon_envelope_<?php echo $i; ?>" name="floating_button_settings[icon_<?php echo $i; ?>]" value="fa-envelope" <?php checked($options['icon_' . $i] ?? '', 'fa-envelope'); ?>>
                                        <label for="icon_envelope_<?php echo $i; ?>"><i class="fa fa-envelope"></i></label>
                                    </div>
                                    <div>
                                        <input type="radio" id="icon_phone_<?php echo $i; ?>" name="floating_button_settings[icon_<?php echo $i; ?>]" value="fa-phone" <?php checked($options['icon_' . $i] ?? '', 'fa-phone'); ?>>
                                        <label for="icon_phone_<?php echo $i; ?>"><i class="fa fa-phone"></i></label>
                                    </div>
                                    <div>
                                        <input type="radio" id="icon_chat_<?php echo $i; ?>" name="floating_button_settings[icon_<?php echo $i; ?>]" value="fa-comment" <?php checked($options['icon_' . $i] ?? '', 'fa-comment'); ?>>
                                        <label for="icon_chat_<?php echo $i; ?>"><i class="fa fa-comment"></i></label>
                                    </div>
                                    <div>
                                        <input type="radio" id="icon_user_<?php echo $i; ?>" name="floating_button_settings[icon_<?php echo $i; ?>]" value="fa-user" <?php checked($options['icon_' . $i] ?? '', 'fa-user'); ?>>
                                        <label for="icon_user_<?php echo $i; ?>"><i class="fa fa-user"></i></label>
                                    </div>
                                    <div>
                                        <input type="radio" id="icon_video_<?php echo $i; ?>" name="floating_button_settings[icon_<?php echo $i; ?>]" value="fa-video" <?php checked($options['icon_' . $i] ?? '', 'fa-video'); ?>>
                                        <label for="icon_video_<?php echo $i; ?>"><i class="fa fa-video"></i></label>
                                    </div>
                                    <div>
                                        <input type="radio" id="icon_none_<?php echo $i; ?>" name="floating_button_settings[icon_<?php echo $i; ?>]" value="" <?php checked($options['icon_' . $i] ?? '', ''); ?>>
                                        <label for="icon_none_<?php echo $i; ?>"><?php _e('なし', 'floating-button'); ?></label>
                                    </div>
                                </div>
                                </p>
                                <p>
                                    <label for="floating_button_text_<?php echo $i; ?>"><?php _e('ボタンテキスト', 'floating-button'); ?></label>
                                    <input type="text" id="floating_button_text_<?php echo $i; ?>" name="floating_button_settings[text_<?php echo $i; ?>]" value="<?php echo esc_attr($options['text_' . $i] ?? 'クリックしてください'); ?>" placeholder="<?php _e('テキストを入力', 'floating-button'); ?>">
                                </p>
                                <div class="preset-color">
                                    <label for="floating_button_preset_<?php echo $i; ?>"><?php _e('ボタンカラー', 'floating-button'); ?></label>
                                    <select id="floating_button_preset_<?php echo $i; ?>" name="floating_button_settings[preset_<?php echo $i; ?>]" class="preset-color-selector">
                                        <?php
                                        $presets = get_button_color_presets();
                                        foreach ($presets as $key => $colors) {
                                            $disabled = ($key === 'custom_color' && $license_status !== 'valid') ? 'disabled' : '';
                                            echo '<option value="' . esc_attr($key) . '" ' . $disabled . '>' . esc_html($colors['label']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <?php if ($license_status !== 'valid') : ?>
                                        <p class="mt-10"><a href="https://wpzen.jp" class="premium-link" target="_blank">🔒カスタムカラーはPro限定</a></p>
                                    <?php endif; ?>
                                </div>
                                <div class="custom-color" <?php if ($license_status !== 'valid') echo 'style="display:none;"'; ?>>
                                    <p>
                                        <label for="floating_button_text_color_<?php echo $i; ?>"><?php _e('文字色', 'floating-button'); ?></label>
                                        <input type="text" class="my-color-field" id="floating_button_text_color_<?php echo $i; ?>" name="floating_button_settings[text_color_<?php echo $i; ?>]" value="<?php echo esc_attr($options['text_color_' . $i] ?? '#FFFFFF'); ?>">
                                    </p>
                                    <p>
                                        <label for="floating_button_bg_color_<?php echo $i; ?>"><?php _e('背景色', 'floating-button'); ?></label>
                                        <input type="text" class="my-color-field" id="floating_button_bg_color_<?php echo $i; ?>" name="floating_button_settings[bg_color_<?php echo $i; ?>]" value="<?php echo esc_attr($options['bg_color_' . $i] ?? '#000000'); ?>">
                                    </p>
                                </div>
                                <h4 style="margin-top: 40px;"><?php _e('特定ページ用設定', 'floating-button'); ?></h4>
                                <p>
                                    <label for="floating_button_page_id_<?php echo $i; ?>"><?php _e('ページID（複数の場合はカンマ区切り）', 'floating-button'); ?></label>
                                    <input type="text" id="floating_button_page_id_<?php echo $i; ?>" name="floating_button_settings[page_ids_<?php echo $i; ?>]" value="<?php echo esc_attr($options['page_ids_' . $i] ?? ''); ?>" placeholder="<?php _e('ページIDを入力', 'floating-button'); ?>">
                                </p>
                                <p>
                                    <label for="floating_button_text_specific_<?php echo $i; ?>"><?php _e('特定ページ用ボタンテキスト', 'floating-button'); ?></label>
                                    <input type="text" id="floating_button_text_specific_<?php echo $i; ?>" name="floating_button_settings[text_specific_<?php echo $i; ?>]" value="<?php echo esc_attr($options['text_specific_' . $i] ?? ''); ?>" placeholder="<?php _e('テキストを入力', 'floating-button'); ?>">
                                </p>
                                <p>
                                    <label for="floating_button_url_specific_<?php echo $i; ?>"><?php _e('特定ページ用URL', 'floating-button'); ?></label>
                                    <input type="url" id="floating_button_url_specific_<?php echo $i; ?>" name="floating_button_settings[url_specific_<?php echo $i; ?>]" value="<?php echo esc_attr($options['url_specific_' . $i] ?? ''); ?>" placeholder="<?php _e('URLを入力', 'floating-button'); ?>">
                                </p>
                                <p>
                                    <label for="floating_button_visibility_specific_<?php echo $i; ?>"><?php _e('特定ページでボタンを表示する', 'floating-button'); ?></label>
                                    <input type="hidden" name="floating_button_settings[visibility_specific_<?php echo $i; ?>]" value="0">
                                    <input type="checkbox" id="floating_button_visibility_specific_<?php echo $i; ?>" name="floating_button_settings[visibility_specific_<?php echo $i; ?>]" value="1" <?php checked($options['visibility_specific_' . $i] ?? '', '1'); ?>>
                                </p>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>


                <h3 class="mt-40">詳細設定</h3>

                <div class="mt-30">
                    <h4>コンテナ設定</h4>
                    <!-- コンテナの背景色 -->
                    <p>
                        <label for="floating_button_container_bg_color"><?php _e('コンテナの背景色', 'floating-button'); ?></label>
                        <input style="width:80px" type="text" id="floating_button_container_bg_color" name="floating_button_settings[container_bg_color]" value="<?php echo esc_attr($options['container_bg_color'] ?? '#eeeeee'); ?>" class="my-color-field" <?php echo ($license_status !== 'valid') ? 'disabled' : ''; ?> />
                        <?php if ($license_status !== 'valid') : ?>
                            <a href="https://wpzen.jp" class="premium-link" target="_blank">🔒Pro限定</a>
                            <span style="font-style: italic;">Pro版ではカラーピッカーでの色指定が可能です。</span>
                        <?php endif; ?>
                    </p>

                    <!-- 閉じるボタンの表示 -->
                    <p>
                        <label for="floating_button_close_button"><?php _e('閉じるボタンを表示する', 'floating-button'); ?></label>
                        <input type="hidden" name="floating_button_settings[close_button]" value="0">
                        <input type="checkbox" id="floating_button_close_button" name="floating_button_settings[close_button]" value="1" <?php checked($options['close_button'] ?? '', '1'); ?>>

                    </p>
                </div>

                <div class="mt-30">
                    <h4>マイクロコピー設定</h4>

                    <!-- マイクロコピー -->
                    <p>
                        <label for="floating_button_microcopy"><?php _e('マイクロコピー', 'floating-button'); ?></label>
                        <input type="text" id="floating_button_microcopy" class="microcopy-input" name="floating_button_settings[microcopy]" value="<?php echo esc_attr($options['microcopy'] ?? ''); ?>" placeholder="<?php _e('マイクロコピーを入力', 'floating-button'); ?>">
                    </p>

                    <!-- マイクロコピーの位置設定 -->
                    <p>
                        <label for="floating_button_microcopy_position"><?php _e('マイクロコピーの位置', 'floating-button'); ?></label>
                        <select id="floating_button_microcopy_position" name="floating_button_settings[microcopy_position]">
                            <option value="left" <?php selected($options['microcopy_position'] ?? 'left', 'left'); ?>><?php _e('左', 'floating-button'); ?></option>
                            <option value="right" <?php selected($options['microcopy_position'] ?? 'right', 'right'); ?>><?php _e('右', 'floating-button'); ?></option>
                            <option value="top" <?php selected($options['microcopy_position'] ?? 'top', 'top'); ?>><?php _e('上', 'floating-button'); ?></option>
                            <option value="bottom" <?php selected($options['microcopy_position'] ?? 'bottom', 'bottom'); ?>><?php _e('下', 'floating-button'); ?></option>
                        </select>

                    </p>
                </div>
                <div class="mt-30">
                    <h4>デバイス表示設定</h4>
                    <p>チェックを入れたデバイスでボタンが表示されます。</p>
                    <div class="device-settings">
                        <?php
                        $devices = [
                            'desktop' => __('PC', 'floating-button'),
                            'tablet' => __('タブレット', 'floating-button'),
                            'mobile' => __('スマホ', 'floating-button')
                        ];
                        foreach ($devices as $device => $label) {
                            $device_option = isset($options['display_on_' . $device]) ? $options['display_on_' . $device] : '1';  // デフォルトは1 (表示)
                        ?>
                            <div class="device-setting">
                                <input type="hidden" name="floating_button_settings[display_on_<?php echo $device; ?>]" value="0">
                                <label for="floating_button_display_on_<?php echo $device; ?>"><?php echo $label; ?></label>
                                <input type="checkbox" id="floating_button_display_on_<?php echo $device; ?>" name="floating_button_settings[display_on_<?php echo $device; ?>]" value="1" <?php checked($device_option, '1'); ?>>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </div>


                <div class="mt-40">
                    <input class="button button-primary" type="submit" value="設定を保存">
                </div>

            </form>


        </div>
    </div>

<?php
}


function handle_save_floating_button_settings()
{
    if (!current_user_can('manage_options')) {
        wp_die('アクセス権がありません');
    }

    check_admin_referer('floating_button_save_settings');
    $posted_data = $_POST['floating_button_settings'];

    $existing_options = get_option('floating_button_settings', []);
    $presets = get_button_color_presets();

    $sanitized_data = [];
    foreach ($posted_data as $key => $value) {
        if (strpos($key, 'image_id_') === 0) {
            $sanitized_data[$key] = intval($value);
        } elseif (strpos($key, 'bg_color_') === 0 || strpos($key, 'text_color_') === 0) {
            $index = explode('_', $key)[2]; // bg_color_1 -> 1
            $preset_key = 'preset_' . $index;
            if ($posted_data[$preset_key] === 'custom_color') {
                update_option('custom_color_' . $key, $value);
                $sanitized_data[$key] = sanitize_hex_color($value);
            } else {
                $colors = $presets[$posted_data[$preset_key]] ?? null;
                if ($colors) {
                    $sanitized_data[$key] = ($key === 'bg_color_' . $index) ? $colors['bg_color'] : $colors['text_color'];
                }
            }
        } else {
            $sanitized_data[$key] = sanitize_text_field($value);
        }
    }

    update_option('floating_button_settings', array_merge($existing_options, $sanitized_data));

    $redirect_url = add_query_arg([
        'page' => 'floating-button-settings',
        'message' => 'settings_updated'
    ], admin_url('admin.php'));
    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_save_floating_button_settings', 'handle_save_floating_button_settings');

function reset_floating_button_settings_core()
{

    // ユーザー権限をチェック
    if (!current_user_can('manage_options')) {
        wp_die('アクセス権がありません');
    }
    // デフォルト設定を定義（必要に応じて変更）
    $default_settings = [
        'container_bg_color' => '#EEEEEE',
        'close_button' => '1',
        'microcopy' => 'お気軽にお問い合わせください',
        'microcopy_position' => 'left',
        'columns' => '1',
        'design' => 'default',
        'display_on_mobile' => '1',
        'display_on_tablet' => '1',
        'display_on_desktop' => '1'


    ];

    // 各ボタンのデフォルト設定
    for ($i = 1; $i <= 3; $i++) {
        $default_settings["use_banner_$i"] = '0'; // バナー画像を使用しない
        $default_settings["image_id_$i"] = ''; // 画像IDを空に
        $default_settings["link_url_$i"] = ''; // デフォルトURL
        $default_settings["icon_1"] = 'fa-envelope';
        $default_settings["icon_2"] = 'fa-phone';
        $default_settings["icon_3"] = 'fa-comment';
        $default_settings["text_1"] = 'メールで問い合わせ';
        $default_settings["text_2"] = '電話で問い合わせ';
        $default_settings["text_3"] = 'チャットで問い合わせ';
        $default_settings["text_color_$i"] = '#333333'; // デフォルト文字色
        $default_settings["bg_color_$i"] = '#C9B037'; // デフォルト背景色
    }

    // オプションの更新
    update_option('floating_button_settings', $default_settings);
}
function reset_floating_button_settings()
{

    check_admin_referer('floating_button_reset_settings');

    reset_floating_button_settings_core(); // コア機能を呼び出し

    // 設定ページにリダイレクト（リセットメッセージを表示）
    $redirect_url = add_query_arg([
        'page' => 'floating-button-settings',
        'message' => 'settings_reset'
    ], admin_url('admin.php'));
    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_reset_floating_button_settings', 'reset_floating_button_settings');


function floating_button_reset_click_count_callback($args)
{
    $id = $args['id'];
    echo '<div class="floating-button-field floating-button-field-' . $id . ' reset-click-count">';
    echo '<button type="button" class="button reset-click-count-button" data-button-id="' . $id . '">Reset Click Count ' . $id . '</button>';
    echo '</div>';
}


add_action('wp_ajax_reset_button_click_count', 'reset_button_click_count');
function reset_button_click_count()
{
    if (isset($_POST['button_id'])) {
        $button_id = intval($_POST['button_id']);
        update_option('button_' . $button_id . '_click_count', 0);  // クリック数をリセット

        wp_send_json_success('Click count reset successfully');
    } else {
        wp_send_json_error('Failed to reset click count');
    }
}

// クリックイベントを記録する関数
function handle_button_click()
{
    if (isset($_POST['button_id'])) {
        $button_id = intval($_POST['button_id']);
        $click_count = get_option('button_' . $button_id . '_click_count', 0);
        $click_count++;
        update_option('button_' . $button_id . '_click_count', $click_count);

        wp_send_json_success(array('click_count' => $click_count));
    } else {
        wp_send_json_error('No button ID provided');
    }
}

// AJAXアクションをフック
add_action('wp_ajax_record_button_click', 'handle_button_click');
add_action('wp_ajax_nopriv_record_button_click', 'handle_button_click'); // ログインしていないユーザーもアクセス可能

function verify_license_key()
{
    if (!isset($_POST['verify_license_nonce']) || !wp_verify_nonce($_POST['verify_license_nonce'], 'verify_license_action')) {
        wp_die('Nonceの検証に失敗しました。');
    }

    $license_key = sanitize_text_field($_POST['license_key']);
    $api_url = 'https://api.wpzen.jp/license_check.php';

    $response = wp_remote_post($api_url, [
        'body' => ['license_key' => $license_key]
    ]);

    if (is_wp_error($response)) {
        error_log('APIリクエストに失敗しました: ' . $response->get_error_message());
        wp_die('APIリクエストに失敗しました: ' . $response->get_error_message());
    }

    $response_body = wp_remote_retrieve_body($response);
    $result = json_decode($response_body);

    if (isset($result->status) && $result->status === 'success') {
        update_option('wp_floating_button_license_key', $license_key);
        update_option('wp_floating_button_license_status', 'valid');
        $redirect_url = add_query_arg('license_status', 'valid', wp_get_referer());
    } else {
        update_option('wp_floating_button_license_status', 'invalid');
        $redirect_url = add_query_arg('license_status', 'invalid', wp_get_referer());
    }

    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_verify_license', 'verify_license_key');

function handle_revoke_license()
{
    if (!isset($_POST['revoke_license_nonce']) || !wp_verify_nonce($_POST['revoke_license_nonce'], 'revoke_license_action')) {
        wp_die('Nonce verification failed.');
    }

    // ライセンス状態を無効にする
    update_option('wp_floating_button_license_status', 'invalid');

    // 設定をデフォルトにリセットする
    reset_floating_button_settings_core();  // 既存のリセット機能を再利用

    // リダイレクトURLにライセンスの状態を追加
    $redirect_url = add_query_arg('license_status', 'invalid', admin_url('admin.php?page=floating-button-settings'));

    // 設定ページにリダイレクト
    wp_redirect($redirect_url);
    exit;
}
add_action('admin_post_revoke_license', 'handle_revoke_license');


function display_license_validation_message()
{
    if (isset($_GET['license_status'])) {
        if ($_GET['license_status'] == 'valid') {
            echo '<div class="notice notice-success is-dismissible"><p>ライセンス認証を完了しました。</p></div>';
        } elseif ($_GET['license_status'] == 'invalid') {
            echo '<div class="notice notice-error is-dismissible"><p>ライセンス認証を解除しました。</p></div>';
        }
    }
}
add_action('admin_notices', 'display_license_validation_message');
