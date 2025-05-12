<?php
/*
* Plugin Name: Templify Builder
* Description: Templify Builder plugin description.
* Version: 1.0
* Author: Templify
*/

add_action('admin_enqueue_scripts', 'templify_builder_enqueue_scripts');
// Enqueue scripts and styles
if (!function_exists('templify_builder_enqueue_scripts')) {
    function templify_builder_enqueue_scripts($hook_suffix) {
        // Enqueue jQuery
        wp_enqueue_script('jquery');
	    // Load media uploader only on specific admin pages
	    if ($hook_suffix === 'toplevel_page_templify-builder') {
	        wp_enqueue_media();
	    }  
	    // Enqueue plugin scripts
	        wp_enqueue_style('templify-builder-style', plugins_url('assets/css/style.css', __FILE__));
	        wp_enqueue_script('templify-builder-script', plugins_url('assets/js/script.js', __FILE__), array('jquery'), '1.0', true);
	        $link_status = templify_get_link_status();
	        wp_localize_script('templify-builder-script', 'wpApiSettings', array(
	        'ajaxUrl' => admin_url('admin-ajax.php'),
	        'nonce' => wp_create_nonce('templify_nonce'),
	        'root'  => esc_url(rest_url()),
	        'userID' => get_current_user_id(),
	        'link_status' => $link_status
	        ));
	}
}
// Activation hook
if (!function_exists('templify_builder_activate')) {
    function templify_builder_activate() {
        // Get the upload directory
        $upload_dir = wp_upload_dir(); // This returns an array with paths
        
        // Path to the new "builder_templates" folder inside uploads
        $builder_templates_dir = $upload_dir['basedir'] . '/builder_templates';
    
        // Check if the directory already exists, if not, create it
        if ( ! file_exists( $builder_templates_dir ) ) {
            wp_mkdir_p( $builder_templates_dir ); // Create the directory
        }
    }
}
register_activation_hook( __FILE__, 'templify_builder_activate' );
// Deactivation hook
function templify_builder_deactivate() {
    // Deactivation code here
}
register_deactivation_hook(__FILE__, 'templify_builder_deactivate');
if(!function_exists('templify_builder_add_menu')){
function templify_builder_add_menu() {
    add_menu_page(
        'Templify Builder',            // Page title
        'Templify Builder',            // Menu title
        'manage_options',              // Capability
        'templify-builder',            // Menu slug
        '',                             // Callback function
        'dashicons-layout',            // Icon URL or Dashicons class
        30                             // Position
    );
    add_submenu_page(
        'templify-builder',             // Parent slug
        'Templify Builder',             // Page title
        'Templify Builder',             // Menu title
        'manage_options',               // Capability
        'templify-builder',             // Menu slug
        'templify_builder_main_page'    // Callback function
    );
}
}
// Add admin menu and submenu
add_action('admin_menu', 'templify_builder_add_menu');
// Callback function for main page
function templify_builder_main_page() {
    global $link_status;
    
    // Set the global variable
    $link_status = templify_get_link_status();
    
    // Make sure to include admin_main.php
    require_once plugin_dir_path(__FILE__) . '/admin/admin_main.php';
}
if(!function_exists('save_templify_configure_data')){
function save_templify_configure_data() {
    // Save theme data
    if (isset($_POST['templify_theme_name'])) {
        $theme_data = array(
            'name' => sanitize_text_field($_POST['templify_theme_name']),
            'author' => sanitize_text_field($_POST['templify_author']),
            'author_link' => sanitize_text_field($_POST['templify_author_link']),
            'version' => sanitize_text_field($_POST['templify_version']),
            'preview_image' => esc_url_raw($_POST['templify_preview_image']),  // Save the image URL
            'private_key' => wp_generate_password(32, false, false)
        );
        // Save theme data to the database
        update_option('templify_theme_data', $theme_data);
    }
    // Save plugin data
    if (isset($_POST['plugins']) && is_array($_POST['plugins'])) {
        $plugins_data = array();
        foreach ($_POST['plugins'] as $plugin_file => $status) {
            $plugins_data[$plugin_file] = array(
                'status' => sanitize_text_field($status),
            );
        }
        update_option('templify_plugins_data', $plugins_data);
    }
}
}
add_action('admin_post_save_templify_configure_data', 'save_templify_configure_data');
add_action('rest_api_init', function () {
    register_rest_route('templify/v1', '/link', array(
        'methods' => 'POST',
        'callback' => 'templify_link_api',
        'permission_callback' => '__return_true', // No permission checks
    ));
});
// Handle the API request
function templify_link_api(WP_REST_Request $request) {
    $url = sanitize_text_field($request->get_param('url'));
    $key = sanitize_text_field($request->get_param('key'));
    $builder_url = site_url(); // Send builder_url

    // Validate the required fields
    if (empty($url) || empty($key)) {
        return new WP_REST_Response(array(
            'status' => 'error',
            'message' => 'URL and key are required.'
        ), 400);
    }
    // Prepare the request to the core API
    $core_api_url = trailingslashit($url) . 'wp-json/templify/v1/check_site_key';
    $body = array(
        'license_key' => $key,
        'builder_url' => $builder_url, // Include builder_url
        'flag' => 1 // Optional flag
    );

    $response = wp_remote_post($core_api_url, array(
        'method'    => 'POST',
        'body'      => wp_json_encode($body),
        'headers'   => array('Content-Type' => 'application/json')
    ));

    if (is_wp_error($response)) {
        return new WP_REST_Response(array(
            'status' => 'error',
            'message' => 'Error: Please check your entered credentials!'
        ), 500);
    }

    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    if (empty($response_data)) {
        return new WP_REST_Response(array(
            'status' => 'error',
            'message' => 'Invalid response from core site.'
        ), 500);
    }

    if ($response_data['status'] === 'success') {
       

        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => 'Core Site Key matches. Linked successfully.'
        ), 200);
    } else {
        return new WP_REST_Response(array(
            'status' => 'error',
            'message' => $response_data['message'] ?? 'Core Site Key does not match.'
        ), 400);
    }
}



// Register the REST API route
add_action('rest_api_init', function () {
    register_rest_route('templify/v1', '/fetch_library_data', array(
        'methods' => 'POST',
        'callback' => 'templify_fetch_library_data_api',
        'permission_callback' => '__return_true', // Adjust this for proper permission checks
    ));
});

// Callback function for fetching library data
function templify_fetch_library_data_api(WP_REST_Request $request) {
    // Get parameters from the request
    $url = sanitize_text_field($request->get_param('url'));
    $key = sanitize_text_field($request->get_param('key'));

    // Retrieve stored options
    $stored_url = get_option('templify_core_url');
    $stored_key = get_option('templify_core_key');

    // Validate the input parameters
    if ($url !== $stored_url || $key !== $stored_key) {
        return new WP_REST_Response(array(
            'status' => 'error',
            'message' => 'Invalid URL or key.',
        ), 400);
    }

    // Fetch templify_theme_data from the database
    $serialized_theme_data = get_option('templify_theme_data');
    
    // Unserialize the data
    $theme_data = maybe_unserialize($serialized_theme_data);

    // Prepare the response data
    $response_data = array(
        'status' => 'success',
        'data' => $theme_data,
    );

    // Return the response
    return new WP_REST_Response($response_data, 200);
}





function templify_get_link_status() {
    // Get the linked status and core site details from WordPress options
  
    $core_url = get_option('templify_core_url');
    $core_key = get_option('templify_core_key');

    return array(
       
        'core_url' => $core_url,
        'core_key' => $core_key
    );
}




add_action('admin_post_generate_templify_zip', 'generate_templify_zip_file');

function generate_templify_zip_file() {
    // Ensure this action is secure
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Retrieve the serialized theme data from the database and unserialize i

   $templify_theme_data = get_option('templify_theme_data', array());
$templify_theme_data = !empty($templify_theme_data) ? maybe_unserialize($templify_theme_data) : array();

    // Extract individual fields from the unserialized data
    $templify_theme_name = isset($templify_theme_data['name']) ? sanitize_text_field($templify_theme_data['name']) : '';
    $templify_author = isset($templify_theme_data['author']) ? sanitize_text_field($templify_theme_data['author']) : '';
    $templify_author_link = isset($templify_theme_data['author_link']) ? esc_url($templify_theme_data['author_link']) : '';
    $templify_version = isset($templify_theme_data['version']) ? sanitize_text_field($templify_theme_data['version']) : '';
    $templify_preview_image = isset($templify_theme_data['preview_image']) ? esc_url($templify_theme_data['preview_image']) : '';
    $templify_private_key = isset($templify_theme_data['private_key']) ? esc_url($templify_theme_data['private_key']) : '';

    // Check if required fields are filled
    // if (empty($templify_theme_name) || empty($templify_author) || empty($templify_version) || empty($templify_preview_image) || empty($templify_author_link)) {
    //     wp_die(__('Please ensure all required fields are filled in Tab 2 before generating the ZIP file.'));
    // }

    // Retrieve the required plugins from the saved option
    $plugins_data = get_option('templify_plugins_data', array());

    // Create a directory to store the files temporarily in the 'builder_templates' folder inside the uploads directory
    $upload_dir = wp_upload_dir();
    $zip_dir = $upload_dir['basedir'] . '/builder_templates/';
    
    // Clear the existing files in the builder_templates directory
    if (file_exists($zip_dir)) {
        delete_directory_contents($zip_dir);  // New function to delete contents
    } else {
        mkdir($zip_dir, 0755, true);  // Create directory if it doesn't exist
    }

// Assuming you have a function or data source to fetch widget data
$widgets_data = get_widgets_data();  // Replace this with your actual method to get widget data

// Now call the create_theme_files function with the correct number of arguments
create_theme_files($zip_dir, $templify_theme_name, $templify_author, $templify_version, $plugins_data, $templify_private_key, $templify_author_link, $widgets_data);

// create_front_page_template($zip_dir); // Add this line to create front-page.php

if (!file_exists($zip_dir . 'style.css')) {
    error_log('style.css was not created.');
}
if (!file_exists($zip_dir . 'functions.php')) {
    error_log('functions.php was not created.');
}
    // Create the starter directory
    $starter_dir = $zip_dir . 'starter/';
    if (!file_exists($starter_dir)) {
        mkdir($starter_dir);
    }

    // Export content, theme options, and widget data into the starter directory
    export_content_xml($starter_dir . 'content.xml', $plugins_data);  // Pass plugins data to export content
    export_theme_options($starter_dir . 'theme_option.json');
    export_widget_data($starter_dir . 'widget_data.json');

    // Add required plugins
    $plugin_dir = $starter_dir . 'plugins/';
    
    if (!file_exists($plugin_dir)) {
        mkdir($plugin_dir);
    }

    foreach ($plugins_data as $plugin_file => $plugin_info) {
        if (isset($plugin_info['status']) && $plugin_info['status'] == 'required') {
            // Get the plugin directory from the plugin file path
            $plugin_dir_name = explode('/', $plugin_file)[0];  // Extract the directory name
    
            $plugin_source_path = WP_PLUGIN_DIR . '/' . $plugin_dir_name; // Full plugin path
            $plugin_dest_path = $plugin_dir . $plugin_dir_name; // Destination inside theme
    
            if (file_exists($plugin_source_path)) {
                // Copy the entire plugin directory to the theme's plugin directory
                copy_plugin_to_theme($plugin_source_path, $plugin_dest_path);
            } else {
                error_log("Plugin directory $plugin_source_path does not exist.");
            }
        }
    }

    // Create ZIP file
    $zip_file_path = $zip_dir . $templify_theme_name .'.zip';
    $zip = new ZipArchive();
    if ($zip->open($zip_file_path, ZipArchive::CREATE) === TRUE) {
        // Add files and folders to the ZIP
        add_folder_to_zip($zip_dir, $zip, strlen($zip_dir)); // Add the contents of the zip_dir, including style.css and functions.php
        $zip->close();
    }

    // Generate the URL to the ZIP file
    $zip_file_url = $upload_dir['baseurl'] . '/builder_templates/'.$templify_theme_name .'.zip';

    // Save the ZIP file URL in the options table
    update_option('templify_zip_url', $zip_file_url);

    // Notify the user that the file was created successfully
    wp_redirect(admin_url('admin.php?page=templify-builder&zip_generated=success'));
    exit();
}

/**
 * Helper function to delete all contents of a directory.
 */
function delete_directory_contents($dir) {
    // Open the directory
    $files = array_diff(scandir($dir), array('.', '..'));

    foreach ($files as $file) {
        // If it's a directory, recursively delete its contents
        if (is_dir("$dir/$file")) {
            delete_directory_contents("$dir/$file");
            rmdir("$dir/$file");
        } else {
            // Delete the file
            unlink("$dir/$file");
        }
    }
}





function create_theme_files($base_dir, $templify_theme_name, $templify_author, $templify_version, $plugins_data, $templify_private_key, $templify_author_link, $widgets_data) {
    // Create style.css
    $style_css = "/*
    Theme Name: {$templify_theme_name}
    Author: {$templify_author}
    Description: A custom Templify WordPress theme
    Version: {$templify_version}
    */";
     file_put_contents($base_dir. 'style.css', $style_css);


     // Generate index.php content
        $index_php = "<?php get_header(); ?>

        <main id=\"main-content\" class=\"site-main\">
            <?php if (have_posts()) : ?>
                <div class=\"post-list\">
                    <?php while (have_posts()) : the_post(); ?>
                        <article id=\"post-<?php the_ID(); ?>\" <?php post_class(); ?>>
                            <h2 class=\"post-title\">
                                <a href=\"<?php the_permalink(); ?>\"><?php the_title(); ?></a>
                            </h2>
                            <div class=\"post-meta\">
                                <span><?php the_date(); ?></span> | <span><?php the_author(); ?></span>
                            </div>
                            <div class=\"post-excerpt\">
                                <?php the_excerpt(); ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>

                <div class=\"pagination\">
                    <?php the_posts_pagination(); ?>
                </div>
            <?php else : ?>
                <p><?php esc_html_e('No posts found.', 'templify'); ?></p>
            <?php endif; ?>
        </main>

        <?php get_footer(); ?>
        ";

        file_put_contents($base_dir . 'index.php', $index_php);


        // Generate header.php
        $header_php = "<!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset=\"<?php bloginfo('charset'); ?>\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
            <?php wp_head(); ?>
        </head>
        <body <?php body_class(); ?>>

        <header class=\"site-header\">
            <h1><a href=\"<?php echo esc_url(home_url('/')); ?>\"><?php bloginfo('name'); ?></a></h1>
            <p><?php bloginfo('description'); ?></p>
        </header>
        ";
        file_put_contents($base_dir . 'header.php', $header_php);

        // Generate footer.php
        $footer_php = "<footer class=\"site-footer\">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
        </footer>

        <?php wp_footer(); ?>
        </body>
        </html>";
        file_put_contents($base_dir . 'footer.php', $footer_php);


        // front-page.php
        $front_page_php = "<?php get_header(); ?>

        <main class=\"site-main\">
            <?php
            if (have_posts()) :
                while (have_posts()) : the_post();
                    the_content();
                endwhile;
            else :
                echo '<p>No content found.</p>';
            endif;
            ?>
        </main>

        <?php get_footer(); ?>
        ";
        file_put_contents($base_dir . 'front-page.php', $front_page_php);

        // page.php
        $page_php = "<?php get_header(); ?>

        <main class=\"site-main\">
            <?php
            if (have_posts()) :
                while (have_posts()) : the_post();
                    the_content();
                endwhile;
            else :
                echo '<p>No content found.</p>';
            endif;
            ?>
        </main>

        <?php get_footer(); ?>
        ";
        file_put_contents($base_dir . 'page.php', $page_php);
        
            // Prepare plugin list
            $plugin_list = array();
            foreach ($plugins_data as $plugin_file => $plugin_info) {
                if (isset($plugin_info['status']) && $plugin_info['status'] === 'required') {
                    $plugin_list[] = "'{$plugin_file}'";
                }
            }
            $plugin_list_str = implode(',', $plugin_list);
        
            // Header and Footer content placeholders
            $header_content = "get_theme_mod('header_html_content', '')";
            $footer_content = "get_theme_mod('footer_html_content', '')";
        
            // Generate functions.php
            $functions_php = "<?php
        /**
         * Enqueue Theme Styles
        */
        function theme_enqueue_styles() {
            wp_enqueue_style('{$templify_theme_name}-style', get_stylesheet_uri(), array(), '{$templify_version}');
        }
        add_action('wp_enqueue_scripts', 'theme_enqueue_styles', 20);
        
        // Register Widgets
        " . generate_widget_registration_code($widgets_data) . "
        
        // Default Plugins Setup
        function theme_add_theme_plugins(\$data) {
            \$data['plugins'] = array($plugin_list_str);
            return \$data;
        }
        add_filter('templify_starter_templates_custom_array', 'theme_add_theme_plugins', 20);
        
        // Header Content
        function theme_add_header_content() {
            echo esc_html(" . $header_content . ");
        }
        add_action('wp_head', 'theme_add_header_content');
        
        // Footer Content
        function theme_add_footer_content() {
            echo esc_html(" . $footer_content . ");
        }
        add_action('wp_footer', 'theme_add_footer_content');
        
        // Theme Options Defaults
        function theme_option_defaults(\$defaults) {
            \$defaults['templify_theme_options_defaults'] = array(
                'default_layout' => 'full-width',
                'font_size' => '16px',
                'header_style' => 'classic',
            );
            return \$defaults;
        }
        add_filter('templify_theme_options_defaults', 'theme_option_defaults', 20);
        
        // Cloud Library
        function theme_add_cloud_library(\$libraries) {
            \$libraries[] = array(
                'slug' => 'theme_library',
                'title' => '{$templify_theme_name}',
                'key' => '{$templify_private_key}',
                'url' => '{$templify_author_link}',
            );
            return \$libraries;
        }
        add_filter('templify_blocks_custom_prebuilt_libraries', 'theme_add_cloud_library', 20);
        ?>";
 
     file_put_contents($base_dir. 'functions.php', $functions_php);
}

function generate_widget_registration_code($widgets_data) {
    $widget_registration_code = '';

    foreach ($widgets_data as $widget) {
        $widget_class = isset($widget['class']) ? sanitize_text_field($widget['class']) : '';
        if (!empty($widget_class)) {
            $widget_registration_code .= "\n// Register Widget: $widget_class\n";
            $widget_registration_code .= "function register_$widget_class() {\n";
            $widget_registration_code .= "    register_widget('$widget_class');\n";
            $widget_registration_code .= "}\n";
            $widget_registration_code .= "add_action('widgets_init', 'register_$widget_class');\n";
        }
    }

    return $widget_registration_code;
}



function get_widgets_data() {
    $widgets = [];
    // Example: Fetch all registered widgets in the current theme or plugin
    $widgets_registered = wp_get_sidebars_widgets();
    foreach ($widgets_registered as $sidebar => $widgets_list) {
        foreach ($widgets_list as $widget_id) {
            $widget_data = get_option("widget_$widget_id");  // Or fetch the widget details from your source
            $widgets[] = $widget_data;
        }
    }
    return $widgets;
}



function export_content_xml($file_path, $plugins_data) {
    // Load the WordPress exporter library if not already loaded
    if (!function_exists('export_wp')) {
        require_once ABSPATH . 'wp-admin/includes/export.php';
    }



    // Get all registered post types
    $default_post_types = array('post', 'page', 'attachment', 'revision', 'nav_menu_item'); // Default WordPress post types

    // Get all custom post types registered by the plugins
    $custom_post_types = get_custom_post_types_by_plugins($plugins_data);

    // Merge default post types with custom post types
    $all_post_types = array_merge($default_post_types, $custom_post_types);

    // Get all taxonomies associated with custom post types
    $taxonomies = get_taxonomies_for_custom_post_types($custom_post_types);

    // Set up the arguments for exporting the content (post types and taxonomies)
    $args = array(
        'content' => 'all',
        'post_type' => $all_post_types, // Export default and custom post types
        'taxonomy' => $taxonomies,      // Export related taxonomies
    );

    // Start capturing output

    ob_start();

    // Export the content based on the post types and taxonomies
    export_wp($args);

    // Get the captured output (XML)
    $export_data = ob_get_clean();

    // Save the XML data to a file
    file_put_contents($file_path, $export_data);
}

/**
 * Helper function to get taxonomies associated with custom post types
 */
function get_taxonomies_for_custom_post_types($custom_post_types) {
    $taxonomies = array();

    foreach ($custom_post_types as $post_type) {
        $post_type_taxonomies = get_object_taxonomies($post_type, 'names');
        $taxonomies = array_merge($taxonomies, $post_type_taxonomies);
    }

    return array_unique($taxonomies);
}

/**
 * Helper function to get custom post types registered by the plugins
 */
function get_custom_post_types_by_plugins($plugins_data) {
    $custom_post_types = array();

    foreach ($plugins_data as $plugin_file => $plugin_info) {
        // Check if the plugin is required and activated
        if (isset($plugin_info['status']) && $plugin_info['status'] == 'required' && is_plugin_active($plugin_file)) {
            // Get custom post types registered by this plugin
            $plugin_post_types = get_post_types(array('public' => true), 'names');

            // Add custom post types to the list, excluding default WordPress post types
            foreach ($plugin_post_types as $post_type) {
                if (!in_array($post_type, array('post', 'page', 'attachment', 'revision', 'nav_menu_item'))) {
                    $custom_post_types[] = $post_type;
                }
            }
        }
    }

    return $custom_post_types;
}

function export_theme_options($file_path) {
    $template = 'kadence'; // Replace with your theme name
    $mods = get_theme_mods();
    $data = array(
        'template' => $template,
        'mods'     => $mods ? $mods : array(),
        'options'  => array(),
    );

    // Add any specific theme options (like global palette, WooCommerce settings)
    if (get_option('kadence_global_palette')) {
        $data['options']['kadence_global_palette'] = get_option('kadence_global_palette');
    }

    // WooCommerce-specific settings if WooCommerce is active
    if (class_exists('woocommerce')) {
        $woocommerce_options = array(
            'woocommerce_catalog_columns',
            'woocommerce_catalog_rows',
            'woocommerce_single_image_width',
            'woocommerce_thumbnail_image_width',
            'woocommerce_thumbnail_cropping',
            'woocommerce_thumbnail_cropping_custom_width',
            'woocommerce_thumbnail_cropping_custom_height',
        );

        foreach ($woocommerce_options as $option) {
            $value = get_option($option);
            if ($value) {
                $data['options'][$option] = $value;
            }
        }
    }

    // Serialize and save the export data
    $serialized_data = serialize($data);
    file_put_contents($file_path, $serialized_data);
}

function export_widget_data($file_path) {
    // Get all available widgets that the site supports
    $available_widgets = get_available_widgets();

    // Get all widget instances
    $widget_instances = array();
    foreach ($available_widgets as $widget_data) {
        $instances = get_option('widget_' . $widget_data['id_base']);
        if (!empty($instances)) {
            foreach ($instances as $instance_id => $instance_data) {
                if (is_numeric($instance_id)) {
                    $unique_instance_id = $widget_data['id_base'] . '-' . $instance_id;
                    $widget_instances[$unique_instance_id] = $instance_data;
                }
            }
        }
    }

    // Get sidebars and associated widget instances
    $sidebars_widgets = get_option('sidebars_widgets');
    $sidebars_widget_instances = array();

    foreach ($sidebars_widgets as $sidebar_id => $widget_ids) {
        if ($sidebar_id === 'wp_inactive_widgets') {
            continue;
        }

        if (!is_array($widget_ids) || empty($widget_ids)) {
            continue;
        }

        foreach ($widget_ids as $widget_id) {
            if (isset($widget_instances[$widget_id])) {
                $sidebars_widget_instances[$sidebar_id][$widget_id] = $widget_instances[$widget_id];
            }
        }
    }

    // Encode the data as JSON and save to the file
    $encoded_data = wp_json_encode($sidebars_widget_instances, JSON_PRETTY_PRINT);
    file_put_contents($file_path, $encoded_data);
}


function get_available_widgets() {
    global $wp_registered_widget_controls;
    $available_widgets = array();

    foreach ($wp_registered_widget_controls as $widget) {
        if (!empty($widget['id_base']) && !isset($available_widgets[$widget['id_base']])) {
            $available_widgets[$widget['id_base']] = array(
                'id_base' => $widget['id_base'],
                'name'    => $widget['name'],
            );
        }
    }

    return apply_filters('wie_available_widgets', $available_widgets);
}




function copy_plugin_to_theme($source, $destination) {
    // Check if the source is a directory
    if (is_dir($source)) {
        // Ensure the destination directory exists, and create it if necessary
        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        // Scan the source directory
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $src_file = $source . '/' . $file;
                $dest_file = $destination . '/' . $file;

                // Recursively copy directories
                if (is_dir($src_file)) {
                    copy_plugin_to_theme($src_file, $dest_file); // Recursion for subdirectories
                } else {
                    // Copy individual files
                    if (!copy($src_file, $dest_file)) {
                        error_log("Failed to copy $src_file to $dest_file"); // Log any failure in copying files
                    }
                }
            }
        }
    } else {
        // If the source is a file, copy it to the destination
        if (!copy($source, $destination)) {
            error_log("Failed to copy $source to $destination");
        }
    }
}

function add_folder_to_zip($folder, &$zip, $exclusive_length) {
    $handle = opendir($folder);
    while ($file = readdir($handle)) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $file_path = "$folder/$file";
        $local_path = substr($file_path, $exclusive_length);
        if (is_dir($file_path)) {
            $zip->addEmptyDir($local_path);
            add_folder_to_zip($file_path, $zip, $exclusive_length);
        } else {
            $zip->addFile($file_path, $local_path);
        }
    }
    closedir($handle);
}

function delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    return rmdir($dir);
}

add_action('rest_api_init', function () {
    register_rest_route('templify/v1', '/check_theme_data', array(
        'methods' => 'POST',
        'callback' => 'templify_check_theme_data_api',
        'permission_callback' => '__return_true', // Improved security
    ));
});


function templify_check_theme_data_api(WP_REST_Request $request) {
    // Get the parameters from the request
    $private_key = sanitize_text_field($request->get_param('license_key'));

    // Retrieve the theme data from the database
    $theme_data_serialized = get_option('templify_theme_data');

//     if (!$theme_data_serialized) {
//         return new WP_Error('no_data', 'No theme data found', array('status' => 404));
//     }

//     // Unserialize the data
     $theme_data = maybe_unserialize($theme_data_serialized);

//     // Check if the private key matches
//     if (isset($theme_data['private_key']) && $theme_data['private_key'] === $private_key) {
        // Return the theme data if private key matches
        return rest_ensure_response($theme_data);
//     } else {
//         // Return an error if private key does not match
//         return new WP_Error('invalid_key', 'The provided private key is invalid.', array('status' => 403));
//     }
}


add_action('rest_api_init', function () {
    register_rest_route('templify/v1', '/check_plugin_data', array(
        'methods' => 'POST',
        'callback' => 'templify_check_plugin_data_api',
        'permission_callback' => '__return_true', // Improved security
    ));
});


function templify_check_plugin_data_api(WP_REST_Request $request) {
    // Get the parameters from the request
    $private_key = sanitize_text_field($request->get_param('license_key'));

    // Retrieve the theme data from the database
    $plugin_data_serialized = get_option('templify_plugins_data');

    $theme_data_serialized = get_option('templify_theme_data');

    if (!$plugin_data_serialized) {
        return new WP_Error('no_data', 'No Plugin data found', array('status' => 404));
    }

    if (!$theme_data_serialized) {
        return new WP_Error('no_data', 'No Theme data found', array('status' => 404));
    }

    // Unserialize the data
    $plugin_data = maybe_unserialize($plugin_data_serialized);


    $theme_data = maybe_unserialize($theme_data_serialized);

    // Check if the private key matches
    if (isset($theme_data['private_key']) && $theme_data['private_key'] === $private_key) {
        // Return the theme data if private key matches
        return rest_ensure_response($plugin_data);
    } else {
        // Return an error if private key does not match
        return new WP_Error('invalid_key', 'The provided private key is invalid.', array('status' => 403));
    }
}


add_action('rest_api_init', function () {
    register_rest_route('templify/v1', '/get_zip_url', array(
        'methods' => 'POST',
        'callback' => 'templify_get_zip_url_api',
        'permission_callback' => '__return_true', // Improved security
    ));
});

function templify_get_zip_url_api(WP_REST_Request $request) {

        // Retrieve the zip URL from the options table
        $zip_url = get_option('templify_zip_url');

        // Check if zip URL is available
        if (!empty($zip_url)) {
            return rest_ensure_response(array('zip_url' => $zip_url));
		}

}
