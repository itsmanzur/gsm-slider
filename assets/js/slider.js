/**
 * GSM Slider for Elementor — Frontend JavaScript
 *
 * Initialises a Swiper instance for each GSM Slider widget on the page.
 * Handles: accessibility (ARIA live, keyboard), autoplay pause on focus/hover,
 * IntersectionObserver lazy-loading for background images, video play/pause,
 * parallax/cinematic CSS class toggling, and thumbnail navigation linking.
 *
 * Depends on: Swiper (registered by Elementor)
 * NO jQuery dependency.
 *
 * @package GSM Slider
 */

( function () {
	'use strict';

	/**
	 * Check if the user prefers reduced motion.
	 *
	 * @returns {boolean}
	 */
	function prefersReducedMotion() {
		return window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	}

	/**
	 * Primary input is touch-only (typical phone). Desktop with mouse may still
	 * expose ontouchstart — do not use that alone to disable 3D tilt.
	 *
	 * @returns {boolean}
	 */
	function gsmSliderIsCoarsePointerOnly() {
		if ( typeof window.matchMedia !== 'function' ) {
			return false;
		}
		try {
			var coarse = window.matchMedia( '(pointer: coarse)' ).matches;
			var fine   = window.matchMedia( '(pointer: fine)' ).matches;
			return coarse && ! fine;
		} catch ( err ) {
			return false;
		}
	}

	/**
	 * @param {object} s data-settings object
	 * @returns {boolean}
	 */
	function gsmSliderTiltEnabled( s ) {
		if ( ! s || prefersReducedMotion() || gsmSliderIsCoarsePointerOnly() ) {
			return false;
		}
		return s.tilt_effect === true || s.tilt_effect === 'yes' || s.tilt_effect === 1;
	}

	/**
	 * @param {object} s data-settings
	 * @returns {number}
	 */
	function gsmSliderTiltIntensityVal( s ) {
		var n = parseInt( s && s.tilt_intensity, 10 );
		if ( ! isNaN( n ) && n >= 1 ) {
			return Math.min( 25, n );
		}
		if ( s && s.tilt_intensity && typeof s.tilt_intensity === 'object' && s.tilt_intensity.size != null ) {
			n = parseInt( s.tilt_intensity.size, 10 );
			if ( ! isNaN( n ) && n >= 1 ) {
				return Math.min( 25, n );
			}
		}
		return 8;
	}

	// ==============================
	// GSM PARTICLE ENGINE
	// ==============================
	function GsmParticles( canvas, config ) {
		var ctx = canvas.getContext( '2d' );
		var particles = [];
		var raf = null;
		var running = false;

		var COLORS_CONFETTI = [ '#ff6b6b', '#ffd93d', '#6bcb77', '#4d96ff', '#ff922b', '#cc5de8' ];

		var resizeTimer = null;
		var resizeHandler = null;

		function randomBetween( a, b ) {
			return a + Math.random() * ( b - a );
		}

		function resize() {
			// Use layout size so canvas matches actual slide dimensions.
			canvas.width = canvas.offsetWidth;
			canvas.height = canvas.offsetHeight;
		}

		function normalizeConfig() {
			config = config || {};
			return config;
		}

		function initParticles() {
			particles = [];
			var cfg = normalizeConfig();
			var count = Math.min( parseInt( cfg.count || 80, 10 ), 200 );
			for ( var i = 0; i < count; i++ ) {
				particles.push( createParticle() );
			}
		}

		function createParticle() {
			var cfg = normalizeConfig();

			var color = cfg.color || '#ffffff';
			if ( cfg.multicolor ) {
				color = COLORS_CONFETTI[ Math.floor( Math.random() * COLORS_CONFETTI.length ) ];
			} else if ( cfg.color2 && Math.random() > 0.5 ) {
				color = cfg.color2;
			}

			var opacityBase = typeof cfg.opacity === 'number' ? cfg.opacity : 0.7;
			var opacity = randomBetween( opacityBase * 0.5, opacityBase );

			var sizeBase = typeof cfg.size === 'number' ? cfg.size : 4;
			var size = randomBetween( sizeBase * 0.5, sizeBase * 1.5 );

			var speedBase = typeof cfg.speed === 'number' ? cfg.speed : 1.5;

			return {
				x: randomBetween( 0, canvas.width ),
				y: randomBetween( -canvas.height, canvas.height ),
				size: size,
				speedY: randomBetween( speedBase * 0.5, speedBase * 1.5 ),
				speedX: cfg.drift ? randomBetween( -0.8, 0.8 ) : 0,
				opacity: opacity,
				color: color,
				angle: randomBetween( 0, Math.PI * 2 ),
				spin: cfg.rotate ? randomBetween( -0.05, 0.05 ) : 0,
				glitter: cfg.glitter ? ( Math.random() > 0.7 ) : false,
				glitterPhase: randomBetween( 0, Math.PI * 2 ),
				bubble: cfg.bubble || false,
			};
		}

		function drawCircle( p ) {
			ctx.beginPath();
			ctx.arc( p.x, p.y, p.size, 0, Math.PI * 2 );
			if ( p.bubble ) {
				ctx.strokeStyle = p.color;
				ctx.lineWidth = 1.5;
				ctx.stroke();
			} else {
				ctx.fillStyle = p.color;
				ctx.fill();
			}
		}

		function drawStar( p ) {
			var cfg = normalizeConfig();
			var spikes = 5;
			var outer = p.size;
			var inner = p.size * 0.4;
			ctx.save();
			ctx.translate( p.x, p.y );
			ctx.rotate( p.angle );
			ctx.beginPath();
			for ( var i = 0; i < spikes * 2; i++ ) {
				var r = i % 2 === 0 ? outer : inner;
				var angle = ( i * Math.PI ) / spikes;
				if ( i === 0 ) {
					ctx.moveTo( r * Math.cos( angle ), r * Math.sin( angle ) );
				} else {
					ctx.lineTo( r * Math.cos( angle ), r * Math.sin( angle ) );
				}
			}
			ctx.closePath();
			ctx.fillStyle = p.color;
			ctx.fill();
			ctx.restore();
		}

		function drawSquare( p ) {
			ctx.save();
			ctx.translate( p.x, p.y );
			ctx.rotate( p.angle );
			ctx.fillStyle = p.color;
			ctx.fillRect( -p.size / 2, -p.size / 2, p.size, p.size );
			ctx.restore();
		}

		function drawTriangle( p ) {
			ctx.save();
			ctx.translate( p.x, p.y );
			ctx.rotate( p.angle );
			ctx.beginPath();
			ctx.moveTo( 0, -p.size );
			ctx.lineTo( p.size * 0.866, p.size * 0.5 );
			ctx.lineTo( -p.size * 0.866, p.size * 0.5 );
			ctx.closePath();
			ctx.fillStyle = p.color;
			ctx.fill();
			ctx.restore();
		}

		function drawConnections() {
			var cfg = normalizeConfig();
			var maxDist = 100;
			for ( var i = 0; i < particles.length; i++ ) {
				for ( var j = i + 1; j < particles.length; j++ ) {
					var dx = particles[ i ].x - particles[ j ].x;
					var dy = particles[ i ].y - particles[ j ].y;
					var dist = Math.sqrt( dx * dx + dy * dy );
					if ( dist < maxDist ) {
						ctx.beginPath();
						ctx.strokeStyle = cfg.color;
						ctx.globalAlpha = ( 1 - dist / maxDist ) * 0.25;
						ctx.lineWidth = 0.5;
						ctx.moveTo( particles[ i ].x, particles[ i ].y );
						ctx.lineTo( particles[ j ].x, particles[ j ].y );
						ctx.stroke();
						ctx.globalAlpha = 1;
					}
				}
			}
		}

		function animate() {
			if ( ! running ) {
				return;
			}
			ctx.clearRect( 0, 0, canvas.width, canvas.height );

			var cfg = normalizeConfig();
			particles.forEach( function ( p ) {
				// Update position.
				p.y += p.speedY;
				p.x += p.speedX;
				p.angle += p.spin;
				p.glitterPhase += 0.1;

				// Glitter opacity pulse.
				var opacity = p.opacity;
				if ( p.glitter ) {
					opacity = p.opacity * ( 0.5 + 0.5 * Math.sin( p.glitterPhase ) );
				}

				ctx.globalAlpha = opacity;

				// Draw shape.
				switch ( cfg.shape ) {
					case 'star':
						drawStar( p );
						break;
					case 'square':
						drawSquare( p );
						break;
					case 'triangle':
						drawTriangle( p );
						break;
					default:
						drawCircle( p );
						break;
				}

				ctx.globalAlpha = 1;

				// Reset when off screen.
				if ( p.y > canvas.height + p.size * 2 ) {
					p.y = -p.size * 2;
					p.x = randomBetween( 0, canvas.width );
				}
				if ( p.x < -p.size * 2 ) {
					p.x = canvas.width + p.size;
				}
				if ( p.x > canvas.width + p.size * 2 ) {
					p.x = -p.size;
				}
			} );

			if ( cfg.connect ) {
				drawConnections();
			}

			raf = requestAnimationFrame( animate );
		}

		this.start = function() {
			if ( prefersReducedMotion() ) {
				return;
			}
			resize();
			if ( particles.length === 0 ) {
				initParticles();
			}
			running = true;
			if ( ! raf ) {
				animate();
			}
		};

		this.stop = function() {
			running = false;
			if ( raf ) {
				cancelAnimationFrame( raf );
				raf = null;
			}
		};

		this.destroy = function() {
			this.stop();
			if ( resizeHandler ) {
				window.removeEventListener( 'resize', resizeHandler );
				resizeHandler = null;
			}
			particles = [];
			try {
				ctx.clearRect( 0, 0, canvas.width, canvas.height );
			} catch ( e ) {
				// no-op
			}
		};

		// Handle resize while instance lives.
		resizeHandler = function() {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( function() {
				resize();
				initParticles();
			}, 200 );
		};
		window.addEventListener( 'resize', resizeHandler );
	}

	// ==============================
	// GSM COUNTDOWN ENGINE
	// ==============================
	function GsmCountdown( el, swiper ) {
		var config = {};
		try {
			config = JSON.parse( el.dataset.countdown || '{}' );
		} catch ( e ) {
			config = {};
		}

		var target = config.target || ( Date.now() + 86400000 );
		var action = config.action || 'message';
		var urgency = config.urgency !== false;
		var reduce = prefersReducedMotion();

		var interval = null;
		var ended = false;

		var daysEl = el.querySelector( '[data-unit="days"] .gsm-countdown-number' );
		var hoursEl = el.querySelector( '[data-unit="hours"] .gsm-countdown-number' );
		var minsEl = el.querySelector( '[data-unit="minutes"] .gsm-countdown-number' );
		var secsEl = el.querySelector( '[data-unit="seconds"] .gsm-countdown-number' );
		var innerEl = el.querySelector( '.gsm-countdown-inner' );
		var expiredEl = el.querySelector( '.gsm-countdown-expired' );

		function pad( n ) {
			return n < 10 ? '0' + n : '' + n;
		}

		function setNumber( numEl, val ) {
			if ( ! numEl ) return;
			var txt = pad( val );
			if ( reduce ) {
				numEl.textContent = txt;
				return;
			}
			if ( numEl.textContent === txt ) return;
			numEl.classList.remove( 'gsm-flip' );
			void numEl.offsetWidth; // eslint-disable-line no-void
			numEl.textContent = txt;
			numEl.classList.add( 'gsm-flip' );
		}

		function clear() {
			if ( interval ) {
				clearInterval( interval );
				interval = null;
			}
		}

		function expire() {
			if ( ended ) return;
			ended = true;
			clear();

			setNumber( daysEl, 0 );
			setNumber( hoursEl, 0 );
			setNumber( minsEl, 0 );
			setNumber( secsEl, 0 );

			el.classList.remove( 'gsm-countdown--urgent' );

			if ( action === 'hide' ) {
				el.style.display = 'none';
			} else if ( action === 'message' ) {
				if ( innerEl ) innerEl.style.display = 'none';
				if ( expiredEl ) expiredEl.style.display = 'block';
			} else if ( action === 'next' && swiper && typeof swiper.slideNext === 'function' ) {
				setTimeout( function () {
					swiper.slideNext();
				}, 800 );
			}
		}

		function tick() {
			var now = Date.now();
			var distance = target - now;

			if ( distance <= 0 ) {
				expire();
				return;
			}

			var days = Math.floor( distance / ( 1000 * 60 * 60 * 24 ) );
			var hours = Math.floor( ( distance % ( 1000 * 60 * 60 * 24 ) ) / ( 1000 * 60 * 60 ) );
			var minutes = Math.floor( ( distance % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 ) );
			var seconds = Math.floor( ( distance % ( 1000 * 60 ) ) / 1000 );

			setNumber( daysEl, days );
			setNumber( hoursEl, hours );
			setNumber( minsEl, minutes );
			setNumber( secsEl, seconds );

			if ( ! reduce && urgency && distance < 3600000 ) {
				el.classList.add( 'gsm-countdown--urgent' );
			} else {
				el.classList.remove( 'gsm-countdown--urgent' );
			}
		}

		this.start = function() {
			clear();
			ended = false;
			if ( expiredEl ) expiredEl.style.display = 'none';
			if ( innerEl ) innerEl.style.display = '';
			el.style.display = '';

			tick();
			interval = setInterval( tick, 1000 );
		};

		this.stop = function() {
			clear();
		};
	}

	/**
	 * Typing effect for .gsm-typing-text (one instance per element).
	 *
	 * @param {HTMLElement} el     Target span (text inserted here).
	 * @param {object}      config strings, typeSpeed, deleteSpeed, pauseTime.
	 * @constructor
	 */
	function GsmTyper( el, config ) {
		var strings     = config.strings && config.strings.length ? config.strings : [ '' ];
		var typeSpeed   = config.typeSpeed || 80;
		var deleteSpeed = config.deleteSpeed || 40;
		var pauseTime   = config.pauseTime || 1800;

		var strIdx   = 0;
		var charIdx  = 0;
		var deleting = false;
		var timer    = null;
		var active   = true;

		function tick() {
			if ( ! active ) {
				return;
			}
			var current = strings[ strIdx % strings.length ];

			if ( ! deleting ) {
				charIdx++;
				el.textContent = current.substring( 0, charIdx );
				if ( charIdx >= current.length ) {
					deleting = true;
					timer = setTimeout( tick, pauseTime );
					return;
				}
				timer = setTimeout( tick, typeSpeed );
			} else {
				charIdx--;
				el.textContent = current.substring( 0, charIdx );
				if ( charIdx <= 0 ) {
					deleting = false;
					strIdx++;
					timer = setTimeout( tick, typeSpeed * 2 );
					return;
				}
				timer = setTimeout( tick, deleteSpeed );
			}
		}

		this.start = function () {
			active = true;
			charIdx = 0;
			deleting = false;
			el.textContent = '';
			clearTimeout( timer );
			timer = setTimeout( tick, 500 );
		};

		this.stop = function () {
			active = false;
			clearTimeout( timer );
		};
	}

	/**
	 * Load full-resolution background after optional LQIP (tiny blur-up) pass.
	 *
	 * @param {HTMLElement} bg .gsm-bg with data-bg (full URL) and optional data-lqip.
	 */
	function loadBg( bg ) {
		var src = bg.dataset.bg;
		if ( ! src || bg.dataset.loaded ) {
			return;
		}

		var lqip = bg.dataset.lqip;

		if ( lqip ) {
			bg.style.backgroundImage = 'url("' + lqip + '")';
			if ( ! prefersReducedMotion() ) {
				bg.classList.add( 'gsm-lqip-loading' );
			}
		}

		var fullImg = new Image();
		if ( bg.getAttribute( 'data-fetchpriority' ) === 'high' && 'fetchPriority' in fullImg ) {
			fullImg.fetchPriority = 'high';
		}
		fullImg.onload = function () {
			bg.style.backgroundImage = 'url("' + src + '")';
			bg.style.backgroundSize = bg.dataset.fit || 'cover';
			bg.style.backgroundPosition = bg.dataset.position || 'center center';
			bg.classList.remove( 'gsm-lqip-loading' );
			bg.classList.add( 'gsm-lqip-loaded' );
			bg.dataset.loaded = 'true';
		};
		fullImg.onerror = function () {
			bg.classList.remove( 'gsm-lqip-loading' );
			if ( lqip ) {
				bg.dataset.loaded = 'true';
			}
		};
		fullImg.src = src;
	}

	/**
	 * Poster / legacy nodes: single-shot lazy background.
	 *
	 * @param {HTMLElement} el
	 */
	function loadLegacyLazyBg( el ) {
		var url = el.dataset.lazyBg;
		if ( ! url ) {
			return;
		}
		el.style.backgroundImage = 'url("' + url + '")';
		el.removeAttribute( 'data-lazy-bg' );
	}

	/**
	 * Lazy-load backgrounds: LQIP + full image for .gsm-bg[data-bg], legacy data-lazy-bg for posters.
	 *
	 * @param {HTMLElement} sliderEl The .gsm-slider element.
	 */
	function initLazyBg( sliderEl ) {
		var lqipTargets = sliderEl.querySelectorAll( '[data-bg]' );
		var legacy = sliderEl.querySelectorAll( '[data-lazy-bg]' );

		if ( ! lqipTargets.length && ! legacy.length ) {
			return;
		}

		if ( ! ( 'IntersectionObserver' in window ) ) {
			lqipTargets.forEach( function ( el ) {
				loadBg( el );
			} );
			legacy.forEach( loadLegacyLazyBg );
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						var el = entry.target;
						if ( el.dataset && el.dataset.bg ) {
							loadBg( el );
						} else {
							loadLegacyLazyBg( el );
						}
						observer.unobserve( el );
					}
				} );
			},
			{ rootMargin: '200px' }
		);

		// Load the first/high-priority background immediately so editor preview
		// doesn't show the fallback dark background when IntersectionObserver
		// doesn't trigger yet.
		lqipTargets.forEach( function ( el ) {
			var priority = el.getAttribute( 'data-fetchpriority' );
			if ( priority === 'high' ) {
				loadBg( el );
			} else {
				observer.observe( el );
			}
		} );

		legacy.forEach( function ( el ) {
			observer.observe( el );
		} );
	}

	/**
	 * Build the Swiper configuration object from data-settings.
	 *
	 * @param {Object}      s         Parsed settings from data-settings attribute.
	 * @param {HTMLElement} sliderEl  The .gsm-slider element.
	 * @param {HTMLElement} thumbsSwiper Optional Swiper instance for thumbnails.
	 * @returns {Object} Swiper config.
	 */
	function buildSwiperConfig( s, sliderEl, thumbsInstance ) {
		var reduced = prefersReducedMotion();
		var paginationType = s.paginationType || s.pagination_type || 'bullets';
		var rawEffect      = s.effect || 'fade';
		var swiperEffect   = rawEffect === 'creative2' ? 'creative' : rawEffect;

		var config = {
			effect:      swiperEffect,
			speed:       reduced ? 1 : ( s.speed || 900 ),
			loop:        !! s.loop,
			grabCursor:  true,
			touchRatio: 1,
			touchAngle: 45,
			touchMoveStopPropagation: false,
			preventInteractionOnTransition: true,
			slidesPerView: s.slidesPerViewMobile || s.slidesPerViewTablet || s.slidesPerView || 1,
			spaceBetween: (s.spaceBetweenMobile !== '' ? s.spaceBetweenMobile : (s.spaceBetweenTablet !== '' ? s.spaceBetweenTablet : (s.spaceBetween || 0))),
			centeredSlides: !!s.centerMode,
			breakpoints: {
				768: {
					slidesPerView: s.slidesPerViewTablet || s.slidesPerView || 1,
					spaceBetween: s.spaceBetweenTablet !== '' ? s.spaceBetweenTablet : (s.spaceBetween || 0),
				},
				1025: {
					slidesPerView: s.slidesPerView || 1,
					spaceBetween: s.spaceBetween || 0,
				}
			},
			a11y: {
				enabled: false,
			},
			keyboard: {
				enabled: false,
			},
		};

		if ( 'fade' === swiperEffect ) {
			config.fadeEffect = {
				crossFade: true,
			};
		}

		// Autoplay.
		if ( s.autoplay && ! reduced ) {
			config.autoplay = {
				delay:                s.delay || 5000,
				disableOnInteraction: false,
				pauseOnMouseEnter:    !! s.pauseOnHover,
			};
		} else {
			config.autoplay = false;
		}

		// Arrows.
		if ( s.arrows ) {
			config.navigation = {
				prevEl: sliderEl.querySelector( '.gsm-btn-prev' ),
				nextEl: sliderEl.querySelector( '.gsm-btn-next' ),
			};
		}

		// Pagination.
		if ( paginationType && 'none' !== paginationType ) {
			config.pagination = {
				el:        sliderEl.querySelector( '.gsm-pagination' ),
				type:      paginationType,
				clickable: paginationType === 'bullets',
			};
		}

		var covRotate = parseInt( s.coverflowRotate || s.coverflow_rotate || 30, 10 );
		var covDepth  = parseInt( s.coverflowDepth || s.coverflow_depth || 100, 10 );

		if ( 'coverflow' === swiperEffect ) {
			config.centeredSlides = true;
			config.coverflowEffect = {
				rotate:       covRotate,
				stretch:      0,
				depth:        covDepth,
				modifier:     1,
				slideShadows: false,
			};
		}

		if ( 'cards' === swiperEffect ) {
			config.cardsEffect = {
				slideShadows:   false,
				rotate:         true,
				perSlideOffset: 8,
				perSlideRotate: 2,
			};
		}

		if ( 'cube' === swiperEffect ) {
			config.cubeEffect = {
				shadow:       false,
				slideShadows: false,
				shadowOffset: 0,
			};
		}

		if ( 'flip' === swiperEffect ) {
			config.flipEffect = {
				slideShadows: false,
			};
		}

		if ( 'creative' === swiperEffect ) {
			if ( rawEffect === 'creative2' ) {
				config.creativeEffect = {
					prev: {
						shadow:    false,
						translate: [ 0, 0, -400 ],
						opacity:   0,
					},
					next: {
						translate: [ '100%', 0, 0 ],
					},
				};
			} else {
				config.creativeEffect = {
					prev: {
						shadow:    false,
						translate: [ '-120%', 0, -500 ],
					},
					next: {
						translate: [ '100%', 0, 0 ],
					},
				};
			}
		}

		// Mousewheel / trackpad vertical navigation (optional; coarse touch devices skip in JS).
		var mwOn = s.mousewheel_enable === true || s.mousewheel_enable === 'yes' || s.mousewheel_enable === 1;
		if ( mwOn ) {
			var mwSens = parseFloat( s.mousewheel_sensitivity );
			if ( isNaN( mwSens ) || mwSens < 0.1 ) {
				mwSens = 1;
			}
			config.mousewheel = {
				enabled: true,
				sensitivity: mwSens,
				releaseOnEdges: true,
				thresholdDelta: 50,
			};
		} else {
			config.mousewheel = false;
		}

		// Thumbnail linking.
		if ( ( s.thumbnailNav || s.thumbnail_nav ) && thumbsInstance ) {
			config.thumbs = {
				swiper: thumbsInstance,
			};
		}

		return config;
	}

	/**
	 * Announce a slide change to the ARIA live region.
	 *
	 * @param {HTMLElement} announcer    The aria-live element.
	 * @param {number}      currentIndex 0-based index.
	 * @param {number}      total        Total slide count.
	 * @param {string}      template     Optional "{current}" / "{total}" template from PHP i18n.
	 */
	function announceSlide( announcer, currentIndex, total, template ) {
		if ( ! announcer ) {
			return;
		}
		var msg = '';
		if ( template && template.indexOf( '{current}' ) !== -1 ) {
			msg = template
				.replace( /\{current\}/g, String( currentIndex + 1 ) )
				.replace( /\{total\}/g, String( total ) );
		} else {
			msg = 'Slide ' + ( currentIndex + 1 ) + ' of ' + total;
		}
		announcer.textContent = '';
		// Force re-read by browsers: blank then set.
		setTimeout( function () {
			announcer.textContent = msg;
		}, 50 );
	}

	/**
	 * Toggle content animation classes when a slide becomes active.
	 *
	 * @param {HTMLElement} slideEl The active slide element.
	 */
	function runContentAnimation( slideEl ) {
		var content = slideEl.querySelector( '.gsm-slide-content' );
		if ( ! content || prefersReducedMotion() ) {
			return;
		}
		var anim = content.dataset.animation || 'fade-up';
		content.classList.remove( 'gsm-anim-done' );
		content.classList.remove( 'gsm-anim-' + anim );
		// Trigger reflow.
		void content.offsetWidth; // eslint-disable-line no-void
		content.classList.add( 'gsm-anim-' + anim );
		content.classList.add( 'gsm-anim-done' );
	}

	/**
	 * Handle cinematic and parallax CSS class toggling on slide change.
	 *
	 * @param {HTMLElement} slideEl The newly active slide element.
	 */
	function handleSlideEffects( slideEl ) {
		if ( prefersReducedMotion() ) {
			return;
		}
		if ( slideEl.classList.contains( 'gsm-cinematic' ) ) {
			slideEl.classList.remove( 'gsm-cinematic-active' );
			void slideEl.offsetWidth; // eslint-disable-line no-void
			slideEl.classList.add( 'gsm-cinematic-active' );
		}
		if ( slideEl.classList.contains( 'gsm-effect-parallax' ) ) {
			slideEl.classList.remove( 'gsm-parallax-active' );
			void slideEl.offsetWidth; // eslint-disable-line no-void
			slideEl.classList.add( 'gsm-parallax-active' );
		}
		if ( slideEl.classList.contains( 'gsm-video-cinematic-intro' ) ) {
			slideEl.classList.remove( 'gsm-video-intro-active' );
			void slideEl.offsetWidth; // eslint-disable-line no-void
			slideEl.classList.add( 'gsm-video-intro-active' );
		}
	}

	/**
	 * Sync play/pause control icon and aria-label for HTML5 slide video.
	 *
	 * @param {HTMLVideoElement} video Active or toggled video element.
	 */
	function updateToggleBtn( video ) {
		var wrap = video.closest( '.gsm-video-wrap' );
		var btn  = wrap ? wrap.querySelector( '.gsm-video-toggle' ) : null;
		if ( ! btn ) {
			return;
		}
		if ( video.paused ) {
			wrap.classList.remove( 'gsm-video-playing' );
			wrap.classList.add( 'gsm-video-paused' );
		} else {
			wrap.classList.remove( 'gsm-video-paused' );
			wrap.classList.add( 'gsm-video-playing' );
		}
		var icon = btn.querySelector( '.gsm-video-toggle-icon' );
		if ( video.paused ) {
			if ( icon ) {
				icon.innerHTML = '&#9654;';
			}
			btn.setAttribute( 'aria-label', 'Play video' );
		} else {
			if ( icon ) {
				icon.innerHTML = '&#9646;&#9646;';
			}
			btn.setAttribute( 'aria-label', 'Pause video' );
		}

		// Sync mute/unmute button if present.
		var muteBtn = wrap.querySelector( '.gsm-video-mute' );
		if ( muteBtn ) {
			var muteIcon = muteBtn.querySelector( '.gsm-video-mute-icon' );
			if ( video.muted ) {
				if ( muteIcon ) {
					muteIcon.innerHTML = '&#128263;'; // muted speaker
				}
				muteBtn.setAttribute( 'aria-label', 'Unmute video' );
			} else {
				if ( muteIcon ) {
					muteIcon.innerHTML = '&#128266;'; // speaker with sound
				}
				muteBtn.setAttribute( 'aria-label', 'Mute video' );
			}
		}
	}

	/**
	 * Pause all MP4 videos; play only the active slide’s video (embed slides unaffected).
	 *
	 * @param {HTMLElement} root The .gsm-slider element.
	 */
	function manageVideos( root ) {
		root.querySelectorAll( '.gsm-slide .gsm-video' ).forEach( function ( v ) {
			var slide = v.closest( '.gsm-slide' );
			if ( ! slide || ! slide.classList.contains( 'swiper-slide-active' ) ) {
				v.pause();
				v.currentTime = 0;
				if ( slide && slide.classList.contains( 'gsm-video-cinematic-intro' ) ) {
					v.style.transform = 'scale(1.25)';
					v.style.filter    = 'blur(12px)';
				} else {
					v.style.transform = '';
					v.style.filter    = '';
				}
			}
		} );

		var activeVideo = root.querySelector( '.swiper-slide-active .gsm-video' );
		if ( activeVideo ) {
			activeVideo.muted = true;
			activeVideo.style.transform = '';
			activeVideo.style.filter    = '';
			var playPromise = activeVideo.play();
			if ( playPromise !== undefined ) {
				playPromise.catch( function ( err ) {
					if ( typeof console !== 'undefined' && console.warn ) {
						console.warn( 'GSM Slider: Autoplay blocked.', err );
					}
					var wrap = activeVideo.closest( '.gsm-video-wrap' );
					if ( wrap ) {
						wrap.classList.add( 'gsm-autoplay-blocked' );
					}
				} );
			}
			updateToggleBtn( activeVideo );
		}
	}

	/**
	 * Book showcase layout sync (no-op — layout is now absolute-positioned via CSS).
	 * Kept as a stub because callers reference it.
	 */
	function syncGsmBookShowcaseLayout() {}

	/**
	 * Initialise thumbnails Swiper.
	 *
	 * @param {HTMLElement} wrapper The .gsm-slider-wrapper element.
	 * @returns {Swiper|null}
	 */
	function initThumbsSwiper( wrapper, s ) {
		// Use the unique ID-based element for reliable targeting
		// (falls back to class selector for backwards-compat).
		var sliderEl = wrapper.querySelector( '.gsm-slider' );
		var widgetId = sliderEl ? ( sliderEl.id.replace( 'gsm-slider-', '' ) ) : '';
		var thumbEl  = widgetId
			? document.getElementById( 'gsm-thumbs-' + widgetId )
			: wrapper.querySelector( '.gsm-thumbs-swiper' );

		if ( ! thumbEl ) {
			return null;
		}

		var gapValue = ( s && ( s.thumbGap || s.thumb_gap ) ) ? ( s.thumbGap || s.thumb_gap ) : 6;
		var gap = parseInt( gapValue, 10 );

		return new window.Swiper( thumbEl, {
			spaceBetween:        gap,
			slidesPerView:       'auto',
			watchSlidesProgress: true,
			freeMode:            true,
			a11y:                { enabled: false },
		} );
	}

	/**
	 * Initialise a single GSM Slider instance.
	 *
	 * @param {HTMLElement} wrapper The .gsm-slider-wrapper element.
	 */
	function initSingleSlider( wrapper ) {
		var sliderEl = wrapper.querySelector( '.gsm-slider' );
		if ( ! sliderEl ) {
			return;
		}
		var slider = sliderEl;

		// Parse settings.
		var rawSettings = sliderEl.getAttribute( 'data-settings' );
		var s = {};
		try {
			s = JSON.parse( rawSettings ) || {};
		} catch ( e ) {
			// Invalid JSON — use defaults.
		}

		// Get total count from real swiper slides.
		var slideEls  = sliderEl.querySelectorAll( '.swiper-slide.gsm-slide' );
		var totalReal = slideEls.length;

		// Prepare animated content nodes for staggered transitions.
		slideEls.forEach( function ( slideEl ) {
			var content = slideEl.querySelector( '.gsm-slide-content' );
			if ( ! content ) {
				return;
			}
			var anim = content.dataset.animation || 'fade-up';
			var hasDirectAnimChild = false;
			var ch = content.children;
			for ( var ci = 0; ci < ch.length; ci++ ) {
				if ( ch[ ci ].classList && ch[ ci ].classList.contains( 'gsm-anim' ) ) {
					hasDirectAnimChild = true;
					break;
				}
			}
			if ( ! hasDirectAnimChild ) {
				content.classList.add( 'gsm-anim' );
				content.classList.add( anim );
			}
		} );

		function animateSlide() {
			// Remove visible state from all animated elements.
			slider.querySelectorAll( '.gsm-anim' ).forEach( function ( el ) {
				el.classList.remove( 'is-visible', 'is-leaving' );
				void el.offsetWidth; // eslint-disable-line no-void
			} );

			// Animate active slide elements with individual delays.
			var activeAnims = slider.querySelectorAll( '.swiper-slide-active .gsm-anim' );
			activeAnims.forEach( function ( el ) {
				var delay = parseInt( el.dataset.gsmDelay || 0, 10 );
				if ( el.classList.contains( 'none' ) ) {
					el.style.transition = 'none';
					el.classList.add( 'is-visible' );
					return;
				}
				el.style.transition = '';
				setTimeout( function () {
					el.classList.add( 'is-visible' );
				}, delay );
			} );
		}

		var widgetIdSuffix = sliderEl.id ? sliderEl.id.replace( /^gsm-slider-/, '' ) : '';
		var liveEl         = widgetIdSuffix ? document.getElementById( 'gsm-live-' + widgetIdSuffix ) : null;
		var announcer      = liveEl || wrapper.querySelector( '[aria-live="polite"]' );
		var announceTpl    = wrapper.dataset.a11ySlideTemplate || '';

		// Lazy-load background images.
		initLazyBg( sliderEl );

		sliderEl.querySelectorAll( '.gsm-video-toggle' ).forEach( function ( btn ) {
			if ( btn.dataset.gsmSliderVideoBound ) {
				return;
			}
			btn.dataset.gsmSliderVideoBound = '1';
			btn.addEventListener( 'click', function () {
				var wrap = this.closest( '.gsm-video-wrap' );
				if ( ! wrap ) {
					return;
				}
				var video = wrap.querySelector( '.gsm-video' );
				if ( ! video ) {
					return;
				}
				if ( video.paused ) {
					wrap.classList.remove( 'gsm-autoplay-blocked' );
					video.play().catch( function () {} );
				} else {
					video.pause();
				}
				updateToggleBtn( video );
			} );
		} );

		sliderEl.querySelectorAll( '.gsm-video-mute' ).forEach( function ( btn ) {
			if ( btn.dataset.gsmSliderMuteBound ) {
				return;
			}
			btn.dataset.gsmSliderMuteBound = '1';
			btn.addEventListener( 'click', function () {
				var wrap = this.closest( '.gsm-video-wrap' );
				if ( ! wrap ) {
					return;
				}
				var video = wrap.querySelector( '.gsm-video' );
				if ( ! video ) {
					return;
				}
				video.muted = ! video.muted;
				updateToggleBtn( video );
			} );
		} );

		// Thumbnails — thumbnailNav is 'yes' (string) from PHP.
		var thumbsEnabled = ( s.thumbnailNav === 'yes' || s.thumbnail_nav === true || s.thumbnail_nav === 'yes' );
		var thumbsInstance = thumbsEnabled ? initThumbsSwiper( wrapper, s ) : null;

		// Build config and create Swiper.
		var config   = buildSwiperConfig( s, sliderEl, thumbsInstance );
		var prevOnInit = config.on && config.on.init;
		config.on      = config.on || {};
		config.on.init = function () {
			if ( typeof prevOnInit === 'function' ) {
				prevOnInit.apply( this, arguments );
			}
			if ( window.innerWidth <= 768 && s.arrows && ! ( s.mobileArrows || s.mobile_arrows ) ) {
				var prevBtn = sliderEl.querySelector( '.gsm-btn-prev' );
				var nextBtn = sliderEl.querySelector( '.gsm-btn-next' );
				if ( prevBtn ) {
					prevBtn.style.display = 'none';
				}
				if ( nextBtn ) {
					nextBtn.style.display = 'none';
				}
			}
			if ( window.innerWidth <= 768 && ! prefersReducedMotion() ) {
				sliderEl.classList.add( 'gsm-swipe-hint' );
			}
			manageVideos( sliderEl );
			syncGsmBookShowcaseLayout( sliderEl );
		};
		var swiper   = new window.Swiper( sliderEl, config );

		function refreshGsmBookShowcaseLayout() {
			syncGsmBookShowcaseLayout( sliderEl );
			if ( swiper && typeof swiper.update === 'function' ) {
				swiper.update();
			}
		}
		wrapper._gsmSwiperInstance = swiper;
		refreshGsmBookShowcaseLayout();
		window.requestAnimationFrame( refreshGsmBookShowcaseLayout );
		if ( typeof window.ResizeObserver !== 'undefined' ) {
			if ( wrapper.gsmBookLayoutRO && typeof wrapper.gsmBookLayoutRO.disconnect === 'function' ) {
				wrapper.gsmBookLayoutRO.disconnect();
			}
			wrapper.gsmBookLayoutRO = new window.ResizeObserver( refreshGsmBookShowcaseLayout );
			wrapper.gsmBookLayoutRO.observe( sliderEl );
		}
		swiper.on( 'slideChangeTransitionEnd', refreshGsmBookShowcaseLayout );

		if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			swiper.params.speed = 1;
			if ( swiper.autoplay && typeof swiper.autoplay.stop === 'function' ) {
				swiper.autoplay.stop();
			}
		}

		// ---- PARTICLE EFFECTS ----
		(function () {
			var particleCanvasEls = slider.querySelectorAll( '.gsm-particles-canvas' );
			if ( ! particleCanvasEls.length ) {
				return;
			}
			if ( prefersReducedMotion() ) {
				return;
			}

			var particleEntries = Array.prototype.slice.call( particleCanvasEls ).map( function ( canvas ) {
				var cfg = {};
				try {
					cfg = JSON.parse( canvas.dataset.particles || '{}' );
				} catch ( err ) {
					cfg = {};
				}
				canvas.style.width  = '100%';
				canvas.style.height = '100%';
				return { canvas: canvas, config: cfg, instance: null };
			} );

			function syncParticles() {
				var activeSlideEls = slider.querySelectorAll( '.swiper-slide-active' );
				var activeSet = new Set( Array.from( activeSlideEls ) );

				particleEntries.forEach( function ( entry ) {
					var slideEl = entry.canvas.closest( '.swiper-slide' );
					var shouldRun = slideEl && activeSet.has( slideEl );

					if ( shouldRun ) {
						if ( ! entry.instance ) {
							entry.instance = new GsmParticles( entry.canvas, entry.config );
							entry.instance.start();
						} else {
							entry.instance.start();
						}
					} else if ( entry.instance ) {
						// Destroy to avoid hidden canvases / RAF leaks when leaving.
						entry.instance.destroy();
						entry.instance = null;
					}
				} );
			}

			swiper.on( 'slideChangeTransitionEnd', function () {
				syncParticles();
			} );

			// First sync after Swiper init.
			if ( swiper.initialized ) {
				syncParticles();
			} else {
				swiper.on( 'init', function () {
					syncParticles();
				} );
			}
		})();

		// ---- COUNTDOWN TIMERS ----
		(function () {
			var countdownEntries = Array.prototype.slice.call( slider.querySelectorAll( '.gsm-countdown' ) ).map( function ( el ) {
				return { el: el, slideEl: el.closest( '.swiper-slide' ), instance: null };
			} );
			if ( ! countdownEntries.length ) {
				return;
			}

			function syncCountdowns() {
				var activeSlideEls = slider.querySelectorAll( '.swiper-slide-active' );
				var activeSet = new Set( Array.from( activeSlideEls ) );

				countdownEntries.forEach( function ( entry ) {
					var shouldRun = entry.slideEl && activeSet.has( entry.slideEl );
					if ( shouldRun ) {
						if ( ! entry.instance ) {
							entry.instance = new GsmCountdown( entry.el, swiper );
						}
						entry.instance.start();
					} else if ( entry.instance ) {
						entry.instance.stop();
					}
				} );
			}

			swiper.on( 'slideChangeTransitionEnd', function () {
				syncCountdowns();
			} );

			if ( swiper.initialized ) {
				syncCountdowns();
			} else {
				swiper.on( 'init', function () {
					syncCountdowns();
				} );
			}
		})();

		// ---- TYPING EFFECT ----
		slider.querySelectorAll( '.gsm-typing-text' ).forEach( function ( tel ) {
			var cfg = {};
			try {
				cfg = JSON.parse( tel.dataset.typing || '{}' );
			} catch ( err ) {
				cfg = {};
			}
			tel._gsmTyper = new GsmTyper( tel, cfg );
		} );

		function startTyperForActiveSlide() {
			if ( ! slider.querySelector( '.gsm-typing-text' ) ) {
				return;
			}
			var activeSlide = slider.querySelector( '.swiper-slide-active' );
			if ( ! activeSlide ) {
				return;
			}
			slider.querySelectorAll( '.gsm-typing-text' ).forEach( function ( tel ) {
				if ( tel._gsmTyper ) {
					tel._gsmTyper.stop();
				}
			} );
			var activeTyperEl = activeSlide.querySelector( '.gsm-typing-text' );
			if ( activeTyperEl && activeTyperEl._gsmTyper ) {
				window.setTimeout( function () {
					activeTyperEl._gsmTyper.start();
				}, 300 );
			}
		}

		// ---- 3D TILT EFFECT ----
		if ( gsmSliderTiltEnabled( s ) ) {
			var tiltIntensity = gsmSliderTiltIntensityVal( s );
			var tiltGlare     = s.tilt_glare !== false && s.tilt_glare !== 'no';

			if ( tiltGlare ) {
				slider.querySelectorAll( '.swiper-slide' ).forEach( function ( slide ) {
					if ( ! slide.querySelector( '.gsm-tilt-glare' ) ) {
						var glareEl = document.createElement( 'div' );
						glareEl.className = 'gsm-tilt-glare';
						slide.appendChild( glareEl );
					}
				} );
			}

			slider.addEventListener( 'mousemove', function ( e ) {
				var activeSlide = slider.querySelector( '.swiper-slide-active' );
				if ( ! activeSlide ) {
					return;
				}
				var rect    = slider.getBoundingClientRect();
				var centerX = rect.left + rect.width / 2;
				var centerY = rect.top + rect.height / 2;

				var mouseX = e.clientX - centerX;
				var mouseY = e.clientY - centerY;

				var rotateY = ( mouseX / ( rect.width / 2 ) ) * tiltIntensity;
				var rotateX = -( mouseY / ( rect.height / 2 ) ) * tiltIntensity;

				activeSlide.style.transform = 'perspective(1200px) rotateX(' + rotateX + 'deg) rotateY(' + rotateY + 'deg) scale3d(1.02, 1.02, 1.02)';
				activeSlide.style.transition = 'transform 0.1s ease';

				if ( tiltGlare ) {
					var glareNode = activeSlide.querySelector( '.gsm-tilt-glare' );
					if ( glareNode ) {
						var gx = ( ( e.clientX - rect.left ) / rect.width ) * 100;
						var gy = ( ( e.clientY - rect.top ) / rect.height ) * 100;
						glareNode.style.background = 'radial-gradient(circle at ' + gx + '% ' + gy + '%, rgba(255,255,255,0.18) 0%, rgba(255,255,255,0) 60%)';
						glareNode.style.opacity = '1';
					}
				}
			} );

			slider.addEventListener( 'mouseleave', function () {
				var activeSlide = slider.querySelector( '.swiper-slide-active' );
				if ( ! activeSlide ) {
					return;
				}
				activeSlide.style.transform = 'perspective(1200px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
				activeSlide.style.transition = 'transform 0.6s ease';
				if ( tiltGlare ) {
					var gn = activeSlide.querySelector( '.gsm-tilt-glare' );
					if ( gn ) {
						gn.style.opacity = '0';
					}
				}
			} );
		}

		// ---- LIGHTBOX ----
		var lbModal    = widgetIdSuffix ? document.getElementById( 'gsm-lightbox-' + widgetIdSuffix ) : null;
		var lbMedia    = lbModal ? lbModal.querySelector( '.gsm-lightbox-media' ) : null;
		var lbClose    = lbModal ? lbModal.querySelector( '.gsm-lightbox-close' ) : null;
		var lbBackdrop = lbModal ? lbModal.querySelector( '.gsm-lightbox-backdrop' ) : null;
		var lbPrevVideo = null;
		var lbPrevWasPlaying = false;
		var lbLastTrigger = null;
		var lbPrevFocus = null;
		var lbAriaHiddenRecords = [];

		function isYouTubeOrVimeo( url ) {
			return url.indexOf( 'youtube' ) !== -1 ||
				url.indexOf( 'youtu.be' ) !== -1 ||
				url.indexOf( 'vimeo' ) !== -1;
		}

		function getEmbedUrl( url ) {
			var ytMatch = url.match( /(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/ );
			if ( ytMatch ) {
				return 'https://www.youtube.com/embed/' + ytMatch[ 1 ] + '?autoplay=1&rel=0&modestbranding=1';
			}
			var vmMatch = url.match( /vimeo\.com\/(?:video\/)?(\d+)/ );
			if ( vmMatch ) {
				return 'https://player.vimeo.com/video/' + vmMatch[ 1 ] + '?autoplay=1';
			}
			return url;
		}

		function isLightboxOpen() {
			return !! ( lbModal && lbModal.style.display === 'flex' );
		}

		function applyAriaHiddenStrategy() {
			if ( ! lbModal ) {
				return;
			}
			lbAriaHiddenRecords = [];
			var bodyChildren = document.body ? document.body.children : [];
			for ( var i = 0; i < bodyChildren.length; i++ ) {
				var node = bodyChildren[ i ];
				if ( node === lbModal ) {
					continue;
				}
				lbAriaHiddenRecords.push( {
					node: node,
					prev: node.getAttribute( 'aria-hidden' ),
				} );
				node.setAttribute( 'aria-hidden', 'true' );
			}
			lbModal.setAttribute( 'aria-hidden', 'false' );
		}

		function restoreAriaHiddenStrategy() {
			if ( ! lbAriaHiddenRecords.length ) {
				return;
			}
			lbAriaHiddenRecords.forEach( function ( rec ) {
				if ( ! rec || ! rec.node ) {
					return;
				}
				if ( rec.prev === null ) {
					rec.node.removeAttribute( 'aria-hidden' );
				} else {
					rec.node.setAttribute( 'aria-hidden', rec.prev );
				}
			} );
			lbAriaHiddenRecords = [];
			if ( lbModal ) {
				lbModal.removeAttribute( 'aria-hidden' );
			}
		}

		function openLightbox( videoUrl ) {
			if ( ! lbModal || ! lbMedia || ! videoUrl ) {
				return;
			}

			// Keep current slide video state to restore on close.
			lbPrevVideo = sliderEl.querySelector( '.swiper-slide-active .gsm-video' );
			lbPrevWasPlaying = !! ( lbPrevVideo && ! lbPrevVideo.paused );
			if ( lbPrevVideo ) {
				lbPrevVideo.pause();
				updateToggleBtn( lbPrevVideo );
			}

			lbMedia.innerHTML = '';

			if ( isYouTubeOrVimeo( videoUrl ) ) {
				var embedUrl = getEmbedUrl( videoUrl );
				var iframe   = document.createElement( 'iframe' );
				iframe.src             = embedUrl;
				iframe.allow           = 'autoplay; fullscreen; picture-in-picture';
				iframe.allowFullscreen = true;
				iframe.className       = 'gsm-lightbox-iframe';
				lbMedia.appendChild( iframe );
			} else {
				var video         = document.createElement( 'video' );
				video.src         = videoUrl;
				video.controls    = true;
				video.autoplay    = true;
				video.className   = 'gsm-lightbox-video';
				video.playsInline = true;
				lbMedia.appendChild( video );
			}

			lbModal.style.display = 'flex';
			document.body.classList.add( 'gsm-lightbox-open' );
			applyAriaHiddenStrategy();
			lbPrevFocus = document.activeElement;
			if ( lbClose ) {
				lbClose.focus();
			}

			if ( swiper.autoplay ) {
				swiper.autoplay.stop();
			}
		}

		function closeLightbox() {
			if ( ! lbModal || ! lbMedia ) {
				return;
			}
			lbMedia.innerHTML = '';
			lbModal.style.display = 'none';
			document.body.classList.remove( 'gsm-lightbox-open' );
			restoreAriaHiddenStrategy();

			// Restore previous slide video if it was playing before opening lightbox.
			if ( lbPrevVideo && lbPrevWasPlaying ) {
				lbPrevVideo.play().catch( function () {} );
				updateToggleBtn( lbPrevVideo );
			}
			lbPrevVideo = null;
			lbPrevWasPlaying = false;

			if ( s.autoplay && swiper.autoplay ) {
				swiper.autoplay.start();
			}

			if ( lbLastTrigger && typeof lbLastTrigger.focus === 'function' ) {
				lbLastTrigger.focus();
			} else if ( lbPrevFocus && typeof lbPrevFocus.focus === 'function' ) {
				lbPrevFocus.focus();
			}
			lbPrevFocus = null;
		}

		slider.querySelectorAll( '.gsm-lightbox-trigger' ).forEach( function ( trigger ) {
			if ( trigger.dataset.gsmSliderLbTriggerBound ) {
				return;
			}
			trigger.dataset.gsmSliderLbTriggerBound = '1';
			trigger.addEventListener( 'click', function () {
				lbLastTrigger = this;
				openLightbox( this.dataset.lightboxUrl );
			} );
			trigger.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					lbLastTrigger = this;
					openLightbox( this.dataset.lightboxUrl );
				}
			} );
		} );

		if ( lbClose && ! lbClose.dataset.gsmSliderLbBound ) {
			lbClose.dataset.gsmSliderLbBound = '1';
			lbClose.addEventListener( 'click', closeLightbox );
		}
		if ( lbBackdrop && ! lbBackdrop.dataset.gsmSliderLbBound ) {
			lbBackdrop.dataset.gsmSliderLbBound = '1';
			lbBackdrop.addEventListener( 'click', closeLightbox );
		}
		if ( lbModal && ! lbModal.dataset.gsmSliderEscBound ) {
			lbModal.dataset.gsmSliderEscBound = '1';
			document.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Escape' && lbModal.style.display === 'flex' ) {
					closeLightbox();
				}
			} );
			lbModal.addEventListener( 'keydown', function ( e ) {
				if ( e.key !== 'Tab' || ! isLightboxOpen() ) {
					return;
				}
				var focusables = lbModal.querySelectorAll( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
				if ( ! focusables.length ) {
					return;
				}
				var first = focusables[ 0 ];
				var last  = focusables[ focusables.length - 1 ];
				if ( e.shiftKey && document.activeElement === first ) {
					e.preventDefault();
					last.focus();
				} else if ( ! e.shiftKey && document.activeElement === last ) {
					e.preventDefault();
					first.focus();
				}
			} );
		}

		sliderEl.querySelectorAll( '.gsm-btn-prev, .gsm-btn-next' ).forEach( function ( btn ) {
			btn.setAttribute( 'type', 'button' );
		} );

		if ( sliderEl.querySelector( '.gsm-pagination[role="tablist"]' ) ) {
			window.requestAnimationFrame( function () {
				sliderEl.querySelectorAll( '.gsm-pagination .swiper-pagination-bullet' ).forEach( function ( bullet ) {
					bullet.setAttribute( 'role', 'tab' );
				} );
			} );
		}

		// Autoplay progress bar.
		var progressBar = wrapper.querySelector( '.gsm-progress-bar' );
		if ( s.autoplay && s.showProgress && progressBar && ! prefersReducedMotion() ) {
			swiper.on( 'autoplayTimeLeft', function ( swiperInstance, time, progress ) {
				progressBar.style.width = Math.max( 0, Math.min( 100, ( 1 - progress ) * 100 ) ) + '%';
			} );
		}

		// Run initial animation and handle effects on the first slide.
		var firstSlide = sliderEl.querySelector( '.swiper-slide-active' );
		if ( firstSlide ) {
			if ( s.scroll_trigger ) {
				slider.querySelectorAll( '.gsm-anim' ).forEach( function ( el ) {
					el.classList.remove( 'is-visible' );
				} );
				var hasTriggered = false;
				if ( 'IntersectionObserver' in window ) {
					var scrollObserver = new IntersectionObserver( function ( entries ) {
						entries.forEach( function ( entry ) {
							if ( entry.isIntersecting && ! hasTriggered ) {
								hasTriggered = true;
								animateSlide();
								startTyperForActiveSlide();
								scrollObserver.unobserve( slider );
							}
						} );
					}, {
						threshold: 0.2,
						rootMargin: '0px 0px -50px 0px',
					} );
					scrollObserver.observe( slider );
				} else {
					animateSlide();
					startTyperForActiveSlide();
				}
			} else {
				animateSlide();
				startTyperForActiveSlide();
			}
			handleSlideEffects( firstSlide );
		}

		// Announce first slide.
		announceSlide( announcer, 0, totalReal, announceTpl );

		swiper.on( 'slideChangeTransitionStart', function () {
			if ( gsmSliderTiltEnabled( s ) ) {
				var tgGlare = s.tilt_glare !== false && s.tilt_glare !== 'no';
				slider.querySelectorAll( '.swiper-slide' ).forEach( function ( sl ) {
					sl.style.transform = '';
					sl.style.transition = 'transform 0.6s ease';
					if ( tgGlare ) {
						var gl = sl.querySelector( '.gsm-tilt-glare' );
						if ( gl ) {
							gl.style.opacity = '0';
						}
					}
				} );
			}
			var leavingSlide = null;
			if ( typeof swiper.previousIndex === 'number' && swiper.slides && swiper.slides[ swiper.previousIndex ] ) {
				leavingSlide = swiper.slides[ swiper.previousIndex ];
			}
			if ( ! leavingSlide ) {
				leavingSlide = slider.querySelector( '.swiper-slide-active' );
			}
			if ( ! leavingSlide ) {
				return;
			}
			leavingSlide.querySelectorAll( '.gsm-anim.is-visible' ).forEach( function ( el ) {
				var exitClass = el.dataset.gsmExit || 'fade-out-up';
				if ( exitClass === 'none' ) {
					return;
				}
				el.classList.add( 'is-exiting', exitClass + '--exit' );
				el.classList.remove( 'is-visible' );
			} );
		} );

		swiper.on( 'slideChangeTransitionEnd', function () {
			slider.querySelectorAll( '.is-exiting' ).forEach( function ( el ) {
				el.className = el.className.replace( /\S+--exit/g, '' ).replace( 'is-exiting', '' ).trim();
			} );
			animateSlide();
			startTyperForActiveSlide();
			manageVideos( sliderEl );
		} );

		// On slide change: effects, video, aria-live.
		swiper.on( 'slideChange', function () {
			var activeSlide = sliderEl.querySelector( '.swiper-slide-active' );
			if ( activeSlide ) {
				handleSlideEffects( activeSlide );
			}
			manageVideos( sliderEl );
			announceSlide( announcer, swiper.realIndex, totalReal, announceTpl );
		} );

		sliderEl.addEventListener( 'keydown', function ( e ) {
			if ( isLightboxOpen() ) {
				return;
			}
			var loop = !! swiper.params.loop;
			var mwNav = s.mousewheel_enable === true || s.mousewheel_enable === 'yes' || s.mousewheel_enable === 1;
			switch ( e.key ) {
				case 'ArrowRight':
					e.preventDefault();
					swiper.slideNext();
					break;
				case 'ArrowDown':
					if ( mwNav && swiper.isEnd && ! loop ) {
						break;
					}
					e.preventDefault();
					swiper.slideNext();
					break;
				case 'ArrowLeft':
					e.preventDefault();
					swiper.slidePrev();
					break;
				case 'ArrowUp':
					if ( mwNav && swiper.isBeginning && ! loop ) {
						break;
					}
					e.preventDefault();
					swiper.slidePrev();
					break;
				case 'Home':
					e.preventDefault();
					if ( loop && typeof swiper.slideToLoop === 'function' ) {
						swiper.slideToLoop( 0 );
					} else {
						swiper.slideTo( 0 );
					}
					break;
				case 'End':
					e.preventDefault();
					if ( loop && typeof swiper.slideToLoop === 'function' ) {
						swiper.slideToLoop( Math.max( 0, totalReal - 1 ) );
					} else {
						swiper.slideTo( Math.max( 0, totalReal - 1 ) );
					}
					break;
				default:
					break;
			}
		} );

		// ---- MOUSEWHEEL NAVIGATION (fine pointer only; touch-primary devices use Swiper swipe) ----
		if ( s.mousewheel_enable && ! gsmSliderIsCoarsePointerOnly() ) {
			var indicatorElMw = widgetIdSuffix ? document.getElementById( 'gsm-scroll-indicator-' + widgetIdSuffix ) : null;
			var progressElMw = widgetIdSuffix ? document.getElementById( 'gsm-scroll-progress-' + widgetIdSuffix ) : null;
			var progressFillMw = progressElMw ? progressElMw.querySelector( '.gsm-scroll-progress-fill' ) : null;
			var totalSlidesMw = totalReal;

			function mwUpdateProgress() {
				if ( ! progressFillMw || totalSlidesMw < 1 ) {
					return;
				}
				if ( totalSlidesMw <= 1 ) {
					progressFillMw.style.height = '100%';
					return;
				}
				var progressPct = ( swiper.realIndex / ( totalSlidesMw - 1 ) ) * 100;
				progressFillMw.style.height = Math.max( 0, Math.min( 100, progressPct ) ) + '%';
			}

			if ( indicatorElMw ) {
				indicatorElMw.classList.add( 'gsm-scroll-indicator--visible' );
				var hideIndicatorTimer = window.setTimeout( function () {
					indicatorElMw.classList.remove( 'gsm-scroll-indicator--visible' );
				}, 4000 );
				swiper.on( 'slideChange', function () {
					window.clearTimeout( hideIndicatorTimer );
					indicatorElMw.classList.remove( 'gsm-scroll-indicator--visible' );
				} );
			}

			if ( progressElMw ) {
				swiper.on( 'slideChange', mwUpdateProgress );
				swiper.on( 'slideChangeTransitionEnd', mwUpdateProgress );
				mwUpdateProgress();
			}

			var isMwInViewport = false;
			var mwViewportObserver = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					isMwInViewport = entry.isIntersecting;
				} );
			}, { threshold: 0.5 } );
			mwViewportObserver.observe( sliderEl );

			sliderEl.addEventListener( 'wheel', function ( e ) {
				if ( ! isMwInViewport ) {
					return;
				}
				var atFirstMw = swiper.isBeginning;
				var atLastMw = swiper.isEnd;
				if ( ( atFirstMw && e.deltaY < 0 ) || ( atLastMw && e.deltaY > 0 ) ) {
					return;
				}
				e.preventDefault();
			}, { passive: false } );

			if ( progressElMw ) {
				progressElMw.addEventListener( 'click', function ( e ) {
					var rect = progressElMw.getBoundingClientRect();
					var clickY = e.clientY - rect.top;
					var ratio = rect.height ? clickY / rect.height : 0;
					var slideIdxMw = Math.round( ratio * Math.max( 0, totalSlidesMw - 1 ) );
					if ( swiper.params.loop && typeof swiper.slideToLoop === 'function' ) {
						swiper.slideToLoop( slideIdxMw );
					} else {
						swiper.slideTo( slideIdxMw );
					}
				} );
				progressElMw.style.cursor = 'pointer';
				progressElMw.setAttribute( 'title', 'Click to navigate' );
			}

			document.addEventListener( 'keydown', function ( e ) {
				if ( ! isMwInViewport || e.defaultPrevented ) {
					return;
				}
				var aeMw = document.activeElement;
				if ( aeMw ) {
					var tnMw = aeMw.tagName;
					if ( tnMw === 'INPUT' || tnMw === 'TEXTAREA' || tnMw === 'SELECT' || aeMw.isContentEditable ) {
						return;
					}
				}
				if ( e.key === 'ArrowDown' || e.key === 'PageDown' ) {
					if ( ! swiper.isEnd ) {
						e.preventDefault();
						swiper.slideNext();
					}
				} else if ( e.key === 'ArrowUp' || e.key === 'PageUp' ) {
					if ( ! swiper.isBeginning ) {
						e.preventDefault();
						swiper.slidePrev();
					}
				}
			} );

			var touchStartXMw = 0;
			var touchStartYMw = 0;
			sliderEl.addEventListener( 'touchstart', function ( e ) {
				if ( ! e.touches || ! e.touches[ 0 ] ) {
					return;
				}
				touchStartXMw = e.touches[ 0 ].clientX;
				touchStartYMw = e.touches[ 0 ].clientY;
			}, { passive: true } );

			sliderEl.addEventListener( 'touchmove', function ( e ) {
				if ( ! e.touches || ! e.touches[ 0 ] ) {
					return;
				}
				var dxMw = e.touches[ 0 ].clientX - touchStartXMw;
				var dyMw = e.touches[ 0 ].clientY - touchStartYMw;
				if ( Math.abs( dyMw ) > Math.abs( dxMw ) && Math.abs( dyMw ) > 30 ) {
					if ( dyMw < 0 && ! swiper.isEnd ) {
						e.preventDefault();
						swiper.slideNext();
					}
					if ( dyMw > 0 && ! swiper.isBeginning ) {
						e.preventDefault();
						swiper.slidePrev();
					}
				}
			}, { passive: false } );
		}

		if ( swiper.autoplay && typeof swiper.autoplay.stop === 'function' && ! prefersReducedMotion() ) {
			sliderEl.addEventListener( 'mouseenter', function () {
				swiper.autoplay && swiper.autoplay.stop();
			} );
			sliderEl.addEventListener( 'mouseleave', function () {
				swiper.autoplay && swiper.autoplay.start();
			} );
			sliderEl.addEventListener( 'focusin', function () {
				swiper.autoplay && swiper.autoplay.stop();
			} );
			sliderEl.addEventListener( 'focusout', function ( ev ) {
				var nextFocus = ev.relatedTarget;
				if ( nextFocus && sliderEl.contains( nextFocus ) ) {
					return;
				}
				swiper.autoplay && swiper.autoplay.start();
			} );
		}

	}

	/**
	 * Elementor frontend hook — called once per widget instance render.
	 *
	 * @param {jQuery} $scope The Elementor widget jQuery element.
	 */
	function initGsmSliderWidget( $scope ) {
		// $scope is a jQuery object; get native element.
		var widgetEl = $scope[ 0 ] || $scope;
		var wrappers = widgetEl.querySelectorAll( '.gsm-slider-wrapper' );
		wrappers.forEach( function ( wrapper ) {
			initSingleSlider( wrapper );
		} );
	}

	/**
	 * Register the widget hook with Elementor frontend.
	 * Uses typeof guards to avoid "Cannot read properties of undefined" errors
	 * when this script executes before elementorFrontend is initialised.
	 */
	function registerElementorHook() {
		if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks ) {
			elementorFrontend.hooks.addAction(
				'frontend/element_ready/gsm_slider.default',
				initGsmSliderWidget
			);
			return;
		}

		// Elementor fires this event once its frontend object is ready.
		window.addEventListener( 'elementor/frontend/init', function () {
			if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks ) {
				elementorFrontend.hooks.addAction(
					'frontend/element_ready/gsm_slider.default',
					initGsmSliderWidget
				);
			}
		} );
	}

	// Tier 1: elementorFrontend is already available (editor preview or cached page).
	if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks ) {
		registerElementorHook();
	} else {
		// Tier 2: wait for DOM — elementorFrontend may load after this script.
		document.addEventListener( 'DOMContentLoaded', function () {
			if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks ) {
				registerElementorHook();
			} else {
				// Tier 3: listen for Elementor's own init event.
				window.addEventListener( 'elementor/frontend/init', registerElementorHook );

				// Final fallback: direct init for non-Elementor contexts or static cached pages.
				document.querySelectorAll( '.gsm-slider-wrapper' ).forEach( function ( wrapper ) {
					if ( typeof Swiper !== 'undefined' ) {
						initSingleSlider( wrapper );
					}
				} );
			}
		} );
	}

	// Expose for Elementor editor template live preview (second IIFE has no closure access).
	window.GsmSliderGsmTyper              = GsmTyper;
	window.GsmSliderGsmParticles        = GsmParticles;
	window.GsmSliderSyncBookShowcaseLayout = syncGsmBookShowcaseLayout;

} )();

( function () {
	'use strict';

	// Only run in Elementor editor.
	if ( typeof elementor === 'undefined' ) {
		return;
	}
	if ( typeof gsmTemplates === 'undefined' ) {
		return;
	}
	// Avoid duplicate UI if the bundle is ever loaded twice in the same window.
	if ( window.__GSM_SLIDER_TEMPLATES_UI_BOUND__ ) {
		return;
	}
	window.__GSM_SLIDER_TEMPLATES_UI_BOUND__ = true;

	var modal = null;
	var currentId = null;
	var currentWidgetId = '';
	var gsmSliderTplImportInFlight = false;
	var GSM_SLIDER_DEBUG = true;

	var previewModal     = null;
	var previewSwiper    = null;
	var previewTypers    = {};
	var previewParticles = {};

	function dbg() {
		if ( ! GSM_SLIDER_DEBUG || typeof console === 'undefined' || ! console.log ) {
			return;
		}
		var args = Array.prototype.slice.call( arguments );
		args.unshift( '[GSM Slider Templates]' );
		console.log.apply( console, args );
	}

	/**
	 * Parse WordPress admin-ajax JSON; recover when plugins/themes output notices before the payload.
	 *
	 * @param {string} text
	 * @param {string} label Debug label.
	 * @returns {object}
	 */
	function parseWpAjaxJson( text, label ) {
		label = label || 'ajax';
		if ( text == null || typeof text !== 'string' ) {
			throw new TypeError( 'GSM empty response' );
		}
		var trimmed = text.trim();
		try {
			return JSON.parse( trimmed );
		} catch ( e ) {
			var start = trimmed.indexOf( '{"success"' );
			if ( start === -1 ) {
				start = trimmed.indexOf( '{' );
			}
			if ( start >= 0 ) {
				var recovered = JSON.parse( trimmed.slice( start ) );
				dbg( 'parseWpAjaxJson: recovered JSON (' + label + ')' );
				return recovered;
			}
			throw e;
		}
	}

	function escapePreviewHtml( str ) {
		if ( str == null ) {
			return '';
		}
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	/**
	 * Book showcase markup for template modal preview (mirrors PHP book layout).
	 */
	function previewBookLayoutMarkup( slide, settings ) {
		var ratioStr = slide.book_content_ratio || '50-50';
		var rp = String( ratioStr ).split( '-' );
		var lw = parseInt( rp[ 0 ], 10 ) || 50;
		var rw = parseInt( rp[ 1 ], 10 ) || 50;
		var flexL = lw + ' 1 0%';
		var flexR = rw + ' 1 0%';
		var maxW = ( slide.content_max_width && slide.content_max_width.size ) ? slide.content_max_width.size + ( slide.content_max_width.unit || 'px' ) : '560px';
		var vAlign = slide.content_vertical || 'middle';
		var valignMap = { top: 'flex-start', middle: 'center', bottom: 'flex-end' };
		var valignCss = valignMap[ vAlign ] || 'center';
		var cardBg = slide.book_card_bg != null ? slide.book_card_bg : 'rgba(255,255,255,0.08)';
		var titleColor = slide.book_title_color || '#fff';
		var showTitle = slide.book_show_title === 'yes';
		var doFloat = slide.book_float_animation === 'yes';
		var out = '';
		out += '<div class="gsm-book-layout">';
		out += '<div class="gsm-book-layout-inner">';
		out += '<div class="gsm-book-content" style="flex:' + flexL + ';min-width:0;display:flex;align-items:' + valignCss + ';">';
		out += '<div class="gsm-book-content-inner" style="max-width:' + escapePreviewHtml( maxW ) + ';width:100%;">';
		if ( slide.badge_text ) {
			out += '<span class="gsm-badge-inline gsm-anim fade-up" data-gsm-delay="0" style="background:' + escapePreviewHtml( slide.badge_color || '#6c63ff' ) + ';display:inline-block;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:#fff;margin-bottom:16px;">' + escapePreviewHtml( slide.badge_text ) + '</span>';
		}
		var hl = ( slide.book_heading_highlight || '' ).trim();
		var rawTag = String( slide.heading_tag || 'h2' ).toLowerCase().replace( /[^a-z0-9]/g, '' );
		var allowedHeadings = { h1: true, h2: true, h3: true, h4: true, h5: true, h6: true };
		var hTag = allowedHeadings[ rawTag ] ? rawTag : 'h2';
		if ( slide.heading ) {
			if ( slide.typing_effect === 'yes' ) {
				var typingStrings = slide.typing_strings;
				if ( Array.isArray( typingStrings ) ) {
					typingStrings = typingStrings.filter( Boolean );
				} else if ( typeof typingStrings === 'string' ) {
					typingStrings = typingStrings.split( '\n' ).filter( Boolean );
				} else {
					typingStrings = String( slide.heading || '' ).split( '\n' ).filter( Boolean );
				}
				if ( ! typingStrings.length ) {
					typingStrings = [ String( slide.heading ) ];
				}
				var typingPayload = {
					strings: typingStrings,
					typeSpeed: parseInt( slide.typing_speed, 10 ) || 80,
					deleteSpeed: parseInt( slide.typing_delete_speed, 10 ) || 40,
					pauseTime: parseInt( slide.typing_pause, 10 ) || 1800,
				};
				out += '<' + hTag + ' class="gsm-anim gsm-anim--heading gsm-typing-wrap fade-up" style="color:#fff;margin:0 0 16px;line-height:1.2;" data-gsm-delay="0"><span class="gsm-typing-text" data-typing="' + escapePreviewHtml( JSON.stringify( typingPayload ) ) + '"></span><span class="gsm-typing-cursor" aria-hidden="true">|</span></' + hTag + '>';
			} else if ( hl && slide.heading.indexOf( hl ) !== -1 ) {
				var ix = slide.heading.indexOf( hl );
				var beforeH = slide.heading.slice( 0, ix );
				var afterH = slide.heading.slice( ix + hl.length );
				out += '<' + hTag + ' class="gsm-anim gsm-anim--heading gsm-heading gsm-book-heading fade-up" data-gsm-delay="0" style="color:#fff;margin:0 0 16px;line-height:1.2;">';
				out += escapePreviewHtml( beforeH );
				out += '<span class="gsm-book-heading-accent">' + escapePreviewHtml( hl ) + '</span>';
				out += escapePreviewHtml( afterH );
				out += '</' + hTag + '>';
			} else {
				out += '<' + hTag + ' class="gsm-anim gsm-anim--heading gsm-heading gsm-book-heading fade-up" data-gsm-delay="0" style="color:#fff;margin:0 0 16px;line-height:1.2;">' + escapePreviewHtml( slide.heading ) + '</' + hTag + '>';
			}
		}
		if ( slide.description ) {
			out += '<p class="gsm-anim gsm-anim--text fade-up" style="color:rgba(255,255,255,0.82);line-height:1.65;margin:0 0 24px;" data-gsm-delay="150">' + escapePreviewHtml( slide.description ) + '</p>';
		}
		var btnBg = settings.btn_bg || '#4a5d32';
		var btnColor = settings.btn_color || '#ffffff';
		var btnRad = ( settings.btn_border_radius && settings.btn_border_radius.size ) ? settings.btn_border_radius.size + 'px' : '6px';
		var hasBtn1 = slide.btn_text && slide.btn_url && slide.btn_url.url;
		var b2u = slide.book_btn2_url || {};
		var hasBtn2 = slide.book_btn2_text && b2u.url;
		if ( hasBtn1 || hasBtn2 ) {
			out += '<div class="gsm-book-btn-row gsm-btn-wrap">';
			if ( hasBtn1 ) {
				out += '<span class="gsm-btn gsm-book-btn-primary gsm-anim gsm-anim--btn fade-up" data-gsm-delay="300" style="display:inline-block;padding:11px 22px;border-radius:' + escapePreviewHtml( btnRad ) + ';background:' + escapePreviewHtml( btnBg ) + ';color:' + escapePreviewHtml( btnColor ) + ';font-weight:600;font-size:14px;cursor:default;">' + escapePreviewHtml( slide.btn_text ) + '</span>';
			}
			if ( hasBtn2 ) {
				out += '<span class="gsm-btn gsm-btn--ghost gsm-book-btn-secondary gsm-anim gsm-anim--btn fade-up" data-gsm-delay="370" style="display:inline-block;padding:11px 22px;border-radius:' + escapePreviewHtml( btnRad ) + ';cursor:default;">' + escapePreviewHtml( slide.book_btn2_text ) + '</span>';
			}
			out += '</div>';
		}
		out += '</div></div>';
		out += '<div class="gsm-book-grid-wrap" style="flex:' + flexR + ';min-width:0;display:flex;align-items:center;justify-content:center;">';
		out += '<div class="gsm-book-grid">';
		var bi;
		for ( bi = 0; bi < 4; bi++ ) {
			var bk = bi + 1;
			var imgOb = slide[ 'book' + bk + '_image' ] || {};
			var url = imgOb.url || '';
			var ttl = slide[ 'book' + bk + '_title' ] || '';
			var floatCls = doFloat ? ' gsm-book-float gsm-book-float--' + ( bi % 2 === 0 ? 'a' : 'b' ) : '';
			var delay = 100 + bi * 120;
			out += '<div class="gsm-book-card gsm-anim fade-up gsm-book-card--tilt-' + bi + '" data-gsm-delay="' + delay + '" style="background:' + escapePreviewHtml( cardBg ) + ';border-radius:10px;padding:8px;box-sizing:border-box;">';
			out += '<div class="gsm-book-inner' + floatCls + '">';
			if ( url ) {
				out += '<div class="gsm-book-cover-wrap"><img class="gsm-book-cover" src="' + escapePreviewHtml( url ) + '" alt="' + escapePreviewHtml( ttl ) + '">';
				out += '<div class="gsm-book-spine" aria-hidden="true"></div><div class="gsm-book-shine" aria-hidden="true"></div></div>';
			} else {
				out += '<div class="gsm-book-cover-wrap gsm-book-placeholder"><div class="gsm-book-cover gsm-book-cover--placeholder" aria-hidden="true"></div></div>';
			}
			if ( showTitle && ttl ) {
				out += '<div class="gsm-book-title" style="color:' + escapePreviewHtml( titleColor ) + ';">' + escapePreviewHtml( ttl ) + '</div>';
			}
			out += '</div></div>';
		}
		out += '</div></div></div></div>';
		return out;
	}

	function closePreview() {
		if ( previewSwiper && typeof previewSwiper.destroy === 'function' ) {
			previewSwiper.destroy( true, true );
			previewSwiper = null;
		}
		Object.keys( previewTypers ).forEach( function ( k ) {
			var t = previewTypers[ k ];
			if ( t && typeof t.stop === 'function' ) {
				t.stop();
			}
		} );
		previewTypers = {};
		Object.keys( previewParticles ).forEach( function ( k ) {
			var p = previewParticles[ k ];
			if ( p && typeof p.destroy === 'function' ) {
				p.destroy();
			}
		} );
		previewParticles = {};
		if ( previewModal ) {
			previewModal.classList.remove( 'open' );
		}
	}

	function buildPreviewModal() {
		if ( previewModal ) {
			return;
		}
		previewModal = document.createElement( 'div' );
		previewModal.id = 'gsm-preview-modal';
		previewModal.innerHTML = [
			'<div class="gsm-preview-backdrop"></div>',
			'<div class="gsm-preview-dialog">',
			'  <div class="gsm-preview-header">',
			'    <span class="gsm-preview-title"></span>',
			'    <div class="gsm-preview-header-actions">',
			'      <button type="button" class="gsm-preview-import-btn">' + escapePreviewHtml( gsmSliderTplI18n( 'importThisTemplate', 'Import This Template' ) ) + '</button>',
			'      <button type="button" class="gsm-preview-close" aria-label="' + escapePreviewHtml( gsmSliderTplI18n( 'close', 'Close' ) ) + '">&times;</button>',
			'    </div>',
			'  </div>',
			'  <div class="gsm-preview-stage">',
			'    <div class="gsm-preview-slider-wrap">',
			'      <div id="gsm-preview-slider" class="swiper gsm-slider gsm-preview-instance">',
			'        <div class="swiper-wrapper" id="gsm-preview-wrapper"></div>',
			'        <div class="swiper-pagination gsm-preview-pagination"></div>',
			'        <div class="swiper-button-prev gsm-preview-arrow gsm-arrow"><span class="gsm-arrow-icon">&#8249;</span></div>',
			'        <div class="swiper-button-next gsm-preview-arrow gsm-arrow"><span class="gsm-arrow-icon">&#8250;</span></div>',
			'      </div>',
			'    </div>',
			'  </div>',
			'  <div class="gsm-preview-info">',
			'    <div class="gsm-preview-info-inner">',
			'      <span class="gsm-preview-tag" id="gsm-preview-effect-tag"></span>',
			'      <span class="gsm-preview-tag" id="gsm-preview-slides-tag"></span>',
			'      <span class="gsm-preview-tag" id="gsm-preview-source-tag"></span>',
			'    </div>',
			'  </div>',
			'</div>',
		].join( '' );

		document.body.appendChild( previewModal );

		previewModal.querySelector( '.gsm-preview-backdrop' ).addEventListener( 'click', closePreview );
		previewModal.querySelector( '.gsm-preview-close' ).addEventListener( 'click', closePreview );
		previewModal.querySelector( '.gsm-preview-import-btn' ).addEventListener( 'click', function () {
			if ( currentId ) {
				importTemplate( currentId );
			}
			closePreview();
		} );
	}

	function openPreview( templateId ) {
		buildPreviewModal();

		currentId = templateId;
		if ( modal ) {
			modal.querySelectorAll( '.gsm-tpl-card' ).forEach( function ( c ) {
				c.classList.toggle( 'selected', c.dataset.id === templateId );
			} );
			var imp = modal.querySelector( '.gsm-tpl-import' );
			if ( imp ) {
				imp.disabled = false;
			}
		}

		var fd = new FormData();
		fd.append( 'action', 'gsm_get_template' );
		fd.append( 'nonce', gsmTemplates.nonce );
		fd.append( 'template_id', templateId );

		fetch( gsmTemplates.ajaxUrl, { method: 'POST', body: fd } )
			.then( function ( r ) {
				return r.text();
			} )
			.then( function ( text ) {
				var res;
				try {
					res = parseWpAjaxJson( text, 'preview' );
				} catch ( err ) {
					dbg( 'openPreview JSON error', err );
					showGsmSliderTplNotice( gsmSliderTplI18n( 'previewFailed', 'Could not load template preview.' ), true );
					return;
				}
				if ( ! res || ! ( res.success === true || res.success === 1 ) || ! res.data || typeof res.data !== 'object' ) {
					showGsmSliderTplNotice( gsmSliderTplI18n( 'previewFailed', 'Could not load template preview.' ), true );
					return;
				}
				renderPreview( templateId, res.data );
				if ( previewModal ) {
					previewModal.classList.add( 'open' );
				}
			} )
			.catch( function ( err ) {
				dbg( 'openPreview fetch', err );
				showGsmSliderTplNotice( gsmSliderTplI18n( 'previewNetwork', 'Network error loading preview.' ), true );
			} );
	}

	function renderPreview( templateId, settings ) {
		var wrapper   = document.getElementById( 'gsm-preview-wrapper' );
		var titleEl   = previewModal.querySelector( '.gsm-preview-title' );
		var effectTag = document.getElementById( 'gsm-preview-effect-tag' );
		var slidesTag = document.getElementById( 'gsm-preview-slides-tag' );
		var sourceTag = document.getElementById( 'gsm-preview-source-tag' );

		if ( previewSwiper && typeof previewSwiper.destroy === 'function' ) {
			previewSwiper.destroy( true, true );
			previewSwiper = null;
		}
		Object.keys( previewTypers ).forEach( function ( k ) {
			var t = previewTypers[ k ];
			if ( t && typeof t.stop === 'function' ) {
				t.stop();
			}
		} );
		previewTypers = {};
		Object.keys( previewParticles ).forEach( function ( k ) {
			var p = previewParticles[ k ];
			if ( p && typeof p.destroy === 'function' ) {
				p.destroy();
			}
		} );
		previewParticles = {};

		if ( ! wrapper ) {
			return;
		}

		var tplTitle = templateId;
		if ( modal ) {
			modal.querySelectorAll( '.gsm-tpl-card' ).forEach( function ( c ) {
				if ( c.dataset.id === templateId ) {
					var st = c.querySelector( 'strong' );
					if ( st ) {
						tplTitle = st.textContent || templateId;
					}
				}
			} );
		}
		if ( titleEl ) {
			titleEl.textContent = tplTitle;
		}

		var slides = settings.slides_data || [];
		var effect = settings.effect || 'fade';
		var source = settings.content_source || 'custom';

		if ( effectTag ) {
			effectTag.textContent = 'Effect: ' + effect;
		}
		if ( slidesTag ) {
			slidesTag.textContent = slides.length + ' slides';
		}
		if ( sourceTag ) {
			sourceTag.textContent = 'Source: ' + source;
		}

		wrapper.innerHTML = '';

		slides.forEach( function ( slide, index ) {
			var slideEl = document.createElement( 'div' );
			slideEl.className = 'swiper-slide gsm-slide gsm-preview-slide' + ( slide.book_layout_enable === 'yes' ? ' gsm-slide--book-layout' : '' );
			slideEl.style.height = '100%';

			var bgUrl = ( slide.bg_image && slide.bg_image.url ) ? slide.bg_image.url : 'https://picsum.photos/seed/' + encodeURIComponent( templateId + '-' + index ) + '/1200/700';

			var html = '';
			html += '<div class="gsm-bg" style="position:absolute;inset:0;background-image:url(' + escapePreviewHtml( bgUrl ) + ');background-size:cover;background-position:center;"></div>';

			if ( slide.overlay_type === 'gradient' ) {
				var gStart = slide.overlay_gradient_start || 'rgba(0,0,0,0.6)';
				var gEnd   = slide.overlay_gradient_end || 'rgba(0,0,0,0.1)';
				var gDir   = slide.overlay_gradient_direction || 'to top';
				html += '<div class="gsm-slide-overlay" style="position:absolute;inset:0;background:linear-gradient(' + escapePreviewHtml( gDir ) + ',' + gStart + ',' + gEnd + ');pointer-events:none;z-index:2;"></div>';
			} else if ( slide.overlay_type && slide.overlay_type !== 'none' ) {
				var oColor = slide.overlay_color || 'rgba(0,0,0,0.4)';
				html += '<div class="gsm-slide-overlay" style="position:absolute;inset:0;background:' + escapePreviewHtml( oColor ) + ';pointer-events:none;z-index:2;"></div>';
			}

			var particleHtml = '';
			if ( slide.particle_enable === 'yes' ) {
				var presetMapPrev = {
					snow:     { color: '#ffffff', color2: '', count: 60, size: 3, speed: 1.5, shape: 'circle', opacity: 0.7, connect: false, drift: true, glitter: false },
					glitter:  { color: '#ffd700', color2: '#ffec8b', count: 80, size: 4, speed: 2, shape: 'star', opacity: 0.9, connect: false, drift: true, glitter: true },
					bubbles:  { color: 'rgba(255,255,255,0.3)', color2: '', count: 30, size: 10, speed: 1, shape: 'circle', opacity: 0.5, connect: false, drift: true, bubble: true },
					stars:    { color: '#ffffff', color2: '#b3d4ff', count: 100, size: 2, speed: 0.5, shape: 'circle', opacity: 0.8, connect: true, drift: false, glitter: true },
					confetti: { color: '#ff6b6b', color2: '#ffd93d', count: 60, size: 6, speed: 3, shape: 'square', opacity: 0.85, connect: false, drift: true, rotate: true, multicolor: true },
				};
				var presetP = slide.particle_preset && presetMapPrev[ slide.particle_preset ] ? slide.particle_preset : 'snow';
				var pConfigP = presetMapPrev[ presetP ] || presetMapPrev.snow;
				particleHtml = '<canvas class="gsm-particles-canvas" data-particles="' + escapePreviewHtml( JSON.stringify( pConfigP ) ) + '" style="position:absolute;inset:0;width:100%;height:100%;z-index:3;pointer-events:none;"></canvas>';
			}

			if ( slide.book_layout_enable === 'yes' ) {
				html += particleHtml;
				html += previewBookLayoutMarkup( slide, settings );
				slideEl.innerHTML = html;
				wrapper.appendChild( slideEl );
				return;
			}

			var vAlign = slide.content_vertical || 'middle';
			var hAlign = slide.content_align || 'left';
			var maxW   = ( slide.content_max_width && slide.content_max_width.size ) ? slide.content_max_width.size + ( slide.content_max_width.unit || 'px' ) : '520px';
			var boxBg  = ( slide.content_box_enable === 'yes' && settings.content_bg ) ? settings.content_bg : 'transparent';

			var valignMap = { top: 'flex-start', middle: 'center', bottom: 'flex-end' };
			var halignMap = { left: 'flex-start', center: 'center', right: 'flex-end' };

			html += '<div class="gsm-slide-inner valign-' + escapePreviewHtml( vAlign ) + '" style="position:absolute;inset:0;display:flex;align-items:' + ( valignMap[ vAlign ] || 'center' ) + ';justify-content:' + ( halignMap[ hAlign ] || 'flex-start' ) + ';padding:20px 40px;z-index:5;">';
			html += '<div class="gsm-slide-content" style="max-width:' + escapePreviewHtml( maxW ) + ';background:' + escapePreviewHtml( boxBg ) + ';padding:' + ( slide.content_box_enable === 'yes' ? '28px 32px' : '0' ) + ';border-radius:8px;color:#fff;">';

			if ( slide.badge_text ) {
				html += '<span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;color:#fff;background:' + escapePreviewHtml( slide.badge_color || '#6c63ff' ) + ';margin-bottom:12px;">' + escapePreviewHtml( slide.badge_text ) + '</span>';
			}

			if ( slide.heading ) {
				var rawTag = String( slide.heading_tag || 'h2' ).toLowerCase().replace( /[^a-z0-9]/g, '' );
				var allowedHeadings = { h1: true, h2: true, h3: true, h4: true, h5: true, h6: true };
				var tag = allowedHeadings[ rawTag ] ? rawTag : 'h2';
				if ( slide.typing_effect === 'yes' ) {
					var typingStrings = slide.typing_strings;
					if ( Array.isArray( typingStrings ) ) {
						typingStrings = typingStrings.filter( Boolean );
					} else if ( typeof typingStrings === 'string' ) {
						typingStrings = typingStrings.split( '\n' ).filter( Boolean );
					} else {
						typingStrings = String( slide.heading || '' ).split( '\n' ).filter( Boolean );
					}
					if ( ! typingStrings.length ) {
						typingStrings = [ String( slide.heading ) ];
					}
					var typingPayload = {
						strings: typingStrings,
						typeSpeed: parseInt( slide.typing_speed, 10 ) || 80,
						deleteSpeed: parseInt( slide.typing_delete_speed, 10 ) || 40,
						pauseTime: parseInt( slide.typing_pause, 10 ) || 1800,
					};
					html += '<' + tag + ' class="gsm-anim gsm-anim--heading fade-up" style="color:#fff;margin:0 0 12px;" data-gsm-delay="0"><span class="gsm-typing-text" data-typing="' + escapePreviewHtml( JSON.stringify( typingPayload ) ) + '"></span><span class="gsm-typing-cursor" aria-hidden="true">|</span></' + tag + '>';
				} else {
					html += '<' + tag + ' class="gsm-anim gsm-anim--heading fade-up" style="color:#fff;margin:0 0 12px;" data-gsm-delay="0">' + escapePreviewHtml( slide.heading ) + '</' + tag + '>';
				}
			}

			if ( slide.description ) {
				html += '<p class="gsm-anim gsm-anim--text fade-up" style="color:rgba(255,255,255,0.85);margin:0 0 16px;line-height:1.6;" data-gsm-delay="150">' + escapePreviewHtml( slide.description ) + '</p>';
			}

			if ( slide.btn_text ) {
				var btnBg    = settings.btn_bg || '#6c63ff';
				var btnColor = settings.btn_color || '#ffffff';
				var btnRad   = ( settings.btn_border_radius && settings.btn_border_radius.size ) ? settings.btn_border_radius.size + 'px' : '6px';
				html += '<div class="gsm-btn-wrap">';
				html += '<span class="gsm-btn gsm-anim gsm-anim--btn fade-up" data-gsm-delay="300" style="display:inline-block;padding:10px 24px;border-radius:' + escapePreviewHtml( btnRad ) + ';background:' + escapePreviewHtml( btnBg ) + ';color:' + escapePreviewHtml( btnColor ) + ';font-weight:500;font-size:13px;cursor:default;">' + escapePreviewHtml( slide.btn_text ) + '</span>';
				html += '</div>';
			}

			html += '</div></div>';

			html += particleHtml;

			slideEl.innerHTML = html;
			wrapper.appendChild( slideEl );
		} );

		if ( slides.length === 0 ) {
			var placeholderEl = document.createElement( 'div' );
			placeholderEl.className = 'swiper-slide gsm-slide gsm-preview-slide';
			placeholderEl.style.height = '100%';
			placeholderEl.innerHTML = '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;background:linear-gradient(135deg,#1a1a2e,#2d2b55);color:rgba(255,255,255,0.7);"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg><p style="font-size:13px;margin:0;">' + escapePreviewHtml( gsmSliderTplI18n( 'previewDynamicHint', 'Preview uses dynamic content from your WordPress site.' ) ) + '</p></div>';
			wrapper.appendChild( placeholderEl );
		}

		var previewSliderEl = document.getElementById( 'gsm-preview-slider' );
		if ( previewSliderEl ) {
			var pag = previewSliderEl.querySelector( '.gsm-preview-pagination' );
			var prevA = previewSliderEl.querySelector( '.swiper-button-prev' );
			var nextA = previewSliderEl.querySelector( '.swiper-button-next' );
			var pagType = settings.pagination_type || 'bullets';
			if ( pagType === 'none' || pagType === '' ) {
				if ( pag ) {
					pag.style.display = 'none';
				}
			} else if ( pag ) {
				pag.style.display = '';
			}
			var showArrows = settings.arrows === 'yes' || settings.arrows === true;
			if ( prevA ) {
				prevA.style.display = showArrows ? '' : 'none';
			}
			if ( nextA ) {
				nextA.style.display = showArrows ? '' : 'none';
			}
		}

		window.setTimeout( function () {
			var el = document.getElementById( 'gsm-preview-slider' );
			if ( ! el ) {
				return;
			}

			if ( typeof Swiper === 'undefined' ) {
				dbg( 'renderPreview: Swiper missing in editor — static fallback' );
				el.classList.add( 'gsm-preview-fallback' );
				el.querySelectorAll( '.swiper-slide' ).forEach( function ( sl, idx ) {
					sl.classList.toggle( 'swiper-slide-active', idx === 0 );
					sl.style.opacity = idx === 0 ? '1' : '0';
					sl.style.pointerEvents = idx === 0 ? '' : 'none';
				} );
				startPreviewAnimations( 0 );
				startPreviewParticles( 0 );
				return;
			}

			el.classList.remove( 'gsm-preview-fallback' );

			var swEffect = settings.effect || 'fade';
			var slideCount = slides.length || 1;
			var swConfig = {
				loop: settings.loop === 'yes' && slideCount > 1,
				speed: parseInt( settings.speed, 10 ) || 900,
				effect: swEffect,
				autoplay: settings.autoplay === 'yes' ? {
					delay: parseInt( settings.delay, 10 ) || 5000,
					disableOnInteraction: false,
				} : false,
				pagination: ( settings.pagination_type && settings.pagination_type !== 'none' ) ? {
					el: el.querySelector( '.gsm-preview-pagination' ),
					clickable: true,
				} : false,
				navigation: {
					nextEl: el.querySelector( '.swiper-button-next' ),
					prevEl: el.querySelector( '.swiper-button-prev' ),
				},
				on: {
					init: function () {
						startPreviewAnimations( 0 );
						startPreviewParticles( 0 );
						if ( window.GsmSliderSyncBookShowcaseLayout ) {
							window.GsmSliderSyncBookShowcaseLayout( el );
						}
					},
					slideChangeTransitionEnd: function () {
						if ( window.GsmSliderSyncBookShowcaseLayout ) {
							window.GsmSliderSyncBookShowcaseLayout( el );
						}
						startPreviewAnimations( this.realIndex );
						startPreviewParticles( this.realIndex );
					},
				},
			};

			if ( swEffect === 'fade' ) {
				swConfig.fadeEffect = { crossFade: true };
			}

			previewSwiper = new Swiper( el, swConfig );
		}, 120 );
	}

	function startPreviewAnimations() {
		var previewSliderEl = document.getElementById( 'gsm-preview-slider' );
		if ( ! previewSliderEl ) {
			return;
		}

		previewSliderEl.querySelectorAll( '.gsm-anim' ).forEach( function ( elAnim ) {
			elAnim.classList.remove( 'is-visible' );
			void elAnim.offsetWidth; // eslint-disable-line no-void
		} );

		previewSliderEl.querySelectorAll( '.swiper-slide-active .gsm-anim' ).forEach( function ( elAnim ) {
			var delay = parseInt( elAnim.dataset.gsmDelay || elAnim.getAttribute( 'data-gsm-delay' ) || 0, 10 );
			window.setTimeout( function () {
				elAnim.classList.add( 'is-visible' );
			}, delay );
		} );

		Object.keys( previewTypers ).forEach( function ( k ) {
			var t = previewTypers[ k ];
			if ( t && typeof t.stop === 'function' ) {
				t.stop();
			}
		} );
		previewTypers = {};

		var TyperCtor = window.GsmSliderGsmTyper;
		var activeSlide = previewSliderEl.querySelector( '.swiper-slide-active' );
		if ( activeSlide && TyperCtor ) {
			activeSlide.querySelectorAll( '.gsm-typing-text' ).forEach( function ( tel, i ) {
				var cfg = {};
				try {
					cfg = JSON.parse( tel.getAttribute( 'data-typing' ) || '{}' );
				} catch ( e1 ) {
					cfg = {};
				}
				var typer = new TyperCtor( tel, cfg );
				previewTypers[ i ] = typer;
				window.setTimeout( function () {
					typer.start();
				}, 400 );
			} );
		}
	}

	function startPreviewParticles() {
		var ParticlesCtor = window.GsmSliderGsmParticles;
		if ( ! ParticlesCtor ) {
			return;
		}

		var previewSliderEl = document.getElementById( 'gsm-preview-slider' );
		if ( ! previewSliderEl ) {
			return;
		}

		Object.keys( previewParticles ).forEach( function ( k ) {
			var p = previewParticles[ k ];
			if ( p && typeof p.destroy === 'function' ) {
				p.destroy();
			}
		} );
		previewParticles = {};

		var activeSlide = previewSliderEl.querySelector( '.swiper-slide-active' );
		if ( ! activeSlide ) {
			return;
		}

		activeSlide.querySelectorAll( '.gsm-particles-canvas' ).forEach( function ( canvas, i ) {
			var cfg = {};
			try {
				cfg = JSON.parse( canvas.getAttribute( 'data-particles' ) || '{}' );
			} catch ( e2 ) {
				cfg = {};
			}
			canvas.width  = canvas.offsetWidth || 800;
			canvas.height = canvas.offsetHeight || 460;
			var ps = new ParticlesCtor( canvas, cfg );
			previewParticles[ 'p_' + i ] = ps;
			ps.start();
		} );
	}

	function buildModal() {
		if ( modal ) {
			dbg( 'buildModal: reuse existing modal' );
			return;
		}
		dbg( 'buildModal: creating modal DOM' );

		modal = document.createElement( 'div' );
		modal.id = 'gsm-templates-modal';
		modal.innerHTML = [
			'<div class="gsm-tpl-backdrop"></div>',
			'<div class="gsm-tpl-dialog">',
			'  <div class="gsm-tpl-header">',
			'    <span class="gsm-tpl-title">GSM Slider Templates</span>',
			'    <button type="button" class="gsm-tpl-close" aria-label="Close">&times;</button>',
			'  </div>',
			'  <div class="gsm-tpl-filters">',
			'    <button type="button" class="gsm-tpl-filter on" data-cat="all">All</button>',
			'    <button type="button" class="gsm-tpl-filter" data-cat="hero">Hero</button>',
			'    <button type="button" class="gsm-tpl-filter" data-cat="portfolio">Portfolio</button>',
			'    <button type="button" class="gsm-tpl-filter" data-cat="testimonial">Testimonial</button>',
			'    <button type="button" class="gsm-tpl-filter" data-cat="corporate">Corporate</button>',
			'    <button type="button" class="gsm-tpl-filter" data-cat="dynamic">Dynamic</button>',
			'  </div>',
			'  <div class="gsm-tpl-grid"></div>',
			'  <div class="gsm-tpl-footer">',
			'    <button type="button" class="gsm-tpl-cancel">Cancel</button>',
			'    <button type="button" class="gsm-tpl-import" disabled>Import Template</button>',
			'  </div>',
			'</div>',
		].join( '' );

		document.body.appendChild( modal );
		dbg( 'buildModal: modal appended' );

		modal.querySelector( '.gsm-tpl-backdrop' ).addEventListener( 'click', closeModal );
		modal.querySelector( '.gsm-tpl-close' ).addEventListener( 'click', closeModal );
		modal.querySelector( '.gsm-tpl-cancel' ).addEventListener( 'click', closeModal );

		modal.querySelector( '.gsm-tpl-import' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			if ( ! currentId ) {
				return;
			}
			importTemplate( currentId );
		} );

		modal.querySelectorAll( '.gsm-tpl-filter' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				modal.querySelectorAll( '.gsm-tpl-filter' ).forEach( function ( b ) {
					b.classList.remove( 'on' );
				} );
				this.classList.add( 'on' );
				filterTemplates( this.dataset.cat );
			} );
		} );

		function selectTemplateCard( card ) {
			if ( ! card || ! modal.contains( card ) ) {
				return;
			}
			dbg( 'template selected', card.dataset.id );
			modal.querySelectorAll( '.gsm-tpl-card' ).forEach( function ( c ) {
				c.classList.remove( 'selected' );
			} );
			card.classList.add( 'selected' );
			currentId = card.dataset.id;
			var imp = modal.querySelector( '.gsm-tpl-import' );
			if ( imp ) {
				imp.disabled = false;
			}
		}

		function eventTargetToElement( t ) {
			if ( ! t ) {
				return null;
			}
			/* Clicks on literal text can yield a Text node — it has no .closest(), so title/description clicks did nothing. */
			if ( t.nodeType === 3 && t.parentElement ) {
				return t.parentElement;
			}
			return t.nodeType === 1 ? t : null;
		}

		function closestTplCard( t ) {
			var el = eventTargetToElement( t );
			return el && el.closest ? el.closest( '.gsm-tpl-card' ) : null;
		}

		var tplGrid = modal.querySelector( '.gsm-tpl-grid' );
		tplGrid.addEventListener( 'click', function ( e ) {
			var previewBtn = e.target.closest && e.target.closest( '.gsm-tpl-preview-btn' );
			if ( previewBtn ) {
				e.preventDefault();
				e.stopPropagation();
				var pid = previewBtn.getAttribute( 'data-id' ) || previewBtn.dataset.id;
				if ( pid ) {
					openPreview( pid );
				}
				return;
			}
			var card = closestTplCard( e.target );
			if ( ! card ) {
				return;
			}
			e.preventDefault();
			selectTemplateCard( card );
		} );
		tplGrid.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'Enter' && e.key !== ' ' ) {
				return;
			}
			var kbPreviewBtn = e.target.closest && e.target.closest( '.gsm-tpl-preview-btn' );
			if ( kbPreviewBtn ) {
				e.preventDefault();
				var kbPid = kbPreviewBtn.getAttribute( 'data-id' ) || kbPreviewBtn.dataset.id;
				if ( kbPid ) {
					openPreview( kbPid );
				}
				return;
			}
			var card = closestTplCard( e.target );
			if ( ! card ) {
				return;
			}
			e.preventDefault();
			selectTemplateCard( card );
		} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'Escape' ) {
				return;
			}
			if ( previewModal && previewModal.classList.contains( 'open' ) ) {
				closePreview();
				return;
			}
			if ( modal && modal.classList.contains( 'open' ) ) {
				closeModal();
			}
		} );
	}

	function openModal( widgetId ) {
		dbg( 'openModal called', { widgetId: widgetId, currentWidgetId: currentWidgetId } );
		closePreview();
		buildModal();
		currentId = null;
		modal.dataset.widgetId = widgetId || currentWidgetId || '';
		modal.classList.remove( 'open' );
		modal.querySelector( '.gsm-tpl-import' ).disabled = true;
		loadTemplates();
		/* Let layout settle so opacity/transform transitions run (not skipped on same tick as DOM changes). */
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				if ( modal ) {
					modal.classList.add( 'open' );
				}
			} );
		} );
		dbg( 'openModal: dataset.widgetId=', modal.dataset.widgetId );
	}

	function closeModal() {
		closePreview();
		if ( modal ) {
			modal.classList.remove( 'open' );
		}
		currentId = null;
		dbg( 'closeModal' );
	}

	function loadTemplates() {
		var grid = modal.querySelector( '.gsm-tpl-grid' );
		grid.innerHTML = '<p style="padding:24px;text-align:center;color:#888;">Loading templates...</p>';
		dbg( 'loadTemplates: sending AJAX', gsmTemplates.ajaxUrl );

		var fd = new FormData();
		fd.append( 'action', 'gsm_get_templates' );
		fd.append( 'nonce', gsmTemplates.nonce );

		fetch( gsmTemplates.ajaxUrl, { method: 'POST', body: fd } )
			.then( function ( r ) {
				dbg( 'loadTemplates: HTTP status', r.status );
				return r.text();
			} )
			.then( function ( text ) {
				var res = parseWpAjaxJson( text, 'list' );
				dbg( 'loadTemplates: response', res );
				var ajaxOk = res && ( res.success === true || res.success === 1 );
				if ( ! ajaxOk ) {
					grid.innerHTML = '<p>Failed to load templates.</p>';
					return;
				}
				renderTemplates( res.data );
			} )
			.catch( function ( err ) {
				dbg( 'loadTemplates: fetch error', err );
				grid.innerHTML = '<p>Network error.</p>';
			} );
	}

	function renderTemplates( templates ) {
		dbg( 'renderTemplates: count', templates ? templates.length : 0 );
		var grid = modal.querySelector( '.gsm-tpl-grid' );
		grid.innerHTML = '';
		try {
			grid.dataset.templates = JSON.stringify( templates );
		} catch ( eTpl ) {
			grid.dataset.templates = '[]';
		}

		templates.forEach( function ( tpl ) {
			var card = document.createElement( 'div' );
			card.className = 'gsm-tpl-card';
			card.dataset.id = tpl.id;
			card.dataset.cat = tpl.category || '';
			card.setAttribute( 'role', 'button' );
			card.setAttribute( 'tabindex', '0' );

			var thumb = document.createElement( 'div' );
			thumb.className = 'gsm-tpl-card__thumb';

			var img = document.createElement( 'img' );
			img.src = tpl.thumbnail || '';
			img.alt = tpl.title || '';
			img.loading = 'lazy';

			var prevBtn = document.createElement( 'button' );
			prevBtn.type = 'button';
			prevBtn.className = 'gsm-tpl-preview-btn';
			prevBtn.setAttribute( 'data-id', tpl.id );
			prevBtn.setAttribute( 'tabindex', '0' );
			prevBtn.setAttribute( 'aria-label', gsmSliderTplI18n( 'previewTemplate', 'Preview template' ) );
			prevBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg> <span>' + escapePreviewHtml( gsmSliderTplI18n( 'preview', 'Preview' ) ) + '</span>';

			thumb.appendChild( img );
			thumb.appendChild( prevBtn );

			var body = document.createElement( 'div' );
			body.className = 'gsm-tpl-card-body';
			var strong = document.createElement( 'strong' );
			strong.textContent = tpl.title || '';
			var span = document.createElement( 'span' );
			span.textContent = tpl.description || '';
			body.appendChild( strong );
			body.appendChild( span );

			card.appendChild( thumb );
			card.appendChild( body );
			grid.appendChild( card );
		} );
	}

	function filterTemplates( cat ) {
		if ( ! modal ) {
			return;
		}
		modal.querySelectorAll( '.gsm-tpl-card' ).forEach( function ( card ) {
			card.style.display = ( cat === 'all' || card.dataset.cat === cat ) ? '' : 'none';
		} );
	}

	function gsmSliderTplI18n( key, fallback ) {
		var pack = gsmTemplates.i18n || {};
		return pack[ key ] || fallback || '';
	}

	function showGsmSliderTplNotice( message, isError ) {
		if ( ! message ) {
			return;
		}
		isError = !! isError;
		var toastType = isError ? 'error' : 'success';
		try {
			if ( window.elementor && elementor.notifications && typeof elementor.notifications.showToast === 'function' ) {
				elementor.notifications.showToast( { message: message, type: toastType } );
				return;
			}
			if ( window.elementorCommon && elementorCommon.notifications && typeof elementorCommon.notifications.showToast === 'function' ) {
				elementorCommon.notifications.showToast( { message: message, type: toastType } );
				return;
			}
		} catch ( err ) {
			dbg( 'showGsmSliderTplNotice toast error', err );
		}
		window.alert( message );
	}

	function idsMatch( a, b ) {
		if ( a == null || b == null ) {
			return false;
		}
		return String( a ) === String( b );
	}

	function isGsmSliderModel( model ) {
		return model && typeof model.get === 'function' && model.get( 'widgetType' ) === 'gsm_slider';
	}

	function viewFromPreviewScope( $scope, widgetId ) {
		if ( ! $scope || ! $scope.find || ! widgetId ) {
			return null;
		}
		var $w = $scope.find( '.elementor-element[data-id="' + widgetId + '"]' );
		if ( ! $w.length ) {
			$w = $scope.find( '[data-id="' + widgetId + '"]' );
		}
		if ( ! $w.length ) {
			return null;
		}
		return $w.data( 'view' ) || null;
	}

	function getPanelEditedGsmSliderModel() {
		try {
			var panel = elementor.getPanelView && elementor.getPanelView();
			if ( ! panel || ! panel.getCurrentPageView ) {
				return null;
			}
			var page = panel.getCurrentPageView();
			if ( ! page || ! page.getOption ) {
				return null;
			}
			var edited = page.getOption( 'editedElementView' );
			if ( ! edited ) {
				return null;
			}
			var em = edited.getEditModel && edited.getEditModel();
			return isGsmSliderModel( em ) ? em : null;
		} catch ( ignore ) {
			return null;
		}
	}

	/**
	 * Resolve container + model without relying only on preview.$el (breaks when the canvas lives in an iframe).
	 */
	function resolveGsmSliderImportTarget( widgetId ) {
		var out = { container: null, model: null };
		if ( typeof elementor === 'undefined' ) {
			return out;
		}

		// 0) Elementor core: walk preview Marionette tree by element id (most reliable when widgetId is correct).
		try {
			if ( widgetId && typeof elementor.getContainer === 'function' ) {
				var byId = elementor.getContainer( String( widgetId ) );
				if ( byId && byId.model && isGsmSliderModel( byId.model ) ) {
					out.container = byId;
					out.model = byId.model;
					dbg( 'resolveGsmSliderImportTarget: elementor.getContainer(id)' );
					return out;
				}
			}
		} catch ( e0 ) {
			dbg( 'resolveGsmSliderImportTarget getContainer(id)', e0 );
		}

		function assignFromView( view ) {
			if ( ! view ) {
				return;
			}
			if ( typeof view.getContainer === 'function' ) {
				try {
					out.container = view.getContainer();
				} catch ( err ) {
					dbg( 'resolveGsmSliderImportTarget getContainer', err );
				}
			}
			if ( typeof view.getEditModel === 'function' ) {
				out.model = view.getEditModel();
			} else if ( view.model && isGsmSliderModel( view.model ) ) {
				out.model = view.model;
			}
		}

		// 1) Elementor multi-select API (container is already a Document Container).
		try {
			if ( elementor.selection && typeof elementor.selection.getElements === 'function' ) {
				var sel = elementor.selection.getElements();
				if ( sel && sel.length && sel[ 0 ] ) {
					var c = sel[ 0 ];
					if ( c.model && isGsmSliderModel( c.model ) && ( ! widgetId || idsMatch( c.model.get( 'id' ), widgetId ) ) ) {
						out.container = c;
						out.model = c.model;
						dbg( 'resolveGsmSliderImportTarget: selection' );
						return out;
					}
				}
			}
		} catch ( e1 ) {
			dbg( 'resolveGsmSliderImportTarget selection', e1 );
		}

		// 2) Widget currently open in the left panel (most reliable while "Edit GSM Slider" is visible).
		try {
			var panel = elementor.getPanelView && elementor.getPanelView();
			if ( panel && panel.getCurrentPageView ) {
				var page = panel.getCurrentPageView();
				if ( page && page.getOption ) {
					var edited = page.getOption( 'editedElementView' );
					if ( edited ) {
						var em = edited.getEditModel && edited.getEditModel();
						if (
							isGsmSliderModel( em ) &&
							( ! widgetId || idsMatch( em.get( 'id' ), widgetId ) )
						) {
							assignFromView( edited );
							dbg( 'resolveGsmSliderImportTarget: panel editedElementView' );
							return out;
						}
					}
				}
			}
		} catch ( e2 ) {
			dbg( 'resolveGsmSliderImportTarget panel', e2 );
		}

		// If panel shows gsm_slider but id mismatch (stale modal id), still apply to the open widget.
		try {
			var panel2 = elementor.getPanelView && elementor.getPanelView();
			if ( panel2 && panel2.getCurrentPageView ) {
				var page2 = panel2.getCurrentPageView();
				if ( page2 && page2.getOption ) {
					var edited2 = page2.getOption( 'editedElementView' );
					if ( edited2 ) {
						var em2 = edited2.getEditModel && edited2.getEditModel();
						if ( isGsmSliderModel( em2 ) ) {
							assignFromView( edited2 );
							dbg( 'resolveGsmSliderImportTarget: panel gsm_slider (ignore id mismatch)' );
							return out;
						}
					}
				}
			}
		} catch ( e3 ) {
			dbg( 'resolveGsmSliderImportTarget panel loose', e3 );
		}

		if ( ! widgetId ) {
			return out;
		}

		// 3) Live preview iframe document (Elementor often mounts the canvas here).
		try {
			var iframe = document.getElementById( 'elementor-preview-iframe' );
			var win = iframe && iframe.contentWindow;
			var jq = win && win.jQuery;
			if ( jq ) {
				var doc = win.document;
				var $scope = jq( doc );
				var v_if = viewFromPreviewScope( $scope, widgetId );
				if ( v_if ) {
					assignFromView( v_if );
					dbg( 'resolveGsmSliderImportTarget: iframe jQuery' );
					return out;
				}
			}
		} catch ( e4 ) {
			dbg( 'resolveGsmSliderImportTarget iframe', e4 );
		}

		// 4) Classic: preview region jQuery (same window as editor).
		try {
			if ( elementor.getPreviewView ) {
				var preview = elementor.getPreviewView();
				if ( preview && preview.$el ) {
					var v_pr = viewFromPreviewScope( preview.$el, widgetId );
					if ( v_pr ) {
						assignFromView( v_pr );
						dbg( 'resolveGsmSliderImportTarget: preview.$el' );
						return out;
					}
				}
			}
		} catch ( e5 ) {
			dbg( 'resolveGsmSliderImportTarget preview', e5 );
		}

		return out;
	}

	function applyTemplateSettingsAsync( widgetId, settings ) {
		return new Promise( function ( resolve ) {
			try {
				if ( ! settings || typeof settings !== 'object' || Array.isArray( settings ) ) {
					resolve( false );
					return;
				}
				var resolved = resolveGsmSliderImportTarget( widgetId );
				var container = resolved.container;
				var model = resolved.model;
	
				var finished = false;
				function safeResolve( val ) {
					if ( finished ) {
						return;
					}
					finished = true;
					resolve( val );
				}
	
				/*
				 * Same as Elementor's document/elements/settings command apply(): one bulk write + render.
				 * Avoids per-key setSetting (can run hundreds of change handlers) and avoids $e.run hangs.
				 */
				if (
					container &&
					container.settings &&
					typeof container.settings.setExternalChange === 'function'
				) {
					window.setTimeout( function () {
						try {
							container.settings.setExternalChange( settings );
							if ( typeof container.render === 'function' ) {
								container.render();
							}
							dbg( 'applyTemplateSettingsAsync: container.settings.setExternalChange + render' );
							safeResolve( true );
						} catch ( err ) {
							dbg( 'applyTemplateSettingsAsync setExternalChange', err );
							runLegacyKeys();
						}
					}, 0 );
					return;
				}
	
				function runLegacyKeys() {
					if ( ! model || typeof model.setSetting !== 'function' ) {
						safeResolve( false );
						return;
					}
					window.setTimeout( function () {
						try {
							Object.keys( settings ).forEach( function ( key ) {
								model.setSetting( key, settings[ key ] );
							} );
							safeResolve( true );
						} catch ( err ) {
							dbg( 'applyTemplateSettingsAsync legacy keys', err );
							safeResolve( false );
						}
					}, 0 );
				}
	
				if ( model && typeof model.setSetting === 'function' ) {
					runLegacyKeys();
					return;
				}
	
				if ( container && window.$e && typeof $e.run === 'function' ) {
					window.setTimeout( function () {
						try {
							var ret = $e.run( 'document/elements/settings', {
								container: container,
								settings: settings,
								options: {
									external: true
								}
							} );
							if ( ret && typeof ret.then === 'function' ) {
								var bail = window.setTimeout( function () {
									dbg( 'applyTemplateSettingsAsync: $e.run bail' );
									safeResolve( true );
								}, 400 );
								ret.then(
									function () {
										window.clearTimeout( bail );
										safeResolve( true );
									},
									function ( err ) {
										window.clearTimeout( bail );
										dbg( 'applyTemplateSettingsAsync $e.run reject', err );
										safeResolve( false );
									}
								);
								return;
							}
							safeResolve( true );
						} catch ( err ) {
							dbg( 'applyTemplateSettingsAsync $e.run throw', err );
							safeResolve( false );
						}
					}, 0 );
					return;
				}
				safeResolve( false );
			} catch ( syncApplyErr ) {
				dbg( 'applyTemplateSettingsAsync sync', syncApplyErr );
				resolve( false );
			}
		} );
	}

	function importTemplate( templateId ) {
		if ( gsmSliderTplImportInFlight ) {
			dbg( 'importTemplate: skip (in flight)' );
			return;
		}
		gsmSliderTplImportInFlight = true;

		dbg( 'importTemplate start', templateId );
		var importBtn = modal.querySelector( '.gsm-tpl-import' );
		importBtn.textContent = 'Importing...';
		importBtn.disabled = true;

		function releaseImportLock() {
			gsmSliderTplImportInFlight = false;
		}

		var fd = new FormData();
		fd.append( 'action', 'gsm_get_template' );
		fd.append( 'nonce', gsmTemplates.nonce );
		fd.append( 'template_id', templateId );

		var importWatchdog = window.setTimeout( function () {
			dbg( 'importTemplate: watchdog — forcing modal/button reset' );
			closeModal();
			if ( importBtn ) {
				importBtn.textContent = 'Import Template';
				importBtn.disabled = false;
			}
			releaseImportLock();
		}, 4000 );

		function clearImportWatchdog() {
			window.clearTimeout( importWatchdog );
		}

		fetch( gsmTemplates.ajaxUrl, { method: 'POST', body: fd } )
			.then( function ( r ) {
				dbg( 'importTemplate: HTTP status', r.status );
				return r.text();
			} )
			.then( function ( text ) {
				var res;
				try {
					res = parseWpAjaxJson( text, 'import' );
				} catch ( err ) {
					clearImportWatchdog();
					dbg( 'importTemplate: JSON error', err, text && text.slice( 0, 280 ) );
					showGsmSliderTplNotice( gsmSliderTplI18n( 'importFailed', 'Could not import the template.' ), true );
					importBtn.textContent = 'Import Template';
					importBtn.disabled = false;
					releaseImportLock();
					return;
				}

				dbg( 'importTemplate: response', res );
				var ajaxOk = res && ( res.success === true || res.success === 1 );
				if ( ! ajaxOk ) {
					clearImportWatchdog();
					showGsmSliderTplNotice( gsmSliderTplI18n( 'importFailed', 'Could not import the template.' ), true );
					importBtn.textContent = 'Import Template';
					importBtn.disabled = false;
					releaseImportLock();
					return;
				}
				if ( ! res.data || typeof res.data !== 'object' ) {
					clearImportWatchdog();
					showGsmSliderTplNotice( gsmSliderTplI18n( 'importAjaxFail', 'Invalid template data.' ), true );
					importBtn.textContent = 'Import Template';
					importBtn.disabled = false;
					releaseImportLock();
					return;
				}

				var panelModel = getPanelEditedGsmSliderModel();
				var widgetId = '';
				if ( panelModel && typeof panelModel.get === 'function' ) {
					widgetId = String( panelModel.get( 'id' ) || '' );
				}
				if ( ! widgetId ) {
					widgetId = String( modal.dataset.widgetId || currentWidgetId || '' );
				}
				if ( ! widgetId ) {
					clearImportWatchdog();
					showGsmSliderTplNotice( gsmSliderTplI18n( 'importNoWidget', 'Missing widget id.' ), true );
					importBtn.textContent = 'Import Template';
					importBtn.disabled = false;
					releaseImportLock();
					return;
				}

				applyTemplateSettingsAsync( widgetId, res.data )
					.then( function ( ok ) {
						clearImportWatchdog();
						if ( ! ok ) {
							showGsmSliderTplNotice( gsmSliderTplI18n( 'importNoWidget', 'Could not find this slider in the preview.' ), true );
							importBtn.textContent = 'Import Template';
							importBtn.disabled = false;
							releaseImportLock();
							return;
						}

						// Apply often succeeds then a post-step throws (e.g. setFlagEditorChange on some Elementor builds).
						// That used to reject this .then and hit .catch — false "import failed" + modal stayed open.
						try {
							if ( elementor.saver && elementor.saver.setFlagEditorChange ) {
								elementor.saver.setFlagEditorChange();
							}
						} catch ( saverErr ) {
							dbg( 'importTemplate: setFlagEditorChange', saverErr );
						}

						try {
							closeModal();
						} catch ( closeErr ) {
							dbg( 'importTemplate: closeModal', closeErr );
						}

						if ( importBtn ) {
							importBtn.textContent = 'Import Template';
							importBtn.disabled = false;
						}
						releaseImportLock();

						window.requestAnimationFrame( function () {
							try {
								showGsmSliderTplNotice( gsmSliderTplI18n( 'importSuccess', 'Slider template imported successfully.' ), false );
							} catch ( toastErr ) {
								dbg( 'importTemplate: success notice', toastErr );
							}
						} );
					} )
					.catch( function ( err ) {
						clearImportWatchdog();
						dbg( 'importTemplate: apply error', err );
						try {
							closeModal();
						} catch ( closeErr ) {
							dbg( 'importTemplate: closeModal (catch)', closeErr );
						}
						showGsmSliderTplNotice( gsmSliderTplI18n( 'importFailed', 'Could not import the template.' ), true );
						if ( importBtn ) {
							importBtn.textContent = 'Import Template';
							importBtn.disabled = false;
						}
						releaseImportLock();
					} );
			} )
			.catch( function ( err ) {
				clearImportWatchdog();
				dbg( 'importTemplate: fetch error', err );
				showGsmSliderTplNotice( gsmSliderTplI18n( 'importFailed', 'Network error during import.' ), true );
				importBtn.textContent = 'Import Template';
				importBtn.disabled = false;
				releaseImportLock();
			} );
	}

	function bindTemplatesButton( panel, model ) {
		var panelEl = null;
		if ( panel && panel.$el && panel.$el[ 0 ] ) {
			panelEl = panel.$el[ 0 ];
		} else if ( panel && panel.el ) {
			panelEl = panel.el;
		}
		var btn = panelEl ? panelEl.querySelector( '.gsm-open-templates' ) : document.querySelector( '.gsm-open-templates' );
		if ( ! btn ) {
			dbg( 'bindTemplatesButton: button not found' );
			return;
		}
		if ( model && typeof model.get === 'function' ) {
			currentWidgetId = model.get( 'id' ) || currentWidgetId;
			btn.dataset.widgetId = currentWidgetId;
		}
		if ( btn.dataset.bound ) {
			dbg( 'bindTemplatesButton: already bound' );
			return;
		}
		btn.dataset.bound = '1';
		btn.addEventListener( 'click', function () {
			var wid = this.dataset.widgetId || currentWidgetId;
			dbg( 'button click -> openModal', wid );
			openModal( wid );
		} );
		dbg( 'bindTemplatesButton: bound success', btn.dataset.widgetId || currentWidgetId );
	}

	function gsmSliderEditorPostId() {
		var id = '';
		try {
			if ( elementor.config && elementor.config.document && elementor.config.document.id ) {
				id = String( elementor.config.document.id );
			} else if ( elementor.documents && typeof elementor.documents.getCurrentId === 'function' ) {
				id = String( elementor.documents.getCurrentId() || '' );
			}
		} catch ( err ) {
			dbg( 'gsmSliderEditorPostId', err );
		}
		return id;
	}

	function bindWidgetManager( panel, model ) {
		if ( typeof gsmManager === 'undefined' ) {
			return;
		}
		var panelEl = null;
		if ( panel && panel.$el && panel.$el[ 0 ] ) {
			panelEl = panel.$el[ 0 ];
		} else if ( panel && panel.el ) {
			panelEl = panel.el;
		}
		var root = panelEl ? panelEl.querySelector( '.gsm-panel-manager' ) : document.querySelector( '.gsm-panel-manager' );
		if ( ! root ) {
			return;
		}

		var wid = model && model.get ? String( model.get( 'id' ) || '' ) : '';
		var slides = model && model.getSetting ? model.getSetting( 'slides_data' ) : null;
		var n = Array.isArray( slides ) ? slides.length : 0;
		var info = root.querySelector( '.gsm-mgr-info' );
		if ( info ) {
			var ms = gsmManager.strings || {};
			info.textContent = ( ms.infoPrefix || 'Widget ID' ) + ': ' + wid + ' \u00b7 ' + ( ms.slidesLabel || 'Slides' ) + ': ' + n;
		}

		var postId = gsmSliderEditorPostId();
		var exp = root.querySelector( '.gsm-mgr-export' );
		var dup = root.querySelector( '.gsm-mgr-duplicate' );
		if ( exp ) {
			exp.dataset.widgetId = wid;
			exp.dataset.postId = postId;
			if ( ! exp.dataset.defaultLabel ) {
				exp.dataset.defaultLabel = exp.textContent.trim();
			}
		}
		if ( dup ) {
			dup.dataset.widgetId = wid;
			dup.dataset.postId = postId;
			if ( ! dup.dataset.defaultLabel ) {
				dup.dataset.defaultLabel = dup.textContent.trim();
			}
		}
	}

	function gsmSliderPanelExport( btn ) {
		var s = gsmManager.strings || {};
		var postId = btn.dataset.postId || gsmSliderEditorPostId();
		var widgetId = btn.dataset.widgetId || currentWidgetId;
		if ( ! postId || ! widgetId ) {
			window.alert( s.noDocument || 'Could not detect the page.' );
			return;
		}
		var defaultLabel = btn.dataset.defaultLabel || btn.textContent.trim();
		btn.disabled = true;
		btn.textContent = s.exporting || 'Exporting...';

		var body = new URLSearchParams();
		body.set( 'action', 'gsm_export_slider' );
		body.set( 'nonce', gsmManager.nonce );
		body.set( 'post_id', postId );
		body.set( 'widget_id', widgetId );

		fetch( gsmManager.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				btn.disabled = false;
				btn.textContent = defaultLabel;
				if ( ! res || ! res.success ) {
					var m = res && res.data && res.data.message ? res.data.message : ( s.error || 'Error' );
					window.alert( m );
					return;
				}
				var blob = new Blob( [ JSON.stringify( res.data, null, 2 ) ], { type: 'application/json' } );
				var url = URL.createObjectURL( blob );
				var a = document.createElement( 'a' );
				a.href = url;
				a.download = 'gsm-slider-export.json';
				document.body.appendChild( a );
				a.click();
				document.body.removeChild( a );
				URL.revokeObjectURL( url );
			} )
			.catch( function () {
				btn.disabled = false;
				btn.textContent = defaultLabel;
				window.alert( s.error || 'Error' );
			} );
	}

	function gsmSliderPanelDuplicate( btn ) {
		var s = gsmManager.strings || {};
		if ( ! window.confirm( s.confirmDuplicate || 'Duplicate?' ) ) {
			return;
		}
		var postId = btn.dataset.postId || gsmSliderEditorPostId();
		var widgetId = btn.dataset.widgetId || currentWidgetId;
		if ( ! postId || ! widgetId ) {
			window.alert( s.noDocument || 'Could not detect the page.' );
			return;
		}
		var defaultLabel = btn.dataset.defaultLabel || btn.textContent.trim();
		btn.disabled = true;
		btn.textContent = s.duplicating || 'Duplicating...';

		var body = new URLSearchParams();
		body.set( 'action', 'gsm_duplicate_slider' );
		body.set( 'nonce', gsmManager.nonce );
		body.set( 'post_id', postId );
		body.set( 'widget_id', widgetId );

		fetch( gsmManager.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				btn.disabled = false;
				btn.textContent = defaultLabel;
				if ( ! res || ! res.success ) {
					var m = res && res.data && res.data.message ? res.data.message : ( s.error || 'Error' );
					window.alert( m );
					return;
				}
				var msg = res.data.message || '';
				var openQ = s.openElementor || 'Open in Elementor?';
				if ( window.confirm( msg + '\n\n' + openQ ) && res.data.elementor_url ) {
					window.open( res.data.elementor_url, '_blank', 'noopener,noreferrer' );
				}
			} )
			.catch( function () {
				btn.disabled = false;
				btn.textContent = defaultLabel;
				window.alert( s.error || 'Error' );
			} );
	}

	document.addEventListener( 'click', function ( e ) {
		var ex = e.target && e.target.closest ? e.target.closest( '.gsm-mgr-export' ) : null;
		if ( ex ) {
			e.preventDefault();
			if ( typeof gsmManager !== 'undefined' ) {
				gsmSliderPanelExport( ex );
			}
			return;
		}
		var du = e.target && e.target.closest ? e.target.closest( '.gsm-mgr-duplicate' ) : null;
		if ( du ) {
			e.preventDefault();
			if ( typeof gsmManager !== 'undefined' ) {
				gsmSliderPanelDuplicate( du );
			}
		}
	} );

	// Open modal when "Choose a Template" button is clicked in panel.
	if ( elementor.hooks && elementor.hooks.addAction ) {
		dbg( 'elementor hooks available, binding actions' );
		elementor.hooks.addAction( 'panel/open_editor/widget/gsm_slider', function ( panel, model ) {
			dbg( 'hook: panel/open_editor/widget/gsm_slider', model && model.get ? model.get( 'id' ) : null );
			bindTemplatesButton( panel, model );
			bindWidgetManager( panel, model );
		} );

		// Fallback: generic widget editor open hook.
		elementor.hooks.addAction( 'panel/open_editor/widget', function ( panel, model ) {
			if ( model && typeof model.get === 'function' && model.get( 'widgetType' ) === 'gsm_slider' ) {
				dbg( 'hook: panel/open_editor/widget (generic)', model.get( 'id' ) );
				bindTemplatesButton( panel, model );
				bindWidgetManager( panel, model );
			}
		} );

		// Auto-open on first add.
		elementor.hooks.addAction( 'editor:widget:after-add', function ( widgetModel ) {
			if ( widgetModel.get( 'widgetType' ) !== 'gsm_slider' ) {
				return;
			}
			dbg( 'hook: editor:widget:after-add', widgetModel.get( 'id' ) );
			currentWidgetId = widgetModel.get( 'id' ) || currentWidgetId;
			var slidesData = widgetModel.getSetting( 'slides_data' );
			if ( ! slidesData || ! slidesData.length ) {
				setTimeout( function () {
					openModal( widgetModel.get( 'id' ) );
				}, 400 );
			}
		} );
	}

	// Final fallback: delegated click, works even if panel hook signature changes.
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target && e.target.closest ? e.target.closest( '.gsm-open-templates' ) : null;
		if ( ! btn ) {
			return;
		}
		var wid = btn.dataset.widgetId || currentWidgetId;
		dbg( 'delegated click -> openModal', wid );
		openModal( wid );
	} );
} )();
