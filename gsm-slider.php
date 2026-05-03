<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Plugin Name:       GSM Slider for Elementor
 * Plugin URI:        https://wordpress.org/plugins/gsm-elementor/
 * Description:       GSM Slider — The cinematic Elementor slider with video backgrounds, glass morphism, WooCommerce integration, dynamic posts, lightbox, and per-slide animations.
 * Version:           1.0.0
 * Author:            gsmdeveloper
 * Author URI:        https://profiles.wordpress.org/gsmdeveloper/
 * Text Domain:       gsm-slider
 * Domain Path:       /languages
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Tested up to:      6.9
 * Elementor tested up to: 3.25.0
 * Elementor Pro tested up to: 3.25.0
 *
 * @package GSM Slider
 */

define( 'GSM_SLIDER_VERSION', '1.0.0' );
define( 'GSM_SLIDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'GSM_SLIDER_URL', plugin_dir_url( __FILE__ ) );
define( 'GSM_SLIDER_FILE', __FILE__ );

require_once GSM_SLIDER_PATH . 'includes/class-gsm-slider-plugin.php';
require_once GSM_SLIDER_PATH . 'includes/class-gsm-templates.php';
require_once GSM_SLIDER_PATH . 'admin/class-gsm-manager.php';

add_action(
	'elementor/editor/before_enqueue_scripts',
	function () {
		// Localize gsmTemplates onto gsm-slider-editor (the script that actually loads
		// in the editor panel). gsm-editor.js reads window.gsmTemplates for the modal.
		// Priority 20 runs BEFORE enqueue_editor_panel_assets (priority 30) which
		// registers + enqueues gsm-slider-editor, so we use wp_add_inline_script instead
		// to be safe regardless of registration order.
		$templates_data = wp_json_encode(
			array(
				'nonce'   => wp_create_nonce( 'gsm_templates_nonce' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'importSuccess'      => __( 'Slider template imported successfully.', 'gsm-slider' ),
					'importFailed'       => __( 'Could not import the template. Please try again.', 'gsm-slider' ),
					'importNoWidget'     => __( 'Could not find this slider in the preview. Click the widget on the canvas, then try importing again.', 'gsm-slider' ),
					'importAjaxFail'     => __( 'The server did not return template data. Please try again.', 'gsm-slider' ),
					'preview'            => __( 'Preview', 'gsm-slider' ),
					'previewTemplate'    => __( 'Preview template', 'gsm-slider' ),
					'importThisTemplate' => __( 'Import This Template', 'gsm-slider' ),
					'close'              => __( 'Close', 'gsm-slider' ),
					'previewFailed'      => __( 'Could not load template preview.', 'gsm-slider' ),
					'previewNetwork'     => __( 'Network error loading preview.', 'gsm-slider' ),
					'previewDynamicHint' => __( 'Preview uses dynamic content from your WordPress site.', 'gsm-slider' ),
				),
			)
		);
		// Inject as a global variable directly — works regardless of which handle is enqueued.
		wp_add_inline_script(
			'elementor-editor',
			'window.gsmTemplates = ' . $templates_data . ';',
			'before'
		);
	},
	20
);

GSM_Slider_Plugin::get_instance();
GSM_Slider_Templates::get_instance();
GSM_Slider_Manager::get_instance();
