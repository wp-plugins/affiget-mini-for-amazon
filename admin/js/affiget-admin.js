(function( $ ) {
	'use strict';

	$(function() {		
		var $ctrl = $('#afg-display-formats-metabox ul'), $input, $dragger, $disabler;
		
		if( ! $ctrl.length ) return;
		
		$input    = $ctrl.parent().find('input[name="afg_display_formats"]');
		
		$disabler = $('<span class="offset-right"><span class="disabler ui-icon"></span></span>');
		$dragger  = $('<span class="offset-left"><span class="dragger ui-icon ui-icon-reorder" title="'+window.affiget.params.msg['dragItemHint']+'"></span></span>');
		
		$ctrl.sortable({
			'axis': 'y', 
			'handle': '.dragger',
		    'stop': function( ) {
		    	updateHiddenInput();
	            return true;
	        },
			'helper': fixWidthHelper
		}).disableSelection();
		
		$ctrl.on({
			'click.afg':      onItemClick,
			'mouseenter.afg': onItemMouseEnter
		}, 'li.element');
		
		function fixWidthHelper(e, ui) {
			ui.children().each(function() {
				$(this).width( $(this).width() );
			});
			return ui;
		} /* fixWidthHelper */		
		
		function onItemMouseEnter( ev ){
    		var $item = $(ev.target), $format;
    		
    		//console.log('onItemMouseEnter', $item );

    		if( ! $item.is('li') ){
    			$item = $item.closest('li'); 
    		}
    		
    		$format = $item.siblings('.format');
  		
    		if( $item.is('.disabled') ){
    			$disabler.attr('title', window.affiget.params.msg['disabledItemHint'].replace('%1$s',$item.text()).replace('%2$s',$format.text()) ).children().addClass('ui-icon-hide').removeClass('ui-icon-show');
    		} else {
    			$disabler.attr('title', window.affiget.params.msg['enabledItemHint'].replace('%1$s',$item.text()).replace('%2$s',$format.text()) ).children().addClass('ui-icon-show').removeClass('ui-icon-hide');	
    		}
    		
    		$item.prepend( $dragger ).prepend( $disabler ); 
    		
    		return false;
    	}/* onItemMouseEnter */
		
    	function onItemClick( ev ){
			var $trg = $(ev.target), $item;
			
			$item = $trg.closest('li');
			
			if( $trg.is('.disabler') ){
				onDisablerClick( $item, $trg );
				ev.stopPropagation();
				return false;
			} else if( $trg.is('li') ){
				showItemEditor( $item, $trg );		    				
    		}
    	} /* onItemClick */
    	
    	function onDisablerClick( $item, $icon ){
    		//console.log('onDisablerClick', $icon, $item );
			if( $icon.is('.ui-icon-show')){
				$icon.removeClass('ui-icon-show').addClass('ui-icon-hide');
			} else {
				$icon.removeClass('ui-icon-hide').addClass('ui-icon-show');
			}
			$item.toggleClass('disabled');
			updateHiddenInput();
    	}
    	
    	function showItemEditor( $item, $target ){
    		//console.log('showItemEditor', $item, $target);
    	}		

    	function collectData(){
    		var data = {};

    		$ctrl.find('.format').each( function(idx, el){
				var $el = $(el), fmt = $el.data('fmt');			
				$ctrl.find('li.'+fmt).each( function(idx2, item){
					var $item = $(item);
					if( typeof data[ $item.data('elem') ] === 'undefined' ){
						data[ $item.data('elem') ] = {
							'label': $item.text(),
							'title': $item.data('title'),
							'description': $item.attr('title'),
							'display': {}
						};
					}
					data[ $item.data('elem') ]['display'][ fmt ] = [
					  $item.is('.disabled') ? 0 : 1,
					  $item.data('mode'),
					  idx2*10
					]
				});
			});
    		//console.log(data);
    		
    		return data;
    	}
    	
		function updateHiddenInput(){
			var data;
			//console.log('updateHiddenInput');
			
			data = collectData();
			
			$input.val( JSON.stringify( data ) );
			
			//console.log($input.val());
		} /* updateHiddenInput */    	
	});
	
	$(function(){
		var $form = $('#amazon_settings_form');
		
		if( ! $form.length ) return;
		
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