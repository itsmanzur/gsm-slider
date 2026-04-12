jQuery( function ( $ ) {
	'use strict';

	var cfg = typeof gsmManager !== 'undefined' ? gsmManager : null;
	if ( ! cfg ) {
		return;
	}

	// ---- IMPORT ACCORDION ----
	$( '#gsm-import-toggle' ).on( 'click', function () {
		var body = $( '#gsm-import-body' );
		var arrow = $( this ).find( '.gsm-toggle-arrow' );
		var head = $( this );

		if ( body.is( ':visible' ) ) {
			body.slideUp( 200 );
			arrow.removeClass( 'open' );
			head.removeClass( 'open' );
		} else {
			body.slideDown( 200 );
			arrow.addClass( 'open' );
			head.addClass( 'open' );
		}
	} );

	function msg( res ) {
		if ( res && res.data ) {
			if ( typeof res.data === 'string' ) {
				return res.data;
			}
			if ( res.data.message ) {
				return res.data.message;
			}
		}
		return cfg.strings.error;
	}

	function cardFromBtn( btn ) {
		return $( btn ).closest( '.gsm-slider-card' );
	}

	function ensureDefaultHtml( $btn ) {
		if ( ! $btn.data( 'defaultHtml' ) ) {
			$btn.data( 'defaultHtml', $btn.html() );
		}
		return $btn.data( 'defaultHtml' );
	}

	/* ---- Import: file selected ---- */
	$( '#gsm-import-file' ).on( 'change', function () {
		var file = this.files[ 0 ];
		if ( ! file ) {
			return;
		}
		$( '#gsm-import-filename' ).text( file.name );
		$( '#gsm-import-selected' ).show();
	} );

	/* ---- Drag & drop ---- */
	var dropZone = document.getElementById( 'gsm-drop-zone' );
	var fileInput = document.getElementById( 'gsm-import-file' );

	if ( dropZone && fileInput ) {
		dropZone.addEventListener( 'dragover', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.classList.add( 'drag-over' );
		} );
		dropZone.addEventListener( 'dragleave', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.classList.remove( 'drag-over' );
		} );
		dropZone.addEventListener( 'drop', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			this.classList.remove( 'drag-over' );
			var file = e.dataTransfer.files[ 0 ];
			var name = file && file.name ? file.name.toLowerCase() : '';
			if ( file && name.endsWith( '.json' ) ) {
				var dt = new DataTransfer();
				dt.items.add( file );
				fileInput.files = dt.files;
				$( '#gsm-import-filename' ).text( file.name );
				$( '#gsm-import-selected' ).show();
			}
		} );
		dropZone.addEventListener( 'click', function ( e ) {
			var t = e.target;
			if ( t && t.tagName !== 'LABEL' && t.tagName !== 'INPUT' && ! t.closest( 'label' ) ) {
				fileInput.click();
			}
		} );
		dropZone.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' || e.key === ' ' ) {
				e.preventDefault();
				fileInput.click();
			}
		} );
	}

	/* ---- Export ---- */
	$( document ).on( 'click', '.gsm-btn-export', function () {
		var btn = $( this );
		var card = cardFromBtn( btn );
		var widgetId = card.data( 'widget-id' ) || btn.data( 'widget-id' );
		var postId = card.data( 'post-id' ) || btn.data( 'post-id' );
		var title = card.data( 'title' ) || 'gsm-slider';
		var defaultHtml = ensureDefaultHtml( btn );

		btn.prop( 'disabled', true ).html( cfg.strings.exporting );

		$.post(
			cfg.ajaxUrl,
			{
				action: 'gsm_export_slider',
				nonce: cfg.nonce,
				widget_id: widgetId,
				post_id: postId
			},
			function ( res ) {
				btn.prop( 'disabled', false ).html( defaultHtml );
				if ( ! res || ! res.success ) {
					window.alert( msg( res ) );
					return;
				}

				var blob = new Blob( [ JSON.stringify( res.data, null, 2 ) ], { type: 'application/json' } );
				var url = URL.createObjectURL( blob );
				var a = document.createElement( 'a' );
				a.href = url;
				a.download = 'gsm-' + String( title ).toLowerCase().replace( /\s+/g, '-' ) + '.json';
				document.body.appendChild( a );
				a.click();
				document.body.removeChild( a );
				URL.revokeObjectURL( url );
			},
			'json'
		).fail( function () {
			btn.prop( 'disabled', false ).html( defaultHtml );
			window.alert( cfg.strings.error );
		} );
	} );

	/* ---- Duplicate ---- */
	$( document ).on( 'click', '.gsm-btn-duplicate', function () {
		if ( ! window.confirm( cfg.strings.confirmDuplicate ) ) {
			return;
		}

		var btn = $( this );
		var card = cardFromBtn( btn );
		var widgetId = card.data( 'widget-id' ) || btn.data( 'widget-id' );
		var postId = card.data( 'post-id' ) || btn.data( 'post-id' );
		var defaultHtml = ensureDefaultHtml( btn );

		btn.prop( 'disabled', true ).html( cfg.strings.duplicating );

		$.post(
			cfg.ajaxUrl,
			{
				action: 'gsm_duplicate_slider',
				nonce: cfg.nonce,
				widget_id: widgetId,
				post_id: postId
			},
			function ( res ) {
				btn.prop( 'disabled', false ).html( defaultHtml );
				if ( ! res || ! res.success ) {
					window.alert( msg( res ) );
					return;
				}
				var openNow = window.confirm(
					res.data.message + '\n\n' + ( cfg.strings.openElementorNow || 'Open in Elementor now?' )
				);
				if ( openNow && res.data.elementor_url ) {
					window.open( res.data.elementor_url, '_blank', 'noopener,noreferrer' );
				}
			},
			'json'
		).fail( function () {
			btn.prop( 'disabled', false ).html( defaultHtml );
			window.alert( cfg.strings.error );
		} );
	} );

	/* ---- Delete ---- */
	$( document ).on( 'click', '.gsm-btn-delete', function () {
		if ( ! window.confirm( cfg.strings.confirmDelete ) ) {
			return;
		}

		var btn = $( this );
		var card = cardFromBtn( btn );
		var widgetId = card.data( 'widget-id' ) || btn.data( 'widget-id' );
		var postId = card.data( 'post-id' ) || btn.data( 'post-id' );
		var defaultHtml = ensureDefaultHtml( btn );

		btn.prop( 'disabled', true ).html( cfg.strings.deleting );

		$.post(
			cfg.ajaxUrl,
			{
				action: 'gsm_delete_slider',
				nonce: cfg.nonce,
				widget_id: widgetId,
				post_id: postId
			},
			function ( res ) {
				if ( ! res || ! res.success ) {
					btn.prop( 'disabled', false ).html( defaultHtml );
					window.alert( msg( res ) );
					return;
				}
				card.fadeOut( 400, function () {
					$( this ).remove();
					filterCards();
					updateBulkBar();
				} );
			},
			'json'
		).fail( function () {
			btn.prop( 'disabled', false ).html( defaultHtml );
			window.alert( cfg.strings.error );
		} );
	} );

	/* ---- Import submit ---- */
	$( '#gsm-import-btn' ).on( 'click', function () {
		var fileInputEl = $( '#gsm-import-file' )[ 0 ];
		var file = fileInputEl && fileInputEl.files ? fileInputEl.files[ 0 ] : null;
		if ( ! file ) {
			window.alert( cfg.strings.selectFile || 'Please select a JSON file first.' );
			return;
		}

		var btn = $( this );
		var result = $( '#gsm-import-result' );
		var fd = new FormData();

		fd.append( 'action', 'gsm_import_slider' );
		fd.append( 'nonce', cfg.nonce );
		fd.append( 'import_file', file );

		var defaultHtml = ensureDefaultHtml( btn );
		var labelImporting = cfg.strings.importing;

		btn.prop( 'disabled', true ).text( labelImporting );
		result.hide().empty().removeClass( 'gsm-alert--success gsm-alert--error' );

		$.ajax( {
			url: cfg.ajaxUrl,
			type: 'POST',
			data: fd,
			processData: false,
			contentType: false,
			dataType: 'json'
		} )
			.done( function ( res ) {
				btn.prop( 'disabled', false ).html( defaultHtml );
				if ( ! res || ! res.success ) {
					result
						.removeClass( 'gsm-alert--success' )
						.addClass( 'gsm-alert gsm-alert--error' )
						.text( msg( res ) )
						.show();
					return;
				}
				var link = res.data.elementor_url
					? ' <a href="' + res.data.elementor_url + '" target="_blank" rel="noopener noreferrer">Open in Elementor</a>'
					: '';
				result
					.removeClass( 'gsm-alert--error' )
					.addClass( 'gsm-alert gsm-alert--success' )
					.html( res.data.message + link )
					.show();
				$( '#gsm-import-file' ).val( '' );
				$( '#gsm-import-selected' ).hide();
				$( '#gsm-import-filename' ).empty();
				window.setTimeout( function () {
					window.location.reload();
				}, 3000 );
			} )
			.fail( function () {
				btn.prop( 'disabled', false ).html( defaultHtml );
				result
					.removeClass( 'gsm-alert--success' )
					.addClass( 'gsm-alert gsm-alert--error' )
					.text( cfg.strings.error )
					.show();
			} );
	} );

	// ---- SEARCH ----
	$( '#gsm-search' ).on( 'input', function () {
		filterCards();
	} );

	// ---- FILTER ----
	$( '#gsm-filter-status, #gsm-filter-type' ).on( 'change', function () {
		filterCards();
	} );

	function filterCards() {
		var search = String( $( '#gsm-search' ).val() || '' ).toLowerCase();
		var status = $( '#gsm-filter-status' ).val();
		var type = $( '#gsm-filter-type' ).val();
		var visible = 0;

		$( '.gsm-slider-card' ).each( function () {
			var $card = $( this );
			var title = String( $card.find( '.gsm-slider-card__title' ).text() || '' ).toLowerCase();
			var cardSt = String( $card.data( 'status' ) || '' );
			var cardType = String( $card.data( 'type' ) || '' );

			var matchSearch = ! search || title.indexOf( search ) !== -1;
			var matchStatus = status === 'all' || cardSt === status;
			var matchType = type === 'all' || cardType === type;

			if ( matchSearch && matchStatus && matchType ) {
				$card.show();
				visible++;
			} else {
				$card.hide();
			}
		} );

		$( '#gsm-results-count' ).text( visible + ' slider' + ( visible !== 1 ? 's' : '' ) );
	}

	// Initialize count
	filterCards();

	// ---- BULK SELECT ----
	$( document ).on( 'change', '.gsm-card-check', function () {
		updateBulkBar();
	} );

	function updateBulkBar() {
		var checked = $( '.gsm-card-check:checked' ).length;
		if ( checked > 0 ) {
			$( '#gsm-bulk-bar' ).show();
			$( '#gsm-selected-count' ).text( checked + ' selected' );
		} else {
			$( '#gsm-bulk-bar' ).hide();
		}
	}

	// Select on preview click
	$( document ).on( 'click', '.gsm-slider-card__preview', function ( e ) {
		if ( $( e.target ).is( 'button, a, input, label' ) ) {
			return;
		}
		var checkbox = $( this ).find( '.gsm-card-check' );
		checkbox.prop( 'checked', ! checkbox.prop( 'checked' ) );
		updateBulkBar();
	} );

	// Deselect all
	$( '#gsm-deselect-all' ).on( 'click', function () {
		$( '.gsm-card-check' ).prop( 'checked', false );
		updateBulkBar();
	} );

	// ---- BULK EXPORT ----
	$( '#gsm-bulk-export' ).on( 'click', function () {
		var selected = [];
		$( '.gsm-card-check:checked' ).each( function () {
			selected.push( {
				widgetId: $( this ).data( 'widget-id' ),
				postId: $( this ).data( 'post-id' ),
			} );
		} );
		if ( ! selected.length ) {
			return;
		}

		var idx = 0;
		function exportNext() {
			if ( idx >= selected.length ) {
				return;
			}
			var item = selected[ idx++ ];
			$.post( cfg.ajaxUrl, {
				action: 'gsm_export_slider',
				nonce: cfg.nonce,
				widget_id: item.widgetId,
				post_id: item.postId,
			}, function ( res ) {
				if ( res && res.success ) {
					var blob = new Blob( [ JSON.stringify( res.data, null, 2 ) ], { type: 'application/json' } );
					var url = URL.createObjectURL( blob );
					var a = document.createElement( 'a' );
					a.href = url;
					a.download = 'gsm-slider-' + item.postId + '.json';
					document.body.appendChild( a );
					a.click();
					document.body.removeChild( a );
					URL.revokeObjectURL( url );
				}
				window.setTimeout( exportNext, 500 );
			}, 'json' ).fail( function () {
				window.setTimeout( exportNext, 500 );
			} );
		}
		exportNext();
	} );

	// ---- BULK DELETE ----
	$( '#gsm-bulk-delete' ).on( 'click', function () {
		var selected = [];
		$( '.gsm-card-check:checked' ).each( function () {
			selected.push( {
				widgetId: $( this ).data( 'widget-id' ),
				postId: $( this ).data( 'post-id' ),
				card: $( this ).closest( '.gsm-slider-card' ),
			} );
		} );
		if ( ! selected.length ) {
			return;
		}
		if ( ! window.confirm( 'Delete ' + selected.length + ' slider(s)? This cannot be undone.' ) ) {
			return;
		}

		var idx = 0;
		function deleteNext() {
			if ( idx >= selected.length ) {
				updateBulkBar();
				filterCards();
				return;
			}
			var item = selected[ idx++ ];
			$.post( cfg.ajaxUrl, {
				action: 'gsm_delete_slider',
				nonce: cfg.nonce,
				widget_id: item.widgetId,
				post_id: item.postId,
			}, function ( res ) {
				if ( res && res.success ) {
					item.card.fadeOut( 300, function () {
						$( this ).remove();
					} );
				}
				window.setTimeout( deleteNext, 300 );
			}, 'json' ).fail( function () {
				window.setTimeout( deleteNext, 300 );
			} );
		}
		deleteNext();
	} );
} );
