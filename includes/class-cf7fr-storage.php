<?php

if (!defined('ABSPATH'))
    exit;

class CF7FR_Storage
{

    public static function init()
    {
        add_action('wpcf7_before_send_mail', [__CLASS__, 'save_message']);
    }

    public static function save_message($contact_form)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'cf7fr_messages';

        $submission = WPCF7_Submission::get_instance();
        if (!$submission)
            return;

        // كل البيانات اللي CF7 بعتها
        $posted = $submission->get_posted_data();

        // فلترة الحقول الداخلية
        $fields = [];
        foreach ($posted as $key => $value) {
            if (strpos($key, '_') === 0)
                continue;
            $fields[$key] = $value;
        }

        // Meta زي Flamingo
        $remote_ip = $submission->get_meta('remote_ip');
        $user_agent = $submission->get_meta('user_agent');
        $url = $submission->get_meta('url');

        // تخزين الرسالة
        $wpdb->insert($table, [
            'fields_json' => wp_json_encode($fields),
            'remote_ip' => $remote_ip,
            'user_agent' => $user_agent,
            'url' => $url,
            'uuid' => wp_generate_uuid4(),
            'created_at' => current_time('mysql')
        ]);
    }
}