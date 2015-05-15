(function( $ ) {
	'use strict';

	$(function() {
		
		$.fn.afgCallToAction = function( p1, p2 ) {
			
			var opts = $.extend( {}, $.fn.afgCallToAction.defaults, p1 );

			if( this.length === 0 ){ return this; }
			
		    return this.each(function() {
		    	
		    	var $ctrl = $(this), $input, $boxes;

		    	init();
		    	
		    	function init(){
		    		
		    		$ctrl.addClass('afg-call-to-action-widget'); /*in case it was not there yet*/		    		
		    		
			    	if( typeof $ctrl.data('afg-already-created') !== 'undefined' && $ctrl.data('afg-already-created') != null ){
			    		//console.log( $ctrl.attr('id'), 'already-created' );
			    		return;
			    	}
			    	
			    	if( 'dummy' == $ctrl.find('afg-element').data('nonce') ){
			    		$ctrl.find('.option.preview').hide();
			    		//console.log( $ctrl.find('.option.preview') );
			    	}
			    	
			    	$input = $ctrl.find('input[type="hidden"].items').first();
		    		$ctrl.find('input[type=checkbox],select').on('change.afg', onOptionChanged);
		    		$ctrl.find('input[type=text]').on('keyup.afg', onOptionChanged);
			    	
			    	$ctrl.uniqueId();			    	

			    	$ctrl.data('afg-already-created', true);
		    		
		    	} /* init */
		    	
		    	function onOptionChanged(ev){
		    		collectOptions( ev );
	    			downloadContent( ev );
		    	}
		    	
		    	function collectOptions( ev ){
		    		var hint, current = $.parseJSON( $input.val() );
		    		
		    		$.each( $ctrl.find('input[type=text]' ), function(idx, el){
		    			current[ el.getAttribute('data-opt') ] = el.value;
		    		});
		    		
		    		$.each( $ctrl.find('input.show[type=checkbox]'), function(idx, el){
		    			if( el.checked ){
		    				current[ el.getAttribute('data-opt') ] = el.getAttribute('data-checked');
		    			} else {
		    				current[ el.getAttribute('data-opt') ] = el.getAttribute('data-unchecked');
		    			}
		    		});
		    		
		    		$.each( $ctrl.find('select' ), function(idx, el){		    			
		    			current[ el.getAttribute('data-opt') ] = el.options[ el.selectedIndex ].value;
		    			if( 'link-url' == el.getAttribute('data-opt') ){
		    				current['link-attr'] = el.options[ el.selectedIndex ].getAttribute('data-attr');
		    				current['product'] = $ctrl.find('[data-opt="product"]').val();
		    				
		    				if( ev.target.getAttribute('data-opt') != 'hint' ){
			    				current['hint'] = affiget.params.call_to_action['hints'][ current['link-attr'] ];
			    				current['hint'] = current['hint'].replace('%s', current['product']);
			    				$ctrl.find('[data-opt="hint"]').val( current['hint'] );
		    				}
		    				if( ev.target.getAttribute('data-opt') != 'caption' ){
			    				current['caption'] = affiget.params.call_to_action['captions'][ current['link-attr'] ];
			    				current['caption'] = current['caption'].replace('%s', current['product']);
			    				if(current['caption'][0] != '-'){
			    					current['caption'] = '-'+current['caption'];
			    				}
			    				$ctrl.find('[data-opt="caption"]').val( current['caption'] );
		    				}
		    			}
		    		});		   
		    		
		    		$input.val( JSON.stringify( current ) );
		    		return current;
		    	}
		    	
		    	function downloadContent( ev ){		    		
		    		var data = {}, img;
		    		
	    			if( 'img-name' == ev.target.getAttribute('data-opt') ){
	    				img = new Image();
	    				img.onload = function() {
	    					var current = $.parseJSON( $input.val() )
	    					$ctrl.find('.afg-element .content img').replaceWith( img );
	    					current['img-width']  = this.width;
	    					current['img-height'] = this.height;
	    					$input.val( JSON.stringify( current ) );
	    				}
	    				img.src = '/'+ev.target.options[ ev.target.selectedIndex ].value;
	    				//console.log(img.src);
	    				return;
	    			}
		    		
		    		data['action']  = opts.actionRetrieve;		    		
		    		data['field']   = opts.field;
		    		data['options'] = $input.val();
		    		data['_nonce']  = $ctrl.data('nonce');
		    		data['afg_post_id'] = $('#post_ID').val();
		    		
		    		var jqxhr = $.post( opts.wpAjaxUrl, data, function( response ){
		    			var $resp = $(response);

		    			if( $resp.children('.content').length ){
		    				$ctrl.find('.content').replaceWith( $resp.children('.content').eq(0) );	
		    			} else {
		    				$ctrl.find('.content').empty();
		    			}
		    					    			
		    			if( $resp.has('.js_new_data').length ){
		    				$input.val( $resp.find('.js_new_data').val() );
		    			}
		    			
		    			$ctrl.find('.spinner').hide();
		    			
    				}, 'html')
    				.fail( function( response ){
	    				$ctrl.find('.spinner').hide();	    				
	    				//console.log( response );
    				});		    		
		    	} /* downloadContent */
		});
	};
		
	$.fn.afgCallToAction.defaults = {
			wpAjaxUrl:      '/wp-admin/admin-ajax.php',
			actionRetrieve: 'afg_retrieve_review_field',				
			actionUpdate:   'afg_update_review_field',
			field:          'call_to_action',			
    };

	/* params will be feeded to page via wp_localize_script */
	window.affiget = window.affiget || {}; 
	window.affiget.params = window.affiget.params || {};
	window.affiget.params.call_to_action = window.affiget.params.call_to_action || {};
	
	$.fn.afgCallToAction.defaults = $.extend( $.fn.afgCallToAction.defaults, affiget.params.call_to_action );
	
	$('.afg-call-to-action-widget').afgCallToAction();
	
	/* Triggered by FLBuilder */
	$(document).on('preview-rendered', 'body', function(e){ 
		$('.afg-call-to-action-widget').afgCallToAction();
	});
	
	/* Triggered by FLBuilder after every content update */
	$(window).resize( function(e){
		$('.afg-call-to-action-widget').afgCallToAction(); 
	});
	
	/*Page Builder by SiteOrigin triggers this */
	$(document).on('panelsopen', function(e) {
	    var dialog = $(e.target);
	    //console.log('panelsopen', e);
	    // Check that this is for our widget class
	    if( !dialog.has('.afg-call-to-action-widget') ) return;

	    $('.afg-call-to-action-widget').afgCallToAction();
	});
	
	$(document).on('form_loaded', function(e1, e2) {
		//console.log('form_loaded', e1, e2);
	});

	/* Event on the standard widgets page at /wp-admin/widgets.php */
	$(document).on('widget-updated widget-added', function(e){
		//console.log(e);
	    $('.afg-call-to-action-widget').afgCallToAction();
	});			
	
  });
		    
})( jQuery );