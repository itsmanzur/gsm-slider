<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
/**
 * Uninstall script for GSM Slider for Elementor.
 *
 * @package GSM Slider
 */

delete_option( 'gsm_settings' );
