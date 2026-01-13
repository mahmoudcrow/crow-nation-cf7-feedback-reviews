<?php
/*
Plugin Name: Crow Nation CF7 Feedback Reviews
Plugin URI: https://github.com/mahmoudcrow/crow-nation-cf7-feedback-reviews
Description: Display Contact Form 7 feedback submissions (stored via Flamingo) as modern review cards in the WordPress admin, with form selection and PNG download.
Version: 1.0
Author: Mahmoud Moustafa
Author URI: https://github.com/mahmoudcrow
*/
/*
|--------------------------------------------------------------------------
|  تنبيهات لو الإضافات المطلوبة مش مفعّلة
|--------------------------------------------------------------------------
*/
if (!defined('ABSPATH')) {
    exit;
}

// تحميل ملف الـ Activator
require_once plugin_dir_path(__FILE__) . 'includes/class-cf7fr-activator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-cf7fr-storage.php';
CF7FR_Storage::init();
require_once plugin_dir_path(__FILE__) . 'includes/class-cf7fr-admin-page.php';
CF7FR_Admin_Page::init();
require_once plugin_dir_path(__FILE__) . 'includes/class-cf7fr-settings.php';
CF7FR_Settings::init();

// Hook التفعيل
register_activation_hook(__FILE__, ['CF7FR_Activator', 'activate']);
add_action('admin_init', function () {
    // check Contact Form 7
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>CF7 Feedback Reviews:</strong> This plugin requires <strong>Contact Form 7</strong> to be installed and activated.</p></div>';
        });
    }

    // check Flamingo (اختياري لكن مهم)
    if (!is_plugin_active('flamingo/flamingo.php')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning"><p><strong>CF7 Feedback Reviews:</strong> For best results, please install and activate the <strong>Flamingo</strong> plugin to store CF7 submissions.</p></div>';
        });
    }
});

/*
|--------------------------------------------------------------------------
|  صفحات الـ Admin (Reviews + Settings)
|--------------------------------------------------------------------------
*/

add_action('admin_menu', 'cf7fr_register_admin_pages');

function cf7fr_register_admin_pages()
{
    // صفحة الكروت
    add_menu_page(
        'Feedback Reviews',
        'Reviews',
        'manage_options',
        'cf7-feedback-reviews',
        'cf7fr_render_reviews_page',
        'dashicons-star-filled',
        25
    );

    // صفحة الإعدادات
    add_submenu_page(
        'cf7-feedback-reviews',
        'CF7 Reviews Settings',
        'Settings',
        'manage_options',
        'cf7-reviews-settings',
        'cf7fr_render_settings_page'
    );
}
// GitHub Auto Update
add_action('plugins_loaded', function () {
    $update_lib = plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

    if (file_exists($update_lib)) {
        require_once $update_lib;

        if (class_exists('Puc_v4_Factory')) {
            $updateChecker = Puc_v4_Factory::buildUpdateChecker(
                'https://github.com/mahmoudcrow/crow-nation-cf7-feedback-reviews/',
                __FILE__,
                'cf7-feedback-reviews'
            );

            $updateChecker->setBranch('main');
        }
    }
});
/*
|--------------------------------------------------------------------------
|  صفحة الإعدادات: اختيار الفورم
|--------------------------------------------------------------------------
*/

function cf7fr_render_settings_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // حفظ اختيار الفورم
    if (isset($_POST['cf7_selected_form']) && check_admin_referer('cf7fr_save_settings', 'cf7fr_nonce')) {
        update_option('cf7_selected_form', sanitize_text_field($_POST['cf7_selected_form']));
        echo '<div class="updated"><p>Settings saved.</p></div>';
    }

    // جلب الفورمات من Contact Form 7
    $pages = get_pages([
        'sort_column' => 'post_title',
        'sort_order' => 'ASC'
    ]);

    $selected = get_option('cf7_selected_form');

    echo '<div class="wrap"><h1>CF7 Reviews Settings</h1>';

    if (empty($pages)) {
        echo '<p>No Pages found. Please create a form first and insert it to page.</p></div>';
        return;
    }

    echo '<form method="post">';
    wp_nonce_field('cf7fr_save_settings', 'cf7fr_nonce');

    echo '<table class="form-table">
            <tr>
                <th scope="row"><label for="cf7_selected_form">Select Contact Form</label></th>
                <td>
                    <select name="cf7_selected_form" id="cf7_selected_form">';
    foreach ($pages as $page) {
        $sel = ($selected == $page->ID) ? 'selected' : '';
        echo "<option value='{$page->ID}' $sel>{$page->post_title}</option>";
    }

    echo '</select>
                    <p class="description">Choose which Contact Form 7 submissions Page to display as reviews.</p>
                </td>
            </tr>
          </table>';

    submit_button('Save Settings');

    echo '</form></div>';
}

/*
|--------------------------------------------------------------------------
|  صفحة الريفيوهات: عرض الكروت
|--------------------------------------------------------------------------
|
|  تعتمد على Flamingo:
|  - posts type: flamingo_inbound
|  - post_parent = ID الفورم المختار
|  - حقول الميتا:
|    _field_your-name
|    _field_your-email
|    _field_phone-number
|    _field_service-type
|    _field_rating
|    _field_testimonial
|
|--------------------------------------------------------------------------
*/

function cf7fr_render_reviews_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;

    $selected_form = get_option('cf7_selected_form');

    echo '<div class="wrap"><h1>Feedback Reviews</h1>';

    if (empty($selected_form)) {
        echo '<p>No Page selected yet. Please go to <strong>Reviews → Settings</strong> and choose a Feedback Page.</p></div>';
        return;
    }

    // جلب رسائل Flamingo الخاصة بالفورم المختار
    $selected_form = intval($selected_form);

    $messages = $wpdb->get_results($wpdb->prepare("
    SELECT p.* 
    FROM {$wpdb->prefix}posts p
    INNER JOIN {$wpdb->prefix}postmeta m 
        ON p.ID = m.post_id
    WHERE p.post_type = 'flamingo_inbound'
    AND m.meta_key = '_cf7fr_page_id'
    AND CAST(m.meta_value AS UNSIGNED) = %d
    ORDER BY p.post_date DESC
", $selected_form));


    if (empty($messages)) {
        echo '<p>No reviews found yet for the selected Page.</p></div>';
        return;
    }

    echo '<p>Showing reviews for form ID: <strong>' . esc_html($selected_form) . '</strong></p>';

    echo '<div class="cf7-reviews-grid">';

    foreach ($messages as $msg) {
        $meta = get_post_meta($msg->ID);

        $name = isset($meta['_field_your-name'][0]) ? esc_html($meta['_field_your-name'][0]) : '';
        $email = isset($meta['_field_your-email'][0]) ? esc_html($meta['_field_your-email'][0]) : '';
        $phone = isset($meta['_field_phone-number'][0]) ? esc_html($meta['_field_phone-number'][0]) : '';
        $service = isset($meta['_field_service-type'][0]) ? esc_html($meta['_field_service-type'][0]) : '';
        $rating = isset($meta['_field_rating'][0]) ? intval($meta['_field_rating'][0]) : 0;
        $feedback = isset($meta['_field_testimonial'][0]) ? esc_html($meta['_field_testimonial'][0]) : '';
        $date = esc_html($msg->post_date);

        if ($rating < 0)
            $rating = 0;
        if ($rating > 5)
            $rating = 5;

        $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

        echo "
        <div class='cf7-review-card' id='cf7-review-{$msg->ID}'>
            <div class='cf7-review-header'>
                <h2>{$name}</h2>
                <span class='cf7-review-service'>{$service}</span>
            </div>
            <p><strong>Phone:</strong> {$phone}</p>
            <p><strong>Email:</strong> {$email}</p>
            <p class='stars'>{$stars}</p>
            <p class='feedback-text'>{$feedback}</p>
            <span class='date'>{$date}</span>
            <button type='button' class='button download-btn' data-id='{$msg->ID}'>Download as PNG</button>
        </div>";
    }

    echo '</div></div>';
}

/*
|--------------------------------------------------------------------------
|  Admin CSS للكروت
|--------------------------------------------------------------------------
*/

add_action('admin_head', function () {
    $screen = get_current_screen();
    if (!$screen || ($screen->id !== 'toplevel_page_cf7-feedback-reviews')) {
        return;
    }

    echo "
    <style>
    .cf7-reviews-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .cf7-review-card {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border-left: 5px solid #4a90e2;
        transition: 0.3s;
        position: relative;
    }
    .cf7-review-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    .cf7-review-card h2 {
        margin: 0 0 5px;
        font-size: 20px;
    }
    .cf7-review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .cf7-review-service {
        background: #4a90e2;
        color: #fff;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 12px;
    }
    .cf7-review-card .stars {
        font-size: 22px;
        color: #f7b500;
        margin: 10px 0;
    }
    .cf7-review-card .feedback-text {
        margin: 10px 0;
        font-style: italic;
    }
    .cf7-review-card .date {
        display: block;
        margin-top: 10px;
        font-size: 12px;
        color: #777;
    }
    .cf7-review-card .download-btn {
        margin-top: 10px;
    }
    </style>
    ";
});

/*
|--------------------------------------------------------------------------
|  تحميل html2canvas + سكريبت Download كصورة
|--------------------------------------------------------------------------
*/

add_action('admin_enqueue_scripts', function ($hook) {
    // نحمل السكريبت بس في صفحة الريفيوهات
    if ($hook !== 'toplevel_page_cf7-feedback-reviews') {
        return;
    }

    wp_enqueue_script(
        'html2canvas',
        'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
        [],
        '1.4.1',
        true
    );
});

add_action('flamingo_inbound_message_after_save', function ($message) {

    // ID الرسالة
    $msg_id = $message->id;

    // نولّد كود أوتو جينيريتد
    $unique_code = wp_generate_uuid4();

    // نخزّنه في postmeta
    update_post_meta($msg_id, '_cf7fr_auto_code', $unique_code);

});

add_action('flamingo_inbound_message_after_save', function ($message) {

    // ID الرسالة
    $msg_id = $message->id;

    // URL اللي Flamingo سجّله
    $url = isset($message->fields['url']) ? $message->fields['url'] : '';

    // نجيب Page ID من الـ URL
    $page_id = url_to_postid($url);

    // نخزّنه في postmeta
    update_post_meta($msg_id, '_cf7fr_page_id', $page_id);

});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'toplevel_page_cf7fr-reviews') {
        wp_enqueue_style('cf7fr-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css');
        wp_enqueue_script('cf7fr-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', [], false, true);
    }
});

add_action('admin_enqueue_scripts', function ($hook) {

    // تحميل السكربتات في صفحة الريفيوهات فقط
    if ($hook === 'toplevel_page_cf7fr-reviews') {

        // تحميل مكتبة html2canvas
        wp_enqueue_script(
            'html2canvas',
            'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
            [],
            '1.4.1',
            true
        );

        // تحميل سكربت الإضافة
        wp_enqueue_script(
            'cf7fr-admin',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            ['html2canvas'], // تحميل admin.js بعد html2canvas
            false,
            true
        );

        // تحميل CSS
        wp_enqueue_style(
            'cf7fr-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css'
        );
    }
});

add_action('admin_footer', function () {
    $screen = get_current_screen();
    if (!$screen || ($screen->id !== 'toplevel_page_cf7-feedback-reviews')) {
        return;
    }

    ?>
    <script>
        (function () {
            document.addEventListener('click', function (e) {
                if (!e.target.classList.contains('download-btn')) return;

                var btn = e.target;
                var id = btn.getAttribute('data-id');
                var card = document.getElementById('cf7-review-' + id);

                if (!card) return;

                html2canvas(card, {
                    backgroundColor: '#ffffff',
                    scale: 2
                }).then(function (canvas) {
                    var link = document.createElement('a');
                    link.download = 'review-' + id + '.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                });
            });
        })();
    </script>

    <?php
});