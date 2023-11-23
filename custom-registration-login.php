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
                echo 'You cannot register as a Paid Subscriber. Contact the administrator.';
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

            $email_subject = 'Welcome to our site';
            $email_message = 'Thank you for registering as a subscriber.';
            wp_mail($email, $email_subject, $email_message);

            $redirect_url = wp_login_url();
            wp_redirect($redirect_url);
            exit;
        }
    }

    ob_start();
    ?>

<div class="wrap">
        <h2>Custom Registration</h2>
        <form method="post" action="" enctype="multipart/form-data" onsubmit="return validateForm();">

            <label for="profile_photo">Profile Photo:</label>
            <input type="file" id="profile_photo" name="profile_photo">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required><br>
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required><br>
            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required><br>
            <label for="user_type">Select User Type:</label>
            <select name="user_type" id="user_type">
                <option value="subscriber">Subscriber</option

>
                <option value="self_publisher">Self-Publisher</option>
            </select><br><br>

            <!-- Additional fields for Self-Publishers -->
            <div id="self_publisher_fields" style="display: none;">
                <label for="bio">Author's Biography:</label>
                <textarea id="bio1" name="bio" rows="4" cols="46" style="max-width: 100%;"></textarea><br>

                <label for="interest">Interest as a Self-Publisher:</label>
                <textarea id="interest1" name="interest" rows="4" cols="46" style="max-width: 100%;"></textarea><br>

                <label for="language">Language:</label>
                <select name="self_publisher_language" id="language1">
                    <option value="english">English</option>
                    <option value="sinhala">Sinhala</option>
                    <option value="tamil">Tamil</option>
                    <option value="other">Other</option>
                </select><br><br>

                <input type="checkbox" id="agree_terms" name="agree_terms">
                <label for="agree_terms">I agree with the Rights of the Self-Publisher and the Terms and Conditions. Refer to the following link for more details.</label>
                <br>
                <a href="https://nonimi.ink/self-publisher-terms-and-conditions/">Terms and Condition</a><br><br>
            </div>

            <input type="submit" name="custom_register" value="Register">
        </form>
