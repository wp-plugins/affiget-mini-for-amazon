(function( $ ) {
	'use strict';

	$(function() {
		
		$.fn.afgFeatureList = function( p1, p2 ) {
			
			var opts = {}, tp1, method = 'init';

			if( this.length === 0 ){ return this; }
			
			tp1 = $.type(p1);
			
	        if (tp1 == 'object' || p1 === undefined || p1 === null) {
	        	opts = $.extend( {}, $.fn.afgFeatureList.defaults, p1 ); //wants to init new plugin(s).
	        } else if (tp1 == 'string' && p1 === 'stopEditing' && p2 === undefined) {
	        	method = 'stopEditing';
	        }

		    return this.each(function() {
		    	
		    	var $ctrl = $(this), $input, $inLabel, $inValue, $inLabelRevert, $inValueRevert, $disabler, post, nonce;
		    	
		    	if( 'stopEditing' == method ){
		    		return stopEditing();
		    	}

		    	init();

		    	function init(){
		    		var $post, $edit, $done;
		    		
			    	if( typeof $ctrl.data('afg-already-created') !== 'undefined' && $ctrl.data('afg-already-created') != null){
			    		//console.log( 'afg-feature-list ', $ctrl.attr('id'), 'already-created' );
		    			//refresh view, maybe?
			    		return;
			    	}

			    	$ctrl.uniqueId();			    	
			    	
			    	/* if element has no post_id, we need to download actual content and reinitialize */
			    	/*post = $ctrl.data('post') || false;
			    	if( ! post ){
			    		
			    		if( typeof $ctrl.data('attempted-loading') != 'undefined' ){ 
			    			return; 
			    		}
			    		
				    	$ctrl.find('.spinner').show();			    		
			    		downloadContent();
			    		return;
			    	}*/			    		
			    	
			    	$ctrl.data('mode', opts.initialMode);
			    	
			    	/* if element has no nonce data, it will be read-only */			    	
			    	nonce = $ctrl.data('nonce') || false;
			    	if( nonce ){
			    		$ctrl.addClass('editable');
			    		
			    		$edit = $('<div class="edit" title="Modify product details"><span>Edit</span></div>');
			    		$edit.on('click', startEditing );
			    		$ctrl.prepend( $edit );
			    		$done = $('<div class="close" title="Finish editing"><span>Done</span></div>');
			    		$done.on('click', stopEditing );

			    		$ctrl.append( $done );
			    	
				    	/*$ctrl.on( 'click.afg', function(ev){
				    		$('.afg-feature-list').not( $ctrl ).afgFeatureList('stopEditing');
				    		startEditing();			    		
				    		return false;			    		
				    	});*/
	
				    	$ctrl.find('table,ul,th,li.label,td,li.span,div.list,div.item').disableSelection();
				    	$disabler = $('<span class="offset-right"><span class="disabler ui-icon"></span></span>');
			    	}				    	
				    	
			    	//if( nonce || ! hasContent() ){ //the content is editable OR not there yet 
			    		prepareInput();
			    	//}
			    	
			    	$ctrl.data('afg-already-created', true);
		    		
		    	} /* init */		  
		    	
			    function prepareInput(){   	
			    	
			    	/* Serialized rating data will be stored in one place only: input.value;
			    	   If input can be found, an internal input gets created, 
			    	   and data for it either resolved from the contained list or gets downloaded. 
			    	*/
			    	
			    	$input = $ctrl.find('input');
		    		if( ! $input.length ){
		    			$input = $( '#'+$ctrl.data('input') );
		    			if( ! $input.length ){
			    			/* create a new element */
			    			$input = $('<input id="'+$ctrl.data('input')+'" name="'+ $ctrl.data('field') +'" type="hidden" />').appendTo( $ctrl );
				    		$input.val( JSON.stringify( collectData()) );
		    			}
		    		}		    		
		    	} /* prepareInput */
		    	
		    	
		    	function _extendListForEditing(){		    		
		    		var entryTmpl; //= '<tr class="new-entry hidden"><th></th><td></td></tr>';
		    		
		    		if( ! $ctrl.is('.extended')){
			    		$ctrl.find('th').contents().wrap('<span class="label"></span>');
			    		$ctrl.find('th:empty').append('<span class="label"></span>');
			    		$ctrl.find('td').contents().wrap('<span class="value"></span>');
			    		$ctrl.find('td:empty').append('<span class="value"></span>');
			    		
			    		$ctrl.find('.label,li.label').before('<span class="offset-left"><span class="dragger ui-icon ui-icon-shuffle"></span></span>');

			    		if( $ctrl.is('.contains-table') ){
				    		$ctrl.find('.list tbody').sortable({
				    			'axis':'y', 
				    			'handle': '.dragger',
				    		    'stop': function( ) {
				    	            submitData();
				    	            return true;
				    	        }								    			
				    		});
			    		} else {
				    		$ctrl.find('.list').sortable({
				    			'axis':'y', 
				    			'handle': '.dragger',
				    		    'stop': function( ) {
				    	            submitData();
				    	            return true;
				    	        }				    			
				    		});			    			
			    		}
			    		$ctrl.addClass('extended');
		    		}
		    	} /* _extendListForEditing */
		    	
		    	function onDisablerClick( $icon, $item ){
		    		
    				if( $icon.is('.ui-icon-circlesmall-close')){
    					$icon.removeClass('ui-icon-circlesmall-close').addClass('ui-icon-circlesmall-plus');
    				} else {
    					$icon.removeClass('ui-icon-circlesmall-plus').addClass('ui-icon-circlesmall-close');
    				}
    				$item.toggleClass('disabled');
    				
    				submitData();
    				
		    	} /* onDisablerClick */
		    	
		    	function onItemClick( ev ){
	    			var $trg = $(ev.target), $item = $trg.closest('.item');	  
	    			
	    			if( $trg.is('.disabler') ){
	    				onDisablerClick( $trg, $item );
	    				ev.stopPropagation();
	    				return false;
	    			} else if( $trg.is('span.label,span.value,label') ){
	    				showItemEditor( $item, $trg);
	    			} else if( $trg.is('th') ){
	    				showItemEditor( $item, $trg.children('.label'));		    				
	    			} else if( $trg.is('td') ){
	    				showItemEditor( $item, $trg.children('.value'));		    				
	    			} else if( $trg.is('a') ){
		    			showItemEditor( $item, $trg.parent());
		    			return false; /*prevent navigation*/
		    		}
		    	} /* onItemClick */

	    		function onItemMouseEnter( ev ){
		    		var $item = $(ev.target);

		    		if( ! $item.is('.item') ){
		    			$item = $item.closest('.item'); 
		    		}	    		
		    		
		    		if( $item.is('.disabled') ){
		    			$disabler.attr('title','Unhide item').children().addClass('ui-icon-circlesmall-plus').removeClass('ui-icon-circlesmall-close');
		    		} else {
		    			$disabler.attr('title','Hide item').children().addClass('ui-icon-circlesmall-close').removeClass('ui-icon-circlesmall-plus');	
		    		}
		    		
		    		if( $ctrl.is('.contains-table') ){
		    			$item.find('td').prepend( $disabler );
		    		}
		    		
		    		return false;
		    	}/* onItemMouseEnter */

	    		function onItemMouseLeave( ev ){/*not used*/
		    		var $item = $(ev.target);
		    		
		    		if( ! $item.is('.item') ){
		    			$item = $item.closest('.item'); 
		    		}
		    		
		    		return false;
		    	}/* onItemMouseLeave */	    		
		    	
		    	function startEditing(){ 

		    		if( !$ctrl.is('.editable') ){
		    			//console.log( 'This control is not editable!');
		    			return;
		    		}
		    		if( $ctrl.data('mode') === 'editing' ) return;
		    		
		    		//console.log( $ctrl.attr('id'),'start editing');		    		
		    		
		    		_extendListForEditing();
		    		
		    		$ctrl.find('.disabled').removeClass('hidden');
		    		
		    		$ctrl.on({
			    			'click.afg':      onItemClick,
			    			'mouseenter.afg': onItemMouseEnter
			    			//'mouseleave.afg': onItemMouseLeave 
	    				}, '.item' )
		    			.addClass('editing')
		    			.data('mode', 'editing');
		    		
			    	/*$ctrl.width( $ctrl.width() + 29 );*//* compensate for fat border */		    		
		    		
		    	} /* startEditing */
		    	
		    	function stopEditing(){/* public method */
		    		
		    		if( $ctrl.data('mode') !== 'editing' ) return;
		    		
		    		//console.log( $ctrl.attr('id'), 'stop editing');
		    		
		    		$ctrl.find('.disabled').addClass('hidden');
		    		
		    		//console.log($ctrl.attr('id'),'unbinding item events', $ctrl.find('.item').length);
		    		
		    		$ctrl.off('.afg', '.item')
		    			.removeClass('editing')
	    				.data('mode', 'viewing');
	    				
		    		$('html, body').animate({
                        scrollTop: ($ctrl.offset().top - 50)
                    }, 1000);		    		
	    			
	    			/*$ctrl.width( $ctrl.width() - 29 );*//* fat border is no more, restore original width*/	    			
	    			
		    		stopItemEdit(null, null, true);
		    		submitData();
		    		
	    			return $ctrl;
		    	} /* stopEditing */
		    	
		    	function stopItemEdit( $inLabel, $inValue, $hideInput ){
		    		var $label, $value;
		    		
		    		$inLabel = $inLabel || $ctrl.find('input.label');
		    		$inValue = $inValue || $ctrl.find('input.value');		    		 
		    		
		    		if( ! $inLabel.length || ! $inValue.length ){ 
		    			return;
		    		}
		    		
		    		//console.log('stopItemEdit', $inLabel, $inValue, $hideInput);
		    		
		    		$label = $inLabel.prev(); 
    				$label.data('initial-val', $inLabel.val()).removeData('modified-val');
    				if( $label.has('a').length ){    					
    					$label.find('a').text( $inLabel.val() );
					} else {
						$label.text( $inLabel.val() );
					}	    			
    				
		    		$value = $inValue.prev(); 
    				$value.data('initial-val', $inValue.val()).removeData('modified-val');
    				if( $value.has('a').length ){
    					$value.find('a').text( $inValue.val() );
					} else {
						$value.text( $inValue.val() );
					}
    				if( $hideInput ){
	    				$inLabel.hide().next().hide();    				
	    				$label.show();
	    				$inValue.hide().next().hide();
	    				$value.show();
		    		}
		    	}
		    	
	    		function showItemEditor( $item, $active ){
	    			var $label, $value;

    				$label   = $item.find('.label:not(input)');
    				$value   = $item.find('.value:not(input)');
	    			
	    			setupItemEdit();
	    			startItemEdit();
	    			
	    			function setupItemEdit(){
	    				
	    				if( typeof $inLabel === 'undefined' ){
			    			$inLabel = $('<input class="label" type="text" />');
			    			$inLabel.on('keyup', function(ev){
			    				if( ev.keyCode == 27 ){
			    					cancelInput(null, $inLabel);
			    				} else if( ev.keyCode == 13 ){
			    					applyInput(null, $inLabel);
			    				}
			    				ev.preventDefault();
			    				ev.stopPropagation();
			    				return false;
			    			});
			    			
	    					$inValue = $('<input class="value" type="text" />');
			    			$inValue.on('keyup', function(ev){
			    				if( ev.keyCode == 27 ){
			    					cancelInput( null, $inValue );
			    				} else if( ev.keyCode == 13 ){
			    					applyInput( null, $inValue );
			    				}
			    				ev.preventDefault();
			    				ev.stopPropagation();
			    				return false;
			    			});			    			
	    					$inLabelRevert  = $('<span class="offset-above"><span class="ui-icon ui-icon-arrowrefresh-1-w"></span></span>');
	    					$inValueRevert  = $('<span class="offset-above"><span class="ui-icon ui-icon-arrowrefresh-1-w"></span></span>');
	    				} else {
	    					cancelInput();     				
	    				}
	    			} /* setupItemEdit */
	    			
	    	    	function startItemEdit(){
	    	    		var val = '';

	    	    		if( typeof $label.data('modified-val') !== 'undefined' && $label.data('modified-val') !== null){
	    	    			//console.log('continue editing: ', $label.data('modified-val'));
	    	    			val = $label.data('modified-val');
	    	    		} else {
	    	    			val = $label.text();
	    	    		}    	
	    	    		$inLabel.val( val );
	    	    		//console.log( $inLabel.val() );	    	    		
	    	    		$label.data('initial-val', $label.text()).hide();
	    	    		
	    	    		if( typeof $value.data('modified-val') !== 'undefined' && $value.data('modified-val') !== null){
	    	    			val = $value.data('modified-val');
	    	    		} else {
	    	    			val = $value.text();
	    	    		}    		
	    	    		$inValue.val( val );
	    	    		$value.data('initial-val', $value.text()).hide();	    	    		
	    	    		
	    	    		unveilItemEdit();
	    	    	} /* startItemEdit */
	    	    	
	    	    	function unveilItemEdit( callback, delay ){	    	    		
	    	    		var h, lh = $label.innerHeight(), vh = $value.innerHeight();

	    	    		h = (vh > 0 && vh < lh) ? vh : lh;/*smaller nonzero*/  
	    	    		
	    	        	delay = delay || 600;
	    	        	
	    				$inLabel
	    					.css({
								'width': $label.parent().width(),
								'height': h,
								'font': $label.parent().css('font'),
								'text-transform': $label.css('text-transform'),
								'background': $label.parent().css('background')
							})
							.insertAfter( $label )
	    	        		.after( $inLabelRevert )
	    	        		.show();
    				
		    			$inValue
			    			.css({
								'width': $value.parent().width(),
								'height': h,			    							
								'font': $value.parent().css('font'),
								'text-transform': $value.css('text-transform'),
								'background': $value.parent().css('background')
							})
	    	        		.insertAfter( $value )
	    	        		.after( $inValueRevert )
	    	        		.show();
	    	        	
	    	        	if( $active.is('.label') ){
	    	        		$inLabel.focus();
	    	    		} else {
	    	    			$inValue.focus();
	    	    		}
	    	        	
	    	        	if( typeof callback == 'function'){
	    		        	setTimeout( function(){				
	    						callback(); 
	    		        	}, delay);
	    	    		}
	    	    	} /* unveilItemEdit */
	    	    	
	    	    	function applyInput( callback, $input ){
	    	    		
	    				if( typeof $input === 'undefined' ){
	    					
	    					/* allowing empty! */
	    					
		    				/*if( '' == $.trim( $inLabel.val()) ){
		    					cancelInput( null, $inLabel );
		    					return;
		    				}
		    				if( '' == $.trim( $inValue.val()) ){
		    					cancelInput( null, $inValue );
		    					return;
		    				}*/
	    					
	    					if( $.trim( $inLabel.prev().data('initial-val')) == $.trim( $inLabel.val())
   	    					&&	$.trim( $inValue.prev().data('initial-val')) == $.trim( $inValue.val())){
    		    					cancelInput();/*neither was modified --> cancel both*/
    		    					return;	    						
   	    					}	    					
	    					
	    					stopItemEdit( $inLabel, $inValue, false );
	    					
    						//TODO call callback properly
	    					submitData();
	    					
		    				hideItemEdit(function(){
		    					$inLabel.prev().show();
		    					$inValue.prev().show();
		    	    			if( typeof callback === 'function'){    	    				
		    	    				callback();
		    	    			}		    					
		    				});		    				
	    				} else {
		    				if( $input.is('.label')){
			    				if( $.trim( $input.prev().data('initial-val')) != $.trim( $input.val())){
			    					
				    				//console.log('apply modifications');
				    				
			    					$input.prev().removeData('modified-val');
			    					
			    					if( $input.prev().has('a').length ){
			        					$input.prev().find('a').text( $input.val() );
			    					} else {
			    						$input.prev().text( $input.val() );
			    					}
				    				
			    					//TODO:submit mods to server( $inValue.val() && $inLabel.val());
			    					
			    					//pass callback forward to be peformed on success!	    				
			    	    			if( typeof callback === 'function'){    	    				
			    	    				callback();
			    	    			}				    				
			    				}
			    				$inValue.focus();
			    			} else {
			    				if( !$input.val() ){
			    					$item.addClass('empty');
			    				} else {
			    					$item.removeClass('empty');
			    				}
			    				applyInput();/*apply both*/
			    			}		    				
	    				}
	    	    	} /* applyInput */	    			
	    			
	    	    	function cancelInput( callback, $input ){
	    	    		
	    	    		/* avoid referencing $label and %value in this func! */
	    	    		var $l = $inLabel.prev(), $v = $inValue.prev();
	    	    		
	    	    		//console.log('cancel item mods', callback, $input);
	    	    		
	    	    		if( typeof $input === 'undefined' ){
	    	    			
		    	    		if( $.trim( $inLabel.val() ) == '' ){
		    	    			$l.removeData('modified-val');
		    	    		} else {
		    	    			$l.data('modified-val', $inLabel.val());
		    	    		}
    	    				
		    	    		if( $.trim( $inValue.val() ) == '' ){
		    	    			$v.removeData('modified-val');
		    	    		} else {
		    	    			$v.data('modified-val', $inValue.val());
		    	    		}
		    	    		
		    	    		hideItemEdit(function(){
		    	    			$l.show();
		    	    			if( $l.has('a').length ){
		    	    				$l.find('a').text( $l.data('initial-val') );
		    	    			} else {
		    	    				$l.text( $l.data('initial-val') );
		    	    			}
		    	    			$v.show();
		    	    			if( $v.has('a').length ){
		    	    				$v.find('a').text( $v.data('initial-val') );
		    	    			} else {
		    	    				$v.text( $v.data('initial-val') );
		    	    			}
		    	    			if( typeof callback === 'function'){
		    	    				callback();
		    	    			}    			
		    	    		});
	    	    		} else {
		    	    		if( $.trim( $input.val() ) == '' ){
		    	    			$input.prev().removeData('modified-val');
		    	    		} else {
		    	    			$input.prev().data('modified-val', $input.val());
		    	    		}
	    	    		}	    	    		
	    	    	} /* cancelInput */
	    	    	
	    	    	function hideItemEdit( callback, delay ){
	    	    		
	    	    		delay = delay || 0;
	    	    		
	    	    		$inLabel.hide().next().hide();
	    	    		$inValue.hide().next().hide();

    	    			if( typeof callback == 'function'){
    	    				if( delay ){
    	    					setTimeout( function(){
    	    						callback();
    	    					}, delay); /* wait for the css transition to complete, maybe */
    	    				} else {
    	    					callback();
    	    				}
    	    			}
	    	    	} /* hideItemEdit */	    			
	    	    	
	    		}/* showItemEditor */		    	
		    	
	    		function collectData(){
    	    		var data = {};
    	    		
    	    		if( $ctrl.is('.contains-table') ){    	    			
    	    			$ctrl.find('.item').each( function(idx, el){
    	    				var $el = $(el), item, $anchor, datatype, status;
    	    				
    	    				datatype = $el.data('type');
    	    				status   = $el.is('.disabled') ? '0' : '1';
    	    				
    	    				switch ( datatype ){
    	    				  case 'link': 
    	    					$anchor = $el.find('td a');
	    	    				item = [
		    	    			    'link',			    
		    	    			    $el.children('th').text(),
		    	    			    {
		    	    			    	'href': $anchor.attr('href'), 
		    	    			    	'title': $anchor.attr('title'),
		    	    			    	'text': $anchor.text(),
		    	    			    },		    	    			    
		    	    			    status    	    					
		    	    			];
	    	    				break;
    	    				  /*case 'price':
	    	    				item = [
		    	    			    'price',    	    				    
		    	    			    $el.children('th').text(),
		    	    			    $el.children('td').text(),		    	    			    
		    	    			    status    	    					
		    	    			];
	    	    				break;*/
    	    				  default: 	    				
	    	    				item = [
	    	    				    datatype,  				    
	    	    				    $el.children('th').text(),
	    	    				    $el.children('td').text(),
	    	    				    status
	    	    				];
    	    				}
    	    				
    	    				data[ $el.data('attr') ] = item;
    	    			});
    	    		}    	    		
    	    		return data; 
    	    	} /* collectData */	    		
	    		
    	    	function submitData(){
		    		var field, data = {}, payload = collectData();

		    		//console.dir(payload);
		    		
		    		payload = JSON.stringify( payload );
		    		
		    		$input.val( payload );
		    		
		    		if( ! nonce || 'dummy' == nonce ) return;		    		
		    		
		    		field = $ctrl.data('field');

		    		if( $ctrl.is('.widget-settings')){
		    			return;
		    			/* value of the input control will be submitted with the form */		    			
		    		}		    		
		    		
		    		data['action'] = opts.actionUpdate;
		    		data['field' ] = $ctrl.data('field') || opts.field;
		    		data[ field  ] = payload;
		    		data['wid'   ] = $ctrl.data('wid') || ''; 
		    		data['_wpnonce'] = nonce;
		    		data['afg_post_id'] = $ctrl.data('post') || opts.post;		    		
		    		
		    		$.post( opts.wpAjaxUrl, data, function( result ){
		    			//console.log( result );
    				}, function( result ){ 
    					console.error( 'Posting failed.' );
    				});
		    		
		    	} /* submitData */  	    		
	    		
		    	function downloadContent(){		    		
		    		var data = {}, $post, attempt;
		    		
		    		/* avoid infinite loop */
		    		attempt = $ctrl.data('attempted-loading');
		    		if( typeof attempt != 'undefined' && attempt != null && attempt ){
		    			return; 
		    		}
		    		
		    		data['action'] = opts.actionRetrieve;		    		
		    		data['field']  = 'product_details';
		    		data['_nonce'] = $ctrl.data('nonce');
		    		data['wid']    = $ctrl.data('wid') || '';
		    		data['afg_post_id'] = $('#post_ID').val();
		    		
		    		/*$post = $ctrl.closest('fieldset').find('input.post_id');
		    		if( $post.length ){
			    		if( 'cloned' == $post.val() ){
			    			prepareInput();
			    			data[ 'prototype' ] = $input.val();
			    		}
		    		}*/
		    		
		    		//console.log('Download content for', $ctrl.attr('id'));

    				$ctrl.data('attempted-loading', true);
    				
		    		var jqxhr = $.post( opts.wpAjaxUrl, data, function( response ){
		    			var id, nm, $newCtrl, $title = $ctrl.closest('fieldset').find('input.title').first();
		    			
    					$newCtrl = $( response );
    					
    					id = $title.attr('id').replace('title', 'items');
    					nm = $title.attr('name').replace('title', 'items');
    					
    					$ctrl.replaceWith( $newCtrl );

    					$newCtrl
    						.attr('data-input', id)
    						.attr('data-field', nm)
    						.data('attempted-loading', true)
    						.afgFeatureList();
    					
    					//$newCtrl.closest('fieldset').find('input.post_id').val( $newCtrl.data('post') );
    						
       					//( $newCtrl.data('input'), $newCtrl.data('field'));    						
    						
    				}, 'html')
    				.fail( function( response ){
	    				$ctrl.find('.spinner').hide();
	    				$ctrl.find('.loading').text('Could not load items.');
	    				/* on error: destroy and recreate */
	    				//console.log( response );
    				});
		    		
		    	} /* downloadContent */			    	

		    	function destroy(){
		    		$ctrl.off('.afg');
		    	}/* destroy */
		    });
		};
		
		$.fn.afgFeatureList.defaults = {
				initialMode:    'viewing',
				wpAjaxUrl:      '/wp-admin/admin-ajax.php',
				actionRetrieve: 'afg_retrieve_review_field',				
				actionUpdate:   'afg_update_review_field',			
	    };

		/* params will be feeded to page via wp_localize_script */
		window.affiget = window.affiget || {}; 
		window.affiget.params = window.affiget.params || {};
		window.affiget.params.feature_list = window.affiget.params.feature_list || {};
		
		$.fn.afgFeatureList.defaults = $.extend( $.fn.afgFeatureList.defaults, affiget.params.feature_list );
		
		$('.afg-feature-list').afgFeatureList();
		
		/* Triggered by FLBuilder */
		$(document).on('preview-rendered', 'body', function(e){ 
			$('.afg-feature-list').afgFeatureList();
		});
		
		/* Triggered by FLBuilder after every content update */
		$(window).resize( function(e){
			$('.afg-feature-list').afgFeatureList(); 
		});
		
		/*Page Builder by SiteOrigin triggers this */
		$(document).on('panelsopen', function(e) {
		    var dialog = $(e.target);
		    //console.log('panelsopen', e);
		    // Check that this is for our widget class
		    if( !dialog.has('.afg-feature-list') ) return;

		    $('.afg-feature-list').afgFeatureList();
		});
		
		$(document).on('form_loaded', function(e1, e2) {
			//console.log('form_loaded', e1, e2);
		});

		/* Event on the standard widgets page at /wp-admin/widgets.php */
		$(document).on('widget-updated widget-added', function(e){
			//console.log(e);
		    $('.afg-feature-list').afgFeatureList();
		});		
		
		$.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
			var post_id;
			if( ! options.data ){ 
				return;
			}
			//console.log(options, jqXHR);
			if( -1 === options.data.indexOf('so_panels_widget_form') && -1 === options.data.indexOf('so_panels_get_prebuilt_layout')){
				return;
			}
			post_id = $('#post_ID').val() || '';
			if( post_id ){
				options.data = options.data + '&afg_post_id=' + post_id;
			}
		});		
		
	});
		    
})( jQuery );