<?php
/**
 * Demo Settings Page
 * 
 * NOTE: This file is FOR DEMO PLUGIN PURPOSES ONLY.
 * Do NOT copy this file to your plugin. This is only for testing/demoing
 * the notice functionality in this demo plugin environment.
 * 
 * Only copy admin/notice.php to your plugin.
 *
 * @package Demo_Delayed_Admin_Notice
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all admin pages for dropdown
 * 
 * @return array Array of screen IDs/slugs => page titles
 */
function demo_notice_get_admin_pages() {
	global $menu, $submenu;
	
	$pages = array();
	
	// Get top-level menu items (use slug directly for .php files)
	if ( ! empty( $menu ) ) {
		foreach ( $menu as $menu_item ) {
			if ( ! empty( $menu_item[0] ) && ! empty( $menu_item[2] ) ) {
				$title = wp_strip_all_tags( $menu_item[0] );
				$slug = $menu_item[2];
				
				// Skip separators
				if ( empty( $slug ) || 'separator' === substr( $slug, 0, 9 ) ) {
					continue;
				}
				
				$pages[ $slug ] = $title;
			}
		}
	}
	
	// Get submenu items (construct screen IDs)
	if ( ! empty( $submenu ) ) {
		foreach ( $submenu as $parent_slug => $submenu_items ) {
			if ( ! empty( $submenu_items ) ) {
				foreach ( $submenu_items as $submenu_item ) {
					if ( ! empty( $submenu_item[0] ) && ! empty( $submenu_item[2] ) ) {
						$title = wp_strip_all_tags( $submenu_item[0] );
						$slug = $submenu_item[2];
						
						// For submenu items, construct screen ID
						// Pattern: {parent_base}_page_{slug}
						// e.g., settings_page_your-plugin or tools_page_your-plugin
						$parent_base = basename( $parent_slug, '.php' );
						
						// Handle query strings in parent slug
						if ( strpos( $parent_base, '?' ) !== false ) {
							$parent_base = substr( $parent_base, 0, strpos( $parent_base, '?' ) );
						}
						
						// If slug is a .php file, use it directly, otherwise construct screen ID
						if ( strpos( $slug, '.php' ) !== false ) {
							$screen_id = $slug;
						} else {
							$screen_id = $parent_base . '_page_' . $slug;
						}
						
						$pages[ $screen_id ] = $title;
					}
				}
			}
		}
	}
	
	// Sort alphabetically by title
	asort( $pages );
	
	return $pages;
}

/**
 * Render demo settings page
 */
function demo_notice_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	$admin_pages = demo_notice_get_admin_pages();
	$generated_code = get_transient( 'demo_notice_generated_code' );
	$demo_config = get_transient( 'demo_notice_config' );
	$generation_message = get_transient( 'demo_notice_generation_message' );
	?>
	<div class="wrap demo-notice-settings-wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		
		<p class="description">
			<?php esc_html_e( 'This page allows you to trigger demo notices in this admin environment and to generate your instantiation code for your own purposes.', 'demo-delayed-admin-notice' ); ?>
		</p>
		
		<div class="demo-notice-generator-container">
			<h2><?php esc_html_e( 'Notice Generator', 'demo-delayed-admin-notice' ); ?></h2>
			
			<form id="demo-notice-generator-form" method="post">
				<?php wp_nonce_field( 'demo_notice_generate', 'demo_notice_nonce' ); ?>
				
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">
							<label for="demo_notice_page"><?php esc_html_e( 'Page', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<select name="demo_notice_page" id="demo_notice_page" class="regular-text" required>
								<option value=""><?php esc_html_e( '-- Select Page --', 'demo-delayed-admin-notice' ); ?></option>
								<?php foreach ( $admin_pages as $slug => $title ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $demo_config['page'] ?? '', $slug ); ?>>
										<?php echo esc_html( $title ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="demo_notice_delay_days"><?php esc_html_e( 'Days Delay', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<select name="demo_notice_delay_days" id="demo_notice_delay_days" class="regular-text" required>
								<?php for ( $i = 1; $i <= 30; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $demo_config['delay_days'] ?? 30, $i ); ?>>
										<?php echo esc_html( $i ); ?>
									</option>
								<?php endfor; ?>
							</select>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="demo_notice_position"><?php esc_html_e( 'Position', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<select name="demo_notice_position" id="demo_notice_position" class="regular-text" required>
								<option value="top" <?php selected( $demo_config['position'] ?? 'top', 'top' ); ?>>
									<?php esc_html_e( 'Top', 'demo-delayed-admin-notice' ); ?>
								</option>
								<option value="bottom-right" <?php selected( $demo_config['position'] ?? 'top', 'bottom-right' ); ?>>
									<?php esc_html_e( 'Bottom Right', 'demo-delayed-admin-notice' ); ?>
								</option>
								<option value="bottom-left" <?php selected( $demo_config['position'] ?? 'top', 'bottom-left' ); ?>>
									<?php esc_html_e( 'Bottom Left', 'demo-delayed-admin-notice' ); ?>
								</option>
							</select>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="demo_notice_prefix"><?php esc_html_e( 'Prefix', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<input type="text" name="demo_notice_prefix" id="demo_notice_prefix" value="<?php echo esc_attr( $demo_config['prefix'] ?? 'your_prefix' ); ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'Used in the generated code only (e.g., myplugin_review)', 'demo-delayed-admin-notice' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="demo_notice_plugin_name"><?php esc_html_e( 'Plugin Name', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<input type="text" name="demo_notice_plugin_name" id="demo_notice_plugin_name" value="<?php echo esc_attr( $demo_config['plugin_name'] ?? 'Your Plugin Name' ); ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'Plugin name displayed in the notice (e.g., My Awesome Plugin)', 'demo-delayed-admin-notice' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'CTAs', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<fieldset>
								<label>
									<input type="checkbox" name="demo_notice_ctas[]" value="review" id="demo_notice_cta_review" <?php checked( in_array( 'review', $demo_config['ctas'] ?? array( 'review' ) ), true ); ?> />
									<?php esc_html_e( 'Reviews page', 'demo-delayed-admin-notice' ); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="demo_notice_ctas[]" value="donation" id="demo_notice_cta_donation" <?php checked( in_array( 'donation', $demo_config['ctas'] ?? array() ), true ); ?> />
									<?php esc_html_e( 'Donation', 'demo-delayed-admin-notice' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
					
					<tr class="demo-notice-plugin-slug-row" style="<?php echo ( in_array( 'review', $demo_config['ctas'] ?? array( 'review' ) ) ) ? '' : 'display:none;'; ?>">
						<th scope="row">
							<label for="demo_notice_plugin_slug"><?php esc_html_e( 'Plugin slug', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<input type="text" name="demo_notice_plugin_slug" id="demo_notice_plugin_slug" value="<?php echo esc_attr( $demo_config['plugin_slug'] ?? '' ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Your WordPress.org plugin slug (e.g., my-plugin-name)', 'demo-delayed-admin-notice' ); ?></p>
						</td>
					</tr>
					
					<tr class="demo-notice-donation-url-row" style="<?php echo ( in_array( 'donation', $demo_config['ctas'] ?? array() ) ) ? '' : 'display:none;'; ?>">
						<th scope="row">
							<label for="demo_notice_donation_url"><?php esc_html_e( 'Donation URL', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<input type="url" name="demo_notice_donation_url" id="demo_notice_donation_url" value="<?php echo esc_attr( $demo_config['donation_url'] ?? '' ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Your donation URL', 'demo-delayed-admin-notice' ); ?></p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="demo_notice_remind_again"><?php esc_html_e( 'Remind again?', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<select name="demo_notice_remind_again" id="demo_notice_remind_again" class="regular-text" required>
								<option value="no" <?php selected( $demo_config['enable_remind_again'] ?? false, false ); ?>>
									<?php esc_html_e( 'No', 'demo-delayed-admin-notice' ); ?>
								</option>
								<option value="yes" <?php selected( $demo_config['enable_remind_again'] ?? false, true ); ?>>
									<?php esc_html_e( 'Yes', 'demo-delayed-admin-notice' ); ?>
								</option>
							</select>
						</td>
					</tr>
					
					<tr class="demo-notice-remind-options" style="<?php echo ( $demo_config['enable_remind_again'] ?? false ) ? '' : 'display:none;'; ?>">
						<th scope="row">
							<label for="demo_notice_remind_mode"><?php esc_html_e( 'Remind again mode', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<select name="demo_notice_remind_mode" id="demo_notice_remind_mode" class="regular-text">
								<option value="once" <?php selected( $demo_config['remind_again_mode'] ?? 'once', 'once' ); ?>>
									<?php esc_html_e( 'Once', 'demo-delayed-admin-notice' ); ?>
								</option>
								<option value="interval" <?php selected( $demo_config['remind_again_mode'] ?? 'once', 'interval' ); ?>>
									<?php esc_html_e( 'Ongoing', 'demo-delayed-admin-notice' ); ?>
								</option>
							</select>
							<p class="description"><?php esc_html_e( 'Once: Remind only once. Ongoing: Continue reminding at the specified interval.', 'demo-delayed-admin-notice' ); ?></p>
						</td>
					</tr>
					
					<tr class="demo-notice-remind-options" style="<?php echo ( $demo_config['enable_remind_again'] ?? false ) ? '' : 'display:none;'; ?>">
						<th scope="row">
							<label for="demo_notice_remind_days"><?php esc_html_e( 'Remind again days', 'demo-delayed-admin-notice' ); ?></label>
						</th>
						<td>
							<select name="demo_notice_remind_days" id="demo_notice_remind_days" class="regular-text">
								<?php for ( $i = 1; $i <= 30; $i++ ) : ?>
									<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $demo_config['remind_again_days'] ?? 60, $i ); ?>>
										<?php echo esc_html( $i ); ?>
									</option>
								<?php endfor; ?>
							</select>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<button type="submit" name="demo_notice_generate" id="demo-notice-generate-btn" class="button button-primary">
						<?php esc_html_e( 'Generate', 'demo-delayed-admin-notice' ); ?>
					</button>
				</p>
			</form>
			
			<div id="demo-notice-ajax-response" style="display:none;"></div>
			
			<?php if ( ! empty( $generation_message ) ) : ?>
				<div id="demo-notice-confirmation-message" class="demo-notice-success-message">
					<?php echo wp_kses_post( $generation_message ); ?>
				</div>
			<?php endif; ?>
			
			<?php if ( ! empty( $generated_code ) ) : ?>
				<div id="demo-notice-generated-code" class="demo-notice-code-block">
					<div class="demo-notice-code-header">
						<span class="demo-notice-code-title"><?php esc_html_e( 'Your Instantiation Code', 'demo-delayed-admin-notice' ); ?></span>
						<button type="button" class="demo-notice-copy-btn" data-code="<?php echo esc_attr( htmlspecialchars( $generated_code, ENT_QUOTES, 'UTF-8' ) ); ?>">
							<span class="dashicons dashicons-clipboard"></span>
							<?php esc_html_e( 'Copy', 'demo-delayed-admin-notice' ); ?>
						</button>
					</div>
					<pre><code><?php echo esc_html( $generated_code ); ?></code></pre>
				</div>
			<?php endif; ?>
		</div>
	</div>
	
	<style>
	.demo-notice-settings-wrap {
		max-width: 960px;
	}
	
	.demo-notice-generator-container {
		background: #fff;
		border: 1px solid #c3c4c7;
		box-shadow: 0 1px 1px rgba(0,0,0,.04);
		padding: 20px;
		margin-top: 20px;
	}
	
	.demo-notice-code-block {
		margin-top: 30px;
		background: #1d2327;
		border-radius: 4px;
		overflow: hidden;
	}
	
	.demo-notice-code-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 12px 16px;
		background: #2c3338;
		border-bottom: 1px solid #3c434a;
	}
	
	.demo-notice-code-title {
		color: #f0f0f1;
		font-weight: 600;
		font-size: 14px;
	}
	
	.demo-notice-copy-btn {
		display: flex;
		align-items: center;
		gap: 6px;
		padding: 6px 12px;
		background: #2271b1;
		color: #fff;
		border: none;
		border-radius: 3px;
		cursor: pointer;
		font-size: 13px;
		transition: background-color 0.2s;
	}
	
	.demo-notice-copy-btn:hover {
		background: #135e96;
	}
	
	.demo-notice-copy-btn:active {
		background: #0a4b78;
	}
	
	.demo-notice-copy-btn .dashicons {
		font-size: 16px;
		width: 16px;
		height: 16px;
	}
	
	.demo-notice-code-block pre {
		margin: 0;
		padding: 20px;
		overflow-x: auto;
		background: #1d2327;
	}
	
	.demo-notice-code-block code {
		color: #f0f0f1;
		font-family: Consolas, Monaco, monospace;
		font-size: 13px;
		line-height: 1.6;
		white-space: pre;
	}
	
	#demo-notice-ajax-response {
		margin-top: 15px;
		padding: 10px 15px;
		background: #d1e7dd;
		border-left: 4px solid #00a32a;
		border-radius: 4px;
	}
	
	#demo-notice-ajax-response.error {
		background: #f8d7da;
		border-left-color: #d63638;
	}
	
	.demo-notice-success-message {
		margin-top: 15px;
		margin-bottom: 20px;
		padding: 12px 16px;
		background: #d1e7dd;
		border-left: 4px solid #00a32a;
		border-radius: 4px;
		color: #0f5132;
	}
	
	.demo-notice-success-message a {
		color: #0f5132;
		text-decoration: underline;
	}
	
	.demo-notice-success-message a:hover {
		color: #0a3d26;
	}
	</style>
	
	<script>
	jQuery(document).ready(function($) {
		// Toggle remind again options
		$('#demo_notice_remind_again').on('change', function() {
			if ($(this).val() === 'yes') {
				$('.demo-notice-remind-options').show();
			} else {
				$('.demo-notice-remind-options').hide();
			}
		});
		
		// Toggle CTA-dependent fields
		function toggleCtaFields() {
			var hasReview = $('#demo_notice_cta_review').is(':checked');
			var hasDonation = $('#demo_notice_cta_donation').is(':checked');
			
			if (hasReview) {
				$('.demo-notice-plugin-slug-row').show();
			} else {
				$('.demo-notice-plugin-slug-row').hide();
			}
			
			if (hasDonation) {
				$('.demo-notice-donation-url-row').show();
			} else {
				$('.demo-notice-donation-url-row').hide();
			}
		}
		
		$('#demo_notice_cta_review, #demo_notice_cta_donation').on('change', toggleCtaFields);
		toggleCtaFields(); // Run on page load
		
		// Copy code button
		$('.demo-notice-copy-btn').on('click', function() {
			var code = $(this).data('code');
			var $temp = $('<textarea>');
			$('body').append($temp);
			$temp.val(code).select();
			document.execCommand('copy');
			$temp.remove();
			
			// Show feedback
			var $btn = $(this);
			var originalText = $btn.html();
			$btn.html('<span class="dashicons dashicons-yes"></span> <?php echo esc_js( __( 'Copied!', 'demo-delayed-admin-notice' ) ); ?>');
			setTimeout(function() {
				$btn.html(originalText);
			}, 2000);
		});
		
		// AJAX form submission
		$('#demo-notice-generator-form').on('submit', function(e) {
			e.preventDefault();
			
			var $form = $(this);
			var $response = $('#demo-notice-ajax-response');
			var $btn = $('#demo-notice-generate-btn');
			
			$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Generating...', 'demo-delayed-admin-notice' ) ); ?>');
			$response.hide().removeClass('error');
			
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: $form.serialize() + '&action=demo_notice_generate',
				success: function(response) {
					if (response.success) {
						// Show temporary AJAX response
						$response.removeClass('error').html(response.data.message).show();
						// Reload to show code block and persistent message
						setTimeout(function() {
							location.reload();
						}, 500);
					} else {
						$response.addClass('error').html(response.data.message || '<?php echo esc_js( __( 'An error occurred.', 'demo-delayed-admin-notice' ) ); ?>').show();
					}
				},
				error: function() {
					$response.addClass('error').html('<?php echo esc_js( __( 'An error occurred. Please try again.', 'demo-delayed-admin-notice' ) ); ?>').show();
				},
				complete: function() {
					$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Generate', 'demo-delayed-admin-notice' ) ); ?>');
				}
			});
		});
	});
	</script>
	<?php
}

/**
 * Handle AJAX request to generate notice
 */
function demo_notice_handle_generate_ajax() {
	check_ajax_referer( 'demo_notice_generate', 'demo_notice_nonce' );
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'demo-delayed-admin-notice' ) ) );
	}
	
	$page = isset( $_POST['demo_notice_page'] ) ? sanitize_text_field( wp_unslash( $_POST['demo_notice_page'] ) ) : '';
	$delay_days = isset( $_POST['demo_notice_delay_days'] ) ? absint( $_POST['demo_notice_delay_days'] ) : 30;
	$position = isset( $_POST['demo_notice_position'] ) ? sanitize_text_field( wp_unslash( $_POST['demo_notice_position'] ) ) : 'top';
	$prefix = isset( $_POST['demo_notice_prefix'] ) ? sanitize_key( $_POST['demo_notice_prefix'] ) : 'your_prefix';
	$plugin_name = isset( $_POST['demo_notice_plugin_name'] ) ? sanitize_text_field( wp_unslash( $_POST['demo_notice_plugin_name'] ) ) : 'Your Plugin Name';
	$remind_again = isset( $_POST['demo_notice_remind_again'] ) ? sanitize_text_field( wp_unslash( $_POST['demo_notice_remind_again'] ) ) : 'no';
	$remind_mode = isset( $_POST['demo_notice_remind_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['demo_notice_remind_mode'] ) ) : 'once';
	$remind_days = isset( $_POST['demo_notice_remind_days'] ) ? absint( $_POST['demo_notice_remind_days'] ) : 60;
	
	// Get CTAs (checkboxes)
	$ctas = isset( $_POST['demo_notice_ctas'] ) && is_array( $_POST['demo_notice_ctas'] ) ? array_map( 'sanitize_text_field', $_POST['demo_notice_ctas'] ) : array( 'review' );
	$plugin_slug = isset( $_POST['demo_notice_plugin_slug'] ) ? sanitize_text_field( wp_unslash( $_POST['demo_notice_plugin_slug'] ) ) : '';
	$donation_url = isset( $_POST['demo_notice_donation_url'] ) ? esc_url_raw( wp_unslash( $_POST['demo_notice_donation_url'] ) ) : '';
	
	if ( empty( $plugin_name ) ) {
		wp_send_json_error( array( 'message' => __( 'Plugin name is required.', 'demo-delayed-admin-notice' ) ) );
	}
	
	if ( empty( $page ) ) {
		wp_send_json_error( array( 'message' => __( 'Please select a page.', 'demo-delayed-admin-notice' ) ) );
	}
	
	// Validate: if review CTA is selected, plugin slug is required
	if ( in_array( 'review', $ctas, true ) && empty( $plugin_slug ) ) {
		wp_send_json_error( array( 'message' => __( 'Plugin slug is required when "Reviews page" is selected.', 'demo-delayed-admin-notice' ) ) );
	}
	
	// Validate: if donation CTA is selected, donation URL is required
	if ( in_array( 'donation', $ctas, true ) && empty( $donation_url ) ) {
		wp_send_json_error( array( 'message' => __( 'Donation URL is required when "Donation" is selected.', 'demo-delayed-admin-notice' ) ) );
	}
	
	// Get page title for link
	$admin_pages = demo_notice_get_admin_pages();
	$page_title = isset( $admin_pages[ $page ] ) ? $admin_pages[ $page ] : $page;
	
	// Build URL - if it's a screen ID, convert to actual URL
	// Use WordPress's menu_page_url() function if available, otherwise construct manually
	if ( strpos( $page, '_page_' ) !== false ) {
		// Extract slug from screen ID (e.g., 'options-general_page_wp-ai-client' -> 'wp-ai-client')
		$parts = explode( '_page_', $page, 2 );
		if ( isset( $parts[1] ) ) {
			$page_slug = $parts[1];
			// Use WordPress's menu_page_url() function for proper URL generation
			if ( function_exists( 'menu_page_url' ) ) {
				$page_url = menu_page_url( $page_slug, false );
			}
			// Fallback: construct URL manually if menu_page_url() doesn't work
			if ( empty( $page_url ) ) {
				$page_url = admin_url( 'admin.php?page=' . $page_slug );
			}
		} else {
			$page_url = admin_url( $page );
		}
	} else {
		// Direct .php file
		$page_url = admin_url( $page );
	}
	
	// Store config in transient (expires in 1 hour)
	$config = array(
		'page' => $page,
		'delay_days' => $delay_days,
		'position' => $position,
		'prefix' => $prefix,
		'plugin_name' => $plugin_name,
		'enable_remind_again' => 'yes' === $remind_again,
		'remind_again_mode' => $remind_mode,
		'remind_again_days' => $remind_days,
		'ctas' => $ctas,
		'plugin_slug' => $plugin_slug,
		'donation_url' => $donation_url,
	);
	set_transient( 'demo_notice_config', $config, HOUR_IN_SECONDS );
	set_transient( 'demo_notice_active', true, HOUR_IN_SECONDS );
	
	// DEMO MODE: Clear dismissal state for demo notice so it can be seen again
	// This is demo-only logic - do NOT copy to your plugin
	$current_user_id = get_current_user_id();
	if ( $current_user_id ) {
		delete_user_meta( $current_user_id, 'demo_notice_review_notice_dismissed' );
	}
	
	// Generate code
	$code = demo_notice_generate_code( $config );
	set_transient( 'demo_notice_generated_code', $code, HOUR_IN_SECONDS );
	
	// Build response message (store in transient so it persists after page reload)
	$message = sprintf(
		/* translators: %1$s: Link to page, %2$s: Page title */
		__( 'Your notice appears <a href="%1$s">%2$s</a>', 'demo-delayed-admin-notice' ),
		esc_url( $page_url ),
		esc_html( $page_title )
	);
	set_transient( 'demo_notice_generation_message', $message, HOUR_IN_SECONDS );
	
	wp_send_json_success( array(
		'message' => $message,
		'code' => $code,
	) );
}
add_action( 'wp_ajax_demo_notice_generate', 'demo_notice_handle_generate_ajax' );

/**
 * Generate instantiation code
 * 
 * @param array $config Configuration array
 * @return string Generated PHP code
 */
function demo_notice_generate_code( $config ) {
	$prefix = $config['prefix'];
	$prefix_pascal = str_replace( ' ', '_', ucwords( str_replace( '_', ' ', $prefix ) ) );
	
	$target_screen = $config['page'];
	$delay_days = $config['delay_days'];
	$position = $config['position'];
	$plugin_name = isset( $config['plugin_name'] ) ? $config['plugin_name'] : 'Your Plugin Name';
	$enable_remind = $config['enable_remind_again'];
	$remind_mode = $config['remind_again_mode'];
	$remind_days = $config['remind_again_days'];
	$ctas = isset( $config['ctas'] ) ? $config['ctas'] : array( 'review' );
	$plugin_slug = isset( $config['plugin_slug'] ) ? $config['plugin_slug'] : '';
	$donation_url = isset( $config['donation_url'] ) ? $config['donation_url'] : '';
	
	$code = "\$notice = new {$prefix_pascal}_Review_Notice( array(\n";
	$code .= "    'prefix' => '{$prefix}',\n";
	$code .= "    'delay_days' => {$delay_days},\n";
	$code .= "    'target_screens' => array( '{$target_screen}' ),\n";
	$code .= "    'position' => '{$position}',\n";
	$code .= "    'plugin_name' => '" . esc_js( $plugin_name ) . "',\n";
	
	if ( $enable_remind ) {
		$code .= "    'enable_remind_again' => true,\n";
		$code .= "    'remind_again_mode' => '{$remind_mode}',\n";
		$code .= "    'remind_again_days' => {$remind_days},\n";
	}
	
	// Add review_url and donate_url if configured
	if ( in_array( 'review', $ctas, true ) && ! empty( $plugin_slug ) ) {
		$code .= "    'review_url' => 'https://wordpress.org/support/plugin/{$plugin_slug}/reviews/',\n";
	}
	
	if ( in_array( 'donation', $ctas, true ) && ! empty( $donation_url ) ) {
		$code .= "    'donate_url' => '" . esc_js( $donation_url ) . "',\n";
	}
	
	$code .= ") );\n";
	$code .= "\$notice->init();\n";
	$code .= "\$notice->set_trigger_date(); // Call in activation hook\n";
	
	return $code;
}
