<?php
/**
 * GSM Slider Manager — admin list, import/export, duplicate, delete.
 *
 * @package GSM Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GSM_Slider_Manager
 */
class GSM_Slider_Manager {

	/**
	 * Singleton instance.
	 *
	 * @var GSM_Slider_Manager|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return GSM_Slider_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_gsm_export_slider', array( $this, 'ajax_export_slider' ) );
		add_action( 'wp_ajax_gsm_duplicate_slider', array( $this, 'ajax_duplicate_slider' ) );
		add_action( 'wp_ajax_gsm_import_slider', array( $this, 'ajax_import_slider' ) );
		add_action( 'wp_ajax_gsm_delete_slider', array( $this, 'ajax_delete_slider' ) );
	}

	/**
	 * Register top-level admin menu.
	 */
	public function register_menu() {
		add_menu_page(
			__( 'GSM Slider Manager', 'gsm-slider' ),
			__( 'GSM Slider', 'gsm-slider' ),
			'edit_posts',
			'gsm-manager',
			array( $this, 'render_page' ),
			'dashicons-images-alt2',
			58
		);
	}

	/**
	 * Enqueue admin assets on manager screen only.
	 *
	 * @param string $hook Current admin hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'toplevel_page_gsm-manager' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'gsm-manager', GSM_SLIDER_URL . 'admin/manager.css', array(), GSM_SLIDER_VERSION );
		wp_enqueue_script( 'gsm-manager', GSM_SLIDER_URL . 'admin/manager.js', array( 'jquery' ), GSM_SLIDER_VERSION, true );
		wp_localize_script(
			'gsm-manager',
			'gsmManager',
			array(
				'nonce'   => wp_create_nonce( 'gsm_manager_nonce' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'strings' => array(
					'confirmDelete'    => __( 'Are you sure you want to delete this slider? This cannot be undone.', 'gsm-slider' ),
					'confirmDuplicate' => __( 'Duplicate this slider to a new page?', 'gsm-slider' ),
					'exporting'        => __( 'Exporting...', 'gsm-slider' ),
					'importing'        => __( 'Importing...', 'gsm-slider' ),
					'duplicating'      => __( 'Duplicating...', 'gsm-slider' ),
					'deleting'         => __( 'Deleting...', 'gsm-slider' ),
					'export'           => __( 'Export', 'gsm-slider' ),
					'duplicate'        => __( 'Duplicate', 'gsm-slider' ),
					'delete'           => __( 'Delete', 'gsm-slider' ),
					'importSlider'     => __( 'Import Slider', 'gsm-slider' ),
					'importNow'        => __( 'Import Now', 'gsm-slider' ),
					'success'          => __( 'Done!', 'gsm-slider' ),
					'error'            => __( 'Something went wrong. Please try again.', 'gsm-slider' ),
					'selectFile'       => __( 'Please select a JSON file first.', 'gsm-slider' ),
					'openElementorNow' => __( 'Open in Elementor now?', 'gsm-slider' ),
				),
			)
		);
	}

	/**
	 * Discard accidental output before JSON response.
	 *
	 * @param mixed $data Success payload.
	 */
	private function send_json_success_clean( $data ) {
		if ( ob_get_length() ) {
			ob_clean();
		}
		wp_send_json_success( $data );
	}

	/**
	 * @param mixed $data Error payload.
	 */
	private function send_json_error_clean( $data = null ) {
		if ( ob_get_length() ) {
			ob_clean();
		}
		wp_send_json_error( $data );
	}

	/**
	 * Collect all gsm_slider instances across Elementor documents.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_all_sliders() {
		$sliders = array();

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Only posts with Elementor JSON meta; avoids a full post table scan.
		$posts = get_posts(
			array(
				'post_type'      => 'any',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
				'meta_key'       => '_elementor_data',
				'no_found_rows'  => true,
			)
		);
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key

		foreach ( $posts as $post ) {
			$data = get_post_meta( $post->ID, '_elementor_data', true );
			if ( empty( $data ) ) {
				continue;
			}
			if ( is_string( $data ) ) {
				$elements = json_decode( $data, true );
			} elseif ( is_array( $data ) ) {
				$elements = $data;
			} else {
				continue;
			}
			if ( ! is_array( $elements ) ) {
				continue;
			}

			$found = array();
			$this->find_gsm_widgets( $elements, $post, $found );
			$sliders = array_merge( $sliders, $found );
		}

		return $sliders;
	}

	/**
	 * Recursively find gsm_slider widgets.
	 *
	 * @param array<int, mixed> $elements Elementor elements tree.
	 * @param WP_Post           $post     Post object.
	 * @param array             $results  Accumulator (by reference).
	 */
	private function find_gsm_widgets( $elements, $post, array &$results ) {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			if ( isset( $element['widgetType'] ) && 'gsm_slider' === $element['widgetType'] ) {
				$settings    = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();
				$slide_count = isset( $settings['slides_data'] ) && is_array( $settings['slides_data'] ) ? count( $settings['slides_data'] ) : 0;
				$source      = isset( $settings['content_source'] ) ? $settings['content_source'] : 'custom';

				$results[] = array(
					'widget_id'     => isset( $element['id'] ) ? $element['id'] : '',
					'post_id'       => (int) $post->ID,
					'post_title'    => $post->post_title,
					'post_type'     => $post->post_type,
					'post_status'   => $post->post_status,
					'post_url'      => get_permalink( $post->ID ),
					'edit_url'      => get_edit_post_link( $post->ID ),
					'elementor_url' => admin_url( 'post.php?post=' . (int) $post->ID . '&action=elementor' ),
					'slide_count'   => $slide_count,
					'source'        => $source,
					'effect'        => isset( $settings['effect'] ) ? $settings['effect'] : 'fade',
					'settings'      => $settings,
				);
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->find_gsm_widgets( $element['elements'], $post, $results );
			}
		}
	}

	/**
	 * Render admin page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'gsm-slider' ) );
		}

		$sliders    = $this->get_all_sliders();
		$total      = count( $sliders );
		$published  = count(
			array_filter(
				$sliders,
				static function ( $s ) {
					return isset( $s['post_status'] ) && 'publish' === $s['post_status'];
				}
			)
		);
		$pages_used = count( array_unique( array_column( $sliders, 'post_id' ) ) );
		?>
		<div class="wrap gsm-manager-admin-wrap">
		<div class="gsm-manager">

			<div class="gsm-manager__header">
				<div class="gsm-manager__header-left">
					<div class="gsm-manager__logo" aria-hidden="true">
						<span class="gsm-manager__logo-icon">&#9654;</span>
					</div>
					<div>
						<h1 class="gsm-manager__title"><?php esc_html_e( 'GSM Slider Manager', 'gsm-slider' ); ?></h1>
						<p class="gsm-manager__subtitle"><?php esc_html_e( 'Manage all your sliders in one place', 'gsm-slider' ); ?></p>
					</div>
				</div>
				<div class="gsm-manager__header-right">
					<span class="gsm-manager__version">v<?php echo esc_html( GSM_SLIDER_VERSION ); ?></span>
				</div>
			</div>

			<div class="gsm-manager__body">

				<div class="gsm-manager__main">

					<?php
					echo '<div class="gsm-stats">';

					// Stat 1.
					echo '<div class="gsm-stat-card gsm-stat-card--blue">';
					echo '<div class="gsm-stat-card__icon">';
					echo '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>';
					echo '</div>';
					echo '<div class="gsm-stat-card__info">';
					echo '<div class="gsm-stat-card__value">' . absint( $total ) . '</div>';
					echo '<div class="gsm-stat-card__label">' . esc_html__( 'Total Sliders', 'gsm-slider' ) . '</div>';
					echo '</div>';
					echo '</div>';

					// Stat 2.
					echo '<div class="gsm-stat-card gsm-stat-card--green">';
					echo '<div class="gsm-stat-card__icon">';
					echo '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>';
					echo '</div>';
					echo '<div class="gsm-stat-card__info">';
					echo '<div class="gsm-stat-card__value">' . absint( $published ) . '</div>';
					echo '<div class="gsm-stat-card__label">' . esc_html__( 'Published', 'gsm-slider' ) . '</div>';
					echo '</div>';
					echo '</div>';

					// Stat 3.
					echo '<div class="gsm-stat-card gsm-stat-card--purple">';
					echo '<div class="gsm-stat-card__icon">';
					echo '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>';
					echo '</div>';
					echo '<div class="gsm-stat-card__info">';
					echo '<div class="gsm-stat-card__value">' . absint( $pages_used ) . '</div>';
					echo '<div class="gsm-stat-card__label">' . esc_html__( 'Pages', 'gsm-slider' ) . '</div>';
					echo '</div>';
					echo '</div>';

					echo '</div>';
					?>

					<?php
					echo '<div class="gsm-section gsm-section--collapsible" id="gsm-import-section">';
					echo '<div class="gsm-section__head gsm-section__head--toggle" id="gsm-import-toggle">';
					echo '<h2 class="gsm-section__title">';
					echo '<span class="gsm-section__icon">&#8681;</span>';
					echo esc_html__( 'Import Slider from JSON', 'gsm-slider' );
					echo '</h2>';
					echo '<span class="gsm-toggle-arrow">&#8250;</span>';
					echo '</div>';
					echo '<div class="gsm-section__body" id="gsm-import-body" style="display:none;">';
					echo '<p class="gsm-section__desc" style="margin-bottom:16px;">' . esc_html__( 'Upload a previously exported GSM Slider JSON file. A new draft page will be created automatically.', 'gsm-slider' ) . '</p>';
					echo '<div class="gsm-import-area" id="gsm-drop-zone" role="button" tabindex="0" aria-label="' . esc_attr__( 'Drop JSON file or browse', 'gsm-slider' ) . '">';
					echo '<div class="gsm-import-area__icon">&#128196;</div>';
					echo '<p class="gsm-import-area__text">' . esc_html__( 'Drop your JSON file here or', 'gsm-slider' ) . '</p>';
					echo '<label class="gsm-btn gsm-btn--outline" for="gsm-import-file">';
					echo esc_html__( 'Browse File', 'gsm-slider' );
					echo '</label>';
					echo '<input type="file" id="gsm-import-file" accept=".json,application/json" style="display:none;">';
					echo '<p class="gsm-import-area__hint">' . esc_html__( 'Only .json files exported from GSM Slider', 'gsm-slider' ) . '</p>';
					echo '</div>';
					echo '<div id="gsm-import-selected" class="gsm-import-selected" style="display:none;">';
					echo '<span id="gsm-import-filename"></span>';
					echo '<button type="button" id="gsm-import-btn" class="gsm-btn gsm-btn--primary">' . esc_html__( 'Import Now', 'gsm-slider' ) . '</button>';
					echo '</div>';
					echo '<div id="gsm-import-result" class="gsm-alert" style="display:none;" role="status"></div>';
					echo '</div>';
					echo '</div>';
					?>

					<div class="gsm-section">
						<div class="gsm-section__head">
							<h2 class="gsm-section__title">
								<span class="gsm-section__icon" aria-hidden="true">&#9783;</span>
								<?php esc_html_e( 'All Sliders', 'gsm-slider' ); ?>
							</h2>
						</div>

						<div class="gsm-manager-toolbar">
							<input type="text" id="gsm-search" placeholder="<?php echo esc_attr__( 'Search sliders...', 'gsm-slider' ); ?>" class="gsm-search-input">
							<select id="gsm-filter-status" class="gsm-filter-select">
								<option value="all"><?php esc_html_e( 'All Status', 'gsm-slider' ); ?></option>
								<option value="publish"><?php esc_html_e( 'Published', 'gsm-slider' ); ?></option>
								<option value="draft"><?php esc_html_e( 'Draft', 'gsm-slider' ); ?></option>
							</select>
							<select id="gsm-filter-type" class="gsm-filter-select">
								<option value="all"><?php esc_html_e( 'All Types', 'gsm-slider' ); ?></option>
								<option value="page"><?php esc_html_e( 'Pages', 'gsm-slider' ); ?></option>
								<option value="post"><?php esc_html_e( 'Posts', 'gsm-slider' ); ?></option>
							</select>
							<span class="gsm-results-count" id="gsm-results-count"></span>
						</div>

						<div class="gsm-bulk-bar" id="gsm-bulk-bar" style="display:none;">
							<span id="gsm-selected-count">0 selected</span>
							<button type="button" class="gsm-btn gsm-btn--outline" id="gsm-bulk-export"><?php esc_html_e( 'Export Selected', 'gsm-slider' ); ?></button>
							<button type="button" class="gsm-btn gsm-btn--danger" id="gsm-bulk-delete"><?php esc_html_e( 'Delete Selected', 'gsm-slider' ); ?></button>
							<button type="button" class="gsm-action-btn" id="gsm-deselect-all"><?php esc_html_e( 'Deselect All', 'gsm-slider' ); ?></button>
						</div>

						<?php if ( empty( $sliders ) ) : ?>
							<div class="gsm-empty">
								<div class="gsm-empty__icon" aria-hidden="true">&#128248;</div>
								<h3><?php esc_html_e( 'No sliders found', 'gsm-slider' ); ?></h3>
								<p><?php esc_html_e( 'Add the GSM Slider widget to any Elementor page to get started.', 'gsm-slider' ); ?></p>
							</div>
						<?php else : ?>
							<div class="gsm-slider-grid">
								<?php
								foreach ( $sliders as $slider ) :
									$effect_key   = isset( $slider['effect'] ) ? (string) $slider['effect'] : 'fade';
									$effect_label = ucfirst( $effect_key );
									$source_label = ( 'custom' === $slider['source'] )
										? __( 'Custom', 'gsm-slider' )
										: ucfirst( (string) $slider['source'] );
									$slide_count  = absint( $slider['slide_count'] );

									$preview_gradients = array(
										'fade'      => 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)',
										'slide'     => 'linear-gradient(135deg, #1a1a2e 0%, #2d1b69 50%, #11998e 100%)',
										'cube'      => 'linear-gradient(135deg, #1a1a2e 0%, #4a1942 50%, #c0392b 100%)',
										'flip'      => 'linear-gradient(135deg, #1a1a2e 0%, #1a3a2e 50%, #27ae60 100%)',
										'coverflow' => 'linear-gradient(135deg, #1a1a2e 0%, #2c3e50 50%, #3498db 100%)',
										'cards'     => 'linear-gradient(135deg, #1a1a2e 0%, #2e1a47 50%, #8e44ad 100%)',
									);
									$preview_bg        = isset( $preview_gradients[ $effect_key ] ) ? $preview_gradients[ $effect_key ] : $preview_gradients['fade'];
									$status_badge_class = sanitize_html_class( $slider['post_status'] );
									?>

								<div class="gsm-slider-card"
									data-widget-id="<?php echo esc_attr( $slider['widget_id'] ); ?>"
									data-post-id="<?php echo absint( $slider['post_id'] ); ?>"
									data-status="<?php echo esc_attr( $slider['post_status'] ); ?>"
									data-type="<?php echo esc_attr( $slider['post_type'] ); ?>"
									data-title="<?php echo esc_attr( $slider['post_title'] ); ?>">

									<div class="gsm-slider-card__preview" style="background:<?php echo esc_attr( $preview_bg ); ?>;">
										<input type="checkbox" class="gsm-card-check" data-widget-id="<?php echo esc_attr( $slider['widget_id'] ); ?>" data-post-id="<?php echo absint( $slider['post_id'] ); ?>">
										<span class="gsm-badge gsm-badge--<?php echo esc_attr( $status_badge_class ); ?>"><?php echo esc_html( $slider['post_status'] ); ?></span>
										<div class="gsm-preview-center">
											<div class="gsm-slider-card__preview-icon" aria-hidden="true">&#9654;</div>
											<div class="gsm-preview-effect"><?php echo esc_html( $effect_label ); ?></div>
										</div>
										<div class="gsm-preview-stats">
											<span><?php echo absint( $slide_count ); ?> <?php esc_html_e( 'slides', 'gsm-slider' ); ?></span>
											<span><?php echo esc_html( $source_label ); ?></span>
										</div>
									</div>

									<div class="gsm-slider-card__body">
										<h3 class="gsm-slider-card__title">
											<a href="<?php echo esc_url( $slider['elementor_url'] ); ?>"><?php echo esc_html( $slider['post_title'] ); ?></a>
										</h3>
										<p class="gsm-slider-card__page">
											<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
											<?php echo esc_html( $slider['post_type'] ); ?> &mdash;
											<a href="<?php echo esc_url( $slider['post_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View', 'gsm-slider' ); ?></a>
										</p>
									</div>

									<div class="gsm-slider-card__actions">
										<a href="<?php echo esc_url( $slider['elementor_url'] ); ?>" class="gsm-action-btn gsm-action-btn--edit" title="<?php esc_attr_e( 'Edit in Elementor', 'gsm-slider' ); ?>">
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
											<?php esc_html_e( 'Edit', 'gsm-slider' ); ?>
										</a>
										<button type="button" class="gsm-action-btn gsm-action-btn--export gsm-btn-export" title="<?php esc_attr_e( 'Export as JSON', 'gsm-slider' ); ?>">
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
											<?php esc_html_e( 'Export', 'gsm-slider' ); ?>
										</button>
										<button type="button" class="gsm-action-btn gsm-action-btn--duplicate gsm-btn-duplicate" title="<?php esc_attr_e( 'Duplicate', 'gsm-slider' ); ?>">
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
											<?php esc_html_e( 'Copy', 'gsm-slider' ); ?>
										</button>
										<button type="button" class="gsm-action-btn gsm-action-btn--delete gsm-btn-delete" title="<?php esc_attr_e( 'Delete slider', 'gsm-slider' ); ?>">
											<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
											<span class="screen-reader-text"><?php esc_html_e( 'Delete', 'gsm-slider' ); ?></span>
										</button>
									</div>

								</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

				</div>

				<div class="gsm-manager__sidebar">

					<div class="gsm-sidebar-card">
						<h3 class="gsm-sidebar-card__title"><?php esc_html_e( 'Quick Links', 'gsm-slider' ); ?></h3>
						<ul class="gsm-quick-links">
							<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>">&#128196; <?php esc_html_e( 'All Pages', 'gsm-slider' ); ?></a></li>
							<li><a href="https://wordpress.org/support/plugin/gsm-elementor/" target="_blank" rel="noopener noreferrer">&#128172; <?php esc_html_e( 'Support Forum', 'gsm-slider' ); ?></a></li>
							<li><a href="https://wordpress.org/plugins/gsm-elementor/#reviews" target="_blank" rel="noopener noreferrer">&#11088; <?php esc_html_e( 'Leave a Review', 'gsm-slider' ); ?></a></li>
						</ul>
					</div>

					<div class="gsm-sidebar-card gsm-sidebar-card--pro">
						<div class="gsm-pro-badge"><?php esc_html_e( 'PRO', 'gsm-slider' ); ?></div>
						<h3 class="gsm-sidebar-card__title"><?php esc_html_e( 'Unlock Pro Features', 'gsm-slider' ); ?></h3>
						<ul class="gsm-pro-features">
							<li>&#10003; <?php esc_html_e( 'Slider Analytics', 'gsm-slider' ); ?></li>
							<li>&#10003; <?php esc_html_e( '10+ Templates', 'gsm-slider' ); ?></li>
							<li>&#10003; <?php esc_html_e( 'Priority Support', 'gsm-slider' ); ?></li>
							<li>&#10003; <?php esc_html_e( 'Advanced Animations', 'gsm-slider' ); ?></li>
						</ul>
						<a href="#" class="gsm-btn gsm-btn--pro"><?php esc_html_e( 'Upgrade to Pro', 'gsm-slider' ); ?></a>
					</div>

				</div>

			</div>

		</div>
		</div>
		<?php
	}

	/**
	 * AJAX: export one widget's settings as JSON payload.
	 */
	public function ajax_export_slider() {
		check_ajax_referer( 'gsm_manager_nonce', 'nonce' );

		$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$widget_id = isset( $_POST['widget_id'] ) ? sanitize_text_field( wp_unslash( $_POST['widget_id'] ) ) : '';

		if ( ! $post_id || '' === $widget_id || ! current_user_can( 'edit_post', $post_id ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'Permission denied.', 'gsm-slider' ) ) );
		}

		$data = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $data ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'No Elementor data found.', 'gsm-slider' ) ) );
		}

		$elements = is_string( $data ) ? json_decode( $data, true ) : $data;
		if ( ! is_array( $elements ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'Invalid Elementor data.', 'gsm-slider' ) ) );
		}

		$widget = $this->find_gsm_widget_by_id( $elements, $widget_id );
		if ( ! $widget ) {
			$this->send_json_error_clean( array( 'message' => __( 'GSM Slider widget not found.', 'gsm-slider' ) ) );
		}

		$export = array(
			'type'       => 'gsm_slider',
			'version'    => GSM_SLIDER_VERSION,
			'exported'   => current_time( 'mysql' ),
			'post_title' => get_the_title( $post_id ),
			'settings'   => isset( $widget['settings'] ) && is_array( $widget['settings'] ) ? $widget['settings'] : array(),
		);

		$this->send_json_success_clean( $export );
	}

	/**
	 * AJAX: duplicate this slider into a new draft (single-widget document).
	 */
	public function ajax_duplicate_slider() {
		check_ajax_referer( 'gsm_manager_nonce', 'nonce' );

		$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$widget_id = isset( $_POST['widget_id'] ) ? sanitize_text_field( wp_unslash( $_POST['widget_id'] ) ) : '';

		if ( ! $post_id || '' === $widget_id || ! current_user_can( 'edit_post', $post_id ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'Permission denied.', 'gsm-slider' ) ) );
		}

		$original_post = get_post( $post_id );
		if ( ! $original_post ) {
			$this->send_json_error_clean( array( 'message' => __( 'Post not found.', 'gsm-slider' ) ) );
		}

		$cap = $this->get_create_capability_for_post_type( $original_post->post_type );
		if ( ! current_user_can( $cap ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'You cannot create posts of this type.', 'gsm-slider' ) ) );
		}

		$data = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $data ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'No Elementor data.', 'gsm-slider' ) ) );
		}

		$elements = is_string( $data ) ? json_decode( $data, true ) : $data;
		if ( ! is_array( $elements ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'Invalid Elementor data.', 'gsm-slider' ) ) );
		}

		$widget = $this->find_gsm_widget_by_id( $elements, $widget_id );
		if ( ! $widget || empty( $widget['settings'] ) || ! is_array( $widget['settings'] ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'GSM Slider widget not found.', 'gsm-slider' ) ) );
		}

		$elementor_data = $this->build_minimal_gsm_document( $widget['settings'] );

		$new_title = $original_post->post_title . ' — ' . __( 'Slider copy', 'gsm-slider' );

		$new_post_id = wp_insert_post(
			array(
				'post_title'  => $new_title,
				'post_type'   => $original_post->post_type,
				'post_status' => 'draft',
			),
			true
		);

		if ( is_wp_error( $new_post_id ) ) {
			$this->send_json_error_clean( array( 'message' => $new_post_id->get_error_message() ) );
		}

		$this->save_elementor_document( $new_post_id, $elementor_data );

		$this->send_json_success_clean(
			array(
				'new_post_id'   => (int) $new_post_id,
				'edit_url'      => get_edit_post_link( $new_post_id, 'raw' ),
				'elementor_url' => admin_url( 'post.php?post=' . (int) $new_post_id . '&action=elementor' ),
				'message'       => __( 'Slider duplicated successfully!', 'gsm-slider' ),
			)
		);
	}

	/**
	 * AJAX: import JSON → new draft page with one gsm_slider.
	 */
	public function ajax_import_slider() {
		check_ajax_referer( 'gsm_manager_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_pages' ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'Permission denied.', 'gsm-slider' ) ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload; tmp_name validated with is_uploaded_file().
		$upload = isset( $_FILES['import_file'] ) ? wp_unslash( $_FILES['import_file'] ) : array();

		if ( empty( $upload['tmp_name'] ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'No file uploaded.', 'gsm-slider' ) ) );
		}

		$file = array(
			'name'     => isset( $upload['name'] ) ? sanitize_file_name( $upload['name'] ) : '',
			'type'     => isset( $upload['type'] ) ? sanitize_mime_type( $upload['type'] ) : '',
			'tmp_name' => $upload['tmp_name'],
			'error'    => isset( $upload['error'] ) ? absint( $upload['error'] ) : 0,
			'size'     => isset( $upload['size'] ) ? absint( $upload['size'] ) : 0,
		);
		if ( ! empty( $file['error'] ) && UPLOAD_ERR_OK !== (int) $file['error'] ) {
			$this->send_json_error_clean( array( 'message' => __( 'Upload failed.', 'gsm-slider' ) ) );
		}

		$tmp = $file['tmp_name'];
		if ( ! is_uploaded_file( $tmp ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'Invalid upload.', 'gsm-slider' ) ) );
		}

		$json = file_get_contents( $tmp );
		if ( false === $json ) {
			$this->send_json_error_clean( array( 'message' => __( 'Could not read file.', 'gsm-slider' ) ) );
		}

		$data = json_decode( $json, true );
		if ( ! is_array( $data ) || empty( $data['settings'] ) || ! is_array( $data['settings'] ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'Invalid GSM Slider JSON file.', 'gsm-slider' ) ) );
		}

		$settings   = $data['settings'];
		$post_title = isset( $data['post_title'] ) ? sanitize_text_field( $data['post_title'] ) : __( 'Imported Slider', 'gsm-slider' );

		$elementor_data = $this->build_minimal_gsm_document( $settings );

		$new_post_id = wp_insert_post(
			array(
				'post_title'  => $post_title . ' ' . __( '(Imported)', 'gsm-slider' ),
				'post_type'   => 'page',
				'post_status' => 'draft',
			),
			true
		);

		if ( is_wp_error( $new_post_id ) ) {
			$this->send_json_error_clean( array( 'message' => $new_post_id->get_error_message() ) );
		}

		$this->save_elementor_document( $new_post_id, $elementor_data );

		$this->send_json_success_clean(
			array(
				'new_post_id'   => (int) $new_post_id,
				'elementor_url' => admin_url( 'post.php?post=' . (int) $new_post_id . '&action=elementor' ),
				'message'       => __( 'Slider imported! A new draft page has been created.', 'gsm-slider' ),
			)
		);
	}

	/**
	 * AJAX: remove one gsm_slider from document.
	 */
	public function ajax_delete_slider() {
		check_ajax_referer( 'gsm_manager_nonce', 'nonce' );

		$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$widget_id = isset( $_POST['widget_id'] ) ? sanitize_text_field( wp_unslash( $_POST['widget_id'] ) ) : '';

		if ( ! $post_id || '' === $widget_id || ! current_user_can( 'edit_post', $post_id ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'Permission denied.', 'gsm-slider' ) ) );
		}

		$data = get_post_meta( $post_id, '_elementor_data', true );
		if ( empty( $data ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'No data found.', 'gsm-slider' ) ) );
		}

		$elements = is_string( $data ) ? json_decode( $data, true ) : $data;
		if ( ! is_array( $elements ) ) {
			$this->send_json_error_clean( array( 'message' => __( 'Invalid Elementor data.', 'gsm-slider' ) ) );
		}

		$removed = $this->remove_gsm_widget_by_id( $elements, $widget_id );
		if ( ! $removed ) {
			$this->send_json_error_clean( array( 'message' => __( 'Widget not found.', 'gsm-slider' ) ) );
		}

		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elements ) ) );

		$this->clear_elementor_cache();

		$this->send_json_success_clean( array( 'message' => __( 'Slider removed from this page.', 'gsm-slider' ) ) );
	}

	/**
	 * Capability needed to create a new post of a given type.
	 *
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	private function get_create_capability_for_post_type( $post_type ) {
		$pto = get_post_type_object( $post_type );
		if ( $pto && ! empty( $pto->cap->create_posts ) ) {
			return $pto->cap->create_posts;
		}
		return 'edit_posts';
	}

	/**
	 * Build minimal section > column > gsm_slider tree.
	 *
	 * @param array<string, mixed> $settings Widget settings.
	 * @return array<int, array<string, mixed>>
	 */
	private function build_minimal_gsm_document( array $settings ) {
		$widget_id = substr( md5( uniqid( 'gsm-slider', true ) ), 0, 7 );
		$col_id    = substr( md5( uniqid( 'gsm-slider', true ) ), 0, 7 );
		$sec_id    = substr( md5( uniqid( 'gsm-slider', true ) ), 0, 7 );

		return array(
			array(
				'id'       => $sec_id,
				'elType'   => 'section',
				'settings' => array(),
				'isInner'  => false,
				'elements' => array(
					array(
						'id'       => $col_id,
						'elType'   => 'column',
						'settings' => array( '_column_size' => 100 ),
						'isInner'  => false,
						'elements' => array(
							array(
								'id'         => $widget_id,
								'elType'     => 'widget',
								'widgetType' => 'gsm_slider',
								'settings'   => $settings,
								'elements'   => array(),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Persist Elementor document meta for a post.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<int, mixed>    $elementor_data Root elements.
	 */
	private function save_elementor_document( $post_id, array $elementor_data ) {
		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $elementor_data ) ) );
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			update_post_meta( $post_id, '_elementor_version', ELEMENTOR_VERSION );
		}
	}

	/**
	 * Find gsm_slider element by id.
	 *
	 * @param array<int, mixed> $elements Tree.
	 * @param string            $widget_id Elementor element id.
	 * @return array<string, mixed>|null
	 */
	private function find_gsm_widget_by_id( $elements, $widget_id ) {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			if ( isset( $element['id'], $element['widgetType'] ) && $element['id'] === $widget_id && 'gsm_slider' === $element['widgetType'] ) {
				return $element;
			}
			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$found = $this->find_gsm_widget_by_id( $element['elements'], $widget_id );
				if ( $found ) {
					return $found;
				}
			}
		}
		return null;
	}

	/**
	 * Remove gsm_slider widget by id from tree.
	 *
	 * @param array<int, mixed> $elements Tree (by reference).
	 * @param string            $widget_id Target id.
	 * @return bool
	 */
	private function remove_gsm_widget_by_id( &$elements, $widget_id ) {
		foreach ( $elements as $key => $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			if ( isset( $element['id'], $element['widgetType'] ) && $element['id'] === $widget_id && 'gsm_slider' === $element['widgetType'] ) {
				unset( $elements[ $key ] );
				$elements = array_values( $elements );
				return true;
			}
			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				if ( $this->remove_gsm_widget_by_id( $elements[ $key ]['elements'], $widget_id ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Clear Elementor generated files cache when available.
	 */
	private function clear_elementor_cache() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}
		$plugin = \Elementor\Plugin::$instance;
		if ( isset( $plugin->files_manager ) && is_object( $plugin->files_manager ) && method_exists( $plugin->files_manager, 'clear_cache' ) ) {
			$plugin->files_manager->clear_cache();
		}
	}
}

GSM_Slider_Manager::get_instance();
