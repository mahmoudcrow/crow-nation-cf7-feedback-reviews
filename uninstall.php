<?php
// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// حذف الإعدادات
delete_option('cf7_selected_form');