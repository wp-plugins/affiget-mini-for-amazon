(function( $ ) {
	'use strict';

	$(function(){
		var $form = $('#amazon_settings_form');
		$form.on('submit', function(e) {
			var $spin = $('.afg-spinner', $form);			
			$spin.css('visibility', 'visible');
		    $.post( $form.attr('action'), $form.serialize(), function(response) {
		    		$('#access_key', $form).val( response.data['access_key'] );
		    		$('#secret_key', $form).val( response.data['secret_key'] );
		    		$('#associate_id', $form).val( response.data['associate_id'] );
		    		$spin.css('visibility', 'hidden');
		    		if( response.success ){
		    			$('.afg-invalid-amazon', $form).hide();
		    			$('.afg-valid-amazon', $form).fadeIn();
		    			$('.afg-warning.amazon-settings').fadeOut();
		    		} else {
		    			$('.afg-valid-amazon', $form).hide();
		    			$('.afg-invalid-amazon', $form).fadeIn();
		    			$('.afg-warning.amazon-settings').slideDown();
		    		}		    		
		       },
		       'json' // we are expecting a JSON response
		    );
		    return false; //do not perform actual submit
		});
		
		$('#access_key, #secret_key, #associate_id, #locale', $form).on('change', function(){
			//console.log('changed', this);
			$('.afg-invalid-amazon, .afg-valid-amazon', $form).fadeOut();
			//$('.afg-warning.amazon-settings').slideDown();
		});
	 });
})( jQuery );