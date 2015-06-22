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
		    	
		    	var $ctrl = $(this), $input, $inLabel, $inValue, $disabler, $dragger, post, nonce;
		    	
		    	if( 'stopEditing' == method ){
		    		return stopEditing();
		    	}

		    	init();

		    	function init(){
		    		var $post, $modify, $minimize;
		    		
			    	if( typeof $ctrl.data('afg-already-created') !== 'undefined' && $ctrl.data('afg-already-created') != null){
			    		////console.log( 'afg-feature-list ', $ctrl.attr('id'), 'already-created' );
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
			    	
			    	/* if element has no nonce data, it will be read-only */			    	
			    	nonce = $ctrl.data('nonce') || false;
			    	if( nonce ){
			    		$ctrl.addClass('editable');
			    		
			    		if( 'viewing' == opts.initialMode ){
					    	$ctrl.data('mode', opts.initialMode);
					    	
				    		$modify = $('<div class="modify" title="'+opts.msg['modifyTableHint']+'"><span>'+opts.msg['modifyTable']+'</span></div>');
				    		$modify.data('modifyTableHint', opts.msg['modifyTableHint']).data('modifyTableText', opts.msg['modifyTable']);
				    		$modify.on('click', startOrStopEditing );
				    		$ctrl.prepend( $modify );
				    		
				    		$minimize = $('<div class="minimize" title="'+opts.msg['minimizeTableHint']+'"><span>'+opts.msg['minimizeTable']+'</span></div>');
				    		$minimize.on('click', stopEditing );
				    		$ctrl.append( $minimize );
			    		}			    		

			    		$('body:not(.wp-admin)').on('click.afg', function(ev){
			    			$('.afg-feature-list.editing').afgFeatureList('stopEditing');/*stop editing all tables*/
			    		});
			    		
				    	$ctrl.on( 'click.afg', function(ev){
				    		var $target = $(ev.target);
				    		$('.afg-feature-list').not( $ctrl ).afgFeatureList('stopEditing');/*stop editing other tables*/
				    		if( ! $target.is('.minimize span,.modify span,a')){
					    		startEditing();
				    			ev.stopPropagation();	
				    		} 
				    		if( $target.is('a') ){
				    			return true;
				    		}				    		
				    		return false;			    		
				    	});
	
				    	$ctrl.find('table,ul,th,li.label,td,li.span,div.list,div.item').disableSelection();
				    	$disabler = $('<span class="offset-right"><span class="disabler ui-icon"></span></span>');
				    	$dragger  = $('<span class="offset-left"><span class="dragger ui-icon ui-icon-reorder" title="'+opts.msg['dragItemHint']+'"></span></span>');
				    	
				    	if( 'editing' == opts.initialMode ){
				    		startEditing();
				    	}
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
			    	
			    	$input = $ctrl.find('input[type="hidden"]');
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
		    		
					function fixHelperWidth(ev, ui) {
						//console.log('FixWidth', ev.target, ui);
						ui.children().each(function(idx, el) {
							var $el = $(el), w = $el.width(), wo = $el.outerWidth();
							$(this).width( w );
							$ctrl.find('colgroup col').eq(idx).width( wo );
						});
						ui.css({'background-color': 'white'});
						return ui;
					}
					
		    		if( ! $ctrl.is('.extended')){
		    			$ctrl.find('table').prepend('<colgroup><col /><col /></colgroup>');
			    		$ctrl.find('th').contents().wrap('<span class="label"></span>');
			    		$ctrl.find('th:empty').append('<span class="label"></span>');
			    		$ctrl.find('td').contents().wrap('<span class="value"></span>');
			    		$ctrl.find('td:empty').append('<span class="value"></span>');
			    		
			    		if( $ctrl.is('.contains-table') ){
				    		$ctrl.find('.list tbody').sortable({
				    			'appendTo': 'body',
				    			'axis':'y', 
				    			//'handle': '.dragger',
				    			'items': '> tr',
				    			'helper': fixHelperWidth,
				    			'opacity': 0.75,
				    			//
				    			'placeholder': 'afg-placeholder',
				    			'forcePlaceholderSize': true,
				    			'forceHelperSize': true,
				    			'start': function (event, ui) {
				    		        // Build a placeholder cell that spans all the cells in the row
				    		        var cellCount = 0;
				    		        $( event.target ).addClass('afg-sorting');
				    		        $('td, th', ui.helper).each(function () {
				    		            // For each TD or TH try and get it's colspan attribute, and add that or 1 to the total
				    		            var colspan = 1;
				    		            var colspanAttr = $(this).attr('colspan');
				    		            if( colspanAttr > 1){
				    		                colspan = colspanAttr;
				    		            }
				    		            cellCount += colspan-0; //js is forced to treat the variables as numbers instead of strings
				    		        });				    		        
				    		        // Add the placeholder UI - note that this is the item's content, so TD rather than TR
				    		        ui.placeholder.html('<th colspan="' + cellCount + '">&nbsp;</th>');
				    		    },				    			
				    		    'stop': function(event) {
				    		    	$( event.target ).removeClass('afg-sorting');
				    	            submitData();
				    	            return true;
				    	        }								
				    		}).disableSelection();
			    		} else {
				    		$ctrl.find('.list').sortable({
				    			'axis':'y', 
				    			/*'handle': '.dragger',*/
				    			'placeholder': 'placeholder',
				    		    'stop': function( ) {
				    	            submitData();
				    	            return true;
				    	        },
								'helper': fixWidthHelper
				    		}).disableSelection();			    			
			    		}
			    		$ctrl.addClass('extended');
		    		}
		    	} /* _extendListForEditing */
		    	
		    	function onDisablerClick( $icon, $item ){
		    		
    				if( $icon.is('.ui-icon-show')){
    					$icon.removeClass('ui-icon-show').addClass('ui-icon-hide');
    				} else {
    					$icon.removeClass('ui-icon-hide').addClass('ui-icon-show');
    				}
    				$item.toggleClass('disabled');
    				
    				submitData();
    				
		    	} /* onDisablerClick */
		    	
		    	function onItemClick( ev ){
	    			var $trg = $(ev.target), $item;
	    			
	    			$item = $trg.closest('.item');
	    			
	    			//console.log( 'onItemClick', $trg[0] );
	    			
	    			if( $trg.is('.disabler') ){
	    				onDisablerClick( $trg, $item );
	    				ev.stopPropagation();
	    				return false;
	    			} else if( $trg.is('span.label,span.value,label') ){
	    				showItemEditor( $item, $trg);
	    			} else if( $trg.is('th') ){
	    				showItemEditor( $item, $trg.children('span.label'));		    				
	    			} else if( $trg.is('td') ){
	    				showItemEditor( $item, $trg.children('span.value'));		    				
	    			} else if( $trg.is('a') ){
		    			showItemEditor( $item, $trg.parent());
		    			return false; /*prevent navigation*/
		    		} else if( $trg.is('input[type=text]')){
		    			//console.log('In focus:', $trg.is('input:focus'));
		    			if( ! $trg.is('input:focus') ){
		    				$trg.focus();
		    			}
		    			//showItemEditor( $item, $trg);
		    			return true;
		    		}
		    	} /* onItemClick */

	    		function onItemMouseEnter( ev ){
		    		var $item = $(ev.target);

		    		if( ! $item.is('.item') ){
		    			$item = $item.closest('.item'); 
		    		}	    		
		    		
		    		if( $item.is('.disabled') ){
		    			$disabler.attr('title', opts.msg['unhideItemHint']).children().addClass('ui-icon-hide').removeClass('ui-icon-show');
		    		} else {
		    			$disabler.attr('title', opts.msg['hideItemHint']).children().addClass('ui-icon-show').removeClass('ui-icon-hide');	
		    		}
		    		
		    		if( $ctrl.is('.contains-table') ){
		    			$item.find('th').prepend( $dragger )
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
		    	
	    		function startOrStopEditing(){
	    			if( $ctrl.is('.editing') ){
	    				stopEditing();
	    			} else {
	    				startEditing();
	    			}
	    		} /* startOrStopEditing */
	    		
		    	function startEditing(){ 

		    		if( ! $ctrl.is('.editable') ){
		    			////console.log( 'This control is not editable!');
		    			return;
		    		}
		    		if( $ctrl.data('mode') === 'editing' ) return;
		    		
		    		////console.log( $ctrl.attr('id'),'start editing');
		    		
		    		$ctrl.find('.modify').attr('title', opts.msg['minimizeTableHint']).find('span').text( opts.msg['minimizeTable'] );
		    		
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
		    		
		    		var $btn;
		    		
		    		
		    		if( $ctrl.data('mode') !== 'editing' ) return;
		    		
		    		$ctrl.find('.disabled').addClass('hidden');
		    		
		    		$btn = $ctrl.find('.modify');  		
		    		$btn.attr('title', $btn.data('modifyTableHint') ).find('span').text( $btn.data('modifyTableText') );
		    		
		    		////console.log($ctrl.attr('id'),'unbinding item events', $ctrl.find('.item').length);
		    		
		    		$ctrl.off('.afg', '.item')
		    			.removeClass('editing')
	    				.data('mode', 'viewing');
	    				
		    		/*$('html, body').animate({
                        scrollTop: ($ctrl.offset().top - 50)
                    }, 1000);*/		    		
	    			
	    			/*$ctrl.width( $ctrl.width() - 29 );*//* fat border is no more, restore original width*/	    			
	    			
		    		hideItemEditor();
		    		submitData();
		    		
	    			return $ctrl;
		    	} /* stopEditing */
		    	
	    		function showItemEditor( $item, $active ){
	    			var $label, $value;
	    			
	    			if( $active.is('input') ){
	    				return true;
	    			}	    			
	    			
    				$label   = $item.find('.label:not(input)');
    				$value   = $item.find('.value:not(input)');
    				
	    			//console.log( 'showItemEditor', $item, $active.text(), $label.text(), $value.text() );    				
	    			
	    			setupItemEdit();
	    			startItemEdit();
	    			
	    			function setupItemEdit(){
	    				
	    	    		if( typeof $inLabel !== 'undefined' && null !== $inLabel ){
	    	    			
	    					//revertInputChanges();
		    	    		hideItemEditor();
		    	    		
	    	    		} else {	    					
			    			$inLabel = $('<input class="label" type="text" />');
			    			$inLabel.on('keyup', function(ev){
			    				
			    				if( ev.keyCode == 27 ){ 
			    					ev.preventDefault();
				    				ev.stopPropagation();
				    				
				    				$inLabel.blur();
				    				
			    					//revertInputChanges( null, $( ev.target ));
			    					hideItemEditor();
			    					
			    				} else if( ev.keyCode == 13 ){
			    					ev.preventDefault();
				    				ev.stopPropagation();			    					
			    					$inValue.focus(); //note, inLabel.blur() gets called, as a result
			    				}
			    				
			    			}).on('blur', function( ev ){
			    				var $target = $( ev.target );
			    				//console.log('blur.label', $target.val());
			    				//console.trace();
			    				submitInputChanges( null, $target );
			    				return true;
			    				
			    			}).on('click', function( ev ){
			    				
			    				//console.log('click.label', ev.target.value);
			    				return true;			    				
			    			});
			    			
	    					$inValue = $('<input class="value" type="text" />');
			    			$inValue.on('keyup', function(ev){
			    				var $nextItem;
			    				
			    				if( ev.keyCode == 27 ){
			    					ev.preventDefault();
				    				ev.stopPropagation();	
				    				
				    				$inValue.blur();
				    				
			    					//revertInputChanges( null, $( ev.target ));
			    					hideItemEditor();
			    					
			    				} else if( ev.keyCode == 13 ){
			    					ev.preventDefault();
				    				ev.stopPropagation();
				    				
				    				$nextItem = $inValue.closest('.item').next();
				    				if( ! $nextItem.length ){
				    					$nextItem = $inValue.closest('.list').find('.item:first-child');
				    				}
				    				
				    				$inValue.blur();
				    				
			    					showItemEditor( $nextItem, $nextItem.find('span.label') );
			    				}
			    			}).on('blur', function( ev ){
			    				var $target = $( ev.target );
			    				
			    				//console.log('blur.value', $target.val());
			    				submitInputChanges( null, $target );
			    				return true;
			    				
			    			}).on('click', function( ev ){
			    				
			    				//console.log('click.value', ev.target.value);
			    				return true;
			    			});
			    			
	    				} 
	    			} /* setupItemEdit */
	    			
	    	    	function startItemEdit(){
	    	    		
	    	    		$inLabel.val( $label.text() );
	    	    		$label.data('initial-val', $label.text());
	    	    		
	    	    		$inValue.val( $value.text() );
	    	    		$value.data('initial-val', $value.text());	    	    		
	    	    		
	    	    		unveilItemEditor();
	    	    	} /* startItemEdit */
	    	    	
	    	    	function unveilItemEditor( callback, delay ){	    	    		
	    	    		var h, lh = $label.innerHeight(), vh = $value.innerHeight();

	    	    		h = (vh > 0 && vh < lh) ? vh : lh;/*smaller nonzero*/  
	    	    		
	    	        	delay = delay || 600;

	    	        	$label.hide();
	    				$inLabel
	    					.css({
								'width': ($label.parent().width()-5),
								'margin-right': 5,
								'height': h,
								'font': $label.parent().css('font'),
								'font-weight': $label.parent().css('font-weight'),
								'font-size': $label.parent().css('font-size'),
								'text-transform': $label.css('text-transform'),
								'background': $label.parent().css('background')
							})
							.insertAfter( $label )
	    	        		.show();
	    				
	    				$value.hide();
		    			$inValue
			    			.css({
								'width': $value.parent().width(),
								'height': h,			    							
								'font': $value.parent().css('font'),
								'text-transform': $value.css('text-transform'),
								'background': $value.parent().css('background')
							})
	    	        		.insertAfter( $value )
	    	        		.show();
	    	        	
	    	        	if( $active.is('.label')){
	    	        		$inLabel.focus();
	    	    		} else if( $active.is('.value')){
	    	    			$inValue.focus();
	    	    		}
	    	        	
	    	        	if( typeof callback == 'function'){
	    		        	setTimeout( function(){				
	    						callback(); 
	    		        	}, delay);
	    	    		}
	    	    	} /* unveilItemEditor */
	    	    	
	    	    	function submitInputChanges( callback, $edit ){
	    	    		var $span; 
	    	    		
	    	    		if( typeof $edit === 'undefined' ){
	    	    			
	    	    			submitInputChanges( null, $inLabel );
	    	    			submitInputChanges( callback, $inValue );
	    	    			
	    	    		} else if( typeof $edit !== 'undefined' ){
	    	    			
	    	    			$span = $edit.prev();
	    	    			
		    				if( $.trim( $span.data('initial-val')) != $.trim( $edit.val()) ){
		    					/* input value is different from the initial value */
		    					
		    					/* assign new value to the underlying span */
		    					if( $span.has('a').length ){
		        					$span.find('a').text( $edit.val() );
		    					} else {
		    						$span.text( $edit.val() );
		    					}
		    					
		    					/* assign new value as a new initial-val?
		    					$span
		    						.removeData('modified-val')
	    							.data('initial-val', $edit.val());
		    					*/	    							
		    					
		    					submitData();
		    					
		    					//XXX: pass callback forward, to be peformed only on success!	    				
		    	    			if( typeof callback === 'function'){    	    				
		    	    				callback();
		    	    			}
		    				}			    				
	    				}
	    					
	    				if( $edit.is('.value') ){
	    					if( '' == $.trim( $edit.val() )){ /* the value part is empty --> mark item as empty */
		    					$item.addClass('empty');
		    				} else {
		    					$item.removeClass('empty');
		    				}
	    				}
	    	    	} /* submitInputChanges */	    			
	    			
	    	    	function revertInputChanges( callback, $edit ){
	    	    		
	    	    		/* avoid referencing $label and $value in this func! */
	    	    		var $span;
	    	    		
	    	    		if( typeof $edit !== 'undefined' ){
	    	    			
	    	    			/* assign initial value to span */
	    	    			$span = $edit.prev();
	    	    			if( $span.has('a').length ){
	    	    				$span.find('a').text( $span.data('initial-val') );
	    	    			} else {
	    	    				$span.text( $span.data('initial-val') );
	    	    			} 
	    	    			
	    	    			/* store current input value as a modified-val -- might be useful in the future */
	    	    			$span.data('modified-val', $.trim( $edit.val() ));
	    	    			$edit.val( $span.data('initial-val') );	    	    			

		    	    		if( typeof callback === 'function'){
	    	    				callback();
	    	    			}    			
	    	    			
	    	    		} else {
	    	    			
	    	    			revertInputChanges( null, $inLabel );
	    	    			revertInputChanges( callback, $inValue );
	    	    			
	    	    		}
	    	    		
	    	    	} /* revertInputChanges */
	    	    	
	    		}/* showItemEditor */

    	    	function hideItemEditor( callback, delay ){
    	    		
    	    		//console.log('hideItemEditor', $ctrl.find('.item input.label').val(), $ctrl.find('.item input.value').val());
    	    		
    	    		delay = delay || 0;
    	    		
    	    		$ctrl.find('.item input.label').hide().blur().prev().show(); //hide input, show preceding span
    	    		$ctrl.find('.item input.value').hide().blur().prev().show(); //blur is enforced here to avoid it getting blured on DOM re-append

	    			if( typeof callback == 'function'){
	    				if( delay ){
	    					setTimeout( function(){
	    						callback();
	    					}, delay ); /* wait for the css transition to complete, maybe */
	    				} else {
	    					callback();
	    				}
	    			}
    	    	} /* hideItemEditor */		    		
	    		
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
		    		
		    		if( typeof $input === 'undefined' ){
		    			$input = $ctrl.find('input[type="hidden"]');
		    		}
		    		
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
		    		data['_wpnonce']    = nonce;
		    		data['afg_post_id'] = $ctrl.data('post') || opts.post;		    		
		    		
		    		$.post( opts.wpAjaxUrl, data, function( result ){
		    			////console.log( result );
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
		    		
		    		////console.log('Download content for', $ctrl.attr('id'));

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
	    				////console.log( response );
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
				msg: {
					'modifyTableHint':   'Modify product details',		
					'modifyTable':       'Modify table',
					'minimizeTableHint': 'Exit the editing mode',		
					'minimizeTable':     'Minimize table',
					'hideItemHint':      'Hide this item from your visitors',
					'unhideItemHint':    'Make this item visible to everyone',
					'dragItemHint':      'Drag this item to its new position'
				}
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
		    ////console.log('panelsopen', e);
		    // Check that this is for our widget class
		    if( !dialog.has('.afg-feature-list') ) return;

		    $('.afg-feature-list').afgFeatureList();
		});
		
		$(document).on('form_loaded', function(e1, e2) {
			////console.log('form_loaded', e1, e2);
		});

		/* Event on the standard widgets page at /wp-admin/widgets.php */
		$(document).on('widget-updated widget-added', function(e){
			////console.log(e);
		    $('.afg-feature-list').afgFeatureList();
		});				
		
		$.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
			var post_id;
			if( ! options.data ){ 
				return;
			}
			////console.log(options, jqXHR);
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