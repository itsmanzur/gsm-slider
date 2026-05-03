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
		// Register scripts/styles early (before Elementor needs them).
		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'init', array( $this, 'register_styles' ) );
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

		/*
		 * Load a lightweight editor-only script (gsm-editor.js) in the Elementor editor panel.
		 * slider.js + Swiper are registered globally (via 'init') so get_script_depends()
		 * can resolve them for the preview iframe without duplicating registration here.
		 */
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_editor_panel_assets' ), 30 );
	}

	/**
	 * Register scripts without enqueuing them globally.
	 * Elementor will enqueue them automatically via get_script_depends()
	 * only on pages where the widget is used.
	 */
	public function register_assets() {
		// Register Swiper from local vendor (no external CDN dependency).
		wp_register_script(
			'swiper',
			GSM_SLIDER_URL . 'assets/vendor/swiper/swiper-bundle.min.js',
			array(),
			'11.2.6',
			true
		);

		wp_register_script(
			'gsm-slider',
			GSM_SLIDER_URL . 'assets/js/slider.js',
			array( 'swiper' ),
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
		// Register Swiper CSS from local vendor.
		wp_register_style(
			'swiper',
			GSM_SLIDER_URL . 'assets/vendor/swiper/swiper-bundle.min.css',
			array(),
			'11.2.6'
		);

		wp_register_style(
			'gsm-slider',
			GSM_SLIDER_URL . 'assets/css/slider.css',
			array( 'swiper' ),
			GSM_SLIDER_VERSION
		);
	}

	/**
	 * Register and enqueue a lightweight editor-only script for the Elementor panel.
	 * We intentionally do NOT load the full slider.js here — it contains frontend
	 * Swiper initialization that requires real slider DOM elements and crashes the editor.
	 */
	public function enqueue_editor_panel_assets() {
		// Register the small editor-only script (template modal + manager panel).
		wp_register_script(
			'gsm-slider-editor',
			GSM_SLIDER_URL . 'assets/js/gsm-editor.js',
			array( 'elementor-editor' ),
			GSM_SLIDER_VERSION,
			true
		);

		wp_enqueue_script( 'gsm-slider-editor' );
		wp_enqueue_style( 'gsm-slider' );

		// Pass both gsmTemplates (from gsm-slider.php hook) data AND gsmManager data to the editor script.
		wp_localize_script(
			'gsm-slider-editor',
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
