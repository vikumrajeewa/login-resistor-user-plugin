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
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var userTypeSelect = document.getElementById('user_type');
                var selfPublisherFields = document.getElementById('self_publisher_fields');

                // Initial check on page load
                toggleFieldsVisibility();

                // Add an event listener to the user type select
                userTypeSelect.addEventListener('change', function () {
                    toggleFieldsVisibility();
                });

                function toggleFieldsVisibility() {
                    console.log('Toggling fields visibility');
                    selfPublisherFields.style.display = (userTypeSelect.value === 'self_publisher') ? 'block' : 'none';
                }
            });

            // Function to validate the form before submission
            function validateForm() {
                var userType = document.getElementById('user_type').value;
                var agreeTermsCheckbox = document.getElementById('agree_terms');

                // Validate additional fields for self-publishers
                if (userType === 'self_publisher') {
                    var bio = document.getElementById('bio1').value;
                    var interest = document.getElementById('interest1').value;
                    var language = document.getElementById('language1').value;

                    if (!bio.trim() || !interest.trim() || language === 'default') {
                        alert('Please fill in all the required fields for Self-Publishers.');
                        return false;
                    }
                }

                 // Validate the agreement checkbox
                // if (!agreeTermsCheckbox.checked) {
                //     alert('Please agree to the terms and conditions.');
                //     return false;
                // }

                // Additional validation for other fields if needed

                // If all validations pass, allow form submission
                return true;
            }
        </script>

    </div>
    <?php
    return ob_get_clean();
}


// Handle role switching and profile updates
function custom_role_switching_form() {
    if (isset($_POST['switch_role'])) {
        $new_role = sanitize_text_field($_POST['new_role']);
        $current_user = wp_get_current_user();
        $current_role = $current_user->roles[0];

        if (in_array($new_role, ['subscriber', 'self_publisher'])) {
            if ($new_role === 'paid_subscriber') {
                echo 'You cannot switch to Paid Subscriber. Contact the administrator.';
            } else {
                $current_user->set_role($new_role);
                echo 'Your role has been switched to ' . $new_role;
            }
        } else {
            echo 'Invalid role selection.';
        }
    }

    if (isset($_POST['update_profile'])) {
        $new_first_name = sanitize_text_field($_POST['new_first_name']);
        $new_last_name = sanitize_text_field($_POST['new_last_name']);

        // Update first name and last name
        update_user_meta(get_current_user_id(), 'first_name', $new_first_name);
        update_user_meta(get_current_user_id(), 'last_name', $new_last_name);

        // Handle profile photo update
        if (isset($_FILES['new_profile_photo'])) {
            $file = $_FILES['new_profile_photo'];
            $upload_overrides = array('test_form' => false);
            $upload_info = wp_handle_upload($file, $upload_overrides);

            if (!empty($upload_info['file'])) {
                update_user_meta(get_current_user_id(), 'profile_photo', $upload_info['url']);
            }
        }

        echo 'Profile information updated successfully.';
    }

    ob_start();
    ?>
    <div class="wrap">
        <h2>Role Switching and Profile Update</h2>
        <form method="post" action="" enctype="multipart/form-data">
            <!-- Add a dropdown to select the new role -->
            <label for="new_role">Select your new role:</label>
            <select name="new_role" id="new_role">
                <option value="subscriber">Subscriber</option>
                <option value="self_publisher">Self-Publisher</option>
                <!-- You can add more role options here -->
            </select>
            <label for="new_first_name">New First Name:</label>
            <input type="text" id="new_first_name" name="new_first_name" required><br>
            <label for="new_last_name">New Last Name:</label>
            <input type="text" id="new_last_name" name="new_last_name" required><br>
            <label for="new_profile_photo">New Profile Photo:</label>
            <input type="file" id="new_profile_photo" name="new_profile_photo"><br><br><br>
            <input type="submit" name="update_profile" value="Update Profile">&nbsp;&nbsp;
            <input type="submit" name="switch_role" value="Switch Role">
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// Define a function for the login form
function custom_user_login_form() {
    ob_start();
    ?>
    <div class="wrap">
        <h2>Login</h2>
        <form method="post" action="<?php echo wp_login_url(); ?>">
            <label for="username">Username:</label>
            <input type="text" id="username" name="log" required><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="pwd" required><br><br>
            <input type="submit" name="custom_login" value="Log In">
        </form>
    </div>
    <?php
    return ob_get_clean();
}

// Add WordPress hooks to display registration, role-switching, and login forms using shortcodes
add_shortcode('custom_user_registration_form', 'custom_user_registration_form');
add_shortcode('custom_role_switching_form', 'custom_role_switching_form');
add_shortcode('custom_user_login_form', 'custom_user_login_form');

// Create a settings page for your plugin
function custom_registration_role_settings_menu() {
    add_menu_page('Custom Registration & Role Settings', 'Plugin Settings', 'manage_options', 'custom-reg-role-settings', 'custom_registration_role_settings_page');
}
add_action('admin_menu', 'custom_registration_role_settings_menu');

// Create the settings page content
function custom_registration_role_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['save_settings'])) {
        // Handle and save settings here
        echo 'Settings saved!';
    }

    ob_start();
    ?>
    <div class="wrap">
        <h2>Custom Registration & Role Settings</h2>
        <form method="post" action="">
            <!-- Example setting field -->
            <label for="custom_option">Custom Option:</label>
            <input type="text" id="custom_option" name="custom_option" value="<?php echo esc_attr(get_option('custom_option')); ?>"><br><br>

            <!-- Add more setting fields as needed -->

            <input type="submit" name="save_settings" value="Save Settings">
        </form>
    </div>
    <?php
    return ob_get_clean();
}