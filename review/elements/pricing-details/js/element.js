(function( $ ) {
	'use strict';

	$(function() {
		
		$.fn.afgPricingDetails = function( p1, p2 ) {
			
			var opts = $.extend( {}, $.fn.afgPricingDetails.defaults, p1 );

			if( this.length === 0 ){ return this; }
			
		    return this.each(function() {
		    	
		    	var $ctrl = $(this), $input, $boxes;

		    	init();
		    	
		    	function init(){
		    		
		    		$ctrl.addClass('afg-pricing-details-widget'); /*in case it was not there yet*/		    		
		    		
			    	if( typeof $ctrl.data('afg-already-created') !== 'undefined' && $ctrl.data('afg-already-created') != null ){
			    		//console.log( $ctrl.attr('id'), 'already-created' );
			    		return;
			    	}
			    	
			    	if( 'dummy' == $ctrl.find('afg-element').data('nonce') ){
			    		$ctrl.find('.option.preview').hide();
			    		//console.log( $ctrl.find('.option.preview') );
			    	}
			    	
			    	$input = $ctrl.find('input.items').first();
		    		$ctrl.find('input[type=checkbox]').on('change.afg', onOptionChanged);
			    	
			    	$ctrl.uniqueId();			    	

			    	$ctrl.data('afg-already-created', true);
		    		
		    	} /* init */
		    	
		    	function onOptionChanged(){
		    		collectOptions();
		    		if( ! $('body.wp-admin.widgets-php').length ){
		    			downloadContent();
		    		}
		    	}
		    	
		    	function collectOptions(){
		    		var current = $.parseJSON( $input.val() );
		    		
		    		current.options.allow_conditions = [];
		    		$.each( $ctrl.find('input.condition[type=checkbox]'), function(idx, bx){
		    			if( bx.checked )
		    				current.options.allow_conditions.push( bx.getAttribute('value') );
		    		});
		    		
		    		current.options.show_details = [];
		    		$.each( $ctrl.find('input.show[type=checkbox]'), function(idx, bx){
		    			if( bx.checked )
		    				current.options.show_details.push( bx.getAttribute('value') );
		    		});
		    		
		    		$input.val( JSON.stringify( current ) );
		    		return current;
		    	}
		    	
		    	function downloadContent(){		    		
		    		var data = {};
		    		
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
		
	$.fn.afgPricingDetails.defaults = {
			wpAjaxUrl:      '/wp-admin/admin-ajax.php',
			actionRetrieve: 'afg_retrieve_review_field',				
			actionUpdate:   'afg_update_review_field',
			field:          'pricing_details'
    };

	/* params will be feeded to page via wp_localize_script */
	window.affiget = window.affiget || {}; 
	window.affiget.params = window.affiget.params || {};
	window.affiget.params.pricing_details = window.affiget.params.pricing_details || {};
	
	$.fn.afgPricingDetails.defaults = $.extend( $.fn.afgPricingDetails.defaults, affiget.params.pricing_details );
	
	$('.afg-pricing-details-widget').afgPricingDetails();
	
	/* Triggered by FLBuilder */
	$(document).on('preview-rendered', 'body', function(e){ 
		$('.afg-pricing-details-widget').afgPricingDetails();
	});
	
	/* Triggered by FLBuilder after every content update */
	$(window).resize( function(e){
		$('.afg-pricing-details-widget').afgPricingDetails(); 
	});
	
	/*Page Builder by SiteOrigin triggers this */
	$(document).on('panelsopen', function(e) {
	    var dialog = $(e.target);
	    //console.log('panelsopen', e);
	    // Check that this is for our widget class
	    if( !dialog.has('.afg-pricing-details-widget') ) return;

	    $('.afg-pricing-details-widget').afgPricingDetails();
	});
	
	$(document).on('form_loaded', function(e1, e2) {
		//console.log('form_loaded', e1, e2);
	});

	/* Event on the standard widgets page at /wp-admin/widgets.php */
	$(document).on('widget-updated widget-added', function(e){
		//console.log(e);
	    $('.afg-pricing-details-widget').afgPricingDetails();
	});			
	
  });
		    
})( jQuery );