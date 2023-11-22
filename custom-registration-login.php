<?php
/*
Plugin Name: Custom Registration and Role Switching
Description: A custom registration and role switching plugin.
Version: 1.0
Author: vikum rajeewa
*/

// Function to create the table on plugin activation
function custom_registration_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_registration_data';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        username varchar(50) NOT NULL,
        first_name varchar(50) NOT NULL,
        last_name varchar(50) NOT NULL,
        email varchar(100) NOT NULL,
        user_type varchar(20) NOT NULL,
        bio text,
        interest text,
        language varchar(20),
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}