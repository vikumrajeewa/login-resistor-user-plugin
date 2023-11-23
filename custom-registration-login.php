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

register_activation_hook(__FILE__, 'custom_registration_create_table');

// Enqueue the CSS for role distinctions
function custom_user_management_enqueue_styles() {
   wp_enqueue_style('custom-user-management', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('wp_enqueue_scripts', 'custom_user_management_enqueue_styles');


// Handle user registration
function custom_user_registration_form() {
    if (isset($_POST['custom_register'])) {
        $username = sanitize_user($_POST['username']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];
        $user_type = sanitize_text_field($_POST['user_type']);

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            echo 'Registration failed. Please try again.';
        } else {
            update_user_meta($user_id, 'first_name', $first_name);
            update_user_meta($user_id, 'last_name', $last_name);
            update_user_meta($user_id, 'bio', sanitize_textarea_field($_POST['bio']));
            update_user_meta($user_id, 'interest', sanitize_textarea_field($_POST['interest']));
            update_user_meta($user_id, 'language', sanitize_text_field($_POST['language']));

            $default_role = 'subscriber';
            if ($user_type === 'self_publisher') {
                $default_role = 'self_publisher';
            } elseif ($user_type === 'paid_subscriber') {
                echo 'You cannot register as Paid Subscriber. Contact the administrator.';
                return;
            }

            $user = new WP_User($user_id);
            $user->set_role($default_role);

            if (isset($_FILES['profile_photo'])) {
                $file = $_FILES['profile_photo'];
                $upload_overrides = array('test_form' => false);
                $upload_info = wp_handle_upload($file, $upload_overrides);

                if (!empty($upload_info['file'])) {
                    update_user_meta($user_id, 'profile_photo', $upload_info['url']);
                }
            }
