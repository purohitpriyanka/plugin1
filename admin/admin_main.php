<?php if (isset($_GET['zip_generated']) && $_GET['zip_generated'] === 'success') : ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Your zip was generated successfully!', 'templify-builder'); ?></p>
    </div>
<?php endif; ?>


<div class="templify-builder-container">
    <div class="templify-builder-header">
        <div class="logo-container">
            <?php $logo_image = plugins_url('images/logo.png', __FILE__); ?>
            <img src="<?php echo $logo_image ?>" alt="Templify Builder Logo">
        </div>

        <h3 class="templify-h3">The most innovative, intuitive and lightning fast WordPress theme.<br>
        Build your next web project visually, in no time.</h3>
       
        <nav class="templify-nav">
            <ul class="templify-ul">
                <li class="tab-link active" data-tab="tab1"><a href="#">Link</a></li>
                <!-- <li class="tab-link =$hide?>" data-tab="libraries"><a href="#">Linked Libraries</a></li> -->
                <li class="tab-link" data-tab="tab2"><a href="#">Configure</a></li>
                <li class="tab-link" data-tab="tab3"><a href="#">Generate</a></li>
            </ul>
        </nav>
    </div>

<div class="templify-builder-content">
    <div id="tab1" class="tab-content active">
        <?php global $link_status; ?>
        
        <?php
        // Retrieve core URL and key from link_status
        $coreurl = $link_status['core_url'];
        $corekey  = $link_status['core_key'];
        // Set button text and disabled state based on core details
        $btntext = "Unlink With Templify Core";
        $btndisabled = "Disabled";
        // Check if both URL and key are available to decide on the button state
        if (!empty($coreurl) && !empty($corekey)) {
            $btntext = "Unlink With Templify Core";
            $btndisabled = ""; // Allow button click if already linked
        } else {
            $btntext = "Link With Templify Core";
            $btndisabled = ""; // Allow button click to link
        }
        ?>
        <form action="#" class="templify-form">
            <input class="templify-input"type="text"id="templify-core-url"name="templify-core-url"value="<?=$coreurl?>">
            <input class="templify-input"type="text" id="templify-core-key"
                name="templify-core-key"value="<?=$corekey?>">
            <button class="templify-button"id="templify-link-button" <?php echo $btndisabled; ?>>
                <?php echo $btntext; ?>
            </button>
        </form>
    </div>
    <?php
    // Ensure this is within a WordPress context
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    // Retrieve all plugins
    $all_plugins = get_plugins();
    // Retrieve the stored plugins data from the database
    $plugins_data = get_option('templify_plugins_data', array());
    // Retrieve the stored theme data from the database
    $theme_data = get_option('templify_theme_data', array(
        'name' => '',
        'author' => '',
        'author_link' => '',
        'preview_image' => '',
        'version' => ''
    ));
    ?>
<div id="tab2" class="tab-content">
    <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="save_templify_configure_data">
        <div class="settings-container">
            <!-- Plugins Section -->
            <div class="plugins-section">
                <h3>Plugins</h3>
                <ul class="plugins-list">
                <?php
    foreach ($all_plugins as $plugin_file => $plugin_data) :
                        // Default status to 'optional' if not set in the database
                        $status = isset($plugins_data[$plugin_file]['status']) ?
                        $plugins_data[$plugin_file]['status'] : 'optional';
                ?>
                <li>
                        <label><?php echo esc_html($plugin_data['Name']); ?></label>
                        <select name="plugins[<?php echo esc_attr($plugin_file); ?>]">
                           <option value="required"<?php selected('required', $status);?>>Required</option>
                           <option value="optional"<?php selected('optional', $status);?>>Optional</option>
                           <option value="exclude"<?php selected('exclude', $status);?>>Exclude</option>
                        </select>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <!-- Theme Section -->
            <div class="theme-section">
                <h3>Theme</h3>
                <div class="inputrow">
                    <label>Name</label>
                    <input type="text" name="templify_theme_name" value="<?php echo esc_attr($theme_data['name']); ?>" 
                        placeholder="Enter Name">
                </div>
                <div class="inputrow">
                    <label>Author</label>
                    <input type="text" name="templify_author" value="<?php echo esc_attr($theme_data['author']); ?>" 
                        placeholder="Enter Author">
                </div>
                <div class="inputrow">
                    <label>Author Link</label>
                    <input type="text" name="templify_author_link" 
                        value="<?php echo esc_attr($theme_data['author_link']); ?>" 
                        placeholder="Enter Author Link">
                </div>
                <div class="inputrow">
                    <label>Preview Image</label>
                    <input type="hidden" name="templify_preview_image" id="templify_preview_image" 
                        value="<?php echo esc_attr($theme_data['preview_image']); ?>">
                    <button type="button" id="upload_image_button" class="button">Upload/Select Image</button>
                    <img id="templify_preview_image_preview" src="<?php echo esc_url($theme_data['preview_image']); ?>" 
                        alt="Preview Image" style="max-width: 150px; <?php echo empty($theme_data['preview_image']) ?
                            'display:none;' : ''; ?>" />
                </div>
                <div class="inputrow">
                    <label>Version</label>
                    <input type="text" name="templify_version" value="<?php echo esc_attr($theme_data['version']); ?>" 
                        placeholder="Enter Version">
                </div>
            </div>
        </div>
        <button type="submit" class="templify-formbutton">Save</button>
    </form>
</div>
    <div id="tab3" class="tab-content">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="generate_templify_zip">
            <button type="submit" class="templify-formbutton" name="generate_zip">Generate Zip</button>
        </form>   
    </div>
 </div>
</div>
