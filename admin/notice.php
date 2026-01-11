<?php
/**
 * Review Notice
 * Handles delayed admin notice for review requests
 *
 * @package Your_Prefix
 * @since   2.0.0
 *
 * CONFIGURATION REQUIRED:
 * 1. Do a search/replace for 'your_prefix_', 'Your_Prefix', and 'your-plugin-textdomain'
 * 2. Pass configuration array to constructor (see constructor documentation)
 * 3. For deeper customizations (wording, styling), modify the code as needed
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Review Notice Class
 * Displays a delayed admin notice to encourage users to leave a review
 */
class Your_Prefix_Review_Notice {

	/**
	 * Default delay in days before showing notice
	 *
	 * @var int
	 */
	private const DELAY_DAYS = 30;

	/**
	 * Default option name prefix
	 *
	 * @var string
	 */
	private const DEFAULT_PREFIX = 'your_prefix';

	/**
	 * Default enable remind-again feature
	 *
	 * @var bool
	 */
	private const ENABLE_REMIND_AGAIN = false;

	/**
	 * Default remind-again mode
	 *
	 * @var string
	 */
	private const REMIND_AGAIN_MODE = 'once';

	/**
	 * Default days to wait before reminding again
	 *
	 * @var int
	 */
	private const REMIND_AGAIN_DAYS = 60;

	/**
	 * Instance configuration
	 *
	 * @var array
	 */
	private $config;

	/**
	 * Delay in days before showing notice
	 *
	 * @var int
	 */
	private $delay_days;

	/**
	 * Option name for trigger date
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * User meta key for dismissal
	 *
	 * @var string
	 */
	private $user_meta_key;

	/**
	 * AJAX action name
	 *
	 * @var string
	 */
	private $ajax_action;

	/**
	 * Nonce action name
	 *
	 * @var string
	 */
	private $nonce_action;

	/**
	 * Enable remind-again feature
	 *
	 * @var bool
	 */
	private $enable_remind_again;

	/**
	 * Remind-again mode
	 *
	 * @var string
	 */
	private $remind_again_mode;

	/**
	 * Days to wait before reminding again
	 *
	 * @var int
	 */
	private $remind_again_days;

	/**
	 * Target admin screens/pages
	 *
	 * @var array
	 */
	private $target_screens;

	/**
	 * Notice position
	 *
	 * @var string
	 */
	private $position;

	/**
	 * Review URL
	 *
	 * @var string
	 */
	private $review_url;

	/**
	 * Donation URL
	 *
	 * @var string
	 */
	private $donate_url;

	/**
	 * Plugin name for display
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Constructor
	 *
	 * @param array $config Configuration array. Accepts:
	 *   - 'prefix' (string): Prefix for option names and meta keys (default: 'your_prefix')
	 *   - 'delay_days' (int): Days before showing notice (default: 30)
	 *   - 'target_screens' (array): Screen IDs or page slugs where notice appears (default: empty array)
	 *   - 'position' (string): Notice position - 'top', 'bottom-right', 'bottom-left' (default: 'top')
	 *   - 'enable_remind_again' (bool): Enable remind-again feature (default: false)
	 *   - 'remind_again_mode' (string): 'once' or 'interval' (default: 'once')
	 *   - 'remind_again_days' (int): Days before reminding again (default: 60)
	 *   - 'review_url' (string): URL for review button (default: empty string)
	 *   - 'donate_url' (string): URL for donation button (default: empty string)
	 *   - 'plugin_name' (string): Plugin name for display (default: 'Your Plugin Name')
	 */
	public function __construct( $config = array() ) {
		// Merge with defaults
		$this->config = wp_parse_args( $config, array(
			'prefix'              => self::DEFAULT_PREFIX,
			'delay_days'          => self::DELAY_DAYS,
			'target_screens'      => array(),
			'position'            => 'top',
			'enable_remind_again' => self::ENABLE_REMIND_AGAIN,
			'remind_again_mode'   => self::REMIND_AGAIN_MODE,
			'remind_again_days'   => self::REMIND_AGAIN_DAYS,
			'review_url'          => '',
			'donate_url'          => '',
			'plugin_name'         => 'Your Plugin Name',
		) );

		// Set instance properties
		$this->delay_days          = (int) $this->config['delay_days'];
		$this->enable_remind_again = (bool) $this->config['enable_remind_again'];
		$this->remind_again_mode   = $this->config['remind_again_mode'];
		$this->remind_again_days   = (int) $this->config['remind_again_days'];
		$this->target_screens      = (array) $this->config['target_screens'];
		$this->position            = sanitize_html_class( $this->config['position'] );
		$this->review_url          = esc_url_raw( $this->config['review_url'] );
		$this->donate_url          = esc_url_raw( $this->config['donate_url'] );
		$this->plugin_name         = sanitize_text_field( $this->config['plugin_name'] );

		// Build option/meta keys based on prefix
		$prefix = sanitize_key( $this->config['prefix'] );
		$this->option_name   = $prefix . '_review_notice_trigger_date';
		$this->user_meta_key = $prefix . '_review_notice_dismissed';
		$this->ajax_action   = $prefix . '_dismiss_review_notice';
		$this->nonce_action  = $prefix . '_review_notice';
	}

	/**
	 * Initialize review notice
	 */
	public function init() {
		if ( ! is_admin() ) {
			return;
		}

		// Hook into appropriate action based on position
		if ( 'top' === $this->position ) {
			add_action( 'admin_notices', array( $this, 'maybe_show_notice' ) );
		} else {
			// For bottom positions, use admin_footer to inject into content area
			add_action( 'admin_footer', array( $this, 'maybe_show_notice' ) );
		}

		// Register AJAX handler
		add_action( 'wp_ajax_' . $this->ajax_action, array( $this, 'handle_dismiss_ajax' ) );
	}

	/**
	 * Set trigger date on plugin activation
	 * Instance method - call this for each notice instance
	 */
	public function set_trigger_date() {
		if ( get_option( $this->option_name ) === false ) {
			$trigger_date = time() + ( DAY_IN_SECONDS * $this->delay_days );
			add_option( $this->option_name, $trigger_date, '', false );
		}
	}

	/**
	 * Set trigger date on plugin activation (static method for backward compatibility)
	 * Uses default prefix. For multiple instances, use instance method instead.
	 *
	 * @deprecated Use instance method set_trigger_date() instead
	 */
	public static function set_trigger_date_static() {
		$default_prefix = self::DEFAULT_PREFIX;
		$option_name = $default_prefix . '_review_notice_trigger_date';
		if ( get_option( $option_name ) === false ) {
			$trigger_date = time() + ( DAY_IN_SECONDS * self::DELAY_DAYS );
			add_option( $option_name, $trigger_date, '', false );
		}
	}

	/**
	 * Check if notice should be shown
	 *
	 * @return bool|string True if notice should be shown, 'remind_again' if remind-again notice, false otherwise
	 */
	private function should_show_notice() {
		// Check if user has capability (super admin, admin, or editor)
		if ( ! is_super_admin() && ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		// Check if we're on a target screen/page
		if ( ! $this->is_target_screen() ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			return false;
		}

		$dismissed = get_user_meta( $user_id, $this->user_meta_key, true );

		// REMIND AGAIN FEATURE (only if enabled)
		if ( $this->enable_remind_again && ! empty( $dismissed ) && '1' !== $dismissed ) {
			// Permanently dismissed (once mode, second dismissal)
			if ( 'permanent' === $dismissed ) {
				return false;
			}

			// Check if remind-again time has passed
			$dismissal_timestamp = is_numeric( $dismissed ) ? (int) $dismissed : 0;
			if ( $dismissal_timestamp > 0 ) {
				$current_time = time();
				$days_since_dismissal = ( $current_time - $dismissal_timestamp ) / DAY_IN_SECONDS;

				if ( $days_since_dismissal >= $this->remind_again_days ) {
					return 'remind_again'; // Show remind-again notice
				}
			}

			return false; // Not enough days passed yet
		}

		// ORIGINAL LOGIC (if remind-again disabled or never dismissed)
		if ( '1' === $dismissed || 'permanent' === $dismissed ) {
			return false;
		}

		// Check if trigger date option exists, initialize if not (for existing users upgrading)
		$trigger_date = get_option( $this->option_name );
		if ( false === $trigger_date ) {
			// Initialize for existing users who upgraded to version with this feature
			// Only runs once because option will exist after this
			$this->initialize_trigger_date_for_existing_user();
			// Re-fetch the option (should now exist)
			$trigger_date = get_option( $this->option_name, 0 );
		}

		if ( empty( $trigger_date ) || ! is_numeric( $trigger_date ) ) {
			return false;
		}

		$current_time = time();
		if ( $current_time < (int) $trigger_date ) {
			return false;
		}

		return true; // Show original notice
	}

	/**
	 * Check if current screen is a target screen
	 *
	 * @return bool True if current screen matches any target screen
	 */
	private function is_target_screen() {
		// If no target screens configured, show on all admin pages (not recommended but flexible)
		if ( empty( $this->target_screens ) ) {
			return true;
		}

		$screen = get_current_screen();
		global $pagenow;

		foreach ( $this->target_screens as $target ) {
			// Check screen ID (e.g., 'appearance_page_your-plugin-slug')
			if ( $screen && $screen->id === $target ) {
				return true;
			}

			// Check pagenow (e.g., 'plugins.php', 'themes.php')
			if ( $pagenow === $target ) {
				return true;
			}

			// Check $_GET['page'] parameter (fallback for submenu pages)
			// Extract page slug from screen ID format (e.g., 'options-general_page_ai-experiments' -> 'ai-experiments')
			if ( strpos( $target, '_page_' ) !== false ) {
				$parts = explode( '_page_', $target, 2 );
				if ( isset( $parts[1] ) && isset( $_GET['page'] ) ) {
					$page_param = sanitize_text_field( wp_unslash( $_GET['page'] ) );
					if ( $page_param === $parts[1] ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Initialize trigger date for existing users who upgraded
	 * Only called when option doesn't exist and we're on the relevant page
	 */
	private function initialize_trigger_date_for_existing_user() {
		// Ensure plugin.php is loaded for is_plugin_active()
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Get the plugin file path (assuming the notice file is in admin/notice.php)
		$plugin_dir = dirname( dirname( __FILE__ ) );
		$plugin_file = plugin_basename( $plugin_dir . '/plugin.php' );

		// Double-check plugin is active (safety check)
		if ( ! is_plugin_active( $plugin_file ) ) {
			return;
		}

		// Set trigger date to delay_days from now for existing users
		$trigger_date = time() + ( DAY_IN_SECONDS * $this->delay_days );
		add_option( $this->option_name, $trigger_date, '', false );
	}

	/**
	 * Maybe show admin notice
	 */
	public function maybe_show_notice() {
		$notice_type = $this->should_show_notice();
		if ( false === $notice_type ) {
			return;
		}

		// Use values from constructor
		$plugin_name = $this->plugin_name;
		$review_url  = ! empty( $this->review_url ) ? $this->review_url : 'https://wordpress.org/support/plugin/your-plugin-slug/reviews/';
		$donate_url  = ! empty( $this->donate_url ) ? $this->donate_url : '';

		$nonce = wp_create_nonce( $this->nonce_action );

		// Prepare heading and subheading based on notice type
		if ( 'remind_again' === $notice_type ) {
			/* translators: %s: Plugin name */
			$heading = sprintf( __( 'Still enjoying %s?', 'your-plugin-textdomain' ), $plugin_name );
			$subheading = __( 'We asked before, but could you spare a moment to leave a review?', 'your-plugin-textdomain' );
			if ( 'once' === $this->remind_again_mode ) {
				$subheading .= ' ' . __( 'We won\'t ask again after this.', 'your-plugin-textdomain' );
			}
        } else {
			/* translators: %s: Plugin name */
			$heading = sprintf( __( 'Enjoying %s?', 'your-plugin-textdomain' ), $plugin_name );
			$subheading = __( 'Leave us a kind review on WordPress.org', 'your-plugin-textdomain' );
			if ( ! empty( $donate_url ) ) {
				$subheading .= ' ' . __( 'or make a donation', 'your-plugin-textdomain' );
			}
		}

		$this->render_notice( $heading, $subheading, $review_url, $donate_url, $nonce );
	}

	/**
	 * Render notice (unified method for both original and remind-again)
	 *
	 * @param string $heading    Heading text
	 * @param string $subheading Subheading text
	 * @param string $review_url Review URL
	 * @param string $donate_url Donation URL (optional)
	 * @param string $nonce      Nonce for AJAX
	 */
	private function render_notice( $heading, $subheading, $review_url, $donate_url, $nonce ) {
		$position_class = 'your-prefix-review-notice-position-' . $this->position;
		?>
		<style>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded CSS, safe to output directly
		echo $this->get_notice_css();
		?>
		</style>

		<div class="your-prefix-review-notice <?php echo esc_attr( $position_class ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<h3 class="your-prefix-review-notice-heading">
				<?php echo esc_html( $heading ); ?>
			</h3>

			<p class="your-prefix-review-notice-subheading">
				<?php echo esc_html( $subheading ); ?>
			</p>

			<div class="your-prefix-review-notice-stars">
				⭐⭐⭐⭐⭐
			</div>

			<div class="your-prefix-review-notice-actions">
				<?php if ( ! empty( $review_url ) ) : ?>
					<a href="<?php echo esc_url( $review_url ); ?>" target="_blank" rel="noopener noreferrer" class="your-prefix-review-notice-button">
						<?php esc_html_e( 'Leave your Review Here', 'your-plugin-textdomain' ); ?>
					</a>
				<?php endif; ?>
				<?php if ( ! empty( $donate_url ) ) : ?>
					<a href="<?php echo esc_url( $donate_url ); ?>" target="_blank" rel="noopener noreferrer" class="your-prefix-review-notice-button" style="background: #00a32a;">
						<?php esc_html_e( 'Make a Donation', 'your-plugin-textdomain' ); ?>
					</a>
				<?php endif; ?>
				<a href="#" class="your-prefix-review-notice-dismiss-link" data-action="dismiss-review-notice" aria-label="<?php echo esc_attr__( 'Dismiss this notice', 'your-plugin-textdomain' ); ?>">
					<?php esc_html_e( "I'd rather not (dismiss)", 'your-plugin-textdomain' ); ?>
				</a>
			</div>

			<p class="your-prefix-review-notice-footer">
				<em><?php esc_html_e( 'Your review and feedback keeps us developing this plugin for more users like you!', 'your-plugin-textdomain' ); ?></em>
			</p>
		</div>

		<script>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded JavaScript, safe to output directly
		echo $this->get_notice_js();
		?>
		</script>
		<?php
	}

	/**
	 * Get notice CSS (shared by all notice types)
	 *
	 * @return string CSS styles
	 */
	private function get_notice_css() {
		return '.your-prefix-review-notice {
	position: relative;
	width: 100%;
	padding: 24px 32px;
	background: linear-gradient(135deg, #f7f8f9 0%, #f0f1f3 100%);
	border: 1px solid #e5e7e9;
	border-radius: 8px;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
	box-sizing: border-box;
	margin: 20px 0;
}

/* Bottom-right position (fixed to viewport) */
.your-prefix-review-notice.your-prefix-review-notice-position-bottom-right {
	position: fixed;
	bottom: 20px;
	right: 20px;
	max-width: 400px;
	width: auto;
	margin: 0;
	z-index: 999999;
}

/* Bottom-left position (fixed to viewport) */
.your-prefix-review-notice.your-prefix-review-notice-position-bottom-left {
	position: fixed;
	bottom: 20px;
	left: 180px;
	max-width: 400px;
	width: auto;
	margin: 0;
	z-index: 999999;
}

.your-prefix-review-notice-heading {
	margin: 0 0 8px 0;
	padding: 0;
	font-size: 18px;
	font-weight: 600;
	color: #1d2327;
	line-height: 1.4;
}

.your-prefix-review-notice-subheading {
	margin: 0 0 12px 0;
	padding: 0;
	font-size: 14px;
	color: #50575e;
	line-height: 1.5;
}

.your-prefix-review-notice-stars {
	margin: 0 0 16px 0;
	font-size: 20px;
	line-height: 1;
	letter-spacing: 2px;
}

.your-prefix-review-notice-actions {
	margin: 0 0 12px 0;
	display: flex;
	align-items: center;
	gap: 16px;
	flex-wrap: wrap;
}

.your-prefix-review-notice-button {
	display: inline-block;
	padding: 10px 20px;
	background: #2271b1;
	color: #fff;
	text-decoration: none;
	border-radius: 4px;
	font-size: 14px;
	font-weight: 500;
	line-height: 1.5;
	transition: background-color 0.2s ease, color 0.2s ease;
}

.your-prefix-review-notice-button:hover,
.your-prefix-review-notice-button:focus {
	background: #135e96;
	color: #fff;
	text-decoration: none;
	outline: 2px solid #2271b1;
	outline-offset: 2px;
}

.your-prefix-review-notice-button:active {
	background: #0a4b78;
}

.your-prefix-review-notice-footer {
	margin: 0;
	padding: 0;
	font-size: 12px;
	color: #646970;
	line-height: 1.5;
}

.your-prefix-review-notice-footer em {
	font-style: italic;
	color: #646970;
}

.your-prefix-review-notice-dismiss-link {
	color: #646970;
	text-decoration: none;
	font-size: 13px;
	cursor: pointer;
	transition: color 0.2s ease;
}

.your-prefix-review-notice-dismiss-link:hover,
.your-prefix-review-notice-dismiss-link:focus {
	color: #1d2327;
	text-decoration: underline;
	outline: none;
}

.your-prefix-review-notice-dismiss-link:active {
	color: #0a4b78;
}';
	}

	/**
	 * Get notice JavaScript (shared by all notice types)
	 *
	 * @return string JavaScript code
	 */
	private function get_notice_js() {
		$ajax_action = esc_js( $this->ajax_action );
		$position_class = 'your-prefix-review-notice-position-' . esc_js( $this->position );
		
		return "(function() {
	var notice = document.querySelector('." . $position_class . "');
	if (!notice) {
		return;
	}

	var dismissLink = notice.querySelector('.your-prefix-review-notice-dismiss-link');
	if (!dismissLink) {
		return;
	}

	var nonce = notice.getAttribute('data-nonce');
	if (!nonce) {
		return;
	}

	dismissLink.addEventListener('click', function(event) {
		event.preventDefault();

		notice.style.transition = 'opacity 0.3s ease';
		notice.style.opacity = '0';

		var formData = new FormData();
		formData.append('action', '" . $ajax_action . "');
		formData.append('nonce', nonce);

		var ajaxUrl = (typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php');
		fetch(ajaxUrl, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin'
		})
		.then(function(response) {
			return response.json();
		})
		.then(function(data) {
			if (data.success) {
				setTimeout(function() {
					notice.remove();
				}, 300);
			} else {
				notice.style.opacity = '1';
			}
		})
		.catch(function(error) {
			console.error('Error dismissing notice:', error);
			notice.style.opacity = '1';
		});
	});
})();";
	}

	/**
	 * Handle AJAX dismissal request
	 */
	public function handle_dismiss_ajax() {
		// Check user capability
		if ( ! is_super_admin() && ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'your-plugin-textdomain' ) ) );
			return;
		}

		// Verify nonce
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $this->nonce_action ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid security token. Please refresh the page and try again.', 'your-plugin-textdomain' ) ) );
			return;
		}

		// Get current user ID
		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) {
			wp_send_json_error( array( 'message' => __( 'User not found.', 'your-plugin-textdomain' ) ) );
			return;
		}

		// Save dismissal
		$dismissed = get_user_meta( $user_id, $this->user_meta_key, true );

		if ( $this->enable_remind_again ) {
			// REMIND AGAIN FEATURE: Store timestamp or permanent flag
			if ( empty( $dismissed ) || '1' === $dismissed ) {
				// First dismissal: store timestamp
				$value = time();
			} elseif ( is_numeric( $dismissed ) && 'once' === $this->remind_again_mode ) {
				// Second dismissal in 'once' mode: permanently dismiss
				$value = 'permanent';
			} elseif ( is_numeric( $dismissed ) ) {
				// Interval mode or subsequent dismissal: update timestamp
				$value = time();
			} else {
				// Already permanent or invalid, do nothing
				$value = $dismissed;
			}
			update_user_meta( $user_id, $this->user_meta_key, $value );
		} else {
			// ORIGINAL BEHAVIOR: Store '1' (permanent dismissal)
			update_user_meta( $user_id, $this->user_meta_key, '1' );
		}

		wp_send_json_success( array( 'message' => __( 'Notice dismissed.', 'your-plugin-textdomain' ) ) );
	}

	/**
	 * Cleanup on plugin uninstall
	 * Instance method - call this for each notice instance
	 */
	public function cleanup() {
		// Delete option
		delete_option( $this->option_name );

		// Delete user meta for all users
		$users = get_users( array( 'fields' => 'ID' ) );
		foreach ( $users as $user_id ) {
			delete_user_meta( $user_id, $this->user_meta_key );
		}
	}

	/**
	 * Cleanup on plugin uninstall (static method for backward compatibility)
	 * Uses default prefix. For multiple instances, use instance method instead.
	 *
	 * @deprecated Use instance method cleanup() instead
	 */
	public static function cleanup_static() {
		$default_prefix = self::DEFAULT_PREFIX;
		$option_name = $default_prefix . '_review_notice_trigger_date';
		$user_meta_key = $default_prefix . '_review_notice_dismissed';

		// Delete option
		delete_option( $option_name );

		// Delete user meta for all users
		$users = get_users( array( 'fields' => 'ID' ) );
		foreach ( $users as $user_id ) {
			delete_user_meta( $user_id, $user_meta_key );
		}
	}
}