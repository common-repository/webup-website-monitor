<?php

/*
Plugin Name: WUM Website Uptime Monitor
Plugin URI: https://wum.httpstatus.co.uk
Description: Welcome to WUM, the world's first class responsive free website uptime monitoring cloud service. 24/7 monitoring available from around the world, with instant response times and notifications.
Version: 1.0.2
Author: WUM Solutions
License: GPLv2 or later
Text Domain: wum
*/

// Add actions
add_action('admin_menu', 'wum_admin_setupmenu');
add_action('admin_init', 'wum_load_plugin_css' );

/**
 * @return void
 */
function wum_admin_setupmenu(){
    add_menu_page( 'Monitors', 'WUM', 'manage_options', 'wum', 'wum_show_wum_page' );
}

/**
 * Load styles for the admin page of WUM.
 * @return void
 */
function wum_load_plugin_css() {

    // Try and get the plugin url
    if(function_exists('plugin_dir_url')){

        // Get the plugin URL
        $plugin_url = plugin_dir_url( __FILE__ );

        // Grid CSS
        wp_enqueue_style( 'wum_grid_grid', $plugin_url . 'css/grid.css' );

        // Core CSS
        wp_enqueue_style( 'wum_grid_core', $plugin_url . 'css/core.css' );

        // Font Awesome
        wp_enqueue_style( 'wum_grid_fontawesome', $plugin_url . 'css/fontawesome-all.min.css' );

    }

}

/**
 * Check if website is connected to WUM.
 * @return string|boolean
 */
function wum_get_website_check(){

    // Declare
    $website_key = false;

    // Try/Catch
    try{

        // Get the website key from wp options
        $website_key = get_option('wum_solutions_website_key');

        // Check if not empty string
        if('' !== trim($website_key)){

            // Check the key is valid
            if(preg_match("/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i", $website_key)){

                // HTTP Request using wordpress built in function
                $response = wp_remote_get( 'https://www.httpstatus.co.uk/uptime-monitoring/validate-website-key', [
                    'body' => [
                        'key' => $website_key
                    ],
                    'timeout' => 5
                ]);

                // Check for error, timeout etc
                if( is_wp_error( $response ) ){                
                    return 'Oops! The WUM server may have timed out or is busy, try refreshing the page.';
                }

                // Check response
                if($response){

                    // Check the HTTP response code
                    if(wp_remote_retrieve_response_code($response)){

                        // Check the response returned from the server
                        if((int)wp_remote_retrieve_body( $response ) === 1){
                            return true;
                        }

                    }

                }

            }

        }

    }catch(Exception $ex){
        
    }

    return false;

}

/**
 * @return object|boolean
 */
function wum_get_check_data(){

    // Get website key
    $website_key = get_option('wum_solutions_website_key');

    // Validate
    if(isset($website_key) && is_string($website_key) && '' !== trim($website_key) && preg_match("/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i", $website_key)){

        // Retrieve data from WUM
        $response = wp_remote_post( 'https://www.httpstatus.co.uk/uptime-monitoring/get-wp-check', [
            'body' => [
                'key' => $website_key
            ],
            'timeout' => 5
        ]);

        // Check response
        if($response){

            // Check the HTTP response code
            if((int)wp_remote_retrieve_response_code($response) === 200){

                // Check the response returned from the server
                if( is_string(wp_remote_retrieve_body( $response )) ){

                    // Convert string (JSON) to Object
                    try{

                        // Return 
                        return json_decode(wp_remote_retrieve_body( $response ));

                    }catch(Exception $e){
                        return false;
                    }

                }

            }

        }        


    }

    return false;

}

/**
 * Save website key to WP Options
 * @param string $key
 */
function wum_save_website_key($key){

    // Check the the var is a string and not empty
    if(isset($key) && is_string($key) && '' !== trim($key)){

        // Trim
        $key = trim($key);

        // Safe to update the option
        update_option('wum_solutions_website_key', $key);

    }

}

/**
 * Show admin page.
 */
function wum_show_wum_page(){

    // Error
    $wum_save_error = '';

    // Check form post for wum_website_key submission
    if(isset($_POST) && isset($_POST['wum_website_key'])){

        // Check nonce before running function
        if(check_admin_referer( 'wum_save_website_key' )){

            // Get the website key in a temp variable and sanitize
            $temp_website_key = sanitize_text_field($_POST['wum_website_key']);

            // Check format of string
            if(preg_match("/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i", $temp_website_key)){

                // Save the website key
                wum_save_website_key($_POST['wum_website_key']);

            }else{
                $wum_save_error = 'The website key is not valid.';
            }

        }else{
            // Add error
            $wum_save_error = 'There was an issue saving your website key, please make sure you have entered your Website Key from WUM\'s website.';
        }

    }

    ?>

    <!-- Page Header -->
    <div class="section group pr-1">
        <div class="col span_12_of_12 text-center">

            <!-- Main Title -->        
            <img src="<?php echo plugin_dir_url( __FILE__ ); ?>/images/logo.png" alt="WUM Logo" />        

            <a href="https://wum.httpstatus.co.uk" target="_blank" class="button button-primary float-right">Visit WUM</a>

        </div>
    </div><!-- /Page Header -->

    <?php $wum_site_key = wum_get_website_check(); // Validate website key ?>

    <?php if('boolean' === gettype($wum_site_key) && $wum_site_key): // Connected notice ?>

        <?php

            // Get check data
            $check_data = wum_get_check_data();

        ?>

        <div class="col span_5_of_12">
            <div class="notice notice-success">
                <h1 class="text-center">Connected</h1>

                <div class="text-center mt-5 mb-5">
                    <i class="far fa-check-circle success" style="font-size: 100px;"></i>
                </div>

                <h3 class="text-center grey">Everything is good</h3>

            </div>
        </div>

        <div class="col span_6_of_12">
            <div class="notice notice-info">
                <h1 class="text-center">Latest Check</h1>

                <div class="text-center mt-5 mb-5">
                    <i class="fas fa-clock primary" style="font-size: 100px;"></i>
                </div>
            
                <h3 class="text-center grey">
                    <?php if(isset($check_data) && is_object($check_data)): ?>

                        <?php if(isset($check_data->data) && $check_data->data): ?>

                            <?php if((int)$check_data->data->down === 1 || (int)$check_data->data->down === -1): ?>
                                <strong class="danger">Down</strong> at <?php echo $check_data->data->date_added ?>.
                            <?php endif; ?>

                            <?php if((int)$check_data->data->up === 1): ?>
                                <strong class="success">Up</strong> at <?php echo $check_data->data->date_added ?> with a response time of <?php echo $check_data->data->response_time ?>.
                            <?php endif; ?>

                        <?php else: ?>
                            Waiting for more data to be collected.
                        <?php endif; ?>

                    <?php endif; ?>
                </h3>

            </div>
        </div>

    <?php endif; ?>

    <?php if('string' === gettype($wum_site_key) && '' !== trim($wum_site_key)): // Connected notice, failed to validate ?>

        <div class="col span_5_of_12">
            <div class="notice notice-error is-dismissible">
                <h2 class="text-center">Failed to validate website key</h3>
                <p><?php echo $wum_site_key; ?></p>
            </div>
        </div>

    <?php endif; ?>    

    <?php if('boolean' === gettype($wum_site_key) && !$wum_site_key): ?>

        <div class="section group">

            <div class="col span_12_of_12">
                <div class="notice">

                    <h1>Welcome</h1>
                    <p>This is your website's personal dashboard from WUM, start monitoring your website by completing the steps below.</p>

                </div>
            </div>

        </div>

        <div class="section group">

            <div class="col span_12_of_12 mb-5">
                <div class="notice-info notice">
                    <h1 style="margin-bottom:0;">Step 1. <small>Setup your website to be monitored</small></h1>

                    <p>It looks like your website is not yet being monitored by WUM Solutions, click the button "Start Monitoring" below to obtain your Website key from WUM Solutions website. When you click the button below you will be taken to WUM's website to complete your account setup and add your website, once completed you can then add your website key below.</p>
                    <div style="text-align:center;margin-top:20px;margin-bottom:20px;">

                        <form action="https://wum.httpstatus.co.uk/" method="get" target="_blank">
                            <input type="hidden" name="wordpress" value="1" />
                            <input type="hidden" name="redirect_url" value="<?php echo urlencode(get_site_url() . '/wp-admin/admin.php?page=webup') ?>" />
                            <input type="hidden" value="<?php echo urlencode(get_site_url()) ?>" style="width:300px" name="website_address" />
                            <input type="hidden" value="<?php echo urlencode(get_bloginfo('admin_email')) ?>" style="width:300px" name="email_address" />

                            <center>
                                <?php submit_button("Start Monitoring"); ?>
                            </center>

                        </form>

                    </div>
                </div>
            </div>  

        </div>

        <div class="section group">
            <div class="col span_3_of_12"></div>
            <div class="col span_6_of_12">
                <div class="notice">

                    <h1 class="text-center mb-5 mt-5">Enter your website key</h1>
                    <form style="margin-bottom:20px;" action="<?php echo $_SERVER['SELF']; ?>" method="post">
                        <p>This key will be the one you received from WUM's website when you signed up, you can get it from your My Account section. Visit <a href="https://wum.httpstatus.co.uk" target="_blank">https://wum.httpstatus.co.uk</a> to login to your dashboard and obtain your website key.</p>

                        <input type="text" value="" name="wum_website_key" placeholder="Website key from WUM" style="width:400px;" />

                        <?php if(isset($wum_save_error) && '' !== trim($wum_save_error)): ?>
                            <p style="color:#e74c3c;margin-bottom:0;"><strong><?php echo $wum_save_error; ?></strong></p>
                        <?php endif; ?>

                        <?php wp_nonce_field( 'wum_save_website_key' ); ?>
                        <?php submit_button("Complete Setup"); ?>
                    </form>

                </div>
            </div>
            <div class="col span_3_of_12"></div>
        </div>

    <?php endif; ?>

    <?php
}