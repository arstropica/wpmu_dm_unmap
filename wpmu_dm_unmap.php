<?php
    /*
    Plugin Name: WordPress MU Domain Unmap Plugin
    Plugin URI: http://arstropica.com/
    Description: Batch reset child blog domains mapped with the WordPress Domain Mapping plugin.
    Author: Akin Williams
    Version: 0.1
    Author URI: http://arstropica.com/
    */

    ini_set('max_execution_time', 1000);
    ini_set('memory_limit', '1024M');
    $wpmu_dm_reset = new wpmu_dm_reset();
    $wpmu_dm_reset->init();

    class wpmu_dm_reset{

        var $menupage;

        public function init() {
            if(is_network_admin()){
                if ( ! function_exists( 'is_plugin_active_for_network' ) )
                    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
                // Makes sure the plugin is defined before trying to use it

                if ( is_plugin_active_for_network( 'domain_mapping.php' ) || (file_exists(untrailingslashit(ABSPATH) . DIRECTORY_SEPARATOR . 'wp-content' . DIRECTORY_SEPARATOR . 'mu-plugins' . DIRECTORY_SEPARATOR . 'domain_mapping.php'))) {
                    // Plugin is activated
                    add_action('network_admin_notices', array($this, 'network_deactivate_wpmu_dm'), 0);
                } else {
                    add_action('network_admin_menu', array($this, 'add_network_options_page'));
                }                
            } 
        }

        public function add_network_options_page(){
            $this->menupage = add_submenu_page('settings.php', 'WordPress MU Domain Unmap Plugin', 'Domain UnMap', 'manage_network_options', 'wpmu_dm_unmap', array(&$this, 'wpmu_dm_reset_settings_page'));
        }

        public function get_blogs(){
            global $wpdb;
            $blog_arry = array();
            $select = "SELECT blog_id, domain FROM $wpdb->blogs ";
            $where = "WHERE site_id = %d AND public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'";
            $order = " ORDER BY registered DESC";
            $query = $select . $where . $order;
            $blogs = $wpdb->get_results( $wpdb->prepare($query, $wpdb->siteid), ARRAY_A );
            foreach ( (array) $blogs as $details ) {
                $blog_arry[$details['blog_id']] = $details;
            }

            return $blog_arry;
        }

        public function wpmu_dm_reset_settings_page(){
            $blogs_arry = $this->get_blogs();
            if ($_POST){
                if (! check_admin_referer('wpmu_dm_reset_options', '_wpnonce')){
                    die('<p>Cheatin\' huh?</p>');
                } 
                $selected_blogs = $_POST['wpmu_dm_blog'];
                if (is_array($selected_blogs) && (! empty($selected_blogs))){
                    foreach($selected_blogs as $selected_blog_id){
                        update_blog_option($selected_blog_id, 'home', "http://" . $blogs_arry[$selected_blog_id]['domain']);
                        update_blog_option($selected_blog_id, 'siteurl', "http://" . $blogs_arry[$selected_blog_id]['domain']);
                    }
                    echo "<div class=\"updated\"><p>The selected blog(s) have been updated.</p></div>";
                } else {
                    echo "<div class=\"error\"><p>No blog(s) have been selected.</p></div>";
                }
            }
        ?>
        <div class="wrap">
            <h2>Reset the Home URL for WordPress MU Child Blog(s)</h2>
            <form name="wpmu_dm_blog_reset" method="post">
                <p><input type="submit" class="button-primary" value="Reset Selected" /></p>
                <table class="widefat">
                    <tr>
                        <th>Blog ID</th>
                        <th>Home URL</th>
                        <th>Site URL</th>
                        <th>Unmapped URL</th>
                        <th><input type="checkbox" id="wpmu_dm_blog_all" name="wpmu_dm_blog_all" style="margin: inherit !important;" /></th>
                    </tr>
                    <?php 
                        foreach ( (array) $blogs_arry as $details ) {
                            $current_blog_id = $details['blog_id'];
                            if ($current_blog_id == 1) continue;
                            $unmapped = "http://" . $details['domain'];
                            $homeurl = get_blog_option($current_blog_id, 'home');
                            $siteurl = get_blog_option($current_blog_id, 'siteurl');
                            $ismapped = stristr($homeurl, $unmapped) ? false : true;
                        ?>
                        <tr class="<?php echo $ismapped ? 'mapped sorthelper' : ''; ?>">
                            <td><?php echo $current_blog_id; ?></td>
                            <td><?php echo $homeurl; ?></td>
                            <td><?php echo $siteurl; ?></td>
                            <td><?php echo $unmapped; ?></td>
                            <td><input type="checkbox" class="wpmu_dm_blog" name="wpmu_dm_blog[]" value="<?php echo $current_blog_id; ?>"></td>
                        </tr>
                        <?php } ?>
                </table>
                <?php wp_nonce_field('wpmu_dm_reset_options'); ?>
                <p><input type="submit" class="button-primary" value="Reset Selected" /></p>
            </form>
        </div>
        <style type="text/css">
            /* <![CDATA[ */
            INPUT.select{
            outline: 5px solid #CCF3FA;            }
            /* ]]> */
        </style>
        <script type="text/javascript">
            jQuery(function(){
                jQuery("#wpmu_dm_blog_all").click(function(){
                    if (jQuery(this).is(':checked') && ( ! jQuery(this).hasClass('select'))){
                        // Check Mapped
                        jQuery(this).addClass('select');
                        jQuery(this).attr('checked', 'checked');
                        jQuery('.wpmu_dm_blog').removeAttr('checked');
                        jQuery('TR.mapped .wpmu_dm_blog').attr('checked', 'checked');
                        //alert('mapped');
                    } else if (jQuery(this).is(':not(:checked)') && (jQuery(this).hasClass('select'))){
                        // Check All
                        jQuery(this).removeClass('select');
                        jQuery(this).attr('checked', 'checked');
                        jQuery('.wpmu_dm_blog').attr('checked', 'checked');
                        //alert('all');
                    } else {
                        // Check None
                        jQuery(this).removeAttr('checked');
                        jQuery(this).removeClass('select');
                        jQuery('.wpmu_dm_blog').removeAttr('checked');
                        //alert('none');
                    }
                });
            });
        </script>
        <?php
        }

        public function network_deactivate_wpmu_dm(){
            echo "<div class=\"updated\"><p>The WordPress MU Domain Mapping Plugin is activated. Please <a href=\"" . admin_url('network/plugins.php') . "\">deactivate</a> before using the WordPress MU Domain Unmap Plugin.</p></div>";
        }

    }
?>
