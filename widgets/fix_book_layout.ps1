$widgetFile = 'class-gsm-slider-widget.php'
$cssFile    = '..\assets\css\slider.css'

# ==========================================
# READ FILES
# ==========================================
$widget = [System.IO.File]::ReadAllText($widgetFile, [System.Text.Encoding]::UTF8) -replace "`r`n", "`n"
$css    = [System.IO.File]::ReadAllText($cssFile,    [System.Text.Encoding]::UTF8) -replace "`r`n", "`n"

# ==========================================
# PATCH W1: Book controls loop — add ribbon / spine per book
# ==========================================
$oldLoop = @'
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
		}
'@

$newLoop = @'
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
					'placeholder' => esc_html__( 'Publisher name…', 'gsm-slider' ),
					'description' => esc_html__( 'Text shown vertically on the book spine. Leave blank for plain spine.', 'gsm-slider' ),
					'condition'   => $book_img_condition,
				)
			);
		}
'@

# ==========================================
# PATCH W2: book_single_width SELECT — add after book_float_animation control
# ==========================================
$oldFloatEnd = @'
		$repeater->add_control(
			'book_heading_highlight',
'@

$newFloatEnd = @'
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
'@

# ==========================================
# PATCH W3: $books array — add ribbon / spine fields
# Old line: 'nofollow'    => ! empty( $url_row['nofollow'] ),
#                    );
# New: adds ribbon / spine / ribbon_color
# ==========================================
$oldBooks = @'
			$books[] = array(
				'image'       => $img_url,
				'title'       => isset( $slide[ 'book' . $b . '_title' ] ) ? $slide[ 'book' . $b . '_title' ] : '',
				'url'         => $lnk,
				'is_external' => ! empty( $url_row['is_external'] ),
				'nofollow'    => ! empty( $url_row['nofollow'] ),
			);
'@

$newBooks = @'
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
'@

# ==========================================
# PATCH W4: single-book width — use control
# ==========================================
$oldSingleW = @'
		if ( 1 === $book_count ) {
			$grid_css = 'display:grid;grid-template-columns:1fr;gap:16px;width:100%;max-width:180px;margin:0 auto;';
		} elseif ( 2 === $book_count ) {
'@

$newSingleW = @'
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
'@

# ==========================================
# PATCH W5: Render ribbon + spine text inside book card
# ==========================================
$oldCoverWrap = @'
			echo '<div class="gsm-book-cover-wrap">';
			echo '<img class="gsm-book-cover" src="' . esc_url( $book['image'] ) . '" alt="' . esc_attr( $book['title'] ) . '" loading="lazy">';
			echo '<div class="gsm-book-spine" aria-hidden="true"></div>';
			echo '<div class="gsm-book-shine" aria-hidden="true"></div>';
			echo '</div>';
'@

$newCoverWrap = @'
			echo '<div class="gsm-book-cover-wrap">';
			echo '<img class="gsm-book-cover" src="' . esc_url( $book['image'] ) . '" alt="' . esc_attr( $book['title'] ) . '" loading="lazy">';
			// Spine — shows decorative strip; optional publisher text runs vertically.
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
'@

# ==========================================
# APPLY WIDGET PATCHES
# ==========================================
$patches = @(
	@{ name='W1 (book controls loop)'; old=$oldLoop;      new=$newLoop      },
	@{ name='W2 (single-width select)'; old=$oldFloatEnd;   new=$newFloatEnd  },
	@{ name='W3 ($books ribbon/spine)'; old=$oldBooks;     new=$newBooks     },
	@{ name='W4 (single-book width)';  old=$oldSingleW;   new=$newSingleW   },
	@{ name='W5 (ribbon/spine render)'; old=$oldCoverWrap; new=$newCoverWrap }
)

foreach ($p in $patches) {
	if ($widget.Contains($p.old)) {
		Write-Host "PATCH $($p.name) — FOUND, applying..."
		$widget = $widget.Replace($p.old, $p.new)
	} else {
		Write-Host "PATCH $($p.name) — NOT FOUND (skipped)"
	}
}

# ==========================================
# CSS ADDITION — ribbon badge + spine text
# Append before the final @media reduced-motion block
# ==========================================
$cssInsertMarker = '@media (prefers-reduced-motion: reduce) {'

$ribbonCss = @'
/* =========================================================
   Book Ribbon Badge
   ========================================================= */
.gsm-book-ribbon {
	position: absolute;
	top: 10px;
	right: -2px;
	padding: 4px 10px;
	font-size: 10px;
	font-weight: 700;
	letter-spacing: 0.04em;
	color: #111111;
	border-radius: 4px 4px 0 4px;
	z-index: 10;
	white-space: nowrap;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.30), -2px 2px 0 rgba(0,0,0,0.18);
	pointer-events: none;
}

/* Tiny folded corner effect */
.gsm-book-ribbon::after {
	content: '';
	position: absolute;
	bottom: -5px;
	right: 0;
	border-width: 5px 5px 0 0;
	border-style: solid;
	border-color: rgba(0, 0, 0, 0.25) transparent transparent transparent;
}

/* =========================================================
   Book Spine Text
   ========================================================= */
.gsm-book-spine {
	display: flex;
	align-items: center;
	justify-content: center;
	overflow: hidden;
}

.gsm-book-spine-text {
	writing-mode: vertical-rl;
	text-orientation: mixed;
	transform: rotate(180deg);
	font-size: 9px;
	font-weight: 600;
	color: rgba(255, 255, 255, 0.88);
	letter-spacing: 0.07em;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	max-height: 90%;
	text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

'@

if ($css.Contains($cssInsertMarker)) {
	Write-Host "CSS marker found — inserting ribbon/spine styles..."
	$css = $css.Replace($cssInsertMarker, $ribbonCss + $cssInsertMarker)
} else {
	Write-Host "CSS marker NOT FOUND — appending to end of file"
	$css = $css + "`n" + $ribbonCss
}

# ==========================================
# WRITE FILES
# ==========================================
[System.IO.File]::WriteAllText($widgetFile, $widget, [System.Text.Encoding]::UTF8)
[System.IO.File]::WriteAllText($cssFile,    $css,    [System.Text.Encoding]::UTF8)

Write-Host "All done!"
