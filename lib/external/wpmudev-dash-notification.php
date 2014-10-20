<?php
/////////////////////////////////////////////////////////////////////////
/* -------- WPMU DEV Dashboard Notice - Aaron Edwards (Incsub) ------- */
if ( !class_exists('WPMUDEV_Dashboard_Notice3') ) {
	class WPMUDEV_Dashboard_Notice3 {
		
		var $version = '3.0';
		var $screen_id = false;
		var $product_name = false;
		var $product_update = false;
		var $theme_pack = 128;
		var $server_url = 'http://premium.wpmudev.org/wdp-un.php';
		var $update_count = 0;
		
		function __construct() {
			add_action( 'init', array( &$this, 'init' ) );
		}
		
		function init() {
			global $wpmudev_un;
			
			if ( class_exists( 'WPMUDEV_Dashboard' ) || ( isset($wpmudev_un->version) && version_compare($wpmudev_un->version, '3.4', '<') ) )
				return;
			
			// Schedule update jobs
			if ( !wp_next_scheduled('wpmudev_scheduled_jobs') ) {
				wp_schedule_event(time(), 'twicedaily', 'wpmudev_scheduled_jobs');
			}
			add_action( 'wpmudev_scheduled_jobs', array( $this, 'updates_check') );
			add_action( 'delete_site_transient_update_plugins', array( &$this, 'updates_check' ) ); //refresh after upgrade/install
			add_action( 'delete_site_transient_update_themes', array( &$this, 'updates_check' ) ); //refresh after upgrade/install
			
			if ( is_admin() && current_user_can( 'install_plugins' ) ) {
				
				add_action( 'site_transient_update_plugins', array( &$this, 'filter_plugin_count' ) );
				add_action( 'site_transient_update_themes', array( &$this, 'filter_theme_count' ) );
				add_filter( 'plugins_api', array( &$this, 'filter_plugin_info' ), 20, 3 ); //run later to work with bad autoupdate plugins
				add_filter( 'themes_api', array( &$this, 'filter_plugin_info' ), 20, 3 ); //run later to work with bad autoupdate plugins
				add_action( 'admin_init', array( &$this, 'filter_plugin_rows' ), 15 ); //make sure it runs after WP's
				add_action( 'core_upgrade_preamble', array( &$this, 'disable_checkboxes' ) );
				add_action( 'activated_plugin', array( &$this, 'set_activate_flag' ) );
				
				//remove version 1.0
				remove_action( 'admin_notices', 'wdp_un_check', 5 );
				remove_action( 'network_admin_notices', 'wdp_un_check', 5 );
				//remove version 2.0, a bit nasty but only way
				remove_all_actions( 'all_admin_notices', 5 );
				
				//if dashboard is installed but not activated
				if ( file_exists(WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php') ) {
					if ( !get_site_option('wdp_un_autoactivated') ) {
						//include plugin API if necessary
						if ( !function_exists('activate_plugin') ) require_once(ABSPATH . 'wp-admin/includes/plugin.php');
						$result = activate_plugin( '/wpmudev-updates/update-notifications.php', network_admin_url('admin.php?page=wpmudev'), is_multisite() );
						if ( !is_wp_error($result) ) { //if autoactivate successful don't show notices
							update_site_option('wdp_un_autoactivated', 1);
							return;
						}
					}
					
					add_action( 'admin_print_styles', array( &$this, 'notice_styles' ) );
					add_action( 'all_admin_notices', array( &$this, 'activate_notice' ), 5 );
				} else { //dashboard not installed at all
					if ( get_site_option('wdp_un_autoactivated') ) {
						update_site_option('wdp_un_autoactivated', 0);//reset flag when dashboard is deleted
					}
					add_action( 'admin_print_styles', array( &$this, 'notice_styles' ) );
					add_action( 'all_admin_notices', array( &$this, 'install_notice' ), 5 );
				}
			}
		}
		
		function is_allowed_screen() {
			global $wpmudev_notices;
			$screen = get_current_screen();
			$this->screen_id = $screen->id;
			
			//Show special message right after plugin activation
			if ( in_array( $this->screen_id, array('plugins', 'plugins-network') ) && ( isset($_GET['activate']) || isset($_GET['activate-multi']) ) ) {	
				$activated = get_site_option('wdp_un_activated_flag');
				if ($activated === false) $activated = 1; //on first encounter of new installed notice show
				if ($activated) {
					if ($activated >= 2)
						update_site_option('wdp_un_activated_flag', 0);
					else
						update_site_option('wdp_un_activated_flag', 2);
					return true;
				}
			}
			
			//always show on certain core pages if updates are available
			$updates = get_site_option('wdp_un_updates_available');
			if (is_array($updates) && count($updates)) {
				$this->update_count = count($updates);
				if ( in_array( $this->screen_id, array('plugins', 'update-core', /*'plugin-install', 'theme-install',*/ 'plugins-network', 'themes-network', /*'theme-install-network', 'plugin-install-network',*/ 'update-core-network') ) )
					return true;
			}
			
			//check our registered plugins for hooks
			if ( isset($wpmudev_notices) && is_array($wpmudev_notices) ) {
				foreach ( $wpmudev_notices as $product ) {
					if ( isset($product['screens']) && is_array($product['screens']) && in_array( $this->screen_id, $product['screens'] ) ) {
						$this->product_name = $product['name'];
						//if this plugin needs updating flag it
						if ( isset($product['id']) && isset($updates[$product['id']]) )
							$this->product_update = true;
						return true;
					}
				}
			}
			
			if ( defined('WPMUDEV_SCREEN_ID') ) var_dump($this->screen_id); //for internal debugging
			
			return false;
		}
		
		function auto_install_url() {
			$function = is_multisite() ? 'network_admin_url' : 'admin_url';
			return wp_nonce_url($function("update.php?action=install-plugin&plugin=install_wpmudev_dash"), "install-plugin_install_wpmudev_dash");
		}
		
		function activate_url() {
			$function = is_multisite() ? 'network_admin_url' : 'admin_url';
			return wp_nonce_url($function('plugins.php?action=activate&plugin=wpmudev-updates%2Fupdate-notifications.php'), 'activate-plugin_wpmudev-updates/update-notifications.php');
		}
		
		function install_notice() {
			if ( !$this->is_allowed_screen() ) return;

			echo '<div class="updated" id="wpmu-install-dashboard"><div class="wpmu-install-wrap"><p class="wpmu-message">';
			
			if ($this->product_name) {
				if ($this->product_update)
					echo 'Important updates are available for <strong>' . esc_html($this->product_name) . '</strong>. Install the free WPMU DEV Dashboard plugin now for updates and support!';
				else
					echo '<strong>' . esc_html($this->product_name) . '</strong> is almost ready - install the free WPMU DEV Dashboard plugin for updates and support!';
			} else if ($this->update_count) {
				echo "Important updates are available for your WPMU DEV plugins/themes. Install the free WPMU DEV Dashboard plugin now for updates and support!";
			} else {
				echo 'Almost ready - install the free WPMU DEV Dashboard plugin for updates and support!';
			}
			echo '</p><a class="wpmu-button" href="' . $this->auto_install_url() . '">Install WPMU DEV Dashboard</a>';
			echo '</div>';
			echo '<div class="wpmu-more-wrap"><a href="http://premium.wpmudev.org/update-notifications-plugin-information/" class="wpmu-more-info">More Info&raquo;</a></div>';
			echo '</div>';
		}
		
		function activate_notice() {
			if ( !$this->is_allowed_screen() ) return;
			
			if ($this->product_name) {
				$msg = $this->product_update
					? 'Important updates are available for <strong>' . esc_html($this->product_name) . '</strong>. Activate the WPMU DEV Dashboard to update now!'
					: 'Just one more step to enable updates and support for <strong>' . esc_html($this->product_name) . '</strong>!'
				;
			} else if ($this->update_count) {
				$msg = 'Important updates are available for your WPMU DEV plugins/themes. Activate the WPMU DEV Dashboard to update now!';
			} else {
				$msg = 'Just one more step - activate the WPMU DEV Dashboard plugin and you\'re all done!';
			}
			echo '<div class="wrap"><div class="wdnag"><p class="wd_message">' .
				$msg .
				'</p>' . 
				'<div class="wd_cta"><a class="wd_btn">Activate <strong>WPMU DEV Dashboard</strong></a></div>' .
			'</div></div>';
		}
		
		function notice_styles() {
			if ( !$this->is_allowed_screen() ) return;
			?>
<!-- WPMU DEV Dashboard notice -->
<style type="text/css" media="all">
/* New Style */
.wdnag{background-color:#fff;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;padding:15px;width:100%;display:table;border-left:5px solid #3EBAE8;-webkit-box-shadow:1px 1px 5px #dfdfdf;box-shadow:1px 1px 5px #dfdfdf}.wdnag:after{content:"";clear:both}.wdnag .wd_message{width:65%;vertical-align:middle;float:left}.wdnag .wd_btn,.wdnag .wd_message{margin:0;padding:0;font:400 14px / 22px "Open Sans",Helvetica Neueu,Arial,sans-serif;display:inline-block}.wdnag .wd_btn{width:30%;padding:5px;height:100%;background:0 0;border:3px solid rgba(24,85,132,.3);-webkit-transition:border .25s ease-in-out;-o-transition:border .25s ease-in-out;transition:border .25s ease-in-out;float:right;text-align:center}.wdnag .wd_btn:hover{border-color:rgba(62,186,232,1);cursor:pointer}@media only screen and (max-width :800px){.wdnag .wd_btn{display:block;margin:0 auto;float:left;width:90%;margin-right:15px}.wdnag .wd_message{width:100%;margin-bottom:20px;display:block}}
</style>
			<?php
		}
		
		function get_id_plugin($plugin_file) {
			return get_file_data( $plugin_file, array('name' => 'Plugin Name', 'id' => 'WDP ID', 'version' => 'Version') );
		}
		
		//simple check for updates
		function updates_check() {
			global $wp_version;
			$local_projects = array();
	
			//----------------------------------------------------------------------------------//
			//plugins directory
			//----------------------------------------------------------------------------------//
			$plugins_root = WP_PLUGIN_DIR;
			if( empty($plugins_root) ) {
				$plugins_root = ABSPATH . 'wp-content/plugins';
			}
	
			$plugins_dir = @opendir($plugins_root);
			$plugin_files = array();
			if ( $plugins_dir ) {
				while (($file = readdir( $plugins_dir ) ) !== false ) {
					if ( substr($file, 0, 1) == '.' )
						continue;
					if ( is_dir( $plugins_root.'/'.$file ) ) {
						$plugins_subdir = @ opendir( $plugins_root.'/'.$file );
						if ( $plugins_subdir ) {
							while (($subfile = readdir( $plugins_subdir ) ) !== false ) {
								if ( substr($subfile, 0, 1) == '.' )
									continue;
								if ( substr($subfile, -4) == '.php' )
									$plugin_files[] = "$file/$subfile";
							}
						}
					} else {
						if ( substr($file, -4) == '.php' )
							$plugin_files[] = $file;
					}
				}
			}
			@closedir( $plugins_dir );
			@closedir( $plugins_subdir );
	
			if ( $plugins_dir && !empty($plugin_files) ) {
				foreach ( $plugin_files as $plugin_file ) {
					if ( is_readable( "$plugins_root/$plugin_file" ) ) {
	
						unset($data);
						$data = $this->get_id_plugin( "$plugins_root/$plugin_file" );
	
						if ( isset($data['id']) && !empty($data['id']) ) {
							$local_projects[$data['id']]['type'] = 'plugin';
							$local_projects[$data['id']]['version'] = $data['version'];
							$local_projects[$data['id']]['filename'] = $plugin_file;
						}
					}
				}
			}
	
			//----------------------------------------------------------------------------------//
			// mu-plugins directory
			//----------------------------------------------------------------------------------//
			$mu_plugins_root = WPMU_PLUGIN_DIR;
			if( empty($mu_plugins_root) ) {
				$mu_plugins_root = ABSPATH . 'wp-content/mu-plugins';
			}
	
			if ( is_dir($mu_plugins_root) && $mu_plugins_dir = @opendir($mu_plugins_root) ) {
				while (($file = readdir( $mu_plugins_dir ) ) !== false ) {
					if ( substr($file, -4) == '.php' ) {
						if ( is_readable( "$mu_plugins_root/$file" ) ) {
	
							unset($data);
							$data = $this->get_id_plugin( "$mu_plugins_root/$file" );
	
							if ( isset($data['id']) && !empty($data['id']) ) {
								$local_projects[$data['id']]['type'] = 'mu-plugin';
								$local_projects[$data['id']]['version'] = $data['version'];
								$local_projects[$data['id']]['filename'] = $file;
							}
						}
					}
				}
				@closedir( $mu_plugins_dir );	
			}
	
			//----------------------------------------------------------------------------------//
			// wp-content directory
			//----------------------------------------------------------------------------------//
			$content_plugins_root = WP_CONTENT_DIR;
			if( empty($content_plugins_root) ) {
				$content_plugins_root = ABSPATH . 'wp-content';
			}
	
			$content_plugins_dir = @opendir($content_plugins_root);
			$content_plugin_files = array();
			if ( $content_plugins_dir ) {
				while (($file = readdir( $content_plugins_dir ) ) !== false ) {
					if ( substr($file, 0, 1) == '.' )
						continue;
					if ( !is_dir( $content_plugins_root.'/'.$file ) ) {
						if ( substr($file, -4) == '.php' )
							$content_plugin_files[] = $file;
					}
				}
			}
			@closedir( $content_plugins_dir );
	
			if ( $content_plugins_dir && !empty($content_plugin_files) ) {
				foreach ( $content_plugin_files as $content_plugin_file ) {
					if ( is_readable( "$content_plugins_root/$content_plugin_file" ) ) {
						unset($data);
						$data = $this->get_id_plugin( "$content_plugins_root/$content_plugin_file" );
	
						if ( isset($data['id']) && !empty($data['id']) ) {
							$local_projects[$data['id']]['type'] = 'drop-in';
							$local_projects[$data['id']]['version'] = $data['version'];
							$local_projects[$data['id']]['filename'] = $content_plugin_file;
						}
					}
				}
			}
			
			//----------------------------------------------------------------------------------//
			//themes directory
			//----------------------------------------------------------------------------------//
			$themes_root = WP_CONTENT_DIR . '/themes';
			if ( empty($themes_root) ) {
				$themes_root = ABSPATH . 'wp-content/themes';
			}
	
			$themes_dir = @opendir($themes_root);
			$themes_files = array();
			$local_themes = array();
			if ( $themes_dir ) {
				while (($file = readdir( $themes_dir ) ) !== false ) {
					if ( substr($file, 0, 1) == '.' )
						continue;
					if ( is_dir( $themes_root.'/'.$file ) ) {
						$themes_subdir = @ opendir( $themes_root.'/'.$file );
						if ( $themes_subdir ) {
							while (($subfile = readdir( $themes_subdir ) ) !== false ) {
								if ( substr($subfile, 0, 1) == '.' )
									continue;
								if ( substr($subfile, -4) == '.css' )
									$themes_files[] = "$file/$subfile";
							}
						}
					} else {
						if ( substr($file, -4) == '.css' )
							$themes_files[] = $file;
					}
				}
			}
			@closedir( $themes_dir );
			@closedir( $themes_subdir );
	
			if ( $themes_dir && !empty($themes_files) ) {
				foreach ( $themes_files as $themes_file ) {
	
					//skip child themes
					if ( strpos( $themes_file, '-child' ) !== false )
						continue;
	
					if ( is_readable( "$themes_root/$themes_file" ) ) {
	
						unset($data);
						$data = $this->get_id_plugin( "$themes_root/$themes_file" );
	
						if ( isset($data['id']) && !empty($data['id']) ) {
							$local_projects[$data['id']]['type'] = 'theme';
							$local_projects[$data['id']]['filename'] = substr( $themes_file, 0, strpos( $themes_file, '/' ) );
							
							//keep record of all themes for 133 themepack
							if ($data['id'] == $this->theme_pack) {
								$local_themes[$themes_file]['id'] = $data['id'];
								$local_themes[$themes_file]['filename'] = substr( $themes_file, 0, strpos( $themes_file, '/' ) );
								$local_themes[$themes_file]['version'] = $data['version'];
								//increment 133 theme pack version to lowest in all of them
								if ( isset($local_projects[$data['id']]['version']) && version_compare($data['version'], $local_projects[$data['id']]['version'], '<') ) {
									$local_projects[$data['id']]['version'] = $data['version'];
								} else if ( !isset($local_projects[$data['id']]['version']) ) {
									$local_projects[$data['id']]['version'] = $data['version'];
								}
							} else {
								$local_projects[$data['id']]['version'] = $data['version'];
							}
						}
					}
				}
			}
			update_site_option('wdp_un_local_themes', $local_themes);
			
			update_site_option('wdp_un_local_projects', $local_projects);
			
			//now check the API
			$projects = '';
			foreach ($local_projects as $pid => $project)
				$projects .= "&p[$pid]=" . $project['version'];
			
			//get WP/BP version string to help with support
			$wp = is_multisite() ? "WordPress Multisite $wp_version" : "WordPress $wp_version";
			if ( defined( 'BP_VERSION' ) )
				$wp .= ', BuddyPress ' . BP_VERSION;
			
			//add blog count if multisite
			$blog_count = is_multisite() ? get_blog_count() : 1;
			
			$url = $this->server_url . '?action=check&un-version=3.3.3&wp=' . urlencode($wp) . '&bcount=' . $blog_count . '&domain=' . urlencode(network_site_url()) . $projects;
	
			$options = array(
				'timeout' => 15,
				'user-agent' => 'Dashboard Notification/' . $this->version
			);
	
			$response = wp_remote_get($url, $options);
			if ( wp_remote_retrieve_response_code($response) == 200 ) {
				$data = $response['body'];
				if ( $data != 'error' ) {
					$data = unserialize($data);
					if ( is_array($data) ) {
						
						//we've made it here with no errors, now check for available updates
						$remote_projects = isset($data['projects']) ? $data['projects'] : array();
						$updates = array();
				
						//check for updates
						if ( is_array($remote_projects) ) {
							foreach ( $remote_projects as $id => $remote_project ) {
								if ( isset($local_projects[$id]) && is_array($local_projects[$id]) ) {
									//match
									$local_version = $local_projects[$id]['version'];
									$remote_version = $remote_project['version'];
									
									if ( version_compare($remote_version, $local_version, '>') ) {
										//add to array
										$updates[$id] = $local_projects[$id];
										$updates[$id]['url'] = $remote_project['url'];
										$updates[$id]['instructions_url'] = $remote_project['instructions_url'];
										$updates[$id]['support_url'] = $remote_project['support_url'];
										$updates[$id]['name'] = $remote_project['name'];
										$updates[$id]['thumbnail'] = $remote_project['thumbnail'];
										$updates[$id]['version'] = $local_version;
										$updates[$id]['new_version'] = $remote_version;
										$updates[$id]['changelog'] = $remote_project['changelog'];
										$updates[$id]['autoupdate'] = $remote_project['autoupdate'];
									}
								}
							}
				
							//record results
							update_site_option('wdp_un_updates_available', $updates);
						} else {
							return false;
						}
					}
				}
			}
		}
		
		function filter_plugin_info($res, $action, $args) {
			global $wp_version;
			$cur_wp_version = preg_replace('/-.*$/', '', $wp_version);
			
			//if in details iframe on update core page short-curcuit it
			if ( ($action == 'plugin_information' || $action == 'theme_information') && strpos($args->slug, 'wpmudev_install') !== false ) {
				$string = explode('-', $args->slug);
				$id = intval($string[1]);
				$updates = get_site_option('wdp_un_updates_available');
				if ( did_action( 'install_plugins_pre_plugin-information' ) && is_array( $updates ) && isset($updates[$id]) ) {
					echo '<iframe width="100%" height="100%" border="0" style="border:none;" src="' . $this->server_url . '?action=details&id=' . $id . '"></iframe>';
					exit;
				}
				
				$res = new stdClass;
				$res->name = $updates[$id]['name'];
				$res->slug = sanitize_title($updates[$id]['name']);
				$res->version = $updates[$id]['version'];
				$res->rating = 100;
				$res->homepage = $updates[$id]['url'];
				$res->download_link = '';
				$res->tested = $cur_wp_version;
				
				return $res;
			}
			
			if ( $action == 'plugin_information' && strpos($args->slug, 'install_wpmudev_dash') !== false ) {
				$res = new stdClass;
				$res->name = 'WPMU DEV Dashboard';
				$res->slug = 'wpmu-dev-dashboard';
				$res->version = '';
				$res->rating = 100;
				$res->homepage = 'http://premium.wpmudev.org/project/wpmu-dev-dashboard/';
				$res->download_link = $this->server_url . "?action=install_wpmudev_dash";
				$res->tested = $cur_wp_version;
				
				return $res;
			}
	
			return $res;
		}
		
		function filter_plugin_rows() {
			if ( !current_user_can( 'update_plugins' ) )
				return;
			
			$updates = get_site_option('wdp_un_updates_available');
			if ( is_array($updates) && count($updates) ) {
				foreach ( $updates as $id => $plugin ) {
					if ( $plugin['autoupdate'] != '2' ) {
						if ( $plugin['type'] == 'theme' ) {
							remove_all_actions( 'after_theme_row_' . $plugin['filename'] );
							add_action('after_theme_row_' . $plugin['filename'], array( &$this, 'plugin_row'), 9, 2 );
						} else {
							remove_all_actions( 'after_plugin_row_' . $plugin['filename'] );
							add_action('after_plugin_row_' . $plugin['filename'], array( &$this, 'plugin_row'), 9, 2 );
						}
					}
				}
			}
			
			$local_themes = get_site_option('wdp_un_local_themes');
			if ( is_array($local_themes) && count($local_themes) ) {
				foreach ( $local_themes as $id => $plugin ) {
					remove_all_actions( 'after_theme_row_' . $plugin['filename'] );
					//only add the notice if specific version is wrong
					if ( isset($updates[$this->theme_pack]) && version_compare($plugin['version'], $updates[$this->theme_pack]['new_version'], '<') ) {
						add_action('after_theme_row_' . $plugin['filename'], array( &$this, 'themepack_row'), 9, 2 );
					}
				}
			}
		}
	
		function filter_plugin_count( $value ) {
			
			//remove any conflicting slug local WPMU DEV plugins from WP update notifications
			$local_projects = get_site_option('wdp_un_local_projects');
			if ( is_array($local_projects) && count($local_projects) ) {
				foreach ( $local_projects as $id => $plugin ) {
					if (isset($value->response[$plugin['filename']]))
						unset($value->response[$plugin['filename']]);
				}
			}
			
			$updates = get_site_option('wdp_un_updates_available');
			if ( is_array($updates) && count($updates) ) {
				foreach ( $updates as $id => $plugin ) {
					if ( $plugin['type'] != 'theme' && $plugin['autoupdate'] != '2' ) {
						
						//build plugin class
						$object = new stdClass;
						$object->url = $plugin['url'];
						$object->slug = "wpmudev_install-$id";
						$object->upgrade_notice = $plugin['changelog'];
						$object->new_version = $plugin['new_version'];
						$object->package = '';
							
						//add to class
						$value->response[$plugin['filename']] = $object;
					}
				}
			}
				
			return $value;
		}
	
		function filter_theme_count( $value ) {
			
			$updates = get_site_option('wdp_un_updates_available');
			if ( is_array($updates) && count($updates) ) {
				foreach ( $updates as $id => $theme ) {
					if ( $theme['type'] == 'theme' && $theme['autoupdate'] != '2' ) {
						//build theme listing
						$value->response[$theme['filename']]['url'] = $this->server_url . '?action=details&id=' . $id;
						$value->response[$theme['filename']]['new_version'] = $theme['new_version'];
						$value->response[$theme['filename']]['package'] = '';
					}
				}
			}
			
			//filter 133 theme pack themes from the list unless update is available
			$local_themes = get_site_option('wdp_un_local_themes');
			if ( is_array($local_themes) && count($local_themes) ) {
				foreach ( $local_themes as $id => $theme ) {
					//add to count only if new version exists, otherwise remove
					if (isset($updates[$theme['id']]) && isset($updates[$theme['id']]['new_version']) && version_compare($theme['version'], $updates[$theme['id']]['new_version'], '<')) {
						$value->response[$theme['filename']]['new_version'] = $updates[$theme['id']]['new_version'];
						$value->response[$theme['filename']]['package'] = '';
					} else if (isset($value) && isset($value->response) && isset($theme['filename']) && isset($value->response[$theme['filename']])) {
						unset($value->response[$theme['filename']]);
					}
				}
			}
			
			return $value;
		}
	
		function plugin_row( $file, $plugin_data ) {
	
			//get new version and update url
			$updates = get_site_option('wdp_un_updates_available');
			if ( is_array($updates) && count($updates) ) {
				foreach ( $updates as $id => $plugin ) {
					if ($plugin['filename'] == $file) {
						$project_id = $id;
						$version = $plugin['new_version'];
						$plugin_url = $plugin['url'];
						$autoupdate = $plugin['autoupdate'];
						$filename = $plugin['filename'];
						$type = $plugin['type'];
						break;
					}
				}
			} else {
				return false;
			}
	
			$plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
			$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );
	
			$info_url = $this->server_url . '?action=details&id=' . $project_id . '&TB_iframe=true&width=640&height=800';
			if ( file_exists(WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php') ) {
				$message = "Activate WPMU DEV Dashboard";
				$action_url = $this->activate_url();
			} else { //dashboard not installed at all
				$message = "Install WPMU DEV Dashboard";
				$action_url = $this->auto_install_url();
			}
			
			if ( current_user_can('update_plugins') ) {
				echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message wpmu-update-row">';
				printf( 'There is a new version of %1$s available on WPMU DEV. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s details</a> or <a href="%5$s">%6$s</a> to update.', $plugin_name, esc_url($info_url), esc_attr($plugin_name), $version, esc_url($action_url), $message );
				echo '</div></td></tr>';
			}
		}
		
		function themepack_row( $file, $plugin_data ) {
	
			//get new version and update url
			$updates = get_site_option('wdp_un_updates_available');
			if ( isset($updates[$this->theme_pack]) ) {
				$plugin = $updates[$this->theme_pack];
				$project_id = $this->theme_pack;
				$version = $plugin['new_version'];
				$plugin_url = $plugin['url'];
			} else {
				return false;
			}
	
			$plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
			$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );
	
			$info_url = $this->server_url . '?action=details&id=' . $project_id . '&TB_iframe=true&width=640&height=800';
			if ( file_exists(WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php') ) {
				$message = "Activate WPMU DEV Dashboard";
				$action_url = $this->activate_url();
			} else { //dashboard not installed at all
				$message = "Install WPMU DEV Dashboard";
				$action_url = $this->auto_install_url();
			}
			
			if ( current_user_can('update_themes') ) {
				echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update colspanchange"><div class="update-message">';
				printf( 'There is a new version of %1$s available on WPMU DEV. <a href="%2$s" class="thickbox" title="%3$s">View version %4$s details</a> or <a href="%5$s">%6$s</a> to update.', $plugin_name, esc_url($info_url), esc_attr($plugin_name), $version, esc_url($action_url), $message );
				echo '</div></td></tr>';
			}
		}
		
		function disable_checkboxes() {
	
			$updates = get_site_option('wdp_un_updates_available');
			if ( !is_array( $updates ) || ( is_array( $updates ) && !count( $updates ) ) ) {
				return;
			}
		
			$jquery = '';
			foreach ( (array) $updates as $id => $plugin) {
				$jquery .= "<script type='text/javascript'>jQuery(\"input:checkbox[value='".esc_attr($plugin['filename'])."']\").remove();</script>\n";
			}
	
			//disable checkboxes for 133 theme pack themes
			$local_themes = get_site_option('wdp_un_local_themes');
			if ( is_array($local_themes) && count($local_themes) ) {
				foreach ( $local_themes as $id => $theme ) {
					$jquery .= "<script type='text/javascript'>jQuery(\"input:checkbox[value='".esc_attr($theme['filename'])."']\").remove();</script>\n";
				}
			}
			echo $jquery;
		}
		
		function set_activate_flag($plugin) {
			$data = $this->get_id_plugin( WP_PLUGIN_DIR . '/' . $plugin );
			if ( isset($data['id']) && !empty($data['id']) ) {
				update_site_option('wdp_un_activated_flag', 1);
			}
		}
		
	}
	$WPMUDEV_Dashboard_Notice3 = new WPMUDEV_Dashboard_Notice3();
}

//disable older version
if ( !class_exists('WPMUDEV_Dashboard_Notice') ) {
	class WPMUDEV_Dashboard_Notice {}
}