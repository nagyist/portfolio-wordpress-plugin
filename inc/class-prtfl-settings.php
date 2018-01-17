<?php
/**
 * Displays the content on the plugin settings page
 */

require_once( dirname( dirname( __FILE__ ) ) . '/bws_menu/class-bws-settings.php' );

if ( ! class_exists( 'Prtfl_Settings_Tabs' ) ) {
	class Prtfl_Settings_Tabs extends Bws_Settings_Tabs {
		public $wp_image_sizes = array();
		public $cstmsrch_options, $fields;

		/**
		 * Constructor.
		 *
		 * @access public
		 *
		 * @see Bws_Settings_Tabs::__construct() for more information on default arguments.
		 *
		 * @param string $plugin_basename
		 */
		public function __construct( $plugin_basename ) {
			global $prtfl_options, $prtfl_plugin_info, $prtfl_BWS_demo_data;

			$tabs = array(
				'settings' 		=> array( 'label' => __( 'Settings', 'portfolio' ) ),
				'misc' 			=> array( 'label' => __( 'Misc', 'portfolio' ) ),
				'custom_code' 	=> array( 'label' => __( 'Custom Code', 'portfolio' ) ),
				'import-export' => array( 'label' => __( 'Import / Export', 'portfolio' ) ),
				'license'		=> array( 'label' => __( 'License Key', 'portfolio' ) ),
			);

			parent::__construct( array(
				'plugin_basename' 	 => $plugin_basename,
				'plugins_info'		 => $prtfl_plugin_info,
				'prefix' 			 => 'prtfl',
				'default_options' 	 => prtfl_get_options_default(),
				'options' 			 => $prtfl_options,
				'tabs' 				 => $tabs,
				'wp_slug'			 => 'portfolio',
				'demo_data'			 => $prtfl_BWS_demo_data,
				'pro_page' 			 => 'edit.php?post_type=portfolio&page=portfolio-pro.php',
				'bws_license_plugin' => 'portfolio-pro/portfolio-pro.php',
				'link_key' 			 => 'f047e20c92c972c398187a4f70240285',
				'link_pn' 			 => '74',
			) );

			$wp_sizes = get_intermediate_image_sizes();	
		
			foreach ( (array) $wp_sizes as $size ) {
				if ( ! array_key_exists( $size, $prtfl_options['custom_size_px'] ) ) {
					if ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
						$width  = absint( $_wp_additional_image_sizes[ $size ]['width'] );
						$height = absint( $_wp_additional_image_sizes[ $size ]['height'] );
					} else {
						$width  = absint( get_option( $size . '_size_w' ) );
						$height = absint( get_option( $size . '_size_h' ) );
					}

					if ( ! $width && ! $height ) {
						$this->wp_image_sizes[] = array(
							'value'  => $size,
							'name'   => ucwords( str_replace( array( '-', '_' ), ' ', $size ) ),
						);
					} else {
						$this->wp_image_sizes[] = array(
							'value'  => $size,
							'name'   => ucwords( str_replace( array( '-', '_' ), ' ', $size ) ) . ' (' . $width . ' &#215; ' . $height . ')',
							'width'  => $width,
							'height' => $height
						);
					}
				}
			}

			$this->cstmsrch_options = get_option( 'cstmsrch_options' );

			$this->fields = array(
				'executor'			=> __( 'Executors', 'portfolio' ),
				'technologies'		=> __( 'Technologies', 'portfolio' ),
				'date'				=> __( 'Date of completion', 'portfolio' ),
				'link'				=> __( 'Link', 'portfolio' ),
				'shrdescription'	=> __( 'Short Description', 'portfolio' ),
				'description'		=> __( 'Description', 'portfolio' ),
				'svn'				=> __( 'SVN URL', 'portfolio' )
			);

			add_action( get_parent_class( $this ) . '_display_custom_messages', array( $this, 'display_custom_messages' ) );
			add_action( get_parent_class( $this ) . '_additional_misc_options_affected', array( $this, 'additional_misc_options_affected' ) );
			add_action( get_parent_class( $this ) . '_additional_import_export_options', array( $this, 'additional_import_export_options' ) );
			add_action( get_parent_class( $this ) . '_display_metabox', array( $this, 'display_metabox' ) );
		}

		/**
		 * Save plugin options to the database
		 * @access public
		 * @param  void
		 * @return array    The action results
		 */
		public function save_options() {

			$this->options["custom_image_row_count"] = intval( $_POST['prtfl_custom_image_row_count'] );
			if ( 1 > $this->options["custom_image_row_count"] )
				$this->options["custom_image_row_count"] = 1;

			$new_image_size_photo 		= esc_attr( $_POST['prtfl_image_size_photo'] );
			$custom_image_size_w_photo 	= intval( $_POST['prtfl_custom_image_size_w_photo'] );
			$custom_image_size_h_photo 	= intval( $_POST['prtfl_custom_image_size_h_photo'] );
			$custom_size_px_photo 		= array( $custom_image_size_w_photo, $custom_image_size_h_photo );
			if ( 'portfolio-photo-thumb' == $new_image_size_photo ) {
				if ( $new_image_size_photo != $this->options['image_size_photo'] ) {
					$need_image_update = true;
				} else {
					foreach ( $custom_size_px_photo as $key => $value ) {
						if ( $value != $this->options['custom_size_px']['portfolio-photo-thumb'][ $key ] ) {
							$need_image_update = true;
							break;
						}
					}
				}
			}			
			$this->options['custom_size_px']['portfolio-photo-thumb'] = $custom_size_px_photo;
			$this->options['image_size_photo'] 				= $new_image_size_photo;

			$new_image_size_album 		= esc_attr( $_POST['prtfl_image_size_album'] );
			$custom_image_size_w_album 	= intval( $_POST['prtfl_custom_image_size_w_album'] );
			$custom_image_size_h_album 	= intval( $_POST['prtfl_custom_image_size_h_album'] );
			$custom_size_px_album 		= array( $custom_image_size_w_album, $custom_image_size_h_album );
			if ( 'portfolio-thumb' == $new_image_size_album ) {
				if ( $new_image_size_album != $this->options['image_size_album'] ) {
					$need_image_update = true;
				} else {
					foreach ( $custom_size_px_album as $key => $value ) {
						if ( $value != $this->options['custom_size_px']['portfolio-thumb'][ $key ] ) {
							$need_image_update = true;
							break;
						}
					}
				}
			}

			$this->options['custom_size_px']['portfolio-thumb'] = $custom_size_px_album;
			$this->options['image_size_album'] 				= $new_image_size_album;

			if ( ! empty( $_POST['prtfl_page_id_portfolio_template'] ) && $this->options['page_id_portfolio_template'] != intval( $_POST['prtfl_page_id_portfolio_template'] ) ) {
				/* for rewrite */
				$this->options["flush_rewrite_rules"] = 1;
				$this->options['page_id_portfolio_template'] = intval( $_POST['prtfl_page_id_portfolio_template'] );
			}

			$this->options["order_by"]		= esc_attr( $_POST['prtfl_order_by'] );
			$this->options["order"]			= esc_attr( $_POST['prtfl_order'] );

			if ( ! empty( $need_image_update ) )
				$this->options['need_image_update'] = __( 'Custom image size was changed. You need to update project images.', 'portfolio' );

			$this->options["link_additional_field_for_non_registered"] = isset( $_REQUEST["prtfl_link_additional_field_for_non_registered"] ) ? 1 : 0;

			foreach ( $this->fields as $field_key => $field_title ) {
				$this->options[ $field_key . '_additional_field'] = isset( $_REQUEST['prtfl_' . $field_key . '_additional_field'] ) ? 1 : 0;
				$this->options[ $field_key . '_text_field'] = stripslashes( esc_html( $_REQUEST['prtfl_' . $field_key . '_text_field'] ) );
			}

			$this->options['screenshot_text_field'] = stripslashes( esc_html( $_REQUEST['prtfl_screenshot_text_field'] ) );

			$slug = strtolower( trim( stripslashes( esc_html( $_POST['prtfl_slug'] ) ) ) );
			$slug = preg_replace( "/[^a-z0-9\s-]/", "", $slug );
			$slug = trim( preg_replace( "/[\s-]+/", " ", $slug ) );
			$slug = preg_replace( "/\s/", "-", $slug );
			if ( $this->options["slug"] != $slug )
				$this->options["flush_rewrite_rules"] = 1;
			$this->options["slug"] = $slug;

			if ( ! empty( $this->cstmsrch_options ) ) {
				if ( isset( $this->cstmsrch_options['output_order'] ) ) {
					$is_enabled = isset( $_POST['prtfl_add_to_search'] ) ? 1 : 0;
					$post_type_exist = false;
					foreach ( $this->cstmsrch_options['output_order'] as $key => $item ) {
						if ( $item['name'] == 'portfolio' && $item['type'] == 'post_type' ) {
							$post_type_exist = true;
							if ( $item['enabled'] != $is_enabled ) {
								$this->cstmsrch_options['output_order'][ $key ]['enabled'] = $is_enabled;
								$cstmsrch_options_update = true;
							}
							break;
						}
					}	
					if ( ! $post_type_exist ) {
						$this->cstmsrch_options['output_order'][] = array( 
							'name' 		=> 'portfolio',
							'type' 		=> 'post_type',
							'enabled' 	=> $is_enabled );
						$cstmsrch_options_update = true;
					}					
				} else if ( isset( $this->cstmsrch_options['post_types'] ) ) {
					if ( isset( $_POST['prtfl_add_to_search'] ) && ! in_array( 'portfolio', $this->cstmsrch_options['post_types'] ) ) {
						array_push( $this->cstmsrch_options['post_types'], 'portfolio' );
						$cstmsrch_options_update = true;
					} else if ( ! isset( $_POST['prtfl_add_to_search'] ) && in_array( 'portfolio', $this->cstmsrch_options['post_types'] ) ) {
						unset( $this->cstmsrch_options['post_types'][ array_search( 'portfolio', $this->cstmsrch_options['post_types'] ) ] );
						$cstmsrch_options_update = true;
					}
				}
				if ( isset( $cstmsrch_options_update ) )
					update_option( 'cstmsrch_options', $this->cstmsrch_options );
			}

			update_option( 'prtfl_options', $this->options );
			$message = __( "Settings saved.", 'portfolio' );

			return compact( 'message', 'notice', 'error' );
		}

		/**
		 * Display custom error\message\notice
		 * @access public
		 * @param  $save_results - array with error\message\notice
		 * @return void
		 */
		public function display_custom_messages( $save_results ) { ?>
			<noscript><div class="error below-h2"><p><strong><?php _e( "Please enable JavaScript in Your browser.", 'portfolio' ); ?></strong></p></div></noscript>
			<?php if ( ! empty( $this->options['need_image_update'] ) ) { ?>
				<div class="updated bws-notice inline prtfl_image_update_message">
					<p>
						<?php echo $this->options['need_image_update']; ?>
						<input type="button" value="<?php _e( 'Update Images', 'portfolio' ); ?>" id="prtfl_ajax_update_images" name="ajax_update_images" class="button" />
					</p>
				</div>
			<?php }
		}

		/**
		 *
		 */
		public function tab_settings() { ?>
			<h3 class="bws_tab_label"><?php _e( 'Portfolio Settings', 'portfolio' ); ?></h3>
			<?php $this->help_phrase(); ?>
			<hr>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Number of Columns', 'portfolio' ); ?> </th>
					<td>
						<input type="number" name="prtfl_custom_image_row_count" min="1" max="10000" value="<?php echo $this->options["custom_image_row_count"]; ?>" /> <?php _e( 'columns', 'portfolio' ); ?>
						 <div class="bws_info"><?php printf( __( 'Number of image columns (default is %s).', 'portfolio' ), '3' ); ?></div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Image Size', 'portfolio' ); ?></th>
					<td>
						<select name="prtfl_image_size_photo">
							<?php foreach ( $this->wp_image_sizes as $data ) { ?>
								<option value="<?php echo $data['value']; ?>" <?php selected( $data['value'], $this->options['image_size_photo'] ); ?>><?php echo $data['name']; ?></option>
							<?php } ?>
							<option value="portfolio-photo-thumb" <?php selected( 'portfolio-photo-thumb', $this->options['image_size_photo'] ); ?> class="bws_option_affect" data-affect-show=".prtfl_for_custom_image_size"><?php _e( 'Custom', 'portfolio' ); ?></option>
						</select>
						<div class="bws_info"><?php _e( 'Maximum portfolio image size. "Custom" uses the Image Dimensions values.', 'portfolio' ); ?></div>
					</td>
				</tr>
				<tr valign="top" class="prtfl_for_custom_image_size">
					<th scope="row"><?php _e( 'Custom Image Size', 'portfolio' ); ?> </th>
					<td>
						<input type="number" name="prtfl_custom_image_size_w_photo" min="1" max="10000" value="<?php echo $this->options['custom_size_px']['portfolio-photo-thumb'][0]; ?>" /> x <input type="number" name="prtfl_custom_image_size_h_photo" min="1" max="10000" value="<?php echo $this->options['custom_size_px']['portfolio-photo-thumb'][1]; ?>" /> px <div class="bws_info"><?php _e( "Adjust these values based on the number of columns in your project. This won't affect the full size of your images in the lightbox.", 'portfolio' ); ?></div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Portfolio Page', 'portfolio' ); ?></th>
					<td>
						<?php wp_dropdown_pages( array(
							'depth'                 => 0,
							'selected'              => $this->options['page_id_portfolio_template'],
							'name'                  => 'prtfl_page_id_portfolio_template',
							'show_option_none'		=> '...'
						) ); ?>
						<div class="bws_info"><?php _e( 'Base page where all existing projects will be displayed.' , 'portfolio'); ?></div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Cover Image Size', 'portfolio' ); ?> </th>
					<td>
						<select name="prtfl_image_size_album">							
							<?php foreach ( $this->wp_image_sizes as $data ) { ?>
								<option value="<?php echo $data['value']; ?>" <?php selected( $data['value'], $this->options['image_size_album'] ); ?>><?php echo $data['name']; ?></option>
							<?php } ?>
							<option value="portfolio-thumb" <?php selected( 'portfolio-thumb', $this->options['image_size_album'] ); ?> class="bws_option_affect" data-affect-show=".prtfl_for_custom_image_size_album"><?php _e( 'Custom', 'portfolio' ); ?></option>
						</select>
						<div class="bws_info"><?php _e( 'Maximum cover image size. Custom uses the Image Dimensions values.', 'portfolio' ); ?></div>
					</td>
				</tr>			
				<tr valign="top" class="prtfl_for_custom_image_size_album">
					<th scope="row"><?php _e( 'Custom Cover Image Size', 'portfolio' ); ?> </th>
					<td>
						<input type="number" name="prtfl_custom_image_size_w_album" min="1" max="10000" value="<?php echo $this->options['custom_size_px']['portfolio-thumb'][0]; ?>" /> x <input type="number" name="prtfl_custom_image_size_h_album" min="1" max="10000" value="<?php echo $this->options['custom_size_px']['portfolio-thumb'][1]; ?>" /> px
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Sort Projects by', 'portfolio' ); ?></th>
					<td>
						<select name="prtfl_order_by">
							<option value="ID" <?php selected( 'ID', $this->options["order_by"] ); ?>><?php _e( 'Project ID', 'portfolio' ); ?></option>
							<option value="title" <?php selected( 'title', $this->options["order_by"] ); ?>><?php _e( 'Title', 'portfolio' ); ?></option>
							<option value="date" <?php selected( 'date', $this->options["order_by"] ); ?>><?php _e( 'Date', 'portfolio' ); ?></option>
							<option value="modified" <?php selected( 'modified', $this->options["order_by"] ); ?>><?php _e( 'Last modified date', 'portfolio' ); ?></option>
							<option value="comment_count" <?php selected( 'comment_count', $this->options["order_by"] ); ?>><?php _e( 'Comment count', 'portfolio' ); ?></option>	
							<option value="menu_order" <?php selected( 'menu_order', $this->options["order_by"] ); ?>><?php _e( 'Sorting order (the input field for sorting order)', 'portfolio' ); ?></option>
							<option value="author" <?php selected( 'author', $this->options["order_by"] ); ?>><?php _e( 'Author', 'portfolio' ); ?></option>	
							<option value="rand" <?php selected( 'rand', $this->options["order_by"] ); ?>><?php _e( 'Random', 'portfolio' ); ?></option>							
						</select>
						<div class="bws_info"><?php _e( 'Select projects sorting order in your portfolio page.', 'portfolio' ); ?></div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Arrange Projects by', 'portfolio' ); ?></th>
					<td>
						<fieldset>
							<label><input type="radio" name="prtfl_order" value="ASC" <?php checked( 'ASC', $this->options["order"] ); ?> /> <?php _e( 'Ascending (e.g. 1, 2, 3; a, b, c)', 'portfolio' ); ?></label>
							<br />
							<label><input type="radio" name="prtfl_order" value="DESC" <?php checked( 'DESC', $this->options["order"] ); ?> /> <?php _e( 'Descending (e.g. 3, 2, 1; c, b, a)', 'portfolio' ); ?></label>
						</fieldset>
					</td>
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'portfolio' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr valign="top">
								<th scope="row"><?php _e( 'Manual Sorting', 'portfolio' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="prtfl_sorting_selectbox" value="1" disabled="disabled" /> <span class="bws_info"><?php _e( 'Enable to sort projects manually by date or title.', 'portfolio' ); ?></span>
									</label>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Lightbox Helper', 'portfolio' ); ?></th>
								<td>
									<input disabled type="checkbox" name="" /> <span class="bws_info"><?php _e( 'Enable to use a lightbox helper navigation between images.', 'portfolio' ); ?></span>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Lightbox Helper Type', 'portfolio' ); ?></th>
								<td>
									<select disabled name="">
										<option><?php _e( 'Thumbnails', 'portfolio' ); ?></option>
										<option><?php _e( 'Buttons', 'portfolio' ); ?></option>
									</select>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php } ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Text Link', 'portfolio' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="prtfl_link_additional_field_for_non_registered" value="1" id="prtfl_link_additional_field_for_non_registered" <?php checked( 1, $this->options['link_additional_field_for_non_registered'] ); ?> /> <span class="bws_info"><?php _e( 'Enable to display link field as a text for non-registered users.', 'portfolio' ); ?></span>
						</label>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Projects Fields', 'portfolio' ); ?> </th>
					<td>
						<fieldset>
							<?php foreach ( $this->fields as $field_key => $field_title ) { ?>
								<label>
									<input<?php echo $this->change_permission_attr; ?> type="checkbox" name="prtfl_<?php echo $field_key; ?>_additional_field" value="1" <?php checked( 1, $this->options[ $field_key . '_additional_field'] ); ?> />
									 <?php echo $field_title; ?>
									<br>
									<input<?php echo $this->change_permission_attr; ?> type="text" name="prtfl_<?php echo $field_key; ?>_text_field" maxlength="250" value="<?php echo $this->options[ $field_key . '_text_field']; ?>" />
								</label>
								<br />
							<?php } ?>							
							<label>
								 <?php _e( '"More screenshots" block', 'portfolio' ); ?>
								<br>
								<input type="text" name="prtfl_screenshot_text_field" maxlength="250" value="<?php echo $this->options["screenshot_text_field"]; ?>" />								
							</label>
						</fieldset>
					</td>
				</tr>
			</table>
			<?php if ( ! $this->hide_pro_tabs ) { ?>
				<div class="bws_pro_version_bloc">
					<div class="bws_pro_version_table_bloc">
						<button type="submit" name="bws_hide_premium_options" class="notice-dismiss bws_hide_premium_options" title="<?php _e( 'Close', 'portfolio' ); ?>"></button>
						<div class="bws_table_bg"></div>
						<table class="form-table bws_pro_version">
							<tr valign="top">
								<th scope="row"><?php _e( 'Projects Fields', 'portfolio' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" name="prtfl_categories_additional_field" value="1" disabled="disabled" />
											 <?php _e( 'Categories', 'portfolio' ); ?><br />
											<input type="text" name="prtfl_categories_text_field" value="<?php _e( 'Categories', 'portfolio' ); ?>:" disabled="disabled" />											
										</label><br />
										<label>
											<input type="checkbox" name="prtfl_sectors_additional_field" value="1" disabled="disabled" />
											 <?php _e( 'Sectors', 'portfolio' ); ?><br />
											<input type="text" name="prtfl_sectors_text_field" value="<?php _e( 'Sectors', 'portfolio' ); ?>:" disabled="disabled" />											
										</label><br />
										<label>
											<input type="checkbox" name="prtfl_services_additional_field" value="1" disabled="disabled" />
											 <?php _e( 'Services', 'portfolio' ); ?><br />
											<input type="text" name="prtfl_services_text_field" value="<?php _e( 'Services', 'portfolio' ); ?>:" disabled="disabled" />											
										</label><br />
										<label>
											<input type="checkbox" name="prtfl_client_additional_field" value="1" disabled="disabled" />
											 <?php _e( 'Client', 'portfolio' ); ?><br />
											<input type="text" name="prtfl_client_text_field" value="<?php _e( 'Client', 'portfolio' ); ?>:" disabled="disabled" />											
										</label><br />
										<label><input type="checkbox" name="prtfl_disbable_screenshot_block" value="1" disabled="disabled" /> <?php _e( '"More screenshots" block', 'portfolio' ); ?></label><br />
									</fieldset>
								</td>
							</tr>
						</table>
					</div>
					<?php $this->bws_pro_block_links(); ?>
				</div>
			<?php }
		}

		/**
		 *
		 */
		public function additional_import_export_options() { ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><?php _e( 'Demo Data', 'portfolio' ); ?></th>
					<td>
						<?php $this->demo_data->bws_show_demo_button( __( 'Install demo data to create portfolio projects with images, post with shortcodes and page with a list of all portfolio projects.', 'portfolio' ) ); ?>
					</td>
				</tr>
			</table>			
		<?php }

		/**
		 * Display custom options on the 'misc' tab
		 * @access public
		 */
		public function additional_misc_options_affected() {
			global $wp_version;
			if ( ! $this->all_plugins ) {
				if ( ! function_exists( 'get_plugins' ) )
					require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
				$this->all_plugins = get_plugins();
			} ?>					
			<tr valign="top">
				<th scope="row"><?php _e( 'Portfolio Slug', 'portfolio' ); ?></th>
				<td>
					<input type="text" name="prtfl_slug" maxlength="100" value="<?php echo $this->options["slug"]; ?>" />
					<div class="bws_info"><?php _e( 'Enter the unique portfolio slug.', 'portfolio' ); ?></div>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Search Portfolio Projects', 'portfolio' ); ?></th>
				<td>
					<?php $disabled = $checked = $link = '';
					if ( array_key_exists( 'custom-search-plugin/custom-search-plugin.php', $this->all_plugins ) || array_key_exists( 'custom-search-pro/custom-search-pro.php', $this->all_plugins ) ) {
						if ( ! is_plugin_active( 'custom-search-plugin/custom-search-plugin.php' ) && ! is_plugin_active( 'custom-search-pro/custom-search-pro.php' ) ) {
							$disabled = ' disabled="disabled"';
							$link = '<a href="' . admin_url( 'plugins.php' ) . '">' . __( 'Activate Now', 'portfolio' ) . '</a>';
						}
						if ( isset( $this->cstmsrch_options['output_order'] ) ) {
							foreach ( $this->cstmsrch_options['output_order'] as $key => $item ) {
								if ( $item['name'] == 'portfolio' && $item['type'] == 'post_type' ) {
									if ( $item['enabled'] )
										$checked = ' checked="checked"';
									break;
								}
							}
						} elseif ( ! empty( $this->cstmsrch_options['post_types'] ) && in_array( 'portfolio', $this->cstmsrch_options['post_types'] ) ) {
							$checked = ' checked="checked"';
						}
					} else { 
						$disabled = ' disabled="disabled"';
						$link = '<a href="https://bestwebsoft.com/products/wordpress/plugins/custom-search/?k=75e20470c8716645cf65febf9d30f269&amp;pn=' . $this->link_pn . '&amp;v=' . $this->plugins_info["Version"] . '&amp;wp_v=' . $wp_version . '" target="_blank">' . __( 'Install Now', 'portfolio' ) . '</a>';
					} ?>
					<input type="checkbox" name="prtfl_add_to_search" value="1"<?php echo $disabled . $checked; ?> />
					 <span class="bws_info"><?php _e( 'Enable to include portfolio projects to your website search.', 'portfolio' ); ?> <?php printf( __( '%s plugin is required.', 'portfolio' ), 'Custom Search' ); ?> <?php echo $link; ?></span>					
				</td>
			</tr>
		<?php }

		/**
		 * Display custom metabox
		 * @access public
		 * @param  void
		 * @return array    The action results
		 */
		public function display_metabox() { ?>
			<div class="postbox">
				<h3 class="hndle">
					<?php _e( 'Portfolio Shortcode', 'portfolio' ); ?>
				</h3>
				<div class="inside">
					<?php _e( "Add the latest portfolio projects using the following shortcode (where * is a number of projects to display):", 'portfolio' ); ?>
					<?php bws_shortcode_output( '[latest_portfolio_items count=*]' ); ?>							
				</div>
			</div>
		<?php }
	}
}