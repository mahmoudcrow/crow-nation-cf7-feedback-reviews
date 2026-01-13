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

    public static function render_page()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'cf7fr_messages';
        $reviews = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");

        echo '<div class="wrap"><h1>Feedback Reviews</h1>';
        echo '<div class="cf7fr-grid">';

        foreach ($reviews as $review) {
            $fields = json_decode($review->fields_json, true);

            $name = $fields['your-name'] ?? '';
            $rating = $fields['rating'] ?? '';
            $testimonial = $fields['testimonial'] ?? '';

            echo "
            <div class='cf7fr-card'>
                <h2>$name</h2>
                <p class='stars'>" . str_repeat('★', intval($rating)) . "</p>
                <p class='feedback'>$testimonial</p>
                <span class='date'>{$review->created_at}</span>
            </div>";
        }

        echo '</div></div>';
    }
}