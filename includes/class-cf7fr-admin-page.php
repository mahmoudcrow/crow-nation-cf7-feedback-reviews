<?php

if (!defined('ABSPATH'))
    exit;

class CF7FR_Admin_Page
{

    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'register_page']);
    }

    public static function register_page()
    {
        add_menu_page(
            'Feedback Reviews',
            'Reviews',
            'manage_options',
            'cf7fr-reviews',
            [__CLASS__, 'render_page'],
            'dashicons-star-filled',
            25
        );
    }

    public static function render_page(): void
    {
        $active_tab = $_GET['tab'] ?? 'reviews';

        echo '<div class="wrap"><h1>Feedback Reviews</h1>';

        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=cf7fr-reviews&tab=reviews" class="nav-tab ' . ($active_tab === 'reviews' ? 'nav-tab-active' : '') . '">Reviews</a>';
        echo '<a href="?page=cf7fr-reviews&tab=settings" class="nav-tab ' . ($active_tab === 'settings' ? 'nav-tab-active' : '') . '">Settings</a>';
        echo '</nav>';

        if ($active_tab === 'reviews') {
            self::render_reviews();
        } else {
            CF7FR_Settings::render_settings();
        }

        echo '</div>';
    }

    public static function render_reviews(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'cf7fr_messages';
        $reviews = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

        echo '<div class="cf7fr-grid">';

        foreach ($reviews as $review) {
            self::render_single_card($review);
        }

        echo '</div>';
    }

    public static function render_single_card($review): void
    {
        $fields = json_decode($review->fields_json, true);

        $name = $fields['your-name'] ?? 'Anonymous';
        $rating = intval($fields['rating'] ?? 0);
        $testimonial = $fields['testimonial'] ?? '';
        $email = $fields['your-email'] ?? '';
        $phone = $fields['phone-number'] ?? '';
        $service = $fields['service-type'] ?? '';

        echo "
    <div class='cf7fr-card' id='cf7fr-card-{$review->id}'>

        <div class='cf7fr-card-header'>
            <div>
                <h2 class='cf7fr-name'>" . esc_html($name) . "</h2>
                <div class='cf7fr-rating'>" . str_repeat('★', $rating) . "</div>
            </div>
            <button class='cf7fr-download-btn download-btn' data-id='{$review->id}'>
                <span class='dashicons dashicons-download'></span>
            </button>
        </div>

        <div class='cf7fr-card-body'>
            <table class='cf7fr-fields'>
                <tr><th>Email</th><td>" . esc_html($email) . "</td></tr>
                <tr><th>Phone</th><td>" . esc_html($phone) . "</td></tr>
                <tr><th>Service</th><td>" . esc_html($service) . "</td></tr>";

        foreach ($fields as $key => $value) {
            if (in_array($key, ['your-name', 'rating', 'testimonial', 'your-email', 'phone-number', 'service-type']))
                continue;

            echo "<tr><th>" . esc_html($key) . "</th><td>" . esc_html($value) . "</td></tr>";
        }

        echo "
            </table>

            <div class='cf7fr-testimonial'>
                <p>" . esc_html($testimonial) . "</p>
            </div>
        </div>

        <div class='cf7fr-card-footer'>
            <span><strong>IP:</strong> {$review->remote_ip}</span>
            <span><strong>Date:</strong> {$review->created_at}</span>
            <span><strong>UUID:</strong> {$review->uuid}</span>
            <span><strong>URL:</strong> {$review->url}</span>
        </div>

    </div>";
    }

}