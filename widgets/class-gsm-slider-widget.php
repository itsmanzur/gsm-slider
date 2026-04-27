<?php
/**
 * GSM Slider Elementor Widget.
 *
 * @package GSM Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Repeater;
use Elementor\Utils;
use Elementor\Widget_Base;

/**
 * Class GSM_Slider_Widget
 *
 * Elementor widget that renders a fully-accessible Swiper-powered slider.
 */
class GSM_Slider_Widget extends Widget_Base {

	/**
	 * Get widget name (slug).
	 *
	 * @return string
	 */
	public function get_name() {
		return 'gsm_slider';
	}

	/**
	 * Get widget display title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'GSM Slider', 'gsm-slider' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-slider-push';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'general' ];
	}

	/**
	 * Get widget keywords for better search.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'gsm-slider', 'slider', 'carousel', 'hero', 'banner', 'video', 'cinematic' ];
	}

	/**
	 * Get script dependencies. Elementor enqueues these only when the widget is rendered.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'swiper', 'gsm-slider' );
	}

	/**
	 * Get style dependencies. Elementor enqueues these only when the widget is rendered.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'swiper', 'gsm-slider' );
	}

	/**
	 * Register all controls for the widget.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_templates',
			array(
				'label' => esc_html__( 'Templates', 'gsm-slider' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'templates_btn',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => '<button type="button" class="elementor-button elementor-button-default gsm-open-templates" style="width:100%;margin:4px 0;">'
					. '<span class="eicon-library-open" style="margin-right:6px;"></span>'
					. esc_html__( 'Choose a Template', 'gsm-slider' )
					. '</button>',
				'content_classes' => 'gsm-templates-btn-wrap',
			)
		);

		$this->add_control(
			'templates_note',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<p style="font-size:11px;color:#888;margin:0 0 4px;">'
					. esc_html__( 'Import a ready-made template to get started quickly. Your current settings will be replaced.', 'gsm-slider' )
					. '</p>',
			)
		);

		$this->add_control(
			'manager_panel',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<div class="gsm-panel-manager" style="margin-top:8px;padding:10px 12px;background:#f6f7f7;border-radius:6px;border:1px solid #e0e0e0;font-size:12px;">'
					. '<div class="gsm-mgr-info" style="margin-bottom:8px;color:#555;line-height:1.45;"></div>'
					. '<div style="display:flex;gap:6px;flex-wrap:wrap;">'
					. '<button type="button" class="elementor-button elementor-button-default gsm-mgr-export" style="padding:6px 12px;font-size:11px;">'
					. esc_html__( 'Export JSON', 'gsm-slider' )
					. '</button>'
					. '<button type="button" class="elementor-button elementor-button-default gsm-mgr-duplicate" style="padding:6px 12px;font-size:11px;">'
					. esc_html__( 'Duplicate to new page', 'gsm-slider' )
					. '</button>'
					. '</div></div>',
			)
		);

		$this->add_control(
			'manager_link',
			array(
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<div style="margin-top:8px;padding-top:8px;border-top:1px solid #e0e0e0;">'
					. '<a href="' . esc_url( admin_url( 'admin.php?page=gsm-manager' ) ) . '" target="_blank" rel="noopener noreferrer" style="font-size:12px;color:#2271b1;">'
					. '<span class="dashicons dashicons-admin-tools" style="font-size:14px;vertical-align:middle;margin-right:4px;"></span>'
					. esc_html__( 'Slider Manager', 'gsm-slider' )
					. '</a>'
					. '</div>',
			)
		);

		$this->end_controls_section();

		$this->register_slides_section();
		$this->register_slider_settings_section();
		$this->register_mobile_section();
		$this->register_content_box_style_section();
		$this->register_typography_style_section();
		$this->register_book_showcase_style_section();
		$this->register_button_style_section();
		$this->register_navigation_style_section();
	}

	/**
	 * Register the Slides content section.
	 */
	private function register_slides_section() {
		$this->start_controls_section(
			'section_slides',
			array(
				'label' => esc_html__( 'Slides', 'gsm-slider' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$content_source_options = array(
			'custom' => esc_html__( 'Custom Slides', 'gsm-slider' ),
			'posts'  => esc_html__( 'WordPress Posts', 'gsm-slider' ),
		);
		if ( class_exists( 'WooCommerce' ) ) {
			$content_source_options['products'] = esc_html__( 'WooCommerce Products', 'gsm-slider' );
		}

		$this->add_control(
			'content_source',
			array(
				'label'   => esc_html__( 'Content Source', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'custom',
				'options' => $content_source_options,
			)
		);

		// --- Dynamic Posts Query Controls (shown only when source = posts) ---
		$post_types        = get_post_types( array( 'public' => true ), 'objects' );
		$post_type_options = array();
		foreach ( $post_types as $pt ) {
			$post_type_options[ $pt->name ] = $pt->label;
		}

		$this->add_control(
			'query_post_type',
			array(
				'label'     => esc_html__( 'Post Type', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'post',
				'options'   => $post_type_options,
				'condition' => array( 'content_source' => 'posts' ),
			)
		);

		$post_categories  = get_categories( array( 'hide_empty' => false ) );
		$category_options = array();
		foreach ( $post_categories as $cat ) {
			$category_options[ $cat->term_id ] = $cat->name;
		}

		$this->add_control(
			'query_category',
			array(
				'label'       => esc_html__( 'Category Filter', 'gsm-slider' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $category_options,
				'description' => esc_html__( 'Works for standard Post type only.', 'gsm-slider' ),
				'condition'   => array( 'content_source' => 'posts', 'query_post_type' => 'post' ),
			)
		);

		$this->add_control(
			'query_include',
			array(
				'label'       => esc_html__( 'Include by IDs', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( '12, 34, 56', 'gsm-slider' ),
				'description' => esc_html__( 'Comma separated list of Post IDs to show.', 'gsm-slider' ),
				'condition'   => array( 'content_source' => 'posts' ),
			)
		);

		$this->add_control(
			'query_exclude',
			array(
				'label'       => esc_html__( 'Exclude by IDs', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( '12, 34', 'gsm-slider' ),
				'description' => esc_html__( 'Comma separated list of Post IDs to hide.', 'gsm-slider' ),
				'condition'   => array( 'content_source' => 'posts' ),
			)
		);

		$this->add_control(
			'query_posts_per_page',
			array(
				'label'     => esc_html__( 'Number of Slides', 'gsm-slider' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5,
				'min'       => 1,
				'max'       => 20,
				'condition' => array( 'content_source' => 'posts' ),
			)
		);

		$this->add_control(
			'query_orderby',
			array(
				'label'     => esc_html__( 'Order By', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'date',
				'options'   => array(
					'date'       => esc_html__( 'Date', 'gsm-slider' ),
					'title'      => esc_html__( 'Title', 'gsm-slider' ),
					'rand'       => esc_html__( 'Random', 'gsm-slider' ),
					'menu_order' => esc_html__( 'Menu Order', 'gsm-slider' ),
				),
				'condition' => array( 'content_source' => 'posts' ),
			)
		);

		$this->add_control(
			'query_order',
			array(
				'label'     => esc_html__( 'Order', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'DESC',
				'options'   => array(
					'DESC' => esc_html__( 'Newest First', 'gsm-slider' ),
					'ASC'  => esc_html__( 'Oldest First', 'gsm-slider' ),
				),
				'condition' => array( 'content_source' => 'posts' ),
			)
		);

		$this->add_control(
			'query_animation',
			array(
				'label'     => esc_html__( 'Content Animation', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'fade-up',
				'options'   => array(
					'fade-up'    => esc_html__( 'Fade Up', 'gsm-slider' ),
					'fade-left'  => esc_html__( 'Fade Left', 'gsm-slider' ),
					'fade-right' => esc_html__( 'Fade Right', 'gsm-slider' ),
					'zoom-in'    => esc_html__( 'Zoom In', 'gsm-slider' ),
				),
				'condition' => array( 'content_source' => 'posts' ),
			)
		);

		$this->add_control(
			'query_btn_text',
			array(
				'label'     => esc_html__( 'Button Text', 'gsm-slider' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Read More', 'gsm-slider' ),
				'condition' => array( 'content_source' => 'posts' ),
			)
		);

		$this->add_control(
			'query_image_effect',
			array(
				'label'     => esc_html__( 'Image Effect', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'none',
				'options'   => array(
					'none'     => esc_html__( 'None', 'gsm-slider' ),
					'zoom'     => esc_html__( 'Zoom In', 'gsm-slider' ),
					'parallax' => esc_html__( 'Parallax', 'gsm-slider' ),
					'kenburns' => esc_html__( 'Ken Burns', 'gsm-slider' ),
				),
				'condition' => array( 'content_source' => 'posts' ),
			)
		);

		$this->add_control(
			'query_overlay_color',
			array(
				'label'     => esc_html__( 'Overlay Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.45)',
				'condition' => array( 'content_source' => 'posts' ),
			)
		);

		$this->add_control(
			'query_divider',
			array(
				'type'      => Controls_Manager::DIVIDER,
				'condition' => array( 'content_source' => 'custom' ),
			)
		);

		// --- WooCommerce Product Query Controls (shown only when WooCommerce is active) ---
		if ( class_exists( 'WooCommerce' ) ) {

			$product_cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
			$product_cat_options = array();
			if ( ! is_wp_error( $product_cats ) ) {
				foreach ( $product_cats as $cat ) {
					$product_cat_options[ $cat->term_id ] = $cat->name;
				}
			}

			$this->add_control(
				'woo_category',
				array(
					'label'       => esc_html__( 'Product Category Filter', 'gsm-slider' ),
					'type'        => Controls_Manager::SELECT2,
					'multiple'    => true,
					'options'     => $product_cat_options,
					'condition'   => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_include',
				array(
					'label'       => esc_html__( 'Include by IDs', 'gsm-slider' ),
					'type'        => Controls_Manager::TEXT,
					'placeholder' => esc_html__( '12, 34', 'gsm-slider' ),
					'description' => esc_html__( 'Comma separated Product IDs to show.', 'gsm-slider' ),
					'condition'   => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_exclude',
				array(
					'label'       => esc_html__( 'Exclude by IDs', 'gsm-slider' ),
					'type'        => Controls_Manager::TEXT,
					'placeholder' => esc_html__( '12, 34', 'gsm-slider' ),
					'description' => esc_html__( 'Comma separated Product IDs to hide.', 'gsm-slider' ),
					'condition'   => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_products_per_page',
				array(
					'label'     => esc_html__( 'Number of Products', 'gsm-slider' ),
					'type'      => Controls_Manager::NUMBER,
					'default'   => 5,
					'min'       => 1,
					'max'       => 20,
					'condition' => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_orderby',
				array(
					'label'     => esc_html__( 'Order By', 'gsm-slider' ),
					'type'      => Controls_Manager::SELECT,
					'default'   => 'date',
					'options'   => array(
						'date'       => esc_html__( 'Newest', 'gsm-slider' ),
						'price'      => esc_html__( 'Price', 'gsm-slider' ),
						'popularity' => esc_html__( 'Popularity', 'gsm-slider' ),
						'rating'     => esc_html__( 'Rating', 'gsm-slider' ),
						'rand'       => esc_html__( 'Random', 'gsm-slider' ),
					),
					'condition' => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_show_only_sale',
				array(
					'label'        => esc_html__( 'Sale Products Only', 'gsm-slider' ),
					'type'         => Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => '',
					'condition'    => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_show_price',
				array(
					'label'        => esc_html__( 'Show Price', 'gsm-slider' ),
					'type'         => Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => 'yes',
					'condition'    => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_show_sale_badge',
				array(
					'label'        => esc_html__( 'Show Sale Badge', 'gsm-slider' ),
					'type'         => Controls_Manager::SWITCHER,
					'return_value' => 'yes',
					'default'      => 'yes',
					'condition'    => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_btn_type',
				array(
					'label'     => esc_html__( 'Button Action', 'gsm-slider' ),
					'type'      => Controls_Manager::SELECT,
					'default'   => 'view',
					'options'   => array(
						'view' => esc_html__( 'View Product', 'gsm-slider' ),
						'cart' => esc_html__( 'Add to Cart', 'gsm-slider' ),
					),
					'condition' => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_image_effect',
				array(
					'label'     => esc_html__( 'Image Effect', 'gsm-slider' ),
					'type'      => Controls_Manager::SELECT,
					'default'   => 'zoom',
					'options'   => array(
						'none'     => esc_html__( 'None', 'gsm-slider' ),
						'zoom'     => esc_html__( 'Zoom In', 'gsm-slider' ),
						'parallax' => esc_html__( 'Parallax', 'gsm-slider' ),
						'kenburns' => esc_html__( 'Ken Burns', 'gsm-slider' ),
					),
					'condition' => array( 'content_source' => 'products' ),
				)
			);

			$this->add_control(
				'woo_overlay_color',
				array(
					'label'     => esc_html__( 'Overlay Color', 'gsm-slider' ),
					'type'      => Controls_Manager::COLOR,
					'default'   => 'rgba(0,0,0,0.35)',
					'condition' => array( 'content_source' => 'products' ),
				)
			);

		} // end class_exists( 'WooCommerce' )

		$repeater = new Repeater();

		// --- Media Type ---
		$repeater->add_control(
			'media_type',
			array(
				'label'   => esc_html__( 'Media Type', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'image',
				'options' => array(
					'image' => esc_html__( 'Image', 'gsm-slider' ),
					'video' => esc_html__( 'Video', 'gsm-slider' ),
				),
			)
		);

		// --- Image Controls ---
		$repeater->add_control(
			'bg_image',
			array(
				'label'     => esc_html__( 'Background Image', 'gsm-slider' ),
				'type'      => Controls_Manager::MEDIA,
				'default'   => array( 'url' => Utils::get_placeholder_image_src() ),
				'condition' => array( 'media_type' => 'image' ),
			)
		);

		$repeater->add_control(
			'image_fit',
			array(
				'label'     => esc_html__( 'Image Fit', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => esc_html__( 'Cover', 'gsm-slider' ),
					'contain' => esc_html__( 'Contain', 'gsm-slider' ),
					'auto'    => esc_html__( 'Auto', 'gsm-slider' ),
				),
				'condition' => array( 'media_type' => 'image' ),
			)
		);

		$repeater->add_control(
			'image_position',
			array(
				'label'     => esc_html__( 'Image Position', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center center',
				'options'   => array(
					'top left'      => esc_html__( 'Top Left', 'gsm-slider' ),
					'top center'    => esc_html__( 'Top Center', 'gsm-slider' ),
					'top right'     => esc_html__( 'Top Right', 'gsm-slider' ),
					'center left'   => esc_html__( 'Center Left', 'gsm-slider' ),
					'center center' => esc_html__( 'Center Center', 'gsm-slider' ),
					'center right'  => esc_html__( 'Center Right', 'gsm-slider' ),
					'bottom left'   => esc_html__( 'Bottom Left', 'gsm-slider' ),
					'bottom center' => esc_html__( 'Bottom Center', 'gsm-slider' ),
					'bottom right'  => esc_html__( 'Bottom Right', 'gsm-slider' ),
				),
				'condition' => array( 'media_type' => 'image' ),
			)
		);

		$repeater->add_control(
			'image_effect',
			array(
				'label'     => esc_html__( 'Image Effect', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'none',
				'options'   => array(
					'none'     => esc_html__( 'None', 'gsm-slider' ),
					'zoom'     => esc_html__( 'Zoom In', 'gsm-slider' ),
					'parallax' => esc_html__( 'Parallax', 'gsm-slider' ),
					'kenburns' => esc_html__( 'Ken Burns', 'gsm-slider' ),
				),
				'condition' => array( 'media_type' => 'image' ),
			)
		);

		// --- Video Controls ---
		$repeater->add_control(
			'video_source',
			array(
				'label'     => esc_html__( 'Video Source', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'self',
				'options'   => array(
					'self'  => esc_html__( 'Self-hosted (MP4)', 'gsm-slider' ),
					'embed' => esc_html__( 'Embed (YouTube / Vimeo)', 'gsm-slider' ),
				),
				'condition' => array( 'media_type' => 'video' ),
			)
		);

		$repeater->add_control(
			'video_url',
			array(
				'label'       => esc_html__( 'Video URL (MP4)', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'https://example.com/video.mp4', 'gsm-slider' ),
				'condition'   => array(
					'media_type'   => 'video',
					'video_source' => 'self',
				),
			)
		);

		$repeater->add_control(
			'video_embed_url',
			array(
				'label'       => esc_html__( 'Embed URL (YouTube / Vimeo)', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'https://www.youtube.com/watch?v=xxxxx', 'gsm-slider' ),
				'description' => esc_html__( 'Paste any YouTube or Vimeo URL — watch URL, short URL, or embed URL all work.', 'gsm-slider' ),
				'condition'   => array(
					'media_type'   => 'video',
					'video_source' => 'embed',
				),
			)
		);

		$repeater->add_control(
			'video_poster',
			array(
				'label'     => esc_html__( 'Video Poster Image', 'gsm-slider' ),
				'type'      => Controls_Manager::MEDIA,
				'condition' => array( 'media_type' => 'video' ),
			)
		);

		$repeater->add_control(
			'video_mobile_optim',
			array(
				'label'        => esc_html__( 'Disable Video on Mobile', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Saves mobile data by replacing the video with the poster image on small screens (< 768px).', 'gsm-slider' ),
				'condition'    => array( 'media_type' => 'video' ),
			)
		);

		$repeater->add_control(
			'cinematic_video_intro',
			array(
				'label'        => esc_html__( 'Cinematic Intro (Blur to Sharp)', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'media_type' => 'video' ),
			)
		);

		$repeater->add_control(
			'sep_content',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);
		$repeater->add_control(
			'heading_content_label',
			array(
				'label' => esc_html__( 'Content', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		// --- Overlay ---
		$repeater->add_control(
			'overlay_type',
			array(
				'label'   => esc_html__( 'Overlay Type', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'solid',
				'options' => array(
					'none'     => esc_html__( 'None', 'gsm-slider' ),
					'solid'    => esc_html__( 'Solid Color', 'gsm-slider' ),
					'gradient' => esc_html__( 'Gradient', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'overlay_color',
			array(
				'label'     => esc_html__( 'Overlay Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.35)',
				'condition' => array( 'overlay_type' => 'solid' ),
			)
		);

		$repeater->add_control(
			'overlay_gradient_start',
			array(
				'label'     => esc_html__( 'Gradient Start', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.7)',
				'condition' => array( 'overlay_type' => 'gradient' ),
			)
		);

		$repeater->add_control(
			'overlay_gradient_end',
			array(
				'label'     => esc_html__( 'Gradient End', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0)',
				'condition' => array( 'overlay_type' => 'gradient' ),
			)
		);

		$repeater->add_control(
			'overlay_gradient_direction',
			array(
				'label'     => esc_html__( 'Direction', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'to top',
				'options'   => array(
					'to top'     => esc_html__( 'Bottom to Top', 'gsm-slider' ),
					'to bottom'  => esc_html__( 'Top to Bottom', 'gsm-slider' ),
					'to right'   => esc_html__( 'Left to Right', 'gsm-slider' ),
					'to left'    => esc_html__( 'Right to Left', 'gsm-slider' ),
					'135deg'     => esc_html__( 'Diagonal', 'gsm-slider' ),
				),
				'condition' => array( 'overlay_type' => 'gradient' ),
			)
		);

		// --- Particle Effect ---
		$repeater->add_control(
			'particle_sep',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);
		$repeater->add_control(
			'particle_heading_label',
			array(
				'label' => esc_html__( 'Particle Effect', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);
		$repeater->add_control(
			'particle_enable',
			array(
				'label'        => esc_html__( 'Enable Particles', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'gsm-slider' ),
				'label_off'    => esc_html__( 'Off', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Animated floating particles on this slide background.', 'gsm-slider' ),
			)
		);
		$repeater->add_control(
			'particle_preset',
			array(
				'label'   => esc_html__( 'Preset', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'snow',
				'options' => array(
					'snow'     => esc_html__( 'Snow', 'gsm-slider' ),
					'glitter'  => esc_html__( 'Glitter / Gold', 'gsm-slider' ),
					'bubbles'  => esc_html__( 'Bubbles', 'gsm-slider' ),
					'stars'    => esc_html__( 'Stars', 'gsm-slider' ),
					'confetti' => esc_html__( 'Confetti', 'gsm-slider' ),
					'custom'   => esc_html__( 'Custom', 'gsm-slider' ),
				),
				'condition' => array( 'particle_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'particle_color',
			array(
				'label'     => esc_html__( 'Particle Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'condition' => array(
					'particle_enable' => 'yes',
					'particle_preset' => 'custom',
				),
			)
		);
		$repeater->add_control(
			'particle_color2',
			array(
				'label'       => esc_html__( 'Second Color', 'gsm-slider' ),
				'type'        => Controls_Manager::COLOR,
				'default'     => '',
				'description' => esc_html__( 'Optional — mix two colors randomly.', 'gsm-slider' ),
				'condition'   => array(
					'particle_enable' => 'yes',
					'particle_preset' => 'custom',
				),
			)
		);
		$repeater->add_control(
			'particle_count',
			array(
				'label'   => esc_html__( 'Particle Count', 'gsm-slider' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => array( 'px' => array( 'min' => 10, 'max' => 200 ) ),
				'default' => array( 'size' => 60 ),
				'condition' => array( 'particle_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'particle_size',
			array(
				'label'   => esc_html__( 'Particle Size (px)', 'gsm-slider' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => array( 'px' => array( 'min' => 1, 'max' => 20 ) ),
				'default' => array( 'size' => 4 ),
				'condition' => array( 'particle_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'particle_speed',
			array(
				'label'   => esc_html__( 'Speed', 'gsm-slider' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => array( 'px' => array( 'min' => 1, 'max' => 10 ) ),
				'default' => array( 'size' => 3 ),
				'condition' => array( 'particle_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'particle_shape',
			array(
				'label'   => esc_html__( 'Shape', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'circle',
				'options' => array(
					'circle'   => esc_html__( 'Circle', 'gsm-slider' ),
					'star'     => esc_html__( 'Star', 'gsm-slider' ),
					'square'   => esc_html__( 'Square', 'gsm-slider' ),
					'triangle' => esc_html__( 'Triangle', 'gsm-slider' ),
				),
				'condition' => array(
					'particle_enable' => 'yes',
					'particle_preset' => 'custom',
				),
			)
		);
		$repeater->add_control(
			'particle_opacity',
			array(
				'label'   => esc_html__( 'Opacity', 'gsm-slider' ),
				'type'    => Controls_Manager::SLIDER,
				'range'   => array( 'px' => array( 'min' => 10, 'max' => 100 ) ),
				'default' => array( 'size' => 70 ),
				'condition' => array( 'particle_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'particle_connect',
			array(
				'label'        => esc_html__( 'Connect Lines', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Draw lines between nearby particles.', 'gsm-slider' ),
				'condition'    => array( 'particle_enable' => 'yes' ),
			)
		);

		// --- Countdown ---
		$repeater->add_control(
			'countdown_sep',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);
		$repeater->add_control(
			'countdown_heading_label',
			array(
				'label' => esc_html__( 'Countdown Timer', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);
		$repeater->add_control(
			'countdown_enable',
			array(
				'label'        => esc_html__( 'Enable Countdown', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'gsm-slider' ),
				'label_off'    => esc_html__( 'Off', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Show a live countdown timer on this slide.', 'gsm-slider' ),
			)
		);
		$repeater->add_control(
			'countdown_date',
			array(
				'label'       => esc_html__( 'Target Date & Time', 'gsm-slider' ),
				'type'        => Controls_Manager::DATE_TIME,
				'default'     => gmdate( 'Y-m-d H:i', strtotime( '+7 days' ) ),
				'description' => esc_html__( 'Countdown will count down to this date/time.', 'gsm-slider' ),
				'condition'   => array( 'countdown_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'countdown_label_days',
			array(
				'label'     => esc_html__( 'Days Label', 'gsm-slider' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Days', 'gsm-slider' ),
				'condition' => array( 'countdown_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'countdown_label_hours',
			array(
				'label'     => esc_html__( 'Hours Label', 'gsm-slider' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Hours', 'gsm-slider' ),
				'condition' => array( 'countdown_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'countdown_label_minutes',
			array(
				'label'     => esc_html__( 'Minutes Label', 'gsm-slider' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Mins', 'gsm-slider' ),
				'condition' => array( 'countdown_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'countdown_label_seconds',
			array(
				'label'     => esc_html__( 'Seconds Label', 'gsm-slider' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Secs', 'gsm-slider' ),
				'condition' => array( 'countdown_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'countdown_style',
			array(
				'label'   => esc_html__( 'Style', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'blocks',
				'options' => array(
					'blocks'  => esc_html__( 'Blocks (Box style)', 'gsm-slider' ),
					'minimal' => esc_html__( 'Minimal (Flat)', 'gsm-slider' ),
					'circle'  => esc_html__( 'Circle (Ring)', 'gsm-slider' ),
				),
				'condition' => array( 'countdown_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'countdown_position',
			array(
				'label'   => esc_html__( 'Position', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'below-content',
				'options' => array(
					'below-content'   => esc_html__( 'Below Content', 'gsm-slider' ),
					'above-content'   => esc_html__( 'Above Content', 'gsm-slider' ),
					'bottom-center'   => esc_html__( 'Bottom Center (Fixed)', 'gsm-slider' ),
				),
				'condition' => array( 'countdown_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'countdown_number_color',
			array(
				'label'     => esc_html__( 'Number Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'condition' => array( 'countdown_enable' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-countdown-number' => 'color: {{VALUE}};',
				),
			)
		);
		$repeater->add_control(
			'countdown_label_color',
			array(
				'label'     => esc_html__( 'Label Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.7)',
				'condition' => array( 'countdown_enable' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-countdown-label' => 'color: {{VALUE}};',
				),
			)
		);
		$repeater->add_control(
			'countdown_box_bg',
			array(
				'label'     => esc_html__( 'Box Background', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(0,0,0,0.35)',
				'condition' => array(
					'countdown_enable' => 'yes',
					'countdown_style'  => 'blocks',
				),
				'selectors' => array(
					'{{WRAPPER}} .gsm-countdown-item' => 'background: {{VALUE}};',
				),
			)
		);
		$repeater->add_control(
			'countdown_action',
			array(
				'label'   => esc_html__( 'When Expired', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'message',
				'options' => array(
					'message' => esc_html__( 'Show Message', 'gsm-slider' ),
					'hide'     => esc_html__( 'Hide Timer', 'gsm-slider' ),
					'next'     => esc_html__( 'Auto Next Slide', 'gsm-slider' ),
				),
				'condition' => array( 'countdown_enable' => 'yes' ),
			)
		);
		$repeater->add_control(
			'countdown_expired_msg',
			array(
				'label'     => esc_html__( 'Expired Message', 'gsm-slider' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Offer Ended!', 'gsm-slider' ),
				'condition' => array(
					'countdown_enable' => 'yes',
					'countdown_action' => 'message',
				),
			)
		);
		$repeater->add_control(
			'countdown_urgency',
			array(
				'label'        => esc_html__( 'Urgency Flash (last hour)', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Timer flashes red when less than 1 hour remains.', 'gsm-slider' ),
				'condition'    => array( 'countdown_enable' => 'yes' ),
			)
		);

		// --- Content ---
		$repeater->add_control(
			'heading',
			array(
				'label'       => esc_html__( 'Heading', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Slide Heading', 'gsm-slider' ),
				'label_block' => true,
			)
		);

		$repeater->add_control(
			'heading_tag',
			array(
				'label'   => esc_html__( 'Heading HTML Tag', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h2',
				'options' => array(
					'h1'   => esc_html__( 'H1', 'gsm-slider' ),
					'h2'   => esc_html__( 'H2', 'gsm-slider' ),
					'h3'   => esc_html__( 'H3', 'gsm-slider' ),
					'h4'   => esc_html__( 'H4', 'gsm-slider' ),
					'h5'   => esc_html__( 'H5', 'gsm-slider' ),
					'h6'   => esc_html__( 'H6', 'gsm-slider' ),
					'div'  => esc_html__( 'div', 'gsm-slider' ),
					'p'    => esc_html__( 'p', 'gsm-slider' ),
					'span' => esc_html__( 'span', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'typing_effect',
			array(
				'label'        => esc_html__( 'Typing Effect', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'gsm-slider' ),
				'label_off'    => esc_html__( 'Off', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Animate heading text as if being typed live.', 'gsm-slider' ),
			)
		);

		$repeater->add_control(
			'typing_strings',
			array(
				'label'       => esc_html__( 'Typing Strings', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXTAREA,
				'placeholder' => "Welcome to our site\nWe build amazing things\nLet's get started",
				'description' => esc_html__( 'One string per line. Each string will be typed, deleted, then the next typed.', 'gsm-slider' ),
				'condition'   => array( 'typing_effect' => 'yes' ),
			)
		);

		$repeater->add_control(
			'typing_speed',
			array(
				'label'     => esc_html__( 'Typing Speed (ms)', 'gsm-slider' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 80,
				'min'       => 20,
				'max'       => 300,
				'step'      => 10,
				'condition' => array( 'typing_effect' => 'yes' ),
			)
		);

		$repeater->add_control(
			'typing_delete_speed',
			array(
				'label'     => esc_html__( 'Delete Speed (ms)', 'gsm-slider' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 40,
				'min'       => 10,
				'max'       => 200,
				'step'      => 10,
				'condition' => array( 'typing_effect' => 'yes' ),
			)
		);

		$repeater->add_control(
			'typing_pause',
			array(
				'label'     => esc_html__( 'Pause Between Strings (ms)', 'gsm-slider' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 1800,
				'min'       => 500,
				'max'       => 5000,
				'step'      => 100,
				'condition' => array( 'typing_effect' => 'yes' ),
			)
		);

		$repeater->add_control(
			'typing_cursor_color',
			array(
				'label'     => esc_html__( 'Cursor Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'condition' => array( 'typing_effect' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-typing-cursor' => 'color: {{VALUE}};',
				),
			)
		);

		$repeater->add_control(
			'custom_css_class',
			array(
				'label'       => esc_html__( 'Custom CSS Class', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Add custom CSS class to this slide wrapper.', 'gsm-slider' ),
			)
		);

		$repeater->add_control(
			'description',
			array(
				'label'   => esc_html__( 'Description', 'gsm-slider' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Add a short description for this slide.', 'gsm-slider' ),
				'rows'    => 4,
			)
		);

		$repeater->add_control(
			'sep_anim',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);
		$repeater->add_control(
			'heading_anim_label',
			array(
				'label' => esc_html__( 'Animation & Layout', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$repeater->add_control(
			'anim_heading',
			array(
				'label'   => esc_html__( 'Heading Animation', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade-up',
				'options' => array(
					'none'       => esc_html__( 'None', 'gsm-slider' ),
					'fade-up'    => esc_html__( 'Fade Up', 'gsm-slider' ),
					'fade-down'  => esc_html__( 'Fade Down', 'gsm-slider' ),
					'fade-left'  => esc_html__( 'Fade Left', 'gsm-slider' ),
					'fade-right' => esc_html__( 'Fade Right', 'gsm-slider' ),
					'zoom-in'    => esc_html__( 'Zoom In', 'gsm-slider' ),
					'zoom-out'   => esc_html__( 'Zoom Out', 'gsm-slider' ),
					'flip-x'     => esc_html__( 'Flip X', 'gsm-slider' ),
					'flip-y'     => esc_html__( 'Flip Y', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'anim_heading_delay',
			array(
				'label'   => esc_html__( 'Heading Delay (ms)', 'gsm-slider' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => 0,
				'max'     => 3000,
				'step'    => 50,
			)
		);

		$repeater->add_control(
			'anim_text',
			array(
				'label'   => esc_html__( 'Text Animation', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade-up',
				'options' => array(
					'none'       => esc_html__( 'None', 'gsm-slider' ),
					'fade-up'    => esc_html__( 'Fade Up', 'gsm-slider' ),
					'fade-down'  => esc_html__( 'Fade Down', 'gsm-slider' ),
					'fade-left'  => esc_html__( 'Fade Left', 'gsm-slider' ),
					'fade-right' => esc_html__( 'Fade Right', 'gsm-slider' ),
					'zoom-in'    => esc_html__( 'Zoom In', 'gsm-slider' ),
					'zoom-out'   => esc_html__( 'Zoom Out', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'anim_text_delay',
			array(
				'label'   => esc_html__( 'Text Delay (ms)', 'gsm-slider' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 150,
				'min'     => 0,
				'max'     => 3000,
				'step'    => 50,
			)
		);

		$repeater->add_control(
			'anim_btn',
			array(
				'label'   => esc_html__( 'Button Animation', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade-up',
				'options' => array(
					'none'       => esc_html__( 'None', 'gsm-slider' ),
					'fade-up'    => esc_html__( 'Fade Up', 'gsm-slider' ),
					'fade-down'  => esc_html__( 'Fade Down', 'gsm-slider' ),
					'fade-left'  => esc_html__( 'Fade Left', 'gsm-slider' ),
					'fade-right' => esc_html__( 'Fade Right', 'gsm-slider' ),
					'zoom-in'    => esc_html__( 'Zoom In', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'anim_btn_delay',
			array(
				'label'   => esc_html__( 'Button Delay (ms)', 'gsm-slider' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 300,
				'min'     => 0,
				'max'     => 3000,
				'step'    => 50,
			)
		);

		$repeater->add_control(
			'exit_animation_sep',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);

		$repeater->add_control(
			'exit_animation_heading',
			array(
				'label' => esc_html__( 'Exit Animations', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$repeater->add_control(
			'exit_anim_heading',
			array(
				'label'   => esc_html__( 'Heading Exit', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade-out-up',
				'options' => array(
					'none'           => esc_html__( 'None', 'gsm-slider' ),
					'fade-out-up'    => esc_html__( 'Fade Out Up', 'gsm-slider' ),
					'fade-out-down'  => esc_html__( 'Fade Out Down', 'gsm-slider' ),
					'fade-out-left'  => esc_html__( 'Fade Out Left', 'gsm-slider' ),
					'fade-out-right' => esc_html__( 'Fade Out Right', 'gsm-slider' ),
					'zoom-out'       => esc_html__( 'Zoom Out', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'exit_anim_text',
			array(
				'label'   => esc_html__( 'Text Exit', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade-out-up',
				'options' => array(
					'none'           => esc_html__( 'None', 'gsm-slider' ),
					'fade-out-up'    => esc_html__( 'Fade Out Up', 'gsm-slider' ),
					'fade-out-down'  => esc_html__( 'Fade Out Down', 'gsm-slider' ),
					'fade-out-left'  => esc_html__( 'Fade Out Left', 'gsm-slider' ),
					'fade-out-right' => esc_html__( 'Fade Out Right', 'gsm-slider' ),
					'zoom-out'       => esc_html__( 'Zoom Out', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'exit_anim_btn',
			array(
				'label'   => esc_html__( 'Button Exit', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'fade-out-up',
				'options' => array(
					'none'           => esc_html__( 'None', 'gsm-slider' ),
					'fade-out-up'    => esc_html__( 'Fade Out Up', 'gsm-slider' ),
					'fade-out-down'  => esc_html__( 'Fade Out Down', 'gsm-slider' ),
					'fade-out-left'  => esc_html__( 'Fade Out Left', 'gsm-slider' ),
					'fade-out-right' => esc_html__( 'Fade Out Right', 'gsm-slider' ),
					'zoom-out'       => esc_html__( 'Zoom Out', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'anim_badge_delay',
			array(
				'label'   => esc_html__( 'Badge Delay (ms)', 'gsm-slider' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
				'min'     => 0,
				'max'     => 3000,
				'step'    => 50,
			)
		);

		$repeater->add_control(
			'badge_text',
			array(
				'label'       => esc_html__( 'Badge Text', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'NEW', 'gsm-slider' ),
				'description' => esc_html__( 'Small badge shown above the heading. e.g. NEW, SALE, HOT', 'gsm-slider' ),
			)
		);

		$repeater->add_control(
			'badge_color',
			array(
				'label'     => esc_html__( 'Badge Background Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#e74c3c',
				'condition' => array( 'badge_text!' => '' ),
			)
		);

		$repeater->add_control(
			'btn_text',
			array(
				'label'   => esc_html__( 'Button Text', 'gsm-slider' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Learn More', 'gsm-slider' ),
			)
		);

		$repeater->add_control(
			'btn_url',
			array(
				'label'         => esc_html__( 'Button URL', 'gsm-slider' ),
				'type'          => Controls_Manager::URL,
				'placeholder'   => esc_html__( 'https://example.com', 'gsm-slider' ),
				'show_external' => true,
				'default'       => array(
					'url'         => '',
					'is_external' => false,
					'nofollow'    => false,
				),
			)
		);

		$repeater->add_control(
			'lightbox_sep',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);

		$repeater->add_control(
			'lightbox_heading',
			array(
				'label' => esc_html__( 'Video Lightbox', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$repeater->add_control(
			'lightbox_enable',
			array(
				'label'        => esc_html__( 'Enable Lightbox', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$repeater->add_control(
			'lightbox_video_url',
			array(
				'label'       => esc_html__( 'Lightbox Video URL', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'https://www.youtube.com/watch?v=xxxxx', 'gsm-slider' ),
				'description' => esc_html__( 'YouTube, Vimeo, or direct MP4 URL. Opens in fullscreen when slide is clicked.', 'gsm-slider' ),
				'condition'   => array( 'lightbox_enable' => 'yes' ),
			)
		);

		$repeater->add_control(
			'lightbox_btn_icon',
			array(
				'label'        => esc_html__( 'Show Play Button', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => array( 'lightbox_enable' => 'yes' ),
			)
		);

		$repeater->add_control(
			'lightbox_btn_position',
			array(
				'label'     => esc_html__( 'Play Button Position', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center',
				'options'   => array(
					'center'       => esc_html__( 'Center', 'gsm-slider' ),
					'bottom-left'  => esc_html__( 'Bottom Left', 'gsm-slider' ),
					'bottom-right' => esc_html__( 'Bottom Right', 'gsm-slider' ),
				),
				'condition' => array(
					'lightbox_enable'   => 'yes',
					'lightbox_btn_icon' => 'yes',
				),
			)
		);

		$repeater->add_control(
			'sep_layout',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);
		$repeater->add_control(
			'heading_layout_label',
			array(
				'label' => esc_html__( 'Position & Size', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$repeater->add_control(
			'content_align',
			array(
				'label'   => esc_html__( 'Content Align', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'inherit',
				'options' => array(
					'inherit' => esc_html__( 'Inherit from Widget', 'gsm-slider' ),
					'left'    => esc_html__( 'Left', 'gsm-slider' ),
					'center'  => esc_html__( 'Center', 'gsm-slider' ),
					'right'   => esc_html__( 'Right', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'content_vertical',
			array(
				'label'   => esc_html__( 'Vertical Position', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'middle',
				'options' => array(
					'top'    => esc_html__( 'Top', 'gsm-slider' ),
					'middle' => esc_html__( 'Middle', 'gsm-slider' ),
					'bottom' => esc_html__( 'Bottom', 'gsm-slider' ),
				),
			)
		);

		$repeater->add_control(
			'content_box_enable',
			array(
				'label'        => esc_html__( 'Enable Content Box', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$repeater->add_control(
			'split_screen',
			array(
				'label'        => esc_html__( 'Split Screen Layout', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'gsm-slider' ),
				'label_off'    => esc_html__( 'Off', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Divide slide into two halves — image left, content right (or reverse).', 'gsm-slider' ),
			)
		);

		$repeater->add_control(
			'split_reverse',
			array(
				'label'        => esc_html__( 'Reverse Layout', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Content left, image right.', 'gsm-slider' ),
				'condition'    => array( 'split_screen' => 'yes' ),
			)
		);

		$repeater->add_control(
			'split_ratio',
			array(
				'label'     => esc_html__( 'Split Ratio', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '50-50',
				'options'   => array(
					'50-50' => '50 / 50',
					'40-60' => '40 / 60',
					'60-40' => '60 / 40',
					'35-65' => '35 / 65',
					'65-35' => '65 / 35',
				),
				'condition' => array( 'split_screen' => 'yes' ),
			)
		);

		$repeater->add_control(
			'split_content_bg',
			array(
				'label'     => esc_html__( 'Content Panel Background', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1a1a2e',
				'condition' => array( 'split_screen' => 'yes' ),
			)
		);

		$repeater->add_control(
			'split_diagonal',
			array(
				'label'        => esc_html__( 'Diagonal Divider', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Add a diagonal cut between the two panels.', 'gsm-slider' ),
				'condition'    => array( 'split_screen' => 'yes' ),
			)
		);

		$repeater->add_control(
			'book_sep',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);

		$repeater->add_control(
			'book_layout_heading',
			array(
				'label' => esc_html__( 'Book Showcase Layout', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$repeater->add_control(
			'book_layout_enable',
			array(
				'label'        => esc_html__( 'Enable Book Showcase', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'gsm-slider' ),
				'label_off'    => esc_html__( 'Off', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Left: content. Right: 2×2 book grid.', 'gsm-slider' ),
			)
		);

		$book_img_condition = array( 'book_layout_enable' => 'yes' );

		foreach ( array( 1, 2, 3, 4 ) as $bn ) {
			$repeater->add_control(
				'book' . $bn . '_image',
				array(
					/* translators: %d: book slot number 1–4 */
					'label'     => sprintf( esc_html__( 'Book %d Cover', 'gsm-slider' ), $bn ),
					'type'      => Controls_Manager::MEDIA,
					'condition' => $book_img_condition,
				)
			);
			$repeater->add_control(
				'book' . $bn . '_title',
				array(
					/* translators: %d: book slot number 1–4 */
					'label'     => sprintf( esc_html__( 'Book %d Title', 'gsm-slider' ), $bn ),
					'type'      => Controls_Manager::TEXT,
					'condition' => $book_img_condition,
				)
			);
			$repeater->add_control(
				'book' . $bn . '_url',
				array(
					/* translators: %d: book slot number 1–4 */
					'label'     => sprintf( esc_html__( 'Book %d Link', 'gsm-slider' ), $bn ),
					'type'      => Controls_Manager::URL,
					'condition' => $book_img_condition,
				)
			);
			$repeater->add_control(
				'book' . $bn . '_ribbon',
				array(
					/* translators: %d: book slot number 1–4 */
					'label'       => sprintf( esc_html__( 'Book %d Ribbon Text', 'gsm-slider' ), $bn ),
					'type'        => Controls_Manager::TEXT,
					'placeholder' => esc_html__( 'Bestseller', 'gsm-slider' ),
					'description' => esc_html__( 'Small badge shown on top-right corner of the book. Leave blank to hide.', 'gsm-slider' ),
					'condition'   => $book_img_condition,
				)
			);
			$repeater->add_control(
				'book' . $bn . '_ribbon_color',
				array(
					/* translators: %d: book slot number 1–4 */
					'label'     => sprintf( esc_html__( 'Book %d Ribbon Color', 'gsm-slider' ), $bn ),
					'type'      => Controls_Manager::COLOR,
					'default'   => '#c8a800',
					'condition' => array_merge( $book_img_condition, array( 'book' . $bn . '_ribbon!' => '' ) ),
				)
			);
			$repeater->add_control(
				'book' . $bn . '_spine_text',
				array(
					/* translators: %d: book slot number 1–4 */
					'label'       => sprintf( esc_html__( 'Book %d Spine Text', 'gsm-slider' ), $bn ),
					'type'        => Controls_Manager::TEXT,
					'placeholder' => esc_html__( 'Publisher name...', 'gsm-slider' ),
					'description' => esc_html__( 'Text shown vertically on the book spine. Leave blank for plain spine.', 'gsm-slider' ),
					'condition'   => $book_img_condition,
				)
			);
		}

		$repeater->add_control(
			'book_show_title',
			array(
				'label'        => esc_html__( 'Show Book Titles', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => $book_img_condition,
			)
		);

		$repeater->add_control(
			'book_content_ratio',
			array(
				'label'   => esc_html__( 'Content / Books Ratio', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '50-50',
				'options' => array(
					'50-50' => '50 / 50',
					'55-45' => '55 / 45',
					'60-40' => '60 / 40',
					'45-55' => '45 / 55',
				),
				'condition' => $book_img_condition,
			)
		);

		$repeater->add_control(
			'book_card_bg',
			array(
				'label'     => esc_html__( 'Card Background', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => 'rgba(255,255,255,0.08)',
				'condition' => $book_img_condition,
			)
		);

		$repeater->add_control(
			'book_float_animation',
			array(
				'label'        => esc_html__( 'Floating Animation', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Subtle up-down floating effect on books.', 'gsm-slider' ),
				'condition'    => $book_img_condition,
			)
		);

		$repeater->add_control(
			'book_single_width',
			array(
				'label'       => esc_html__( 'Single Book Size', 'gsm-slider' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'normal',
				'options'     => array(
					'compact' => esc_html__( 'Compact (180 px)', 'gsm-slider' ),
					'normal'  => esc_html__( 'Normal (260 px)', 'gsm-slider' ),
					'wide'    => esc_html__( 'Wide (360 px)', 'gsm-slider' ),
					'full'    => esc_html__( 'Full column width', 'gsm-slider' ),
				),
				'description' => esc_html__( 'Controls the maximum width when only one book is set. Has no effect with 2+ books.', 'gsm-slider' ),
				'condition'   => $book_img_condition,
			)
		);

		$repeater->add_control(
			'book_heading_highlight',
			array(
				'label'       => esc_html__( 'Heading Gold Highlight', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Exact phrase from the heading to wrap in accent color (no typing effect).', 'gsm-slider' ),
				'condition'   => $book_img_condition,
			)
		);

		$repeater->add_control(
			'book_btn2_text',
			array(
				'label'       => esc_html__( 'Second Button Text', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXT,
				'description' => esc_html__( 'Outline style; sits next to the primary button.', 'gsm-slider' ),
				'condition'   => $book_img_condition,
			)
		);

		$repeater->add_control(
			'book_btn2_url',
			array(
				'label'     => esc_html__( 'Second Button Link', 'gsm-slider' ),
				'type'      => Controls_Manager::URL,
				'condition' => $book_img_condition,
			)
		);

		$repeater->add_responsive_control(
			'content_max_width',
			array(
				'label'      => esc_html__( 'Content Max Width', 'gsm-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => 100, 'max' => 1400 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
				),
				'default'    => array( 'size' => 520, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} {{CURRENT_ITEM}} .gsm-slide-content' => 'max-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$repeater->add_control(
			'cinematic_effect',
			array(
				'label'        => esc_html__( 'Cinematic Slide Effect', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'slides_data',
			array(
				'label'       => esc_html__( 'Slides', 'gsm-slider' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array(
						'heading'     => esc_html__( 'First Slide', 'gsm-slider' ),
						'description' => esc_html__( 'Add a description for your first slide.', 'gsm-slider' ),
						'btn_text'    => esc_html__( 'Get Started', 'gsm-slider' ),
					),
					array(
						'heading'     => esc_html__( 'Second Slide', 'gsm-slider' ),
						'description' => esc_html__( 'Add a description for your second slide.', 'gsm-slider' ),
						'btn_text'    => esc_html__( 'Learn More', 'gsm-slider' ),
					),
				),
				'title_field' => '<# if ( bg_image && bg_image.url ) { #><img src="{{ bg_image.url }}" alt="" style="width:22px;height:22px;object-fit:cover;border-radius:4px;margin-right:8px;vertical-align:middle;" /><# } else if ( video_poster && video_poster.url ) { #><img src="{{ video_poster.url }}" alt="" style="width:22px;height:22px;object-fit:cover;border-radius:4px;margin-right:8px;vertical-align:middle;" /><# } #>{{{ heading || "Slide" }}}',
				'condition'   => array( 'content_source' => 'custom' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the Slider Settings content section.
	 */
	private function register_slider_settings_section() {
		$this->start_controls_section(
			'section_slider_settings',
			array(
				'label' => esc_html__( 'Slider Settings', 'gsm-slider' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_responsive_control(
			'slider_height',
			array(
				'label'      => esc_html__( 'Slider Height', 'gsm-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array( 'min' => 200, 'max' => 1200 ),
					'vh' => array( 'min' => 20, 'max' => 100 ),
				),
				'default'    => array( 'size' => 520, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-slider' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'effect',
			array(
				'label'       => esc_html__( 'Transition Effect', 'gsm-slider' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'fade',
				'description' => esc_html__( 'Cube, Flip, Coverflow, Cards, and Creative transitions require Swiper modules. If they do not work, your Elementor version may not include these modules.', 'gsm-slider' ),
				'options'     => array(
					'slide'     => esc_html__( 'Slide', 'gsm-slider' ),
					'fade'      => esc_html__( 'Fade', 'gsm-slider' ),
					'cube'      => esc_html__( 'Cube 3D', 'gsm-slider' ),
					'flip'      => esc_html__( 'Flip', 'gsm-slider' ),
					'coverflow' => esc_html__( 'Coverflow', 'gsm-slider' ),
					'cards'     => esc_html__( 'Cards', 'gsm-slider' ),
					'creative'  => esc_html__( 'Creative (Zoom)', 'gsm-slider' ),
					'creative2' => esc_html__( 'Creative (Push)', 'gsm-slider' ),
				),
			)
		);

		$this->add_control(
			'coverflow_rotate',
			array(
				'label'     => esc_html__( 'Coverflow Rotate', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 90 ) ),
				'default'   => array( 'size' => 30 ),
				'condition' => array( 'effect' => 'coverflow' ),
			)
		);

		$this->add_control(
			'coverflow_depth',
			array(
				'label'     => esc_html__( 'Coverflow Depth', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 300 ) ),
				'default'   => array( 'size' => 100 ),
				'condition' => array( 'effect' => 'coverflow' ),
			)
		);

		$this->add_responsive_control(
			'slides_per_view',
			array(
				'label'   => esc_html__( 'Slides Per View', 'gsm-slider' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 10,
				'default' => 1,
			)
		);

		$this->add_responsive_control(
			'spacing',
			array(
				'label'   => esc_html__( 'Space Between (px)', 'gsm-slider' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 0,
				'max'     => 100,
				'default' => 0,
			)
		);

		$this->add_control(
			'center_mode',
			array(
				'label'        => esc_html__( 'Center Mode', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => esc_html__( 'Autoplay', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'delay',
			array(
				'label'     => esc_html__( 'Autoplay Delay (ms)', 'gsm-slider' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5000,
				'min'       => 1000,
				'step'      => 500,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'autoplay_pause_on_hover',
			array(
				'label'        => esc_html__( 'Pause on Hover', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'condition'    => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'autoplay_progress',
			array(
				'label'        => esc_html__( 'Show Progress Bar', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'autoplay_viewport',
			array(
				'label'        => esc_html__( 'Pause When Off-Screen', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Pause autoplay when the slider scrolls out of view and resume when it returns. Saves CPU and battery on long pages.', 'gsm-slider' ),
				'condition'    => array( 'autoplay' => 'yes' ),
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'   => esc_html__( 'Transition Speed (ms)', 'gsm-slider' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 900,
				'min'     => 100,
				'step'    => 100,
			)
		);


		$this->add_control(
			'scroll_trigger',
			array(
				'label'        => esc_html__( 'Scroll Trigger Animation', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'description'  => esc_html__( 'Animate content only when slider enters the viewport. Useful when slider is not at the top of the page.', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'mousewheel_sep',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);

		$this->add_control(
			'mousewheel_enable',
			array(
				'label'        => esc_html__( 'Mousewheel Navigation', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'gsm-slider' ),
				'label_off'    => esc_html__( 'Off', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Scroll wheel changes slides. Page scroll resumes after last slide.', 'gsm-slider' ),
			)
		);

		$this->add_control(
			'mousewheel_sensitivity',
			array(
				'label'     => esc_html__( 'Sensitivity', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 5 ) ),
				'default'   => array( 'size' => 1 ),
				'condition' => array( 'mousewheel_enable' => 'yes' ),
			)
		);

		$this->add_control(
			'mousewheel_indicator',
			array(
				'label'        => esc_html__( 'Show Scroll Indicator', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Show a subtle scroll hint on first load.', 'gsm-slider' ),
				'condition'    => array( 'mousewheel_enable' => 'yes' ),
			)
		);

		$this->add_control(
			'mousewheel_indicator_text',
			array(
				'label'     => esc_html__( 'Indicator Text', 'gsm-slider' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => esc_html__( 'Scroll to navigate', 'gsm-slider' ),
				'condition' => array(
					'mousewheel_enable'    => 'yes',
					'mousewheel_indicator' => 'yes',
				),
			)
		);

		$this->add_control(
			'mousewheel_progress',
			array(
				'label'        => esc_html__( 'Side Progress Bar', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Vertical progress bar on the right side showing current slide position.', 'gsm-slider' ),
				'condition'    => array( 'mousewheel_enable' => 'yes' ),
			)
		);

		$this->add_control(
			'mousewheel_progress_color',
			array(
				'label'     => esc_html__( 'Progress Bar Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'condition' => array(
					'mousewheel_enable'   => 'yes',
					'mousewheel_progress' => 'yes',
				),
				'selectors' => array(
					'{{WRAPPER}} .gsm-scroll-progress-fill' => 'background: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'tilt_effect',
			array(
				'label'        => esc_html__( '3D Tilt Effect', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'On', 'gsm-slider' ),
				'label_off'    => esc_html__( 'Off', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
				'description'  => esc_html__( 'Subtle 3D tilt on mouse move. Works on image slides.', 'gsm-slider' ),
				'separator'    => 'before',
			)
		);

		$this->add_control(
			'tilt_intensity',
			array(
				'label'     => esc_html__( 'Tilt Intensity', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 1, 'max' => 20 ) ),
				'default'   => array( 'size' => 8 ),
				'condition' => array( 'tilt_effect' => 'yes' ),
			)
		);

		$this->add_control(
			'tilt_glare',
			array(
				'label'        => esc_html__( 'Glare Effect', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Add a light glare that follows mouse movement.', 'gsm-slider' ),
				'condition'    => array( 'tilt_effect' => 'yes' ),
			)
		);

		$this->add_control(
			'loop',
			array(
				'label'        => esc_html__( 'Loop', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'arrows',
			array(
				'label'        => esc_html__( 'Arrows', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'pagination_type',
			array(
				'label'   => esc_html__( 'Pagination', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'bullets',
				'options' => array(
					'none'        => esc_html__( 'None', 'gsm-slider' ),
					'bullets'     => esc_html__( 'Bullets', 'gsm-slider' ),
					'progressbar' => esc_html__( 'Progress Bar', 'gsm-slider' ),
					'fraction'    => esc_html__( 'Fraction', 'gsm-slider' ),
				),
			)
		);

		$this->add_control(
			'thumbnail_nav',
			array(
				'label'        => esc_html__( 'Thumbnail Navigation', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'gsm-slider' ),
				'label_off'    => esc_html__( 'Hide', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_responsive_control(
			'thumb_height',
			array(
				'label'      => esc_html__( 'Thumbnail Height', 'gsm-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 50, 'max' => 180 ) ),
				'default'    => array( 'size' => 80, 'unit' => 'px' ),
				'condition'  => array( 'thumbnail_nav' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-thumb-slide' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'thumb_gap',
			array(
				'label'     => esc_html__( 'Gap Between Thumbs', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 2, 'max' => 20 ) ),
				'default'   => array( 'size' => 6 ),
				'condition' => array( 'thumbnail_nav' => 'yes' ),
			)
		);

		$this->add_control(
			'thumb_width',
			array(
				'label'     => esc_html__( 'Strip Width', 'gsm-slider' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'full',
				'options'   => array(
					'full'   => esc_html__( 'Full Screen Width', 'gsm-slider' ),
					'inline' => esc_html__( 'Inline (Fit Content)', 'gsm-slider' ),
				),
				'condition' => array( 'thumbnail_nav' => 'yes' ),
			)
		);

		$this->add_responsive_control(
			'thumb_align',
			array(
				'label'     => esc_html__( 'Alignment', 'gsm-slider' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'flex-start' => array( 'title' => esc_html__( 'Left', 'gsm-slider' ), 'icon' => 'eicon-h-align-left' ),
					'center'     => array( 'title' => esc_html__( 'Center', 'gsm-slider' ), 'icon' => 'eicon-h-align-center' ),
					'flex-end'   => array( 'title' => esc_html__( 'Right', 'gsm-slider' ), 'icon' => 'eicon-h-align-right' ),
				),
				'default'   => '',
				'condition' => array( 'thumbnail_nav' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-thumbs-container'              => 'justify-content: {{VALUE}};',
					'{{WRAPPER}} .gsm-thumbs-swiper .swiper-wrapper' => 'justify-content: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'thumb_padding',
			array(
				'label'      => esc_html__( 'Strip Padding', 'gsm-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'condition'  => array( 'thumbnail_nav' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-thumbs-swiper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'thumb_bg_color',
			array(
				'label'     => esc_html__( 'Strip Background Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'thumbnail_nav' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-thumbs-swiper' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'thumb_bg_hide',
			array(
				'label'        => esc_html__( 'Hide Strip Background', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'transparent',
				'default'      => '',
				'condition'    => array( 'thumbnail_nav' => 'yes' ),
				'selectors'    => array(
					'{{WRAPPER}} .gsm-thumbs-swiper' => 'background-color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'thumb_border_radius',
			array(
				'label'      => esc_html__( 'Thumbnail Radius', 'gsm-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'condition'  => array( 'thumbnail_nav' => 'yes' ),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-thumb-slide' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'thumb_inactive_opacity',
			array(
				'label'     => esc_html__( 'Inactive Opacity', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'max' => 1, 'min' => 0.1, 'step' => 0.05 ) ),
				'condition' => array( 'thumbnail_nav' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-thumb-slide' => 'opacity: {{SIZE}};',
				),
			)
		);

		$this->add_control(
			'thumb_border_width',
			array(
				'label'     => esc_html__( 'Border Width', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'max' => 10, 'min' => 0 ) ),
				'condition' => array( 'thumbnail_nav' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-thumb-slide' => 'border-width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'thumb_inactive_border_color',
			array(
				'label'     => esc_html__( 'Inactive Border Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => array( 'thumbnail_nav' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-thumb-slide' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'thumb_active_border_color',
			array(
				'label'     => esc_html__( 'Active Thumb Border Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'condition' => array( 'thumbnail_nav' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-thumb-slide.swiper-slide-thumb-active' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the Mobile content section.
	 */
	private function register_mobile_section() {
		$this->start_controls_section(
			'section_mobile',
			array(
				'label' => esc_html__( 'Responsive Settings', 'gsm-slider' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		// ─── Tablet ────────────────────────────────────────────────────
		$this->add_control(
			'responsive_tablet_heading',
			array(
				'label' => esc_html__( '— Tablet (768px – 1024px)', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_responsive_control(
			'slider_height_tablet',
			array(
				'label'          => esc_html__( 'Height on Tablet', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px', 'vh' ),
				'range'          => array(
					'px' => array( 'min' => 200, 'max' => 1000 ),
					'vh' => array( 'min' => 20, 'max' => 100 ),
				),
				'default'        => array(
					'size' => 520,
					'unit' => 'px',
				),
				'tablet_default' => array(
					'size' => 520,
					'unit' => 'px',
				),
				'devices'        => array( 'tablet' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-slider' => 'height: {{SIZE}}{{UNIT}};',
				),
				'description'    => esc_html__( 'Overrides the main Slider Height on tablet screens.', 'gsm-slider' ),
			)
		);

		$this->add_responsive_control(
			'tablet_content_padding_scale',
			array(
				'label'      => esc_html__( 'Content Padding on Tablet', 'gsm-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 10, 'max' => 60 ),
				),
				'default'    => array(
					'size' => 24,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-slide-inner' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
				),
				'devices'    => array( 'tablet' ),
			)
		);

		// ─── Mobile ────────────────────────────────────────────────────
		$this->add_control(
			'responsive_mobile_heading',
			array(
				'label'     => esc_html__( '— Mobile (< 768px)', 'gsm-slider' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'slider_height_mobile',
			array(
				'label'          => esc_html__( 'Height on Mobile', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px', 'vh' ),
				'range'          => array(
					'px' => array( 'min' => 200, 'max' => 800 ),
					'vh' => array( 'min' => 30, 'max' => 100 ),
				),
				'default'        => array(
					'size' => 420,
					'unit' => 'px',
				),
				'mobile_default' => array(
					'size' => 420,
					'unit' => 'px',
				),
				'devices'        => array( 'mobile' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-slider' => 'height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'mobile_arrows',
			array(
				'label'        => esc_html__( 'Show Arrows on Mobile', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_responsive_control(
			'mobile_content_font_scale',
			array(
				'label'      => esc_html__( 'Content Font Scale on Mobile', 'gsm-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( '%' ),
				'range'      => array(
					'%' => array( 'min' => 50, 'max' => 100 ),
				),
				'default'    => array(
					'size' => 75,
					'unit' => '%',
				),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-slide-content' => 'font-size: {{SIZE}}{{UNIT}};',
				),
				'devices'    => array( 'mobile' ),
			)
		);

		$this->add_responsive_control(
			'mobile_content_padding_scale',
			array(
				'label'      => esc_html__( 'Content Padding on Mobile', 'gsm-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array( 'min' => 10, 'max' => 30 ),
				),
				'default'    => array(
					'size' => 16,
					'unit' => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-slide-inner' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}};',
				),
				'devices'    => array( 'mobile' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the Content Box style section.
	 */
	private function register_content_box_style_section() {
		$this->start_controls_section(
			'section_style_content_box',
			array(
				'label' => esc_html__( 'Content Box', 'gsm-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'content_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-slide-content' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'content_glass_effect',
			array(
				'label'        => esc_html__( 'Glass Morphism Effect', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_control(
			'content_glass_blur',
			array(
				'label'     => esc_html__( 'Glass Blur Intensity (px)', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 4, 'max' => 30 ) ),
				'default'   => array( 'size' => 10, 'unit' => 'px' ),
				'condition' => array( 'content_glass_effect' => 'yes' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-glass' => '--gsm-glass-blur: {{SIZE}}px;',
				),
			)
		);

		$this->add_control(
			'content_hover_effect',
			array(
				'label'        => esc_html__( 'Hover Lift Effect', 'gsm-slider' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gsm-slider' ),
				'label_off'    => esc_html__( 'No', 'gsm-slider' ),
				'return_value' => 'yes',
				'default'      => '',
			)
		);

		$this->add_responsive_control(
			'content_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'gsm-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em' ),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-slide-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'content_border',
				'selector' => '{{WRAPPER}} .gsm-slide-content',
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'content_shadow',
				'selector' => '{{WRAPPER}} .gsm-slide-content',
			)
		);

		$this->add_responsive_control(
			'content_padding',
			array(
				'label'      => esc_html__( 'Padding', 'gsm-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'default'    => array(
					'top'    => '32',
					'right'  => '40',
					'bottom' => '32',
					'left'   => '40',
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-slide-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'content_margin',
			array(
				'label'      => esc_html__( 'Margin', 'gsm-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-slide-content' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'content_position_heading',
			array(
				'label'     => esc_html__( 'Position Offset', 'gsm-slider' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_responsive_control(
			'content_offset_x',
			array(
				'label'      => esc_html__( 'Horizontal Offset', 'gsm-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => -200, 'max' => 200 ),
					'%'  => array( 'min' => -50, 'max' => 50 ),
				),
				'default'    => array( 'size' => 0, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-slide-content' => 'position: relative; left: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'content_offset_y',
			array(
				'label'      => esc_html__( 'Vertical Offset', 'gsm-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array( 'min' => -200, 'max' => 200 ),
					'%'  => array( 'min' => -50, 'max' => 50 ),
				),
				'default'    => array( 'size' => 0, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-slide-content' => 'top: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'content_alignment',
			array(
				'label'        => esc_html__( 'Text Alignment', 'gsm-slider' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'gsm-slider' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'gsm-slider' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'gsm-slider' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'selectors'    => array(
					'{{WRAPPER}} .gsm-slide-content' => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .gsm-btn-wrap' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'content_safe_padding',
			array(
				'label'     => esc_html__( 'Safe Area Side Padding (px)', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'default'   => array( 'size' => 20, 'unit' => 'px' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-slide-inner' => 'padding-left: max({{SIZE}}px, env(safe-area-inset-left)); padding-right: max({{SIZE}}px, env(safe-area-inset-right));',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the Typography style section.
	 */
	private function register_typography_style_section() {
		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => esc_html__( 'Typography', 'gsm-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'heading_typo',
				'label'    => esc_html__( 'Heading Typography', 'gsm-slider' ),
				'selector' => '{{WRAPPER}} .gsm-slide-content h1, {{WRAPPER}} .gsm-slide-content h2, {{WRAPPER}} .gsm-slide-content h3, {{WRAPPER}} .gsm-slide-content h4, {{WRAPPER}} .gsm-slide-content h5, {{WRAPPER}} .gsm-slide-content h6, {{WRAPPER}} .gsm-slide-content .gsm-heading',
			)
		);

		$this->add_control(
			'heading_color',
			array(
				'label'     => esc_html__( 'Heading Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-slide-content h1' => 'color: {{VALUE}};',
					'{{WRAPPER}} .gsm-slide-content h2' => 'color: {{VALUE}};',
					'{{WRAPPER}} .gsm-slide-content h3' => 'color: {{VALUE}};',
					'{{WRAPPER}} .gsm-slide-content h4' => 'color: {{VALUE}};',
					'{{WRAPPER}} .gsm-slide-content h5' => 'color: {{VALUE}};',
					'{{WRAPPER}} .gsm-slide-content h6' => 'color: {{VALUE}};',
					'{{WRAPPER}} .gsm-slide-content .gsm-heading' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'text_typo',
				'label'    => esc_html__( 'Description Typography', 'gsm-slider' ),
				'selector' => '{{WRAPPER}} .gsm-slide-content p',
			)
		);

		$this->add_control(
			'text_color',
			array(
				'label'     => esc_html__( 'Description Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-slide-content p' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'badge_typo',
				'label'    => esc_html__( 'Badge Typography', 'gsm-slider' ),
				'selector' => '{{WRAPPER}} .gsm-slide-content .gsm-badge-inline',
			)
		);

		$this->add_control(
			'badge_color_text',
			array(
				'label'     => esc_html__( 'Badge Text Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-slide-content .gsm-badge-inline' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the Book Showcase style section.
	 */
	private function register_book_showcase_style_section() {
		$this->start_controls_section(
			'section_style_book_showcase',
			array(
				'label' => esc_html__( 'Book Showcase', 'gsm-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'book_layout_heading',
			array(
				'label' => esc_html__( 'Content Container', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'book_showcase_layout_type',
			array(
				'label'   => esc_html__( 'Layout Structure', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'boxed',
				'options' => array(
					'boxed'      => esc_html__( 'Boxed', 'gsm-slider' ),
					'full_width' => esc_html__( 'Full Width', 'gsm-slider' ),
				),
			)
		);

		$this->add_responsive_control(
			'book_showcase_box_width',
			array(
				'label'      => esc_html__( 'Box Width', 'gsm-slider' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%', 'vw' ),
				'range'      => array(
					'px' => array( 'min' => 400, 'max' => 1600 ),
					'%'  => array( 'min' => 10, 'max' => 100 ),
					'vw' => array( 'min' => 10, 'max' => 100 ),
				),
				'default'    => array( 'size' => 1140, 'unit' => 'px' ),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-book-layout-inner' => 'width: 100%; max-width: {{SIZE}}{{UNIT}}; margin: 0 auto;',
				),
				'condition'  => array(
					'book_showcase_layout_type' => 'boxed',
				),
			)
		);

		$this->add_responsive_control(
			'book_grid_columns',
			array(
				'label'       => esc_html__( 'Grid Columns', 'gsm-slider' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 4,
				'step'        => 1,
				'selectors'   => array(
					'{{WRAPPER}} .gsm-book-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr) !important;',
				),
				'description' => esc_html__( 'Leave blank for auto columns.', 'gsm-slider' ),
			)
		);

		$this->add_control(
			'book_title_heading',
			array(
				'label'     => esc_html__( 'Book Title', 'gsm-slider' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'book_title_typography',
				'label'    => esc_html__( 'Typography', 'gsm-slider' ),
				'selector' => '{{WRAPPER}} .gsm-book-title',
			)
		);

		$this->add_control(
			'book_global_title_color',
			array(
				'label'     => esc_html__( 'Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-book-title' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'book_ribbon_heading',
			array(
				'label'     => esc_html__( 'Ribbon Text', 'gsm-slider' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'book_ribbon_typography',
				'label'    => esc_html__( 'Typography', 'gsm-slider' ),
				'selector' => '{{WRAPPER}} .gsm-book-ribbon',
			)
		);

		$this->add_control(
			'book_global_ribbon_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-book-ribbon' => 'color: {{VALUE}} !important;',
				),
			)
		);

		$this->add_control(
			'book_spine_heading',
			array(
				'label'     => esc_html__( 'Spine Text', 'gsm-slider' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'book_spine_typography',
				'label'    => esc_html__( 'Typography', 'gsm-slider' ),
				'selector' => '{{WRAPPER}} .gsm-book-spine-text',
			)
		);

		$this->add_control(
			'book_global_spine_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-book-spine-text' => 'color: {{VALUE}} !important;',
				),
			)
		);

		/* ── Responsive Controls ──────────────────────────────────── */
		$this->add_control(
			'book_responsive_heading',
			array(
				'label'     => esc_html__( 'Responsive Settings', 'gsm-slider' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'book_responsive_desc',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => '<p style="font-size:11px;color:#aaa;margin:0 0 4px;">' . esc_html__( 'Use the device icons (Desktop / Tablet / Mobile) at the top of the panel to switch breakpoints for the controls below.', 'gsm-slider' ) . '</p>',
				'content_classes' => 'elementor-descriptor',
			)
		);

		// ── Slider Height (per breakpoint) ──────────────────────────
		$this->add_responsive_control(
			'book_slider_height',
			array(
				'label'          => esc_html__( 'Slider Height', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px', 'vh' ),
				'range'          => array(
					'px' => array( 'min' => 200, 'max' => 1200 ),
					'vh' => array( 'min' => 20,  'max' => 100 ),
				),
				'tablet_default' => array( 'size' => 700, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 620, 'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-slider' => 'height: {{SIZE}}{{UNIT}};',
				),
				'description'    => esc_html__( 'Override the slider height for this breakpoint. Leave blank to inherit the global Slider Height.', 'gsm-slider' ),
			)
		);

		// ── Mobile Layout Mode ──────────────────────────────────────
		$this->add_control(
			'book_mobile_layout',
			array(
				'label'       => esc_html__( 'Mobile Layout', 'gsm-slider' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'stack',
				'options'     => array(
					'stack'    => esc_html__( 'Stack (content top, books below)', 'gsm-slider' ),
					'books_up' => esc_html__( 'Books Top (books above, content below)', 'gsm-slider' ),
					'side'     => esc_html__( 'Side by Side (keep horizontal)', 'gsm-slider' ),
					'hide_books' => esc_html__( 'Hide Books (content only)', 'gsm-slider' ),
				),
				'description' => esc_html__( 'Controls column order when stacked on mobile/tablet.', 'gsm-slider' ),
				'prefix_class' => 'gsm-book-mobile-',
			)
		);

		// ── Inner Padding (per breakpoint) ──────────────────────────
		$this->add_responsive_control(
			'book_inner_padding_v',
			array(
				'label'          => esc_html__( 'Inner Vertical Padding (px)', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'tablet_default' => array( 'size' => 20, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 16, 'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-layout-inner' => 'padding-top: {{SIZE}}{{UNIT}} !important; padding-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'book_inner_padding_h',
			array(
				'label'          => esc_html__( 'Inner Horizontal Padding (px)', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 120 ) ),
				'tablet_default' => array( 'size' => 16, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 12, 'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-layout-inner' => 'padding-left: {{SIZE}}{{UNIT}} !important; padding-right: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		// ── Book Grid Columns (per breakpoint) ──────────────────────
		$this->add_responsive_control(
			'book_grid_columns_responsive',
			array(
				'label'          => esc_html__( 'Book Grid Columns', 'gsm-slider' ),
				'type'           => Controls_Manager::NUMBER,
				'min'            => 1,
				'max'            => 4,
				'step'           => 1,
				'default'        => '',
				'tablet_default' => 2,
				'mobile_default' => 2,
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr) !important;',
				),
				'description'    => esc_html__( 'Number of columns in the book grid for each breakpoint. (1–4)', 'gsm-slider' ),
			)
		);

		// ── Book Grid Gap (per breakpoint) ──────────────────────────
		$this->add_responsive_control(
			'book_grid_gap',
			array(
				'label'          => esc_html__( 'Book Grid Gap', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array(
					'px' => array( 'min' => 0, 'max' => 60 ),
				),
				'tablet_default' => array( 'size' => 12, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 8,  'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-grid' => 'gap: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		// ── Book Grid Max Width (per breakpoint) ────────────────────
		$this->add_responsive_control(
			'book_grid_max_width',
			array(
				'label'          => esc_html__( 'Book Grid Max Width', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px', '%' ),
				'range'          => array(
					'px' => array( 'min' => 100, 'max' => 900 ),
					'%'  => array( 'min' => 10,  'max' => 100 ),
				),
				'tablet_default' => array( 'size' => 100, 'unit' => '%' ),
				'mobile_default' => array( 'size' => 100, 'unit' => '%' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-grid' => 'max-width: {{SIZE}}{{UNIT}};',
				),
				'description'    => esc_html__( 'Limit the total width of the book grid. Useful on tablet to prevent very wide grids.', 'gsm-slider' ),
			)
		);

		// ── Heading Font Size (per breakpoint) ──────────────────────
		$this->add_responsive_control(
			'book_heading_font_size',
			array(
				'label'          => esc_html__( 'Heading Font Size', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px', 'rem', 'vw' ),
				'range'          => array(
					'px'  => array( 'min' => 12, 'max' => 80 ),
					'rem' => array( 'min' => 0.8, 'max' => 5, 'step' => 0.1 ),
					'vw'  => array( 'min' => 1, 'max' => 10, 'step' => 0.1 ),
				),
				'tablet_default' => array( 'size' => 28, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 22, 'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-heading, {{WRAPPER}} .gsm-book-content .gsm-heading, {{WRAPPER}} .gsm-book-content h1, {{WRAPPER}} .gsm-book-content h2, {{WRAPPER}} .gsm-book-content h3' => 'font-size: {{SIZE}}{{UNIT}} !important;',
				),
				'description'    => esc_html__( 'Override the heading font size for this breakpoint.', 'gsm-slider' ),
			)
		);

		// ── Description Font Size (per breakpoint) ──────────────────
		$this->add_responsive_control(
			'book_desc_font_size',
			array(
				'label'          => esc_html__( 'Description Font Size', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px', 'rem' ),
				'range'          => array(
					'px'  => array( 'min' => 10, 'max' => 30 ),
					'rem' => array( 'min' => 0.6, 'max' => 2, 'step' => 0.05 ),
				),
				'tablet_default' => array( 'size' => 15, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 13, 'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-content p' => 'font-size: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		// ── Content Section Width (per breakpoint) ──────────────────
		$this->add_responsive_control(
			'book_content_col_width',
			array(
				'label'          => esc_html__( 'Content Column Width', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( '%', 'px' ),
				'range'          => array(
					'%'  => array( 'min' => 20, 'max' => 100 ),
					'px' => array( 'min' => 100, 'max' => 900 ),
				),
				'tablet_default' => array( 'size' => 100, 'unit' => '%' ),
				'mobile_default' => array( 'size' => 100, 'unit' => '%' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-content' => 'flex: 0 0 {{SIZE}}{{UNIT}} !important; max-width: {{SIZE}}{{UNIT}} !important;',
				),
				'description'    => esc_html__( 'Width of the left content column. Set to 100% on mobile to stack full-width.', 'gsm-slider' ),
			)
		);

		// ── Book Grid Section Width (per breakpoint) ─────────────────
		$this->add_responsive_control(
			'book_grid_col_width',
			array(
				'label'          => esc_html__( 'Book Grid Column Width', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( '%', 'px' ),
				'range'          => array(
					'%'  => array( 'min' => 20, 'max' => 100 ),
					'px' => array( 'min' => 100, 'max' => 900 ),
				),
				'tablet_default' => array( 'size' => 100, 'unit' => '%' ),
				'mobile_default' => array( 'size' => 100, 'unit' => '%' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-grid-wrap' => 'flex: 0 0 {{SIZE}}{{UNIT}} !important; max-width: {{SIZE}}{{UNIT}} !important;',
				),
				'description'    => esc_html__( 'Width of the right book grid column. Set to 100% on mobile to stack full-width.', 'gsm-slider' ),
			)
		);

		// ── Button Size (per breakpoint) ─────────────────────────────
		$this->add_responsive_control(
			'book_btn_font_size',
			array(
				'label'          => esc_html__( 'Button Font Size', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array(
					'px' => array( 'min' => 10, 'max' => 24 ),
				),
				'tablet_default' => array( 'size' => 14, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 13, 'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-layout .gsm-btn' => 'font-size: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'book_btn_padding_v',
			array(
				'label'          => esc_html__( 'Button Vertical Padding (px)', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'tablet_default' => array( 'size' => 10, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 8, 'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-layout .gsm-btn' => 'padding-top: {{SIZE}}{{UNIT}} !important; padding-bottom: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->add_responsive_control(
			'book_btn_padding_h',
			array(
				'label'          => esc_html__( 'Button Horizontal Padding (px)', 'gsm-slider' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => array( 'px' ),
				'range'          => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'tablet_default' => array( 'size' => 20, 'unit' => 'px' ),
				'mobile_default' => array( 'size' => 16, 'unit' => 'px' ),
				'selectors'      => array(
					'{{WRAPPER}} .gsm-book-layout .gsm-btn' => 'padding-left: {{SIZE}}{{UNIT}} !important; padding-right: {{SIZE}}{{UNIT}} !important;',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the Button style section.
	 */
	private function register_button_style_section() {
		$this->start_controls_section(
			'section_style_button',
			array(
				'label' => esc_html__( 'Button', 'gsm-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'btn_bg',
			array(
				'label'     => esc_html__( 'Background Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_bg_hover',
			array(
				'label'     => esc_html__( 'Hover Background Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_color_hover',
			array(
				'label'     => esc_html__( 'Hover Text Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'btn_padding',
			array(
				'label'      => esc_html__( 'Padding', 'gsm-slider' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'default'    => array(
					'top'    => '12',
					'right'  => '28',
					'bottom' => '12',
					'left'   => '28',
					'unit'   => 'px',
				),
				'selectors'  => array(
					'{{WRAPPER}} .gsm-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'btn_border_radius',
			array(
				'label'     => esc_html__( 'Border Radius', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'   => array( 'size' => 4 ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn' => 'border-radius: {{SIZE}}px;',
				),
			)
		);

		$this->add_control(
			'btn_border_width',
			array(
				'label'     => esc_html__( 'Border Width', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 5 ) ),
				'default'   => array( 'size' => 0 ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn' => 'border-width: {{SIZE}}px; border-style: solid;',
				),
			)
		);

		$this->add_control(
			'btn_border_color',
			array(
				'label'     => esc_html__( 'Border Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn' => 'border-color: {{VALUE}};',
				),
				'condition' => array( 'btn_border_width[size]!' => '0' ),
			)
		);

		$this->add_control(
			'btn_border_color_hover',
			array(
				'label'     => esc_html__( 'Border Color Hover', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn:hover' => 'border-color: {{VALUE}};',
				),
				'condition' => array( 'btn_border_width[size]!' => '0' ),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typo',
				'label'    => esc_html__( 'Button Typography', 'gsm-slider' ),
				'selector' => '{{WRAPPER}} .gsm-btn',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register the Navigation (arrows + pagination) style section.
	 */
	private function register_navigation_style_section() {
		$this->start_controls_section(
			'section_style_navigation',
			array(
				'label' => esc_html__( 'Navigation', 'gsm-slider' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		// --- Arrows ---
		$this->add_control(
			'heading_arrows',
			array(
				'label' => esc_html__( 'Arrows', 'gsm-slider' ),
				'type'  => Controls_Manager::HEADING,
			)
		);

		$this->add_control(
			'arrow_color_normal',
			array(
				'label'     => esc_html__( 'Arrow Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn-prev, {{WRAPPER}} .gsm-btn-next' => '--gsm-arrow-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_color_hover',
			array(
				'label'     => esc_html__( 'Arrow Hover Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn-prev:hover, {{WRAPPER}} .gsm-btn-next:hover' => '--gsm-arrow-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_color_active',
			array(
				'label'     => esc_html__( 'Arrow Active Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn-prev:active, {{WRAPPER}} .gsm-btn-next:active' => '--gsm-arrow-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_position',
			array(
				'label'        => esc_html__( 'Arrow Position', 'gsm-slider' ),
				'type'         => Controls_Manager::SELECT,
				'default'      => 'inside',
				'options'      => array(
					'inside'  => esc_html__( 'Inside', 'gsm-slider' ),
					'outside' => esc_html__( 'Outside', 'gsm-slider' ),
					'bottom'  => esc_html__( 'Bottom', 'gsm-slider' ),
				),
				'prefix_class' => 'gsm-arrow-',
			)
		);

		$this->add_responsive_control(
			'arrow_size',
			array(
				'label'     => esc_html__( 'Arrow Size (px)', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 10, 'max' => 80 ) ),
				'default'   => array( 'size' => 20, 'unit' => 'px' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn-prev, {{WRAPPER}} .gsm-btn-next' => '--gsm-arrow-size: {{SIZE}}px;',
				),
			)
		);

		$this->add_control(
			'arrow_bg',
			array(
				'label'     => esc_html__( 'Arrow Background', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn-prev, {{WRAPPER}} .gsm-btn-next' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_bg_hover',
			array(
				'label'     => esc_html__( 'Arrow Hover Background', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn-prev:hover, {{WRAPPER}} .gsm-btn-next:hover' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'arrow_radius',
			array(
				'label'     => esc_html__( 'Arrow Border Radius (px)', 'gsm-slider' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 60 ) ),
				'default'   => array( 'size' => 50, 'unit' => 'px' ),
				'selectors' => array(
					'{{WRAPPER}} .gsm-btn-prev, {{WRAPPER}} .gsm-btn-next' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'arrow_icon_type',
			array(
				'label'   => esc_html__( 'Arrow Icon Style', 'gsm-slider' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'chevron',
				'options' => array(
					'chevron' => esc_html__( 'Chevron', 'gsm-slider' ),
					'angle'   => esc_html__( 'Angle', 'gsm-slider' ),
					'svg'     => esc_html__( 'Custom SVG', 'gsm-slider' ),
				),
			)
		);

		$this->add_control(
			'arrow_svg',
			array(
				'label'       => esc_html__( 'Custom SVG Code', 'gsm-slider' ),
				'type'        => Controls_Manager::TEXTAREA,
				'description' => esc_html__( 'Paste your SVG code here. The same SVG will be mirrored for both arrows.', 'gsm-slider' ),
				'condition'   => array( 'arrow_icon_type' => 'svg' ),
			)
		);

		// --- Pagination ---
		$this->add_control(
			'heading_pagination',
			array(
				'label'     => esc_html__( 'Pagination', 'gsm-slider' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);

		$this->add_control(
			'dot_color',
			array(
				'label'     => esc_html__( 'Dot Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .swiper-pagination-progressbar-fill' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .swiper-pagination-fraction' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'dot_active_color',
			array(
				'label'     => esc_html__( 'Active Dot Color', 'gsm-slider' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Normalize YouTube / Vimeo URLs to embed URLs with autoplay-friendly params.
	 *
	 * @param string $url User or embed URL.
	 * @return string Escaped embed URL or empty string.
	 */
	private function get_embed_url( $url ) {
		if ( empty( $url ) ) {
			return '';
		}
		$url = trim( $url );

		if ( preg_match( '/(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $matches ) ) {
			$video_id = $matches[1];
			$embed    = 'https://www.youtube.com/embed/' . rawurlencode( $video_id )
				. '?autoplay=1&mute=1&loop=1&playlist=' . rawurlencode( $video_id )
				. '&controls=0&playsinline=1&rel=0&modestbranding=1';
			return esc_url( $embed );
		}

		if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $url, $matches ) ) {
			$video_id = $matches[1];
			$embed    = 'https://player.vimeo.com/video/' . rawurlencode( $video_id )
				. '?autoplay=1&muted=1&loop=1&background=1&playsinline=1';
			return esc_url( $embed );
		}

		if ( strpos( $url, 'youtube.com/embed/' ) !== false ) {
			return esc_url( add_query_arg( array(
				'autoplay'    => '1',
				'mute'        => '1',
				'playsinline' => '1',
			), $url ) );
		}

		if ( strpos( $url, 'player.vimeo.com' ) !== false ) {
			return esc_url( add_query_arg( array(
				'autoplay'    => '1',
				'muted'       => '1',
				'playsinline' => '1',
			), $url ) );
		}

		return esc_url( $url );
	}

	/**
	 * Render arrow icon HTML based on selected icon type.
	 *
	 * @param string $direction  'prev' or 'next'.
	 * @param string $icon_type  'chevron', 'angle', or 'svg'.
	 * @param string $custom_svg Raw SVG markup for custom type.
	 * @return string
	 */
	private function render_arrow_icon( $direction, $icon_type, $custom_svg = '' ) {
		$is_prev  = ( 'prev' === $direction );
		$rotation = $is_prev ? '' : ' style="transform:scaleX(-1)"';

		if ( 'svg' === $icon_type && ! empty( $custom_svg ) ) {
			$allowed_svg = array(
				'svg'      => array( 'xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true ),
				'path'     => array( 'd' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true ),
				'circle'   => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true ),
				'rect'     => array( 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'fill' => true ),
				'line'     => array( 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true ),
				'polyline' => array( 'points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
			);
			return '<span' . $rotation . ' aria-hidden="true">' . wp_kses( $custom_svg, $allowed_svg ) . '</span>';
		}

		// Build the path `d` attribute value — note: these are SVG path commands,
		// so we must use <path d="..."> NOT <polyline points="..."> which only
		// accepts coordinate pairs like "x1,y1 x2,y2".
		if ( 'angle' === $icon_type ) {
			// Narrower angle bracket (←).
			$d = 'M15 18 L9 12 L15 6';
		} else {
			// Wider chevron bracket (←).
			$d = 'M14 7 L7 12 L14 17';
		}

		return '<svg' . $rotation . ' xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="' . esc_attr( $d ) . '"></path></svg>';
	}

	/**
	 * Output heading HTML with optional typing effect (single slide row).
	 *
	 * @param array  $slide        Slide settings.
	 * @param string $legacy_anim  Fallback animation slug.
	 */
	protected function render_slide_heading_element( $slide, $legacy_anim = 'fade-up' ) {
		$typing_on = ! empty( $slide['typing_effect'] ) && 'yes' === $slide['typing_effect'];
		if ( empty( $slide['heading'] ) && ! $typing_on ) {
			return;
		}
		$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'p', 'span' );
		$tag          = ! empty( $slide['heading_tag'] ) && in_array( $slide['heading_tag'], $allowed_tags, true )
			? $slide['heading_tag'] : 'h2';

		$anim_h  = $slide['anim_heading'] ?? $legacy_anim;
		$delay_h = absint( $slide['anim_heading_delay'] ?? 0 );

		if ( $typing_on ) {
			$strings = array();
			if ( ! empty( $slide['typing_strings'] ) ) {
				$lines = explode( "\n", $slide['typing_strings'] );
				foreach ( $lines as $line ) {
					$line = trim( $line );
					if ( $line ) {
						$strings[] = $line;
					}
				}
			}
			if ( empty( $strings ) && ! empty( $slide['heading'] ) ) {
				$strings[] = $slide['heading'];
			}
			if ( empty( $strings ) ) {
				return;
			}

			$typing_data = array(
				'strings'     => $strings,
				'typeSpeed'   => absint( $slide['typing_speed'] ?? 80 ),
				'deleteSpeed' => absint( $slide['typing_delete_speed'] ?? 40 ),
				'pauseTime'   => absint( $slide['typing_pause'] ?? 1800 ),
			);

			printf(
				'<%1$s class="gsm-anim gsm-anim--heading gsm-typing-wrap %2$s" data-gsm-delay="%3$s" data-gsm-exit="%4$s"><span class="gsm-typing-text" data-typing="%5$s"></span><span class="gsm-typing-cursor" aria-hidden="true">|</span></%1$s>',
				esc_attr( $tag ),
				esc_attr( $anim_h ),
				esc_attr( (string) absint( $delay_h ) ),
				esc_attr( $slide['exit_anim_heading'] ?? 'fade-out-up' ),
				esc_attr( wp_json_encode( $typing_data ) )
			);
			return;
		}

		echo '<' . esc_attr( $tag ) . ' class="gsm-anim gsm-anim--heading gsm-heading ' . esc_attr( $anim_h ) . '"';
		if ( 'none' !== $anim_h ) {
			echo ' data-gsm-delay="' . esc_attr( $delay_h ) . '"';
		}
		echo ' data-gsm-exit="' . esc_attr( $slide['exit_anim_heading'] ?? 'fade-out-up' ) . '">';
		echo esc_html( $slide['heading'] );
		echo '</' . esc_attr( $tag ) . '>';
	}

	/**
	 * Book showcase: left column content, right 2×2 book grid (bypasses normal slide inner).
	 *
	 * @param array  $slide        Slide settings.
	 * @param string $legacy_anim  Fallback animation slug.
	 */
	protected function render_book_showcase_layout( $slide, $legacy_anim = 'fade-up' ) {
		$ratio_str  = $slide['book_content_ratio'] ?? '55-45';
		$ratio_arr  = explode( '-', (string) $ratio_str );
		$left_w     = absint( $ratio_arr[0] ?? 55 );
		$right_w    = absint( $ratio_arr[1] ?? 45 );
		$show_title = ! empty( $slide['book_show_title'] ) && 'yes' === $slide['book_show_title'];
		$card_bg    = esc_attr( $slide['book_card_bg'] ?? 'rgba(255,255,255,0.08)' );
		$do_float   = ! empty( $slide['book_float_animation'] ) && 'yes' === $slide['book_float_animation'];

		$books = array();
		for ( $b = 1; $b <= 4; $b++ ) {
			$img_row = $slide[ 'book' . $b . '_image' ] ?? array();
			$url_row = $slide[ 'book' . $b . '_url' ] ?? array();
			$img_url = ! empty( $img_row['url'] ) ? esc_url( $img_row['url'] ) : '';
			if ( empty( $img_url ) ) {
				continue; // Skip slots without a cover image.
			}
			$lnk     = ! empty( $url_row['url'] ) ? esc_url( $url_row['url'] ) : '';
			$books[] = array(
				'image'        => $img_url,
				'title'        => isset( $slide[ 'book' . $b . '_title' ] ) ? $slide[ 'book' . $b . '_title' ] : '',
				'url'          => $lnk,
				'is_external'  => ! empty( $url_row['is_external'] ),
				'nofollow'     => ! empty( $url_row['nofollow'] ),
				'ribbon'       => isset( $slide[ 'book' . $b . '_ribbon' ] ) ? trim( (string) $slide[ 'book' . $b . '_ribbon' ] ) : '',
				'ribbon_color' => ! empty( $slide[ 'book' . $b . '_ribbon_color' ] ) ? $slide[ 'book' . $b . '_ribbon_color' ] : '#c8a800',
				'spine_text'   => isset( $slide[ 'book' . $b . '_spine_text' ] ) ? trim( (string) $slide[ 'book' . $b . '_spine_text' ] ) : '',
			);
		}

		$book_count = count( $books );

		// No books at all - expand left column to full width.
		if ( 0 === $book_count ) {
			$left_w  = 100;
			$right_w = 0;
		}

		// Grid CSS adapts to how many books are present.
		if ( 1 === $book_count ) {
			$single_width = $slide['book_single_width'] ?? 'normal';
			$width_map    = array(
				'compact' => '180px',
				'normal'  => '260px',
				'wide'    => '360px',
				'full'    => '100%',
			);
			$max_single   = $width_map[ $single_width ] ?? '260px';
			$grid_css = 'display:grid;grid-template-columns:1fr;gap:16px;width:100%;max-width:' . $max_single . ';margin:0 auto;';
		} elseif ( 2 === $book_count ) {
			$grid_css = 'display:grid;grid-template-columns:1fr 1fr;gap:16px;width:100%;';
		} else {
			// 3 or 4 books -> standard 2x2 grid.
			$grid_css = 'display:grid;grid-template-columns:1fr 1fr;gap:16px;width:100%;';
		}

		$v_align    = $slide['content_vertical'] ?? 'middle';
		$valign_map = array(
			'top'    => 'flex-start',
			'middle' => 'center',
			'bottom' => 'flex-end',
		);
		$valign_css = $valign_map[ $v_align ] ?? 'center';

		$content_max = $slide['content_max_width'] ?? array( 'size' => 560, 'unit' => 'px' );
		$cmw         = absint( $content_max['size'] ?? 560 );
		$cm_unit     = isset( $content_max['unit'] ) && in_array( $content_max['unit'], array( 'px', '%', 'em', 'rem', 'vw' ), true )
			? $content_max['unit'] : 'px';
		$max_width   = $cmw . $cm_unit;

		echo '<div class="gsm-book-layout">';
		echo '<div class="gsm-book-layout-inner">';

		$flex_left  = sprintf( '%d 1 0%%', $left_w );
		$flex_right = sprintf( '%d 1 0%%', $right_w );

		// Left: content.
		echo '<div class="gsm-book-content gsm-slide-content" style="flex:' . esc_attr( $flex_left ) . ';min-width:0;display:flex;align-items:' . esc_attr( $valign_css ) . ';">';
		echo '<div class="gsm-book-content-inner" style="max-width:' . esc_attr( $max_width ) . ';width:100%;">';

		if ( ! empty( $slide['badge_text'] ) ) {
			$badge_anim = $slide['anim_heading'] ?? $legacy_anim;
			$delay_bd   = absint( $slide['anim_badge_delay'] ?? 0 );
			echo '<span class="gsm-badge-inline gsm-anim ' . esc_attr( $badge_anim ) . '" data-gsm-delay="' . esc_attr( (string) $delay_bd ) . '" style="background:' . esc_attr( $slide['badge_color'] ?? '#6c63ff' ) . ';">' . esc_html( $slide['badge_text'] ) . '</span>';
		}

		$typing_on = ! empty( $slide['typing_effect'] ) && 'yes' === $slide['typing_effect'];
		$highlight = isset( $slide['book_heading_highlight'] ) ? trim( (string) $slide['book_heading_highlight'] ) : '';
		if ( ! $typing_on && $highlight !== '' && ! empty( $slide['heading'] ) && strpos( $slide['heading'], $highlight ) !== false ) {
			$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
			$htag         = ! empty( $slide['heading_tag'] ) && in_array( $slide['heading_tag'], $allowed_tags, true )
				? $slide['heading_tag'] : 'h2';
			$anim_hh      = $slide['anim_heading'] ?? $legacy_anim;
			$delay_hh     = absint( $slide['anim_heading_delay'] ?? 0 );
			$parts        = explode( $highlight, $slide['heading'], 2 );
			echo '<' . esc_attr( $htag ) . ' class="gsm-anim gsm-anim--heading gsm-heading gsm-book-heading ' . esc_attr( $anim_hh ) . '" data-gsm-delay="' . esc_attr( (string) $delay_hh ) . '" data-gsm-exit="' . esc_attr( $slide['exit_anim_heading'] ?? 'fade-out-up' ) . '" style="margin:0 0 16px;line-height:1.2;">';
			echo esc_html( $parts[0] );
			echo '<span class="gsm-book-heading-accent">' . esc_html( $highlight ) . '</span>';
			echo esc_html( $parts[1] );
			echo '</' . esc_attr( $htag ) . '>';
		} else {
			$this->render_slide_heading_element( $slide, $legacy_anim );
		}

		if ( ! empty( $slide['description'] ) ) {
			$anim_t = $slide['anim_text'] ?? $legacy_anim;
			$d_t    = absint( $slide['anim_text_delay'] ?? 150 );
			echo '<p class="gsm-anim gsm-anim--text ' . esc_attr( $anim_t ) . '" data-gsm-delay="' . esc_attr( (string) $d_t ) . '" data-gsm-exit="' . esc_attr( $slide['exit_anim_text'] ?? 'fade-out-up' ) . '" style="margin:0 0 24px;">' . esc_html( $slide['description'] ) . '</p>';
		}

		$btn2_row   = $slide['book_btn2_url'] ?? array();
		$has_btn1   = ! empty( $slide['btn_text'] ) && ! empty( $slide['btn_url']['url'] );
		$has_btn2   = ! empty( $slide['book_btn2_text'] ) && ! empty( $btn2_row['url'] );
		$anim_bk    = $slide['anim_btn'] ?? $legacy_anim;
		$d_bk       = absint( $slide['anim_btn_delay'] ?? 300 );
		$d_bk2      = $d_bk + 70;

		if ( $has_btn1 || $has_btn2 ) {
			echo '<div class="gsm-book-btn-row gsm-btn-wrap">';
			if ( $has_btn1 ) {
				$target_bk = ! empty( $slide['btn_url']['is_external'] ) ? '_blank' : '_self';
				$rel_bk    = ! empty( $slide['btn_url']['nofollow'] ) ? 'nofollow' : '';
				if ( '_blank' === $target_bk ) {
					$rel_bk = trim( $rel_bk . ' noopener noreferrer' );
				}
				echo '<a href="' . esc_url( $slide['btn_url']['url'] ) . '" class="gsm-btn gsm-book-btn-primary gsm-anim gsm-anim--btn ' . esc_attr( $anim_bk ) . '" data-gsm-delay="' . esc_attr( (string) $d_bk ) . '" data-gsm-exit="' . esc_attr( $slide['exit_anim_btn'] ?? 'fade-out-up' ) . '" target="' . esc_attr( $target_bk ) . '"' . ( $rel_bk ? ' rel="' . esc_attr( $rel_bk ) . '"' : '' ) . '>' . esc_html( $slide['btn_text'] ) . '</a>';
			}
			if ( $has_btn2 ) {
				$t2 = ! empty( $btn2_row['is_external'] ) ? '_blank' : '_self';
				$r2 = ! empty( $btn2_row['nofollow'] ) ? 'nofollow' : '';
				if ( '_blank' === $t2 ) {
					$r2 = trim( $r2 . ' noopener noreferrer' );
				}
				echo '<a href="' . esc_url( $btn2_row['url'] ) . '" class="gsm-btn gsm-btn--ghost gsm-book-btn-secondary gsm-anim gsm-anim--btn ' . esc_attr( $anim_bk ) . '" data-gsm-delay="' . esc_attr( (string) $d_bk2 ) . '" data-gsm-exit="' . esc_attr( $slide['exit_anim_btn'] ?? 'fade-out-up' ) . '" target="' . esc_attr( $t2 ) . '"' . ( $r2 ? ' rel="' . esc_attr( $r2 ) . '"' : '' ) . '>' . esc_html( $slide['book_btn2_text'] ) . '</a>';
			}
			echo '</div>';
		}

		echo '</div>';
		echo '</div>';

		// Right: book grid (only rendered when books exist).
		if ( $book_count > 0 ) {
			// padding-top/bottom ensures images never sit flush against the slider edge
			// in full-width (stretched) Elementor sections.
			echo '<div class="gsm-book-grid-wrap" style="flex:' . esc_attr( $flex_right ) . ';min-width:0;display:flex;align-items:center;justify-content:center;padding:32px 24px 32px 16px;box-sizing:border-box;">';
			echo '<div class="gsm-book-grid" style="' . esc_attr( $grid_css ) . '">';

			foreach ( $books as $bi => $book ) {
				$delay      = 100 + ( $bi * 120 );
				$float_cls  = $do_float ? ' gsm-book-float gsm-book-float--' . ( 0 === ( $bi % 2 ) ? 'a' : 'b' ) : '';
				$tilt_class = ' gsm-book-card--tilt-' . absint( $bi );

				echo '<div class="gsm-book-card gsm-anim fade-up' . esc_attr( $tilt_class ) . '" data-gsm-delay="' . esc_attr( (string) absint( $delay ) ) . '" data-book-index="' . esc_attr( (string) absint( $bi ) ) . '" style="background:' . $card_bg . ';border-radius:10px;padding:8px;box-sizing:border-box;">';

				$has_link    = ! empty( $book['url'] );
				$inner_tag   = $has_link ? 'a' : 'div';
				$inner_href  = $has_link ? ' href="' . $book['url'] . '"' : '';
				$target_book = $has_link && ! empty( $book['is_external'] ) ? ' target="_blank"' : '';
				$rel_book    = '';
				if ( $has_link ) {
					if ( ! empty( $book['nofollow'] ) ) {
						$rel_book = ' rel="nofollow' . ( ! empty( $book['is_external'] ) ? ' noopener noreferrer' : '' ) . '"';
					} elseif ( ! empty( $book['is_external'] ) ) {
						$rel_book = ' rel="noopener noreferrer"';
					}
				}

				echo '<' . $inner_tag . ' class="gsm-book-inner' . esc_attr( $float_cls ) . '"' . $inner_href . $target_book . $rel_book . '>';

				echo '<div class="gsm-book-cover-wrap">';
				echo '<img class="gsm-book-cover" src="' . esc_url( $book['image'] ) . '" alt="' . esc_attr( $book['title'] ) . '" loading="lazy">';
				// Spine - shows decorative strip; optional text runs vertically.
				$spine_inner = ! empty( $book['spine_text'] )
					? '<span class="gsm-book-spine-text">' . esc_html( $book['spine_text'] ) . '</span>'
					: '';
				echo '<div class="gsm-book-spine" aria-hidden="true">' . $spine_inner . '</div>';
				echo '<div class="gsm-book-shine" aria-hidden="true"></div>';
				// Ribbon badge (top-right corner).
				if ( ! empty( $book['ribbon'] ) ) {
					echo '<div class="gsm-book-ribbon" style="background:' . esc_attr( $book['ribbon_color'] ) . ';">' . esc_html( $book['ribbon'] ) . '</div>';
				}
				echo '</div>';

				if ( $show_title && ! empty( $book['title'] ) ) {
					echo '<div class="gsm-book-title">' . esc_html( $book['title'] ) . '</div>';
				}

				echo '</' . $inner_tag . '>';
				echo '</div>';
			}

			echo '</div>'; // .gsm-book-grid
			echo '</div>'; // .gsm-book-grid-wrap
		}

		echo '</div>'; // .gsm-book-layout-inner
		echo '</div>'; // .gsm-book-layout
	}

	/**
	 * Render the widget output on the frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		$s        = $settings;

		// ── Dynamic Posts Mode ──────────────────────────────────────────────
		// When content_source is 'posts', run a WP_Query and synthesise
		// a slides_data array that the rest of render() can use normally.
		if ( ! empty( $settings['content_source'] ) && 'posts' === $settings['content_source'] ) {
			$allowed_order = array( 'ASC', 'DESC' );
			$query_args    = array(
				'post_type'      => sanitize_key( $settings['query_post_type'] ?? 'post' ),
				'posts_per_page' => absint( $settings['query_posts_per_page'] ?? 5 ),
				'orderby'        => sanitize_key( $settings['query_orderby'] ?? 'date' ),
				'order'          => in_array( $settings['query_order'] ?? 'DESC', $allowed_order, true )
										? $settings['query_order']
										: 'DESC',
				'no_found_rows'  => true,
				'post_status'    => 'publish',
			);

			if ( ! empty( $settings['query_category'] ) && 'post' === ( $settings['query_post_type'] ?? 'post' ) ) {
				$query_args['category__in'] = $settings['query_category'];
			}

			if ( ! empty( $settings['query_include'] ) ) {
				$query_args['post__in'] = array_map( 'absint', explode( ',', $settings['query_include'] ) );
			}
			if ( ! empty( $settings['query_exclude'] ) ) {
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Optional editor exclude list; combined with low posts_per_page.
				$query_args['post__not_in'] = array_map( 'absint', explode( ',', $settings['query_exclude'] ) );
			}

			$query            = new \WP_Query( $query_args );
			$generated_slides = array();

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$thumb_id           = (int) get_post_thumbnail_id();
					$thumb_url          = $thumb_id ? (string) wp_get_attachment_image_url( $thumb_id, 'full' ) : '';
					$generated_slides[] = array(
						'media_type'           => 'image',
						'bg_image'             => array(
							'url' => $thumb_url ?: '',
							'id'  => $thumb_id,
						),
						'image_fit'            => 'cover',
						'image_position'       => 'center center',
						'image_effect'         => $settings['query_image_effect'] ?? 'none',
						'overlay_color'        => $settings['query_overlay_color'] ?? 'rgba(0,0,0,0.45)',
						'heading'              => get_the_title(),
						'description'          => wp_trim_words( get_the_excerpt(), 18, '...' ),
						'animation'            => $settings['query_animation'] ?? 'fade-up',
						'btn_text'             => $settings['query_btn_text'] ?? __( 'Read More', 'gsm-slider' ),
						'btn_url'              => array( 'url' => get_permalink(), 'is_external' => false ),
						'content_align'        => 'left',
						'content_vertical'     => 'bottom',
						'content_box_enable'   => 'yes',
						'content_max_width'    => array( 'size' => 600, 'unit' => 'px' ),
						'cinematic_effect'     => '',
						'_id'                  => 'post-' . get_the_ID(),
					);
				}
				wp_reset_postdata();
			}

			$settings['slides_data'] = $generated_slides;
		} elseif ( ! empty( $settings['content_source'] ) && 'products' === $settings['content_source'] && class_exists( 'WooCommerce' ) ) {
			// ── WooCommerce Products Mode ─────────────────────────────────────
			$woo_args = array(
				'post_type'      => 'product',
				'posts_per_page' => absint( $settings['woo_products_per_page'] ?? 5 ),
				'post_status'    => 'publish',
				'no_found_rows'  => true,
			);

			if ( ! empty( $settings['woo_category'] ) ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- WooCommerce product category filter selected in widget settings.
				$woo_args['tax_query'] = array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => $settings['woo_category'],
					),
				);
			}

			$woo_include = array();
			if ( ! empty( $settings['woo_include'] ) ) {
				$woo_include = array_map( 'absint', explode( ',', $settings['woo_include'] ) );
			}
			$woo_exclude = array();
			if ( ! empty( $settings['woo_exclude'] ) ) {
				$woo_exclude = array_map( 'absint', explode( ',', $settings['woo_exclude'] ) );
			}

			if ( ! empty( $settings['woo_show_only_sale'] ) && 'yes' === $settings['woo_show_only_sale'] ) {
				$sale_ids = array_merge( array( 0 ), wc_get_product_ids_on_sale() );
				if ( ! empty( $woo_include ) ) {
					$woo_args['post__in'] = array_intersect( $sale_ids, $woo_include );
				} else {
					$woo_args['post__in'] = $sale_ids;
				}
			} elseif ( ! empty( $woo_include ) ) {
				$woo_args['post__in'] = $woo_include;
			}

			if ( ! empty( $woo_exclude ) ) {
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- Optional product exclude IDs from widget settings.
				$woo_args['post__not_in'] = $woo_exclude;
			}

			$woo_orderby = sanitize_key( $settings['woo_orderby'] ?? 'date' );
			// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Standard WooCommerce sort keys (price, sales, rating).
			switch ( $woo_orderby ) {
				case 'price':
					$woo_args['meta_key'] = '_price';
					$woo_args['orderby']  = 'meta_value_num';
					break;
				case 'popularity':
					$woo_args['meta_key'] = 'total_sales';
					$woo_args['orderby']  = 'meta_value_num';
					break;
				case 'rating':
					$woo_args['meta_key'] = '_wc_average_rating';
					$woo_args['orderby']  = 'meta_value_num';
					break;
				default:
					$woo_args['orderby'] = $woo_orderby;
			}
			// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_key

			$woo_query        = new \WP_Query( $woo_args );
			$generated_slides = array();

			if ( $woo_query->have_posts() ) {
				while ( $woo_query->have_posts() ) {
					$woo_query->the_post();
					$product = wc_get_product( get_the_ID() );
					if ( ! $product ) {
						continue;
					}

					$feat_img_id = (int) get_post_thumbnail_id();
					$img_url     = $feat_img_id
						? (string) wp_get_attachment_image_url( $feat_img_id, 'full' )
						: ( (string) get_the_post_thumbnail_url( get_the_ID(), 'full' ) ?: wc_placeholder_img_src( 'full' ) );
					$is_on_sale = $product->is_on_sale();
					$price_html = $product->get_price_html();

					// Button URL / text.
					if ( 'cart' === ( $settings['woo_btn_type'] ?? 'view' ) && $product->is_purchasable() && $product->is_in_stock() ) {
						$woo_btn_text = esc_html__( 'Add to Cart', 'gsm-slider' );
						$woo_btn_url  = $product->add_to_cart_url();
					} else {
						$woo_btn_text = esc_html__( 'View Product', 'gsm-slider' );
						$woo_btn_url  = get_permalink();
					}

					// Price as plain-text description.
					$woo_description = '';
					if ( ! empty( $settings['woo_show_price'] ) && 'yes' === $settings['woo_show_price'] ) {
						$woo_description = wp_strip_all_tags( $price_html );
					}

					$generated_slides[] = array(
						'media_type'           => 'image',
						'bg_image'             => array(
							'url' => $img_url,
							'id'  => $feat_img_id,
						),
						'image_fit'            => 'cover',
						'image_position'       => 'center center',
						'image_effect'         => $settings['woo_image_effect'] ?? 'zoom',
						'overlay_color'        => $settings['woo_overlay_color'] ?? 'rgba(0,0,0,0.35)',
						'heading'              => get_the_title(),
						'description'          => $woo_description,
						'animation'            => 'fade-up',
						'btn_text'             => $woo_btn_text,
						'btn_url'              => array( 'url' => $woo_btn_url, 'is_external' => false ),
						'content_align'        => 'center',
						'content_vertical'     => 'middle',
						'content_box_enable'   => 'yes',
						'content_max_width'    => array( 'size' => 480, 'unit' => 'px' ),
						'cinematic_effect'     => '',
						'_is_on_sale'          => $is_on_sale,
						'_id'                  => 'product-' . get_the_ID(),
					);
				}
				wp_reset_postdata();
			}

			$settings['slides_data'] = $generated_slides;
		}
		// ── End Dynamic Posts Mode ───────────────────────────────────────────

		if ( empty( $settings['slides_data'] ) ) {
			return;
		}

		$slides        = $settings['slides_data'];
		$total_slides  = count( $slides );

		$lqip_map = array();
		foreach ( $slides as $index => $slide_row ) {
			if ( empty( $slide_row['bg_image']['id'] ) ) {
				continue;
			}
			if ( isset( $slide_row['media_type'] ) && 'image' !== $slide_row['media_type'] ) {
				continue;
			}
			$img_id = absint( $slide_row['bg_image']['id'] );
			if ( ! $img_id ) {
				continue;
			}
			$tiny = wp_get_attachment_image_src( $img_id, array( 40, 30 ) );
			if ( $tiny ) {
				$lqip_map[ $index ] = $tiny[0];
			}
		}

		$gsm_preload_first_url = '';
		foreach ( $slides as $slide_row ) {
			$is_image = ! isset( $slide_row['media_type'] ) || 'image' === $slide_row['media_type'];
			if ( $is_image && ! empty( $slide_row['bg_image']['url'] ) ) {
				$gsm_preload_first_url = $slide_row['bg_image']['url'];
				break;
			}
		}
		if ( $gsm_preload_first_url ) {
			$preload_href = $gsm_preload_first_url;
			if ( ! did_action( 'wp_head' ) ) {
				add_action(
					'wp_head',
					static function () use ( $preload_href ) {
						echo '<link rel="preload" as="image" href="' . esc_url( $preload_href ) . '" fetchpriority="high">' . "\n";
					},
					1
				);
			}
		}

		$pagination    = $settings['pagination_type'];
		$show_arrows   = ( 'yes' === $settings['arrows'] );
		$thumb_nav     = ( 'yes' === $settings['thumbnail_nav'] );
		$icon_type     = $settings['arrow_icon_type'];
		$custom_svg    = isset( $settings['arrow_svg'] ) ? $settings['arrow_svg'] : '';
		$widget_id     = $this->get_id();

		// Build JS settings (only what JS needs — no full slide data).
		$transition_speed = absint( $s['speed'] ?? 900 );
		if ( $transition_speed < 100 ) {
			$transition_speed = 900;
		}
		$autoplay_delay = absint( $s['delay'] ?? 5000 );
		if ( $autoplay_delay < 500 ) {
			$autoplay_delay = 5000;
		}

		$js_settings = array(
			'loop'              => ( $s['loop'] ?? '' ) === 'yes',
			'speed'             => $transition_speed,
			'effect'            => sanitize_key( $s['effect'] ?? 'fade' ),
			'coverflow_rotate'  => absint( $s['coverflow_rotate']['size'] ?? 30 ),
			'coverflow_depth'   => absint( $s['coverflow_depth']['size'] ?? 100 ),
			'autoplay'          => ( $s['autoplay'] ?? '' ) === 'yes',
			'delay'             => $autoplay_delay,
			'arrows'            => ( $s['arrows'] ?? '' ) === 'yes',
			'pagination_type'   => sanitize_key( $s['pagination_type'] ?? 'bullets' ),
			'thumbnail_nav'     => ( $s['thumbnail_nav'] ?? '' ) === 'yes',
			'thumb_gap'         => absint( $s['thumb_gap']['size'] ?? 6 ),
			'mobile_arrows'     => ( $s['mobile_arrows'] ?? '' ) === 'yes',
			'scroll_trigger'            => ( $s['scroll_trigger'] ?? '' ) === 'yes',
			'mousewheel_enable'         => ( $s['mousewheel_enable'] ?? '' ) === 'yes',
			'mousewheel_sensitivity'    => floatval( $s['mousewheel_sensitivity']['size'] ?? 1 ),
			'mousewheel_indicator'      => ( $s['mousewheel_indicator'] ?? '' ) === 'yes',
			'mousewheel_indicator_text' => isset( $s['mousewheel_indicator_text'] ) ? sanitize_text_field( $s['mousewheel_indicator_text'] ) : __( 'Scroll to navigate', 'gsm-slider' ),
			'mousewheel_progress'       => ( $s['mousewheel_progress'] ?? '' ) === 'yes',
			'tilt_effect'               => ( $s['tilt_effect'] ?? '' ) === 'yes',
			'tilt_intensity'    => absint( $s['tilt_intensity']['size'] ?? 8 ),
			'tilt_glare'        => ( $s['tilt_glare'] ?? '' ) === 'yes',
		);

		// Wrapper classes.
		$wrapper_classes = array( 'gsm-slider-wrapper' );
		if ( $thumb_nav ) {
			$wrapper_classes[] = 'gsm-has-thumbs';
		}

		$gsm_mousewheel_on = ! empty( $s['mousewheel_enable'] ) && 'yes' === $s['mousewheel_enable'];

		?>
		<div
			class="<?php echo esc_attr( implode( ' ', $wrapper_classes ) ); ?>"
			data-a11y-slide-template="<?php echo esc_attr( __( 'Slide {current} of {total}', 'gsm-slider' ) ); ?>"
		>
			<?php
			if ( ! empty( $gsm_preload_first_url ) && did_action( 'wp_head' ) && ! is_admin() ) :
				printf(
					'<link rel="preload" as="image" href="%s" fetchpriority="high">%s',
					esc_url( $gsm_preload_first_url ),
					"\n"
				);
			endif;
			?>
			<?php if ( $gsm_mousewheel_on ) : ?>
			<div class="gsm-slider-main gsm-slider-main--mousewheel">
			<?php endif; ?>
			<div
				class="swiper gsm-slider"
				id="gsm-slider-<?php echo esc_attr( $widget_id ); ?>"
				role="region"
				aria-roledescription="carousel"
				aria-label="<?php esc_attr_e( 'Image slider', 'gsm-slider' ); ?>"
				tabindex="0"
				data-settings='<?php echo esc_attr( wp_json_encode( $js_settings ) ); ?>'
			>
				<div class="swiper-wrapper">
					<?php foreach ( $slides as $index => $slide ) :
						$slide_num    = $index + 1;
						$media_type   = isset( $slide['media_type'] ) ? esc_attr( $slide['media_type'] ) : 'image';
						$image_effect = isset( $slide['image_effect'] ) ? esc_attr( $slide['image_effect'] ) : 'none';
						$cinematic    = ( isset( $slide['cinematic_effect'] ) && 'yes' === $slide['cinematic_effect'] );
						$vert_align   = isset( $slide['content_vertical'] ) ? $slide['content_vertical'] : 'middle';
						$content_box  = ( isset( $slide['content_box_enable'] ) && 'yes' === $slide['content_box_enable'] );
						$glass        = ( isset( $settings['content_glass_effect'] ) && 'yes' === $settings['content_glass_effect'] );
						$hover_lift   = ( isset( $settings['content_hover_effect'] ) && 'yes' === $settings['content_hover_effect'] );
						$legacy_anim     = isset( $slide['animation'] ) ? $slide['animation'] : 'fade-up';
						$overlay_type  = isset( $slide['overlay_type'] ) ? esc_attr( $slide['overlay_type'] ) : 'solid';
						$overlay_style = '';

						if ( 'gradient' === $overlay_type ) {
							$overlay_gradient_direction = isset( $slide['overlay_gradient_direction'] ) ? esc_attr( $slide['overlay_gradient_direction'] ) : 'to top';
							$overlay_gradient_start     = isset( $slide['overlay_gradient_start'] ) ? esc_attr( $slide['overlay_gradient_start'] ) : 'rgba(0,0,0,0.7)';
							$overlay_gradient_end       = isset( $slide['overlay_gradient_end'] ) ? esc_attr( $slide['overlay_gradient_end'] ) : 'rgba(0,0,0,0)';
							$overlay_style              = 'background-image: linear-gradient(' . $overlay_gradient_direction . ',' . $overlay_gradient_start . ',' . $overlay_gradient_end . ');';
						} elseif ( 'none' !== $overlay_type ) {
							$overlay_color  = isset( $slide['overlay_color'] ) ? esc_attr( $slide['overlay_color'] ) : 'rgba(0,0,0,0.35)';
							$overlay_style  = 'background-color:' . $overlay_color . ';';
						}

						// Slide CSS classes.
						$slide_classes = array( 'swiper-slide', 'gsm-slide', 'gsm-valign-' . esc_attr( $vert_align ) );
						if ( $cinematic ) {
							$slide_classes[] = 'gsm-cinematic';
						}
						if ( 'image' === $media_type && 'zoom' === $image_effect ) {
							$slide_classes[] = 'gsm-effect-zoom';
						}
						if ( 'image' === $media_type && 'parallax' === $image_effect ) {
							$slide_classes[] = 'gsm-effect-parallax';
						}
						if ( 'image' === $media_type && 'kenburns' === $image_effect ) {
							$slide_classes[] = 'gsm-effect-kenburns';
						}
						if ( 'video' === $media_type && isset( $slide['cinematic_video_intro'] ) && 'yes' === $slide['cinematic_video_intro'] ) {
							$slide_classes[] = 'gsm-video-cinematic-intro';
						}

						// BG image fit/position (applied after LQIP/full load in JS).
						$image_fit      = isset( $slide['image_fit'] ) ? $slide['image_fit'] : 'cover';
						$image_position = isset( $slide['image_position'] ) ? $slide['image_position'] : 'center center';

						// Per-slide content alignment.
						$slide_align = isset( $slide['content_align'] ) ? esc_attr( $slide['content_align'] ) : 'inherit';
						if ( 'inherit' !== $slide_align ) {
							$slide_classes[] = 'gsm-align-' . esc_attr( $slide_align );
						}

						if ( ! empty( $slide['custom_css_class'] ) ) {
							$custom_bits = preg_split( '/\s+/', trim( $slide['custom_css_class'] ) );
							foreach ( $custom_bits as $bit ) {
								if ( '' !== $bit ) {
									$slide_classes[] = sanitize_html_class( $bit );
								}
							}
						}

						if ( ! empty( $slide['book_layout_enable'] ) && 'yes' === $slide['book_layout_enable'] ) {
							$slide_classes[] = 'gsm-slide--book-layout';
						}

						// Content box classes.
						$content_classes = array( 'gsm-slide-content' );
						if ( $content_box && $glass ) {
							$content_classes[] = 'gsm-glass';
						}
						if ( $content_box && $hover_lift ) {
							$content_classes[] = 'gsm-content-hover';
						}
						if ( ! $content_box ) {
							$content_classes[] = 'gsm-no-box';
						}
					?>
					<div
						class="<?php echo esc_attr( implode( ' ', $slide_classes ) ); ?> elementor-repeater-item-<?php echo esc_attr( isset( $slide['_id'] ) ? $slide['_id'] : 'gsm-' . $index ); ?>"
						role="group"
						aria-roledescription="slide"
						aria-label="<?php echo esc_attr( sprintf( /* translators: 1: current slide number  2: total slides */ __( 'Slide %1$d of %2$d', 'gsm-slider' ), $slide_num, $total_slides ) ); ?>"
					>
						<?php if ( 'video' === $media_type ) : ?>
							<?php
							$video_source_slide = $slide['video_source'] ?? 'self';
							$poster_url_slide   = isset( $slide['video_poster']['url'] ) ? esc_url( $slide['video_poster']['url'] ) : '';
							$preload_attr       = ( 0 === (int) $index ) ? 'auto' : 'metadata';
							$video_title        = ! empty( $slide['heading'] ) ? $slide['heading'] : __( 'Video', 'gsm-slider' );
							$mobile_optim       = isset( $slide['video_mobile_optim'] ) ? $slide['video_mobile_optim'] : 'yes';
							$src_attr           = ( 'yes' === $mobile_optim ) ? 'data-src' : 'src';
							?>
							<?php if ( 'self' === $video_source_slide && ! empty( $slide['video_url'] ) ) : ?>
								<div class="gsm-video-wrap gsm-video-self" data-mobile-opt="<?php echo esc_attr( $mobile_optim ); ?>">
									<video
										class="gsm-video"
										autoplay
										muted
										loop
										playsinline
										disablepictureinpicture
										preload="<?php echo esc_attr( $preload_attr ); ?>"
										<?php if ( $poster_url_slide ) : ?>
										poster="<?php echo esc_url( $poster_url_slide ); ?>"
										<?php endif; ?>
									>
										<source <?php echo esc_attr( $src_attr ); ?>="<?php echo esc_url( $slide['video_url'] ); ?>" type="video/mp4" />
									</video>
									<button class="gsm-video-toggle" type="button" aria-label="<?php esc_attr_e( 'Pause video', 'gsm-slider' ); ?>">
										<span class="gsm-video-toggle-icon" aria-hidden="true">&#9646;&#9646;</span>
									</button>
									<button class="gsm-video-mute" type="button" aria-label="<?php esc_attr_e( 'Mute video', 'gsm-slider' ); ?>">
										<span class="gsm-video-mute-icon" aria-hidden="true">&#128266;</span>
									</button>
								</div>
							<?php elseif ( 'embed' === $video_source_slide && ! empty( $slide['video_embed_url'] ) ) : ?>
								<?php
								$embed_src = $this->get_embed_url( $slide['video_embed_url'] );
								?>
								<?php if ( $embed_src ) : ?>
								<div class="gsm-video-wrap gsm-video-embed" data-mobile-opt="<?php echo esc_attr( $mobile_optim ); ?>">
									<iframe
										<?php echo esc_attr( $src_attr ); ?>="<?php echo esc_url( $embed_src ); ?>"
										allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
										allowfullscreen
										loading="lazy"
										title="<?php echo esc_attr( $video_title ); ?>"
									></iframe>
								</div>
								<?php endif; ?>
							<?php else : ?>
								<?php if ( $poster_url_slide ) : ?>
								<?php
								$poster_inline_style = 'background-image:url(' . esc_url_raw( $poster_url_slide ) . ');background-size:cover;background-position:center center;';
								?>
								<div
									class="gsm-bg gsm-video-fallback-bg"
									data-bg="<?php echo esc_url( $poster_url_slide ); ?>"
									data-fit="cover"
									data-position="center center"
									<?php if ( 0 === (int) $index ) : ?>
									data-fetchpriority="high"
									<?php endif; ?>
									style="<?php echo esc_attr( $poster_inline_style ); ?>"
									role="img"
									aria-label="<?php echo esc_attr( $video_title ); ?>"
								></div>
								<?php endif; ?>
							<?php endif; ?>
						<?php else : ?>
							<?php
							$is_split_layout = ! empty( $slide['split_screen'] ) && 'yes' === $slide['split_screen'];
							?>
							<?php if ( ! $is_split_layout && ! empty( $slide['bg_image']['url'] ) ) : ?>
								<?php
								$lqip_url = isset( $lqip_map[ $index ] ) ? $lqip_map[ $index ] : '';
								// Render an immediate background to avoid the initial black flash before JS lazy loader runs.
								$initial_bg_url   = $lqip_url ? $lqip_url : ( ( 0 === (int) $index ) ? $slide['bg_image']['url'] : '' );
								$initial_bg_style = '';
								if ( $initial_bg_url ) {
									$initial_bg_style = 'background-image:url(' . esc_url_raw( $initial_bg_url ) . ');background-size:' . $image_fit . ';background-position:' . $image_position . ';';
								}
								?>
								<div
									class="gsm-bg"
									data-bg="<?php echo esc_url( $slide['bg_image']['url'] ); ?>"
									<?php if ( $lqip_url ) : ?>
									data-lqip="<?php echo esc_url( $lqip_url ); ?>"
									<?php endif; ?>
									data-fit="<?php echo esc_attr( $image_fit ); ?>"
									data-position="<?php echo esc_attr( $image_position ); ?>"
									<?php if ( 0 === (int) $index && 'image' === $media_type ) : ?>
									data-fetchpriority="high"
									<?php endif; ?>
									<?php if ( $initial_bg_style ) : ?>
									style="<?php echo esc_attr( $initial_bg_style ); ?>"
									<?php endif; ?>
									role="img"
									aria-label="<?php echo esc_attr( $slide['heading'] ?? '' ); ?>"
								></div>
							<?php endif; ?>
						<?php endif; ?>

						<?php if ( 'none' !== $overlay_type ) : ?>
							<div class="gsm-slide-overlay" style="<?php echo esc_attr( $overlay_style ); ?>"></div>
						<?php endif; ?>

						<?php if ( ! empty( $slide['particle_enable'] ) && 'yes' === $slide['particle_enable'] ) : ?>
							<?php
							$preset = sanitize_key( $slide['particle_preset'] ?? 'snow' );

							$presets = array(
								'snow'     => array(
									'color'   => '#ffffff',
									'color2'  => '',
									'count'   => 80,
									'size'    => 3,
									'speed'   => 1.5,
									'shape'   => 'circle',
									'opacity' => 0.7,
									'connect' => false,
									'drift'   => true,
									'glitter' => false,
								),
								'glitter'  => array(
									'color'    => '#ffd700',
									'color2'   => '#ffec8b',
									'count'    => 100,
									'size'     => 4,
									'speed'    => 2,
									'shape'    => 'star',
									'opacity'  => 0.9,
									'connect'  => false,
									'drift'    => true,
									'glitter'  => true,
								),
								'bubbles'  => array(
									'color'    => 'rgba(255,255,255,0.3)',
									'color2'   => 'rgba(255,255,255,0.15)',
									'count'    => 40,
									'size'     => 12,
									'speed'    => 1,
									'shape'    => 'circle',
									'opacity'  => 0.5,
									'connect'  => false,
									'drift'    => true,
									'glitter'  => false,
									'bubble'   => true,
								),
								'stars'    => array(
									'color'    => '#ffffff',
									'color2'   => '#b3d4ff',
									'count'    => 120,
									'size'     => 2,
									'speed'    => 0.5,
									'shape'    => 'circle',
									'opacity'  => 0.8,
									'connect'  => true,
									'drift'    => false,
									'glitter'  => true,
								),
								'confetti' => array(
									'color'      => '#ff6b6b',
									'color2'     => '#ffd93d',
									'count'      => 80,
									'size'       => 6,
									'speed'      => 3,
									'shape'      => 'square',
									'opacity'    => 0.85,
									'connect'    => false,
									'drift'      => true,
									'glitter'    => false,
									'rotate'     => true,
									'multicolor' => true,
								),
								'custom'   => array(
									'color'      => esc_attr( $slide['particle_color'] ?? '#ffffff' ),
									'color2'     => esc_attr( $slide['particle_color2'] ?? '' ),
									'count'      => absint( $slide['particle_count']['size'] ?? 60 ),
									'size'       => absint( $slide['particle_size']['size'] ?? 4 ),
									'speed'      => floatval( $slide['particle_speed']['size'] ?? 3 ),
									'shape'      => sanitize_key( $slide['particle_shape'] ?? 'circle' ),
									'opacity'    => absint( $slide['particle_opacity']['size'] ?? 70 ) / 100,
									'connect'    => ! empty( $slide['particle_connect'] ) && 'yes' === $slide['particle_connect'],
									'drift'      => true,
									'glitter'    => false,
								),
							);

							$config = isset( $presets[ $preset ] ) ? $presets[ $preset ] : $presets['snow'];
							?>
							<canvas class="gsm-particles-canvas" data-particles='<?php echo esc_attr( wp_json_encode( $config ) ); ?>'></canvas>
						<?php endif; ?>

						<?php
						if ( ! empty( $slide['book_layout_enable'] ) && 'yes' === $slide['book_layout_enable'] ) {
							$this->render_book_showcase_layout( $slide, $legacy_anim );
							continue;
						}
						?>

						<?php
						if ( ! empty( $slide['split_screen'] ) && 'yes' === $slide['split_screen'] ) {
							$ratio    = $slide['split_ratio'] ?? '50-50';
							$parts    = explode( '-', $ratio );
							$img_w    = absint( $parts[0] ?? 50 );
							$cont_w   = absint( $parts[1] ?? 50 );
							$reverse  = ! empty( $slide['split_reverse'] ) && 'yes' === $slide['split_reverse'];
							$diagonal = ! empty( $slide['split_diagonal'] ) && 'yes' === $slide['split_diagonal'];

							echo '<div class="gsm-split' . ( $reverse ? ' gsm-split--reverse' : '' ) . ( $diagonal ? ' gsm-split--diagonal' : '' ) . '">';

							echo '<div class="gsm-split__image" style="flex:' . esc_attr( (string) $img_w ) . ';">';
							echo '<div class="gsm-split__img-inner" style="background-image:url(' . esc_url( isset( $slide['bg_image']['url'] ) ? $slide['bg_image']['url'] : '' ) . ');background-size:cover;background-position:' . esc_attr( $slide['image_position'] ?? 'center center' ) . ';"></div>';
							echo '</div>';

							echo '<div class="gsm-split__content" style="flex:' . esc_attr( (string) $cont_w ) . '; background:' . esc_attr( $slide['split_content_bg'] ?? '#1a1a2e' ) . ';">';
							echo '<div class="gsm-split__content-inner">';

							if ( ! empty( $slide['badge_text'] ) ) {
								echo '<span class="gsm-badge-inline gsm-anim ' . esc_attr( $slide['anim_heading'] ?? $legacy_anim ) . '" data-gsm-delay="' . absint( $slide['anim_badge_delay'] ?? 0 ) . '" style="background:' . esc_attr( $slide['badge_color'] ?? '#6c63ff' ) . ';">' . esc_html( $slide['badge_text'] ) . '</span>';
							}

							$this->render_slide_heading_element( $slide, $legacy_anim );

							if ( ! empty( $slide['description'] ) ) {
								$anim_t_split = $slide['anim_text'] ?? $legacy_anim;
								$delay_t_sp   = absint( $slide['anim_text_delay'] ?? 150 );
								echo '<p class="gsm-anim gsm-anim--text ' . esc_attr( $anim_t_split ) . '" data-gsm-delay="' . esc_attr( $delay_t_sp ) . '" data-gsm-exit="' . esc_attr( $slide['exit_anim_text'] ?? 'fade-out-up' ) . '">' . esc_html( $slide['description'] ) . '</p>';
							}

							if ( ! empty( $slide['btn_text'] ) && ! empty( $slide['btn_url']['url'] ) ) {
								$target_sp = ! empty( $slide['btn_url']['is_external'] ) ? '_blank' : '_self';
								$rel_sp    = ! empty( $slide['btn_url']['nofollow'] ) ? 'nofollow' : '';
								$anim_b_sp = $slide['anim_btn'] ?? $legacy_anim;
								$delay_b_sp = absint( $slide['anim_btn_delay'] ?? 300 );
								echo '<div class="gsm-btn-wrap">';
								echo '<a href="' . esc_url( $slide['btn_url']['url'] ) . '" class="gsm-btn gsm-anim gsm-anim--btn ' . esc_attr( $anim_b_sp ) . '" data-gsm-delay="' . esc_attr( $delay_b_sp ) . '" data-gsm-exit="' . esc_attr( $slide['exit_anim_btn'] ?? 'fade-out-up' ) . '" target="' . esc_attr( $target_sp ) . '"' . ( $rel_sp ? ' rel="' . esc_attr( $rel_sp ) . '"' : '' ) . '>' . esc_html( $slide['btn_text'] ) . '</a>';
								echo '</div>';
							}

							if ( ! empty( $slide['countdown_enable'] ) && 'yes' === $slide['countdown_enable'] ) {
								$target_date    = sanitize_text_field( $slide['countdown_date'] ?? '' );
								$style          = sanitize_key( $slide['countdown_style'] ?? 'blocks' );
								$position       = sanitize_key( $slide['countdown_position'] ?? 'below-content' );
								$action         = sanitize_key( $slide['countdown_action'] ?? 'message' );
								$expired_for_js = ! empty( $slide['countdown_expired_msg'] )
									? sanitize_text_field( $slide['countdown_expired_msg'] )
									: __( 'Offer Ended!', 'gsm-slider' );
								$urgency        = ! empty( $slide['countdown_urgency'] ) && 'yes' === $slide['countdown_urgency'];

								$timestamp = $target_date ? strtotime( $target_date ) : ( time() + 86400 * 7 );

								$cd_data = array(
									'target'   => $timestamp * 1000, // JS timestamp in ms.
									'action'   => $action,
									'expired'  => $expired_for_js,
									'urgency'  => $urgency,
									'style'    => $style,
								);

								$position_class = 'gsm-countdown--' . $position;
								$style_class    = 'gsm-countdown--' . $style;

								echo '<div class="gsm-countdown ' . esc_attr( $position_class ) . ' ' . esc_attr( $style_class ) . ' gsm-anim fade-up"';
								echo ' role="timer" aria-live="off"';
								echo ' data-countdown=\'' . wp_json_encode( $cd_data ) . '\'';
								echo ' data-gsm-delay="350">';
								echo '<div class="gsm-countdown-inner">';

								echo '<div class="gsm-countdown-item" data-unit="days">';
								echo '<span class="gsm-countdown-number">00</span>';
								echo '<span class="gsm-countdown-label">' . esc_html( $slide['countdown_label_days'] ?? __( 'Days', 'gsm-slider' ) ) . '</span>';
								echo '</div>';
								echo '<div class="gsm-countdown-sep">:</div>';

								echo '<div class="gsm-countdown-item" data-unit="hours">';
								echo '<span class="gsm-countdown-number">00</span>';
								echo '<span class="gsm-countdown-label">' . esc_html( $slide['countdown_label_hours'] ?? __( 'Hours', 'gsm-slider' ) ) . '</span>';
								echo '</div>';
								echo '<div class="gsm-countdown-sep">:</div>';

								echo '<div class="gsm-countdown-item" data-unit="minutes">';
								echo '<span class="gsm-countdown-number">00</span>';
								echo '<span class="gsm-countdown-label">' . esc_html( $slide['countdown_label_minutes'] ?? __( 'Mins', 'gsm-slider' ) ) . '</span>';
								echo '</div>';
								echo '<div class="gsm-countdown-sep">:</div>';

								echo '<div class="gsm-countdown-item" data-unit="seconds">';
								echo '<span class="gsm-countdown-number">00</span>';
								echo '<span class="gsm-countdown-label">' . esc_html( $slide['countdown_label_seconds'] ?? __( 'Secs', 'gsm-slider' ) ) . '</span>';
								echo '</div>';

								echo '</div>';
								echo '<div class="gsm-countdown-expired" style="display:none;" aria-live="polite">' . esc_html( $expired_for_js ) . '</div>';
								echo '</div>';
							}

							echo '</div></div></div>';
							continue;
						}
						?>

						<?php if ( ! empty( $slide['lightbox_enable'] ) && 'yes' === $slide['lightbox_enable'] ) : ?>
							<?php
							$lb_url   = esc_url( $slide['lightbox_video_url'] ?? '' );
							$lb_pos   = esc_attr( $slide['lightbox_btn_position'] ?? 'center' );
							$show_btn = empty( $slide['lightbox_btn_icon'] ) || 'yes' === $slide['lightbox_btn_icon'];
							?>
							<?php if ( $lb_url ) : ?>
								<div
									class="gsm-lightbox-trigger"
									data-lightbox-url="<?php echo esc_attr( $lb_url ); ?>"
									role="button"
									tabindex="0"
									aria-label="<?php esc_attr_e( 'Play video in fullscreen', 'gsm-slider' ); ?>"
								>
									<?php if ( $show_btn ) : ?>
										<span class="gsm-lightbox-btn gsm-lightbox-btn--<?php echo esc_attr( $lb_pos ); ?>" aria-hidden="true">
											<span class="gsm-lightbox-play-icon">&#9654;</span>
										</span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
						<?php endif; ?>

						<?php
						// WooCommerce sale badge — shown only when _is_on_sale is set and woo_show_sale_badge is 'yes'.
						if (
							! empty( $slide['_is_on_sale'] ) &&
							! empty( $settings['woo_show_sale_badge'] ) &&
							'yes' === $settings['woo_show_sale_badge']
						) :
						?>
							<span class="gsm-sale-badge" aria-label="<?php esc_attr_e( 'Sale!', 'gsm-slider' ); ?>">
								<?php esc_html_e( 'Sale!', 'gsm-slider' ); ?>
							</span>
						<?php endif; ?>

						<div class="gsm-slide-inner">
							<div
								class="<?php echo esc_attr( implode( ' ', $content_classes ) ); ?>"
							>
								<?php
								if ( ! empty( $slide['badge_text'] ) ) {
									$badge_anim      = isset( $slide['anim_heading'] ) ? $slide['anim_heading'] : $legacy_anim;
									$delay_badge     = absint( $slide['anim_badge_delay'] ?? 0 );
									$badge_color_val = isset( $slide['badge_color'] ) ? $slide['badge_color'] : '#e74c3c';
									echo '<span class="gsm-badge gsm-anim ' . esc_attr( $badge_anim ) . '" data-gsm-delay="' . esc_attr( $delay_badge ) . '" style="' . esc_attr( 'background:' . $badge_color_val . ';' ) . '">';
									echo esc_html( $slide['badge_text'] );
									echo '</span>';
								}
								$this->render_slide_heading_element( $slide, $legacy_anim );
								?>

								<?php if ( ! empty( $slide['description'] ) ) : ?>
									<?php
									$anim_t  = $slide['anim_text'] ?? $legacy_anim;
									$delay_t = absint( $slide['anim_text_delay'] ?? 150 );
									?>
									<p class="gsm-anim gsm-anim--text <?php echo esc_attr( $anim_t ); ?>"
										<?php if ( 'none' !== $anim_t ) : ?>
										data-gsm-delay="<?php echo esc_attr( $delay_t ); ?>"
										<?php endif; ?>
										data-gsm-exit="<?php echo esc_attr( $slide['exit_anim_text'] ?? 'fade-out-up' ); ?>">
										<?php echo esc_html( $slide['description'] ); ?>
									</p>
								<?php endif; ?>

								<?php if ( ! empty( $slide['btn_text'] ) && ! empty( $slide['btn_url']['url'] ) ) :
									$target = ! empty( $slide['btn_url']['is_external'] ) ? '_blank' : '_self';
									$rel    = ! empty( $slide['btn_url']['nofollow'] ) ? 'nofollow' : '';
									$anim_b = $slide['anim_btn'] ?? $legacy_anim;
									$delay_b = absint( $slide['anim_btn_delay'] ?? 300 );
									$btn_align = isset( $slide['content_align'] ) && 'inherit' !== $slide['content_align'] ? $slide['content_align'] : ( $settings['content_alignment'] ?? 'left' );
								?>
									<div class="gsm-btn-wrap align-<?php echo esc_attr( $btn_align ); ?>">
										<a
											href="<?php echo esc_url( $slide['btn_url']['url'] ?? '' ); ?>"
											class="gsm-btn gsm-anim gsm-anim--btn <?php echo esc_attr( $anim_b ); ?>"
											target="<?php echo esc_attr( $target ); ?>"
											<?php if ( 'none' !== $anim_b ) : ?>
											data-gsm-delay="<?php echo esc_attr( $delay_b ); ?>"
											<?php endif; ?>
											data-gsm-exit="<?php echo esc_attr( $slide['exit_anim_btn'] ?? 'fade-out-up' ); ?>"
											<?php if ( $rel ) : ?>
												rel="<?php echo esc_attr( $rel ); ?>"
											<?php endif; ?>
										>
											<?php echo esc_html( $slide['btn_text'] ?? '' ); ?>
										</a>
									</div>
								<?php endif; ?>
								<?php if ( ! empty( $slide['countdown_enable'] ) && 'yes' === $slide['countdown_enable'] ) :
									$target_date    = sanitize_text_field( $slide['countdown_date'] ?? '' );
									$style          = sanitize_key( $slide['countdown_style'] ?? 'blocks' );
									$position       = sanitize_key( $slide['countdown_position'] ?? 'below-content' );
									$action         = sanitize_key( $slide['countdown_action'] ?? 'message' );
									$expired_for_js = ! empty( $slide['countdown_expired_msg'] )
										? sanitize_text_field( $slide['countdown_expired_msg'] )
										: __( 'Offer Ended!', 'gsm-slider' );
									$urgency        = ! empty( $slide['countdown_urgency'] ) && 'yes' === $slide['countdown_urgency'];

									// Convert date to UTC timestamp
									$timestamp = $target_date ? strtotime( $target_date ) : ( time() + 86400 * 7 );

									$cd_data = array(
										'target'   => $timestamp * 1000, // JS timestamp in ms.
										'action'   => $action,
										'expired'  => $expired_for_js,
										'urgency'  => $urgency,
										'style'    => $style,
									);

									$position_class = 'gsm-countdown--' . $position;
									$style_class    = 'gsm-countdown--' . $style;

									echo '<div class="gsm-countdown ' . esc_attr( $position_class ) . ' ' . esc_attr( $style_class ) . ' gsm-anim fade-up"';
									echo ' role="timer" aria-live="off"';
									echo ' data-countdown=\'' . wp_json_encode( $cd_data ) . '\'';
									echo ' data-gsm-delay="350">';
									echo '<div class="gsm-countdown-inner">';

									// Days
									echo '<div class="gsm-countdown-item" data-unit="days">';
									echo '<span class="gsm-countdown-number">00</span>';
									echo '<span class="gsm-countdown-label">' . esc_html( $slide['countdown_label_days'] ?? __( 'Days', 'gsm-slider' ) ) . '</span>';
									echo '</div>';
									echo '<div class="gsm-countdown-sep">:</div>';

									// Hours
									echo '<div class="gsm-countdown-item" data-unit="hours">';
									echo '<span class="gsm-countdown-number">00</span>';
									echo '<span class="gsm-countdown-label">' . esc_html( $slide['countdown_label_hours'] ?? __( 'Hours', 'gsm-slider' ) ) . '</span>';
									echo '</div>';
									echo '<div class="gsm-countdown-sep">:</div>';

									// Minutes
									echo '<div class="gsm-countdown-item" data-unit="minutes">';
									echo '<span class="gsm-countdown-number">00</span>';
									echo '<span class="gsm-countdown-label">' . esc_html( $slide['countdown_label_minutes'] ?? __( 'Mins', 'gsm-slider' ) ) . '</span>';
									echo '</div>';
									echo '<div class="gsm-countdown-sep">:</div>';

									// Seconds
									echo '<div class="gsm-countdown-item" data-unit="seconds">';
									echo '<span class="gsm-countdown-number">00</span>';
									echo '<span class="gsm-countdown-label">' . esc_html( $slide['countdown_label_seconds'] ?? __( 'Secs', 'gsm-slider' ) ) . '</span>';
									echo '</div>';

									echo '</div>'; // .gsm-countdown-inner.

									// Expired message (hidden initially).
									echo '<div class="gsm-countdown-expired" style="display:none;" aria-live="polite">' . esc_html( $expired_for_js ) . '</div>';

									echo '</div>'; // .gsm-countdown.
								endif;
								?>
							</div>
						</div>
					</div>
					<?php endforeach; ?>
				</div>

				<?php if ( 'none' !== $pagination ) : ?>
					<div
						class="swiper-pagination gsm-pagination"
						<?php if ( 'bullets' === $pagination ) : ?>
						role="tablist"
						aria-label="<?php esc_attr_e( 'Slide indicators', 'gsm-slider' ); ?>"
						<?php else : ?>
						role="group"
						aria-label="<?php esc_attr_e( 'Carousel position', 'gsm-slider' ); ?>"
						<?php endif; ?>
					></div>
				<?php endif; ?>

				<?php if ( $show_arrows ) : ?>
					<button
						type="button"
						class="gsm-btn-prev"
						aria-label="<?php esc_attr_e( 'Previous slide', 'gsm-slider' ); ?>"
					>
						<?php echo $this->render_arrow_icon( 'prev', $icon_type, $custom_svg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
					<button
						type="button"
						class="gsm-btn-next"
						aria-label="<?php esc_attr_e( 'Next slide', 'gsm-slider' ); ?>"
					>
						<?php echo $this->render_arrow_icon( 'next', $icon_type, $custom_svg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</button>
				<?php endif; ?>

				<!-- Autoplay Progress Bar -->
				<?php if ( 'yes' === ( $settings['autoplay_progress'] ?? '' ) && 'yes' === $settings['autoplay'] ) : ?>
					<div class="gsm-autoplay-progress">
						<div class="gsm-progress-bar"></div>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( $gsm_mousewheel_on ) : ?>
				<?php if ( ! empty( $s['mousewheel_indicator'] ) && 'yes' === $s['mousewheel_indicator'] ) : ?>
					<div class="gsm-scroll-indicator" id="gsm-scroll-indicator-<?php echo esc_attr( $widget_id ); ?>">
						<span class="gsm-scroll-indicator__icon">
							<svg width="20" height="30" viewBox="0 0 20 30" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
								<rect x="1" y="1" width="18" height="28" rx="9" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
								<g class="gsm-scroll-dot-wrap">
									<circle class="gsm-scroll-dot" cx="10" cy="8" r="3" fill="rgba(255,255,255,0.9)"/>
								</g>
							</svg>
						</span>
						<span class="gsm-scroll-indicator__text"><?php echo esc_html( $s['mousewheel_indicator_text'] ?? __( 'Scroll to navigate', 'gsm-slider' ) ); ?></span>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $s['mousewheel_progress'] ) && 'yes' === $s['mousewheel_progress'] ) : ?>
					<div class="gsm-scroll-progress" id="gsm-scroll-progress-<?php echo esc_attr( $widget_id ); ?>">
						<div class="gsm-scroll-progress-track">
							<div class="gsm-scroll-progress-fill"></div>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php endif; ?>

			<div
				class="gsm-lightbox-modal"
				id="gsm-lightbox-<?php echo esc_attr( $widget_id ); ?>"
				role="dialog"
				aria-modal="true"
				aria-label="<?php esc_attr_e( 'Video lightbox', 'gsm-slider' ); ?>"
				style="display:none;"
			>
				<div class="gsm-lightbox-backdrop"></div>
				<div class="gsm-lightbox-inner">
					<button class="gsm-lightbox-close" type="button" aria-label="<?php esc_attr_e( 'Close video', 'gsm-slider' ); ?>">
						<span aria-hidden="true">&#10005;</span>
					</button>
					<div class="gsm-lightbox-media"></div>
				</div>
			</div>

			<div
				class="gsm-sr-only"
				aria-live="polite"
				aria-atomic="true"
				id="gsm-live-<?php echo esc_attr( $widget_id ); ?>"
			></div>

			<?php if ( $thumb_nav ) : 
				$thumb_width = isset( $settings['thumb_width'] ) ? $settings['thumb_width'] : 'full';
			?>
				<div class="gsm-thumbs-container gsm-thumb-width-<?php echo esc_attr( $thumb_width ); ?>">
					<div
						class="swiper gsm-thumbs-swiper"
						id="gsm-thumbs-<?php echo esc_attr( $widget_id ); ?>"
						aria-hidden="true"
					>
					<div class="swiper-wrapper">
						<?php foreach ( $slides as $index => $slide ) :
							$thumb_url = '';
							if ( isset( $slide['media_type'] ) && 'image' === $slide['media_type'] && ! empty( $slide['bg_image']['url'] ) ) {
								$thumb_url = $slide['bg_image']['url'];
							} elseif ( isset( $slide['media_type'] ) && 'video' === $slide['media_type'] && ! empty( $slide['video_poster']['url'] ) ) {
								$thumb_url = $slide['video_poster']['url'];
							}
						?>
						<div
							class="swiper-slide gsm-thumb-slide"
							<?php if ( $thumb_url ) : ?>
								style="background-image: url('<?php echo esc_url( $thumb_url ); ?>');" 
								role="img"
								aria-label="<?php echo esc_attr( sprintf( /* translators: slide number */ __( 'Go to slide %d', 'gsm-slider' ), $index + 1 ) ); ?>"
							<?php endif; ?>
						>
							<?php if ( ! $thumb_url ) : ?>
								<span class="gsm-thumb-number" aria-hidden="true"><?php echo esc_html( $index + 1 ); ?></span>
							<?php endif; ?>
						</div>
						<?php endforeach; ?>
					</div>
					</div>
				</div>
			<?php endif; ?>

		</div>
		<?php
	}
}
