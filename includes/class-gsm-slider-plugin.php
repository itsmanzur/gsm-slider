<?php
/**
 * Main plugin class for GSM Slider for Elementor.
 *
 * @package GSM Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GSM_Slider_Plugin
 *
 * Singleton class that manages the plugin lifecycle, dependency checks,
 * asset registration, and widget registration.
 */
class GSM_Slider_Plugin {

	/**
	 * Minimum Elementor Version required.
	 *
	 * @var string
	 */
	const MINIMUM_ELEMENTOR_VERSION = '3.0.0';

	/**
	 * Plugin instance.
	 *
	 * @var GSM_Slider_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return GSM_Slider_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor — private to enforce singleton.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'check_elementor' ) );
	}

	/**
	 * Check if Elementor is active and meets version requirements.
	 */
	public function check_elementor() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_missing_elementor' ) );
			return;
		}

		if ( ! version_compare( ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=' ) ) {
			add_action( 'admin_notices', array( $this, 'admin_notice_minimum_elementor_version' ) );
			return;
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

		// Register scripts and styles early so Elementor can use get_script_depends() properly on both frontend and editor.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ), 5 );
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'register_assets' ) );
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'register_styles' ) );
		/*
		 * Load slider.js in the editor frame (left panel + chrome), not only in the preview iframe.
		 * Template picker JS needs window.elementor + gsmTemplates in the same document as the button.
		 */
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_editor_panel_assets' ), 30 );
	}

	/**
	 * Register scripts without enqueuing them globally.
	 * Elementor will enqueue them automatically via get_script_depends()
	 * only on pages where the widget is used.
	 */
	public function register_assets() {
		wp_register_script(
			'gsm-slider',
			GSM_SLIDER_URL . 'assets/js/slider.js',
			array(),
			GSM_SLIDER_VERSION,
			true
		);
	}

	/**
	 * Register styles without enqueuing them globally.
	 * Elementor will enqueue them automatically via get_style_depends()
	 * only on pages where the widget is used.
	 */
	public function register_styles() {
		wp_register_style(
			'gsm-slider',
			GSM_SLIDER_URL . 'assets/css/slider.css',
			array(),
			GSM_SLIDER_VERSION
		);
	}

	/**
	 * Ensure GSM Slider assets load in the Elementor editor document (panel) so template modal + localization run there.
	 */
	public function enqueue_editor_panel_assets() {
		global $wp_scripts;

		if ( isset( $wp_scripts->registered['gsm-slider'] ) ) {
			$deps = &$wp_scripts->registered['gsm-slider']->deps;
			if ( ! in_array( 'elementor-editor', $deps, true ) ) {
				$deps[] = 'elementor-editor';
			}
			/*
			 * Template live preview runs in the editor chrome (same document as the panel).
			 * Swiper is only guaranteed on the preview iframe — without it, slides stay hidden (fade CSS) → black box.
			 */
			$swiper_handle = null;
			foreach ( array( 'swiper', 'elementor-swiper', 'e-swiper' ) as $h ) {
				if ( wp_script_is( $h, 'registered' ) ) {
					$swiper_handle = $h;
					break;
				}
			}
			if ( $swiper_handle ) {
				wp_enqueue_script( $swiper_handle );
				if ( ! in_array( $swiper_handle, $deps, true ) ) {
					$deps[] = $swiper_handle;
				}
			} elseif ( ! wp_script_is( 'gsm-swiper-editor', 'registered' ) ) {
				wp_register_script(
					'gsm-swiper-editor',
					GSM_SLIDER_URL . 'assets/vendor/swiper/swiper-bundle.min.js',
					array(),
					'8.4.7',
					true
				);
				wp_register_style(
					'gsm-swiper-editor',
					GSM_SLIDER_URL . 'assets/vendor/swiper/swiper-bundle.min.css',
					array(),
					'8.4.7'
				);
			}
			if ( ! $swiper_handle && wp_script_is( 'gsm-swiper-editor', 'registered' ) ) {
				wp_enqueue_style( 'gsm-swiper-editor' );
				wp_enqueue_script( 'gsm-swiper-editor' );
				if ( ! in_array( 'gsm-swiper-editor', $deps, true ) ) {
					$deps[] = 'gsm-swiper-editor';
				}
			}
		}

		wp_enqueue_script( 'gsm-slider' );
		wp_enqueue_style( 'gsm-slider' );

		wp_localize_script(
			'gsm-slider',
			'gsmManager',
			array(
				'nonce'   => wp_create_nonce( 'gsm_manager_nonce' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'strings' => array(
					'confirmDuplicate' => __( 'Duplicate this slider to a new draft?', 'gsm-slider' ),
					'exporting'        => __( 'Exporting...', 'gsm-slider' ),
					'duplicating'      => __( 'Duplicating...', 'gsm-slider' ),
					'exportJson'       => __( 'Export JSON', 'gsm-slider' ),
					'duplicatePage'    => __( 'Duplicate to new page', 'gsm-slider' ),
					'openElementor'    => __( 'Open in Elementor now?', 'gsm-slider' ),
					'error'            => __( 'Something went wrong. Please try again.', 'gsm-slider' ),
					'noDocument'       => __( 'Could not detect the page. Save and try again.', 'gsm-slider' ),
					'infoPrefix'       => __( 'Widget ID', 'gsm-slider' ),
					'slidesLabel'      => __( 'Slides', 'gsm-slider' ),
				),
			)
		);
	}

	/**
	 * Register Elementor widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager instance.
	 */
	public function register_widgets( $widgets_manager ) {
		require_once GSM_SLIDER_PATH . 'widgets/class-gsm-slider-widget.php';
		$widgets_manager->register( new GSM_Slider_Widget() );
	}

	/**
	 * Admin notice: Elementor is not installed or activated.
	 */
	public function admin_notice_missing_elementor() {
		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor */
			esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'gsm-slider' ),
			'<strong>' . esc_html__( 'GSM Slider for Elementor', 'gsm-slider' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'gsm-slider' ) . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Admin notice: Installed Elementor version is below minimum required.
	 */
	public function admin_notice_minimum_elementor_version() {
		if ( isset( $_GET['activate'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			unset( $_GET['activate'] );
		}

		$message = sprintf(
			/* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
			esc_html__( '"%1$s" requires "%2$s" version %3$s or greater.', 'gsm-slider' ),
			'<strong>' . esc_html__( 'GSM Slider for Elementor', 'gsm-slider' ) . '</strong>',
			'<strong>' . esc_html__( 'Elementor', 'gsm-slider' ) . '</strong>',
			'<strong>' . self::MINIMUM_ELEMENTOR_VERSION . '</strong>'
		);

		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
