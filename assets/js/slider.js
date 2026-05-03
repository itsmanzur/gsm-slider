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

		// --- Video Mobile Optimization ---
		var isMobile = window.innerWidth <= 768; // Elementor standard mobile breakpoint
		sliderEl.querySelectorAll( '.gsm-video-wrap[data-mobile-opt="yes"]' ).forEach( function ( wrap ) {
			if ( isMobile ) {
				// We are on mobile and optimization is ON.
				// Remove the video source completely from the DOM to ensure we don't consume bandwidth,
				// and fallback to the <video poster> image.
				wrap.classList.add( 'gsm-video-mobile-disabled' );
				var video = wrap.querySelector( 'video' );
				if ( video ) {
					var source = video.querySelector( 'source' );
					if ( source ) {
						source.parentNode.removeChild( source );
					}
					// Calling load() forces the browser to drop the video buffer
					video.load();
				}
				var iframe = wrap.querySelector( 'iframe' );
				if ( iframe ) {
					iframe.parentNode.removeChild( iframe );
				}
				// Also hide the play/pause controls
				var controls = wrap.querySelectorAll( '.gsm-video-toggle, .gsm-video-mute' );
				controls.forEach( function( c ) { c.style.display = 'none'; } );
			} else {
				// Desktop: Swap data-src to src to start loading the video
				var source = wrap.querySelector( 'source[data-src]' );
				var videoNode = wrap.querySelector( 'video' );
				if ( source && videoNode ) {
					source.src = source.getAttribute('data-src');
					source.removeAttribute( 'data-src' );
					videoNode.load();
				}
				var iframeNode = wrap.querySelector( 'iframe[data-src]' );
				if ( iframeNode ) {
					iframeNode.src = iframeNode.getAttribute('data-src');
					iframeNode.removeAttribute( 'data-src' );
				}
			}
		});

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

		// ---- AUTOPLAY PROGRESS BAR ----
		( function () {
			var progressBar = wrapper.querySelector( '.gsm-progress-bar' );
			if ( ! progressBar || prefersReducedMotion() ) {
				return;
			}

			var delay     = ( s.delay && s.delay > 0 ) ? s.delay : 5000;
			var raf       = null;
			var startTime = null;
			var paused    = false;
			var elapsed   = 0; // ms accumulated when paused

			function resetBar() {
				elapsed   = 0;
				startTime = null;
				progressBar.style.transition = 'none';
				progressBar.style.width      = '0%';
			}

			function tick( now ) {
				if ( paused ) {
					return;
				}
				if ( ! startTime ) {
					startTime = now;
				}
				var totalElapsed = ( now - startTime ) + elapsed;
				var pct          = Math.min( ( totalElapsed / delay ) * 100, 100 );
				progressBar.style.width = pct + '%';

				if ( pct < 100 ) {
					raf = requestAnimationFrame( tick );
				}
			}

			function startBar() {
				cancelAnimationFrame( raf );
				elapsed   = 0;
				startTime = null;
				paused    = false;
				progressBar.style.transition = 'none';
				progressBar.style.width      = '0%';
				// Tiny delay so the reset CSS takes effect before we start animating.
				requestAnimationFrame( function () {
					raf = requestAnimationFrame( tick );
				} );
			}

			function pauseBar() {
				if ( paused ) {
					return;
				}
				paused = true;
				if ( startTime !== null ) {
					elapsed += performance.now() - startTime;
					startTime = null;
				}
				cancelAnimationFrame( raf );
			}

			function resumeBar() {
				if ( ! paused ) {
					return;
				}
				paused    = false;
				startTime = null;
				raf       = requestAnimationFrame( tick );
			}

			// Hook into Swiper events.
			swiper.on( 'autoplayTimeLeft', function ( swp, timeLeft ) {
				// Swiper fires this event ~every 10ms with remaining ms.
				// Use it as a sync source so we never drift from the real timer.
				var pct = Math.max( 0, Math.min( 100, ( 1 - timeLeft / delay ) * 100 ) );
				progressBar.style.width = pct + '%';
			} );

			swiper.on( 'slideChange', function () {
				resetBar();
			} );

			swiper.on( 'autoplayStart', function () {
				resumeBar();
			} );

			swiper.on( 'autoplayStop', function () {
				pauseBar();
			} );

			swiper.on( 'autoplayPause', function () {
				pauseBar();
			} );

			swiper.on( 'autoplayResume', function () {
				resumeBar();
			} );

			// Kick off on first load.
			if ( s.autoplay ) {
				startBar();
			}
		} )();

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

		// ---- INTERSECTION OBSERVER AUTOPLAY ----
		// Start autoplay when slider enters viewport (≥40% visible).
		// Pause when it leaves. This saves CPU/battery and avoids slides
		// spinning away while completely off-screen.
		// Respects the "Pause When Off-Screen" widget toggle (autoplay_viewport).
		var viewportAutoplayEnabled =
			s.autoplay &&
			! prefersReducedMotion() &&
			( 'IntersectionObserver' in window ) &&
			// Default ON. Only disable when explicitly set to 'no' or false.
			( s.autoplayViewport !== 'no' && s.autoplayViewport !== false &&
			  s.autoplay_viewport !== 'no' && s.autoplay_viewport !== false );

		if ( viewportAutoplayEnabled ) {
			// Track whether the user has manually interacted (drag/click arrow).
			// After interaction we still pause on hide but never forcibly restart
			// if the user intentionally paused via hover — Swiper's own
			// pauseOnMouseEnter handles that case.
			var gsmAutoplayPausedByIO = false;

			var autoplayObserver = new IntersectionObserver(
				function ( entries ) {
					entries.forEach( function ( entry ) {
						if ( entry.isIntersecting ) {
							// Slider is (back) in view — resume if we paused it.
							if ( gsmAutoplayPausedByIO ) {
								gsmAutoplayPausedByIO = false;
								if ( swiper.autoplay && typeof swiper.autoplay.start === 'function' ) {
									swiper.autoplay.start();
								}
							}
						} else {
							// Slider scrolled out of view — pause to save resources.
							if (
								swiper.autoplay &&
								swiper.autoplay.running &&
								typeof swiper.autoplay.stop === 'function'
							) {
								gsmAutoplayPausedByIO = true;
								swiper.autoplay.stop();
							}
						}
					} );
				},
				{
					// Fire when ≥40% of the slider becomes visible / hidden.
					// Lower threshold means "start earlier"; higher = wait until
					// more of the slider is on screen.
					threshold: 0.4,
				}
			);

			autoplayObserver.observe( sliderEl );

			// Also reset the progress-bar width to 0 when hidden so it doesn't
			// show a stale value when scrolled back into view.
			if ( progressBar ) {
				swiper.on( 'autoplayStop', function () {
					if ( gsmAutoplayPausedByIO && progressBar ) {
						progressBar.style.width = '0%';
					}
				} );
			}
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

