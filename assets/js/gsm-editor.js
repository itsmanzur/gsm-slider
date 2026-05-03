/**
 * GSM Slider — Elementor Editor Panel Script (lightweight)
 *
 * This file is loaded ONLY in the Elementor editor panel (not frontend, not preview iframe).
 * It handles: Template Modal, Manager Panel (export/duplicate), widget hooks.
 *
 * Do NOT include Swiper or frontend slider init code here.
 *
 * @package GSM Slider
 */
( function () {
	'use strict';

	/* ---------------------------------------------------------------
	   Debug helper
	--------------------------------------------------------------- */
	var DEBUG = false;
	function dbg() {
		if ( DEBUG && window.console && console.log ) {
			console.log.apply( console, [ '[GSM-Editor]' ].concat( Array.prototype.slice.call( arguments ) ) );
		}
	}

	/* ---------------------------------------------------------------
	   Safe gsmTemplates / gsmManager accessors
	--------------------------------------------------------------- */
	function getTpl() {
		return ( typeof gsmTemplates !== 'undefined' ) ? gsmTemplates : {};
	}
	function getMgr() {
		return ( typeof gsmManager !== 'undefined' ) ? gsmManager : {};
	}

	var currentWidgetId = null;

	/* ---------------------------------------------------------------
	   Utility: get current post ID from Elementor
	--------------------------------------------------------------- */
	function gsmEditorPostId() {
		try {
			if ( typeof elementor !== 'undefined' ) {
				if ( elementor.config && elementor.config.document && elementor.config.document.id ) {
					return String( elementor.config.document.id );
				}
				if ( elementor.documents && typeof elementor.documents.getCurrentId === 'function' ) {
					return String( elementor.documents.getCurrentId() || '' );
				}
			}
		} catch ( e ) { /* ignore */ }
		return '';
	}

	/* ---------------------------------------------------------------
	   Show notice inside Elementor
	--------------------------------------------------------------- */
	function showNotice( message, isError ) {
		if ( ! message ) { return; }
		try {
			if ( window.elementor && elementor.notifications && typeof elementor.notifications.showToast === 'function' ) {
				elementor.notifications.showToast( { message: message, type: isError ? 'error' : 'success' } );
				return;
			}
			if ( window.elementorCommon && elementorCommon.notifications && typeof elementorCommon.notifications.showToast === 'function' ) {
				elementorCommon.notifications.showToast( { message: message, type: isError ? 'error' : 'success' } );
				return;
			}
		} catch ( e ) { /* ignore */ }
		window.alert( message );
	}

	/* ---------------------------------------------------------------
	   Template modal
	--------------------------------------------------------------- */
	var modal = null;

	function getModal() {
		if ( ! modal ) {
			modal = document.getElementById( 'gsm-templates-modal' );
		}
		return modal;
	}

	function openModal( widgetId ) {
		if ( widgetId ) { currentWidgetId = widgetId; }
		var m = getModal();
		if ( ! m ) {
			dbg( 'openModal: #gsm-templates-modal not found' );
			return;
		}
		m.classList.add( 'open' );
		loadTemplates();
	}

	function closeModal() {
		var m = getModal();
		if ( m ) { m.classList.remove( 'open' ); }
	}

	/* Fetch and render templates */
	var templatesLoaded = false;
	function loadTemplates() {
		var m = getModal();
		if ( ! m || templatesLoaded ) { return; }
		var tpl = getTpl();
		if ( ! tpl.ajaxUrl || ! tpl.nonce ) {
			dbg( 'loadTemplates: no ajaxUrl or nonce' );
			return;
		}

		var body = new URLSearchParams();
		body.set( 'action', 'gsm_get_templates' );
		body.set( 'nonce', tpl.nonce );

		fetch( tpl.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
		.then( function ( r ) { return r.json(); } )
		.then( function ( res ) {
			if ( ! res || ! res.success || ! res.data ) {
				dbg( 'loadTemplates: bad response', res );
				return;
			}
			renderTemplates( res.data );
			templatesLoaded = true;
		} )
		.catch( function ( err ) {
			dbg( 'loadTemplates: fetch error', err );
		} );
	}

	function renderTemplates( templates ) {
		var m = getModal();
		if ( ! m ) { return; }
		var grid = m.querySelector( '.gsm-tpl-grid' );
		if ( ! grid ) { return; }

		var keys = Object.keys( templates );
		var html = '';
		keys.forEach( function ( id ) {
			var t = templates[ id ];
			var thumb = t.thumbnail || '';
			var title = t.title || id;
			var cat   = t.category || 'other';
			html += '<div class="gsm-tpl-card" data-id="' + escAttr( id ) + '" data-cat="' + escAttr( cat ) + '" data-title="' + escAttr( title.toLowerCase() ) + '" tabindex="0" role="option" aria-selected="false">';
			html += '<div class="gsm-tpl-card__thumb">';
			if ( thumb ) {
				html += '<img src="' + escAttr( thumb ) + '" alt="' + escAttr( title ) + '" loading="lazy">';
			}
			html += '</div>';
			html += '<div class="gsm-tpl-card-body"><strong>' + escHtml( title ) + '</strong>';
			if ( t.description ) { html += '<span>' + escHtml( t.description ) + '</span>'; }
			html += '</div></div>';
		} );
		grid.innerHTML = html;
		renderCategoryFilters( templates );
	}

	function renderCategoryFilters( templates ) {
		var m = getModal();
		if ( ! m ) { return; }
		var cats = m.querySelector( '.gsm-tpl-cats' );
		if ( ! cats ) { return; }
		var found = { all: true };
		Object.keys( templates ).forEach( function ( id ) {
			var c = templates[ id ].category || 'other';
			found[ c ] = true;
		} );
		var labels = { all: 'All', hero: 'Hero', testimonial: 'Testimonial', portfolio: 'Portfolio', corporate: 'Corporate', news: 'News', woocommerce: 'WooCommerce', split: 'Split', typing: 'Typing', countdown: 'Countdown', books: 'Books', scroll: 'Scroll', other: 'Other' };
		var html = '';
		Object.keys( found ).forEach( function ( c ) {
			var active = ( c === 'all' ) ? ' on' : '';
			html += '<button class="gsm-tpl-filter' + active + '" data-cat="' + escAttr( c ) + '">' + escHtml( labels[ c ] || c ) + '</button>';
		} );
		cats.innerHTML = html;
	}

	function filterTemplates( cat ) {
		var m = getModal();
		if ( ! m ) { return; }
		var search = m.querySelector( '#gsm-tpl-search-input' );
		var keyword = search ? search.value.toLowerCase().trim() : '';
		m.querySelectorAll( '.gsm-tpl-card' ).forEach( function ( card ) {
			var matchCat = ( cat === 'all' || card.dataset.cat === cat );
			var matchSearch = ! keyword || ( card.dataset.title && card.dataset.title.indexOf( keyword ) !== -1 );
			card.style.display = ( matchCat && matchSearch ) ? '' : 'none';
		} );
	}

	var selectedTemplateId = null;

	function selectCard( card ) {
		var m = getModal();
		if ( m ) {
			m.querySelectorAll( '.gsm-tpl-card.selected' ).forEach( function ( c ) { c.classList.remove( 'selected' ); c.setAttribute( 'aria-selected', 'false' ); } );
		}
		card.classList.add( 'selected' );
		card.setAttribute( 'aria-selected', 'true' );
		selectedTemplateId = card.dataset.id;
		var importBtn = m ? m.querySelector( '.gsm-tpl-import' ) : null;
		if ( importBtn ) { importBtn.disabled = false; }
	}

	function importSelectedTemplate() {
		if ( ! selectedTemplateId ) { return; }
		var tpl = getTpl();
		if ( ! tpl.ajaxUrl || ! tpl.nonce ) { return; }

		var body = new URLSearchParams();
		body.set( 'action', 'gsm_apply_template' );
		body.set( 'nonce', tpl.nonce );
		body.set( 'template_id', selectedTemplateId );

		fetch( tpl.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
		.then( function ( r ) { return r.json(); } )
		.then( function ( res ) {
			if ( ! res || ! res.success ) {
				showNotice( ( res && res.data && res.data.message ) || 'Import failed.', true );
				return;
			}
			applyTemplateSettings( res.data );
			closeModal();
			showNotice( 'Slider template imported successfully.', false );
		} )
		.catch( function () {
			showNotice( 'Network error during import.', true );
		} );
	}

	function applyTemplateSettings( settings ) {
		if ( typeof elementor === 'undefined' ) { return; }
		try {
			var container = null;
			if ( currentWidgetId && typeof elementor.getContainer === 'function' ) {
				container = elementor.getContainer( String( currentWidgetId ) );
			}
			if ( ! container ) {
				dbg( 'applyTemplateSettings: no container found' );
				return;
			}
			var model = container.model;
			if ( ! model || typeof model.setSetting !== 'function' ) {
				dbg( 'applyTemplateSettings: model.setSetting not available' );
				return;
			}
			Object.keys( settings ).forEach( function ( key ) {
				try {
					model.setSetting( key, settings[ key ] );
				} catch ( e ) { dbg( 'setSetting', key, e ); }
			} );
			if ( elementor.saver && typeof elementor.saver.setFlagEditorChange === 'function' ) {
				elementor.saver.setFlagEditorChange();
			}
		} catch ( e ) {
			dbg( 'applyTemplateSettings error', e );
		}
	}

	/* ---------------------------------------------------------------
	   Modal event bindings (once)
	--------------------------------------------------------------- */
	function initModalEvents() {
		var m = getModal();
		if ( ! m || m.dataset.gsmBound ) { return; }
		m.dataset.gsmBound = '1';

		// Close button
		var closeBtn = m.querySelector( '.gsm-tpl-close, .gsm-tpl-cancel' );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', closeModal );
		}
		m.querySelectorAll( '.gsm-tpl-close, .gsm-tpl-cancel' ).forEach( function ( b ) {
			b.addEventListener( 'click', closeModal );
		} );

		// Backdrop
		var backdrop = m.querySelector( '.gsm-tpl-backdrop' );
		if ( backdrop ) {
			backdrop.addEventListener( 'click', closeModal );
		}

		// Import button
		var importBtn = m.querySelector( '.gsm-tpl-import' );
		if ( importBtn ) {
			importBtn.addEventListener( 'click', importSelectedTemplate );
		}

		// Category filter
		m.addEventListener( 'click', function ( e ) {
			var filterBtn = e.target.closest ? e.target.closest( '.gsm-tpl-filter' ) : null;
			if ( filterBtn ) {
				m.querySelectorAll( '.gsm-tpl-filter' ).forEach( function ( b ) { b.classList.remove( 'on' ); } );
				filterBtn.classList.add( 'on' );
				filterTemplates( filterBtn.dataset.cat || 'all' );
				return;
			}
			var card = e.target.closest ? e.target.closest( '.gsm-tpl-card' ) : null;
			if ( card ) {
				e.preventDefault();
				selectCard( card );
			}
		} );

		// Search
		var searchInput = m.querySelector( '#gsm-tpl-search-input' );
		if ( searchInput ) {
			searchInput.addEventListener( 'input', function () {
				var activeFilter = m.querySelector( '.gsm-tpl-filter.on' );
				var cat = activeFilter ? ( activeFilter.dataset.cat || 'all' ) : 'all';
				filterTemplates( cat );
			} );
		}

		// Keyboard ESC
		document.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && m.classList.contains( 'open' ) ) {
				closeModal();
			}
		} );
	}

	/* ---------------------------------------------------------------
	   Manager panel (Export / Duplicate)
	--------------------------------------------------------------- */
	function panelExport( btn ) {
		var mgr = getMgr();
		if ( ! mgr.ajaxUrl ) { return; }
		var s = mgr.strings || {};
		var postId   = gsmEditorPostId();
		var widgetId = currentWidgetId;
		if ( ! postId || ! widgetId ) { window.alert( s.noDocument || 'Could not detect the page.' ); return; }

		var defaultLabel = btn.textContent.trim();
		btn.disabled = true;
		btn.textContent = s.exporting || 'Exporting...';

		var body = new URLSearchParams();
		body.set( 'action', 'gsm_export_slider' );
		body.set( 'nonce', mgr.nonce );
		body.set( 'post_id', postId );
		body.set( 'widget_id', widgetId );

		fetch( mgr.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
		.then( function ( r ) { return r.json(); } )
		.then( function ( res ) {
			btn.disabled = false; btn.textContent = defaultLabel;
			if ( ! res || ! res.success ) { window.alert( ( res && res.data && res.data.message ) || s.error || 'Error' ); return; }
			var blob = new Blob( [ JSON.stringify( res.data, null, 2 ) ], { type: 'application/json' } );
			var url  = URL.createObjectURL( blob );
			var a    = document.createElement( 'a' );
			a.href = url; a.download = 'gsm-slider-export.json';
			document.body.appendChild( a ); a.click(); document.body.removeChild( a );
			URL.revokeObjectURL( url );
		} )
		.catch( function () {
			btn.disabled = false; btn.textContent = defaultLabel;
			window.alert( s.error || 'Error' );
		} );
	}

	function panelDuplicate( btn ) {
		var mgr = getMgr();
		if ( ! mgr.ajaxUrl ) { return; }
		var s = mgr.strings || {};
		if ( ! window.confirm( s.confirmDuplicate || 'Duplicate?' ) ) { return; }
		var postId   = gsmEditorPostId();
		var widgetId = currentWidgetId;
		if ( ! postId || ! widgetId ) { window.alert( s.noDocument || 'Could not detect the page.' ); return; }

		var defaultLabel = btn.textContent.trim();
		btn.disabled = true;
		btn.textContent = s.duplicating || 'Duplicating...';

		var body = new URLSearchParams();
		body.set( 'action', 'gsm_duplicate_slider' );
		body.set( 'nonce', mgr.nonce );
		body.set( 'post_id', postId );
		body.set( 'widget_id', widgetId );

		fetch( mgr.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} )
		.then( function ( r ) { return r.json(); } )
		.then( function ( res ) {
			btn.disabled = false; btn.textContent = defaultLabel;
			if ( ! res || ! res.success ) { window.alert( ( res && res.data && res.data.message ) || s.error || 'Error' ); return; }
			var msg = res.data.message || '';
			if ( window.confirm( msg + '\n\n' + ( s.openElementor || 'Open in Elementor?' ) ) && res.data.elementor_url ) {
				window.open( res.data.elementor_url, '_blank', 'noopener,noreferrer' );
			}
		} )
		.catch( function () {
			btn.disabled = false; btn.textContent = defaultLabel;
			window.alert( s.error || 'Error' );
		} );
	}

	/* ---------------------------------------------------------------
	   Bind panel buttons when widget editor opens
	--------------------------------------------------------------- */
	function bindPanelButtons( panelEl, model ) {
		if ( model && typeof model.get === 'function' ) {
			currentWidgetId = model.get( 'id' ) || currentWidgetId;
		}

		// Template modal open button
		var tplBtn = panelEl ? panelEl.querySelector( '.gsm-open-templates' ) : null;
		if ( tplBtn && ! tplBtn.dataset.gsmBound ) {
			tplBtn.dataset.gsmBound = '1';
			tplBtn.addEventListener( 'click', function () {
				initModalEvents();
				openModal( currentWidgetId );
			} );
		}

		// Manager info
		var infoEl = panelEl ? panelEl.querySelector( '.gsm-mgr-info' ) : null;
		if ( infoEl && currentWidgetId ) {
			var mgr = getMgr();
			var s = mgr.strings || {};
			infoEl.textContent = ( s.infoPrefix || 'Widget ID' ) + ': ' + currentWidgetId;
		}
	}

	/* ---------------------------------------------------------------
	   Delegated click — Export / Duplicate buttons in panel
	--------------------------------------------------------------- */
	document.addEventListener( 'click', function ( e ) {
		var ex = e.target && e.target.closest ? e.target.closest( '.gsm-mgr-export' ) : null;
		if ( ex ) { e.preventDefault(); panelExport( ex ); return; }
		var du = e.target && e.target.closest ? e.target.closest( '.gsm-mgr-duplicate' ) : null;
		if ( du ) { e.preventDefault(); panelDuplicate( du ); return; }
		// Delegated open-modal
		var tplBtn = e.target && e.target.closest ? e.target.closest( '.gsm-open-templates' ) : null;
		if ( tplBtn ) {
			e.preventDefault();
			initModalEvents();
			openModal( currentWidgetId );
		}
	} );

	/* ---------------------------------------------------------------
	   Elementor hooks — bind when editor is ready
	--------------------------------------------------------------- */
	function bindElementorHooks() {
		if ( typeof elementor === 'undefined' || ! elementor.hooks || ! elementor.hooks.addAction ) {
			return;
		}

		elementor.hooks.addAction( 'panel/open_editor/widget/gsm_slider', function ( panel, model ) {
			dbg( 'panel/open_editor/widget/gsm_slider', model && model.get ? model.get( 'id' ) : null );
			var panelEl = ( panel && panel.$el && panel.$el[0] ) ? panel.$el[0] : ( panel && panel.el ? panel.el : null );
			bindPanelButtons( panelEl, model );
		} );

		elementor.hooks.addAction( 'panel/open_editor/widget', function ( panel, model ) {
			if ( model && typeof model.get === 'function' && model.get( 'widgetType' ) === 'gsm_slider' ) {
				var panelEl = ( panel && panel.$el && panel.$el[0] ) ? panel.$el[0] : ( panel && panel.el ? panel.el : null );
				bindPanelButtons( panelEl, model );
			}
		} );

		elementor.hooks.addAction( 'editor:widget:after-add', function ( widgetModel ) {
			if ( widgetModel.get( 'widgetType' ) !== 'gsm_slider' ) { return; }
			currentWidgetId = widgetModel.get( 'id' ) || currentWidgetId;
			var slidesData = widgetModel.getSetting( 'slides_data' );
			if ( ! slidesData || ! slidesData.length ) {
				setTimeout( function () {
					initModalEvents();
					openModal( currentWidgetId );
				}, 400 );
			}
		} );
	}

	// Try immediately (editor already loaded) or wait for init event.
	if ( typeof elementor !== 'undefined' && elementor.hooks ) {
		bindElementorHooks();
	} else {
		window.addEventListener( 'elementor/init', bindElementorHooks );
		// Also try on DOMContentLoaded in case elementor/init already fired.
		document.addEventListener( 'DOMContentLoaded', function () {
			if ( typeof elementor !== 'undefined' && elementor.hooks ) {
				bindElementorHooks();
			}
		} );
	}

	/* ---------------------------------------------------------------
	   Helpers
	--------------------------------------------------------------- */
	function escHtml( str ) {
		return String( str ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
	}
	function escAttr( str ) {
		return String( str ).replace( /"/g, '&quot;' ).replace( /'/g, '&#039;' );
	}

} )();
