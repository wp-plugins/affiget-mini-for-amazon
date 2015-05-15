(function( $ ) {
	'use strict';

	$(function() {
		
		$.fn.afgStarRatings = function( p1, p2 ) {
			
			var opts = $.extend( {}, $.fn.afgStarRatings.defaults, p1 );

			if( this.length === 0 ){ return this; }
			
		    return this.each(function() {
		    	
		    	var $ctrl = $(this), $input;
		    	var post, field, nonce, id;

		    	init();
		    	
		    	function init(){

		    		$ctrl.addClass('afg-star-ratings'); /*in case it was not there yet*/		    		
		    		
			    	post  = $ctrl.data('post')  || opts.post;
			    	field = $ctrl.data('field') || opts.field;
			    	
			    	/* if element has no nonce data, it will be read-only */			    	
			    	nonce = $ctrl.data('nonce') || false;
			    	
			    	if( typeof $ctrl.data('afg-already-created') !== 'undefined' && $ctrl.data('afg-already-created') != null ){
			    		//console.log( $ctrl.attr('id'), '.afg-star-ratings already created', $ctrl );
			    		if( $ctrl.is('.editable') ){
				    		prepareInput();    		
				    		refreshView();
			    		}
			    		return;
			    	}
			    	
			    	$ctrl.uniqueId();			    	

			    	$ctrl.closest('fieldset').find('input.aspects, input.ranks').on('change.afg', onSettingsChanged);

			    	if( nonce || ! hasContent() ){ /* the content is editable OR not there yet */
			    		$ctrl.addClass('editable');
			    		prepareValues();
			    	} else {
			    		//console.log($ctrl.attr('id'), '.afg-star-ratings not editable');
			    	}
			    	  
			    	$ctrl.data('afg-already-created', true);
			    	//console.log( $ctrl.attr('id'), '.afg-star-ratings created', $ctrl );
		    		
		    	} /* init */
		    	
		    	function onStarClicked( value, event ){	    		
					var $stars = $(this), data = [], 
						$img   = $(event.target), $div,
					    aspect = $stars.closest('.item').data("aspect"),
					    decimal, adjusted;
					    /*idx    = $ctrl.find('div').index( $stars );*/
					
					/*$img.nextAll('input[name="widgets[{$i}][score]"]').val( value );*/
					
					decimal = (value % 1).toFixed(2)
					if (decimal < $.fn.raty.defaults.round.down){
						adjusted = Math.floor( value );
					} else if( decimal > $.fn.raty.defaults.round.up){
						adjusted = Math.ceil( value );
					} else {
						adjusted = Math.floor( value ) + 0.5;
						//half
					}
					$div = $img.closest('div').raty('score', adjusted);					
					//console.log('star clicked', value, adjusted);
					
					submitValues();
					
					//$('.afg-star-ratings[data-post="'+$ctrl.data('post')+'"] .item:nth-child('+(idx+1)+') div').not( $stars ).raty('score', value );					

		    	} /* onStarClicked */			    	
		    	
		    	function onSettingsChanged(ev){
		    		var $set    = $ctrl.closest('fieldset'),
	    		    	ranks   = $set.find('input.ranks').val(),		    		
		    		    aspects = $set.find('input.aspects').val(),
		    		    items   = $.parseJSON( $input.val()),
		    		    fresh   = [], 
		    		    tmp     = [];
		    		    

		    		if( ranks ){
		    			ranks = $.map(ranks.split(','), function(a, idx){
		    				return $.trim(a) || null;
		    			});		    			
		    		}
	    			if( !ranks.length ){
	    				ranks = opts.ranks;
	    			}
	    			items.ranks = ranks;
		    		$set.find('input.ranks').val( ranks.join(',') );

	    			if( aspects.length ){
		    			aspects = $.map(aspects.split(','), function(a, idx){
		    				return $.trim(a) || null;
		    			});		
	    			}
	    			if( !aspects.length ){
	    				aspects = opts.aspects;
	    			}	    			
	    			
		    		$.each( aspects, function( i, aspect ){
		    			var idx, found = false;
		    			if( ! aspect ){
		    				return true;
		    			}
		    			$.each( items.aspects, function(j, asp){
		    				if( aspect === asp[0] ){
		    					asp[1] = asp[1] ? asp[1] : Math.min(i+0.5, aspects.length); 
		    					fresh.push( asp );
		    					tmp.push( asp[0] );
		    					found = true;
		    					return false;
		    				}		    				
		    			});
		    			if( ! found ){
		    				tmp.push( aspect );
		    				fresh.push( [aspect, Math.min(i + 0.5, aspects.length)] );
		    			}		    			
		    		});
		    		
		    		items.aspects = fresh;
		    		$set.find('input.aspects').val( tmp.join(',') );
		    		
		    		//console.log('onSettingsChanged', items.aspects);
		    		
		    		$input.val( JSON.stringify( items ));
		    		
		    		recreateView();
		    	}
		    	
		    	function refreshView(){/* apply values from input to divs */		    		
		    		var items = $.parseJSON( $input.val() );

		    		//console.log( $input, items );

					/* assuming rated aspects are still the same, and only values are changed */
		    		$.each( items.aspects, function(key, aspect){
		    			//console.log(key, aspect, $ctrl.find( '.item[data-aspect="'+aspect[0]+'"] div img' ).length);
		    			$ctrl.find( '.item[data-aspect="'+aspect[0]+'"] div' ).raty('score', aspect[1]);		    			
		    		});
		    		
		    	} /* refreshView */			    	
		    	
		    	function prepareInput(){
		    		
			    	$input = $ctrl.find('input.items');
		    		if( ! $input.length ){
		    			$input = $( '#'+$ctrl.data('input') );
		    			if( ! $input.length ){
			    			/* create a new element */
			    			//$input = $('<input name="'+ $ctrl.data('name') +'" type="hidden" />').appendTo( $ctrl );
			    			$input = $('<input id="'+$ctrl.data('input')+'" name="'+ $ctrl.data('field') +'" type="hidden" />').appendTo( $ctrl );
		    			}
		    		}
		    	} /* prepareInput */
		    	
			    function prepareValues(){   	
			    	
			    	/* Serialized rating data will be stored in one place only: input.value;
			    	   If input can be found, an internal input gets created, 
			    	   and data for it either resolved from the contained list or gets downloaded. 
			    	*/
			    	
			    	var downloading = false;
			    	
			    	prepareInput();
			    	
		    		if( ! $input.val() ){ /* empty value */
		    			if( hasContent() ){
		    				updateValues(); /* resolve data from view */
		    			} else {
		    				downloading = true;
		    				downloadValues(); /* download data */
		    			}
		    		}
		    		
		    		if( ! downloading ){
		    			recreateView(); /* if downloading, will recreate in the callback instead */
		    		}
		    		
		    	} /* prepareValues */
			    
		    	function hasContent(){
		    		//console.log('hasContent?', $ctrl.find('ul, table').length );
		    		return $ctrl.find('ul, table').length > 0;
		    	} /* hasContent */
		    	
		    	function downloadValues(){		    		
		    		var data = {};
		    		
		    		data[ 'action' ]      = opts.actionRetrieve;
		    		data[ 'afg_post_id' ] = post;
		    		data[ 'field' ]       = field;
		    		
		    		$.get( opts.wpAjaxUrl, data, function( result ){
		    			if( result.success ){ 
		    				if( result.data.value ){
		    					$input.val( result.data.value );
		    					if( hasContent() ){ 
		    						refreshView();
		    					} else {
		    						recreateView();
		    					}
		    				}
		    			} else {
		    				/* on error: destroy and recreate */
		    				setDefaultValues();
		    				recreateView();
		    			}		    			
    				});
		    		
		    	} /* downloadValues */			    	
		    	
		    	function updateValues(){		    		
					
					$input.val( JSON.stringify( collectData() ));
					
		    	} /* updateValues */			    	
		    	
		    	function recreateView(){

		    		var $list, items = $.parseJSON( $input.val() ), isTable = $ctrl.is('.contains-table');

		    		destroyContent();
		    		
		    		//console.log( 'recreateView', items );
		    		
		    		items.aspects = items.aspects || []; 
		    		
			    	if( items.aspects.length ){
			    		if( isTable ){
			    			$list = $('<table class="list"><tbody></tbody></table>').appendTo( $ctrl );	
			    		} else {
			    			$list = $('<ul class="list"></ul>').appendTo( $ctrl );			    			
			    		}
			    		
			    		items.aspects.forEach( function(aspect, i){
			    			var $item, args = {
				    				score     : aspect[1],
				    				hints     : items.ranks,
				    				number    : items.ranks.length,
				    				numberMax : 10,
				    				half      : true,
				    				halfShow  : true,
				    				scoreName : 'widgets[{$i}][score]',  
				    				readOnly  : (!nonce),
				    				path      : opts.images,
				    				starOff   : 'star-off.png',
				    				starHalf  : 'star-half.png',
				    				starOn    : 'star-on.png',
				    				click     : onStarClicked
				    		};
			    			
			    			$item = isTable ? $('<tr class="item"></tr>') : $('<li class="item"></li>');			    					
			    			
			    			$item.data('aspect', aspect[0])
					    		.append(isTable ? '<th>'+aspect[0]+'</th>' : '<label>'+aspect[0]+'</label>')
					    		.append(isTable ? '<td><div></div></td>':'<div></div>')
					    		.appendTo( $list )
					    		.find('div').raty( args );
				    			//console.log('item', aspect[0], aspect[1], items.ranks);
			    			
			    		});/*forEach*/
			    	}
			    	
		    	}/* recreateView */
		    	
		    	function setDefaultValues(){		    		
		    		var items = {'ranks': opts.ranks, 'aspects':[]};
		    		
		    		opts.aspects.forEach( function(el, i){
		    			items.aspects.push([ el, 0 ]);
		    		});
			    	$input.val( JSON.stringify( items ));
			    	
		    	} /* assignDefaultValues */
		    	
		    	function collectData(){
					var items = {'ranks': [], 'aspects': []};
					
					$ctrl.find('.item:first-child img').each( function(){
						items.ranks.push( this.getAttribute('title') );
					});
					
					$ctrl.find('.item').each( function(){
						var $item = $(this);
						items.aspects.push( [ $item.data('aspect'), $item.find('input')[0].value || '0' ] );
					});
					//console.log( 'collectData', items );
					return items;
		    	}
		    	
		    	function submitValues(){		    		
		    		var data = {}, payload = collectData();

		    		payload = JSON.stringify( payload );
		    		$input.val( payload );		    		
		    		
		    		if( ! $ctrl.data('nonce') || $ctrl.data('nonce') == 'dummy' ) return;
		    		
		    		data[ 'action' ]  = opts.actionUpdate;
		    		data[ 'afg_post_id' ]   = $ctrl.data('post')  || opts.post;
		    		data[ 'field' ]   = $ctrl.data('field') || opts.field;
		    		data[ field ]     = payload;
		    		data[ 'wid' ]     = $ctrl.data('wid') || ''; 
		    		data[ '_wpnonce'] = $ctrl.data('nonce');
		    		
		    		$.post( opts.wpAjaxUrl, data, function( result ){
		    			/*console.log( result );*/
    				}, function( result ){ 
    					console.error( 'Posting failed.' );
    				});			
		    		
		    	} /* submitStoredValue */
		    	
		    	function destroy(){
		    		
		    		destroyContent();		    		
		    		
		    		$ctrl.removeClass('afg-star-ratings')
		    			.removeUniqueId()
		    			.closest('fieldset')
		    				.find('input.aspects, input.ranks')
		    					.off('.afg');
		    	}
		    	
		    	function destroyContent(){
		    		
		    		$( 'ul, table', $ctrl ).remove();
		    		
		    		$ctrl.find('div').each( function(){
						$(this).raty('destroy');
					});		    		
		    	}
		    });
		};
		
		$.fn.afgStarRatings.defaults = {
				aspects:        ['Rating'],
				ranks:          ['Bad', 'Poor', 'Average', 'Good', 'Excellent'],
				images:         '/wp-content/plugins/affiget/review/element/star-ratings/libs/raty/images/',
				wpAjaxUrl:      '/wp-admin/admin-ajax.php',
				actionRetrieve: 'afg_retrieve_review_field',				
				actionUpdate:   'afg_update_review_field',
				field:          'star_ratings'
	    };
		$.fn.raty.defaults.round.down = 0.01; //even tiny bits will be rounded to half
		$.fn.raty.defaults.round.up   = 0.51; //even tiny bits beyond half will be rounded to full

		/* params will be feeded to page via wp_localize_script */
		window.affiget = window.affiget || {}; 
		window.affiget.params = window.affiget.params || {};
		window.affiget.params.star_ratings = window.affiget.params.star_ratings || {};
		
		$.fn.afgStarRatings.defaults = $.extend( $.fn.afgStarRatings.defaults, affiget.params.star_ratings );
		
		$('.afg-star-ratings').afgStarRatings();
		
		/* Triggered by FLBuilder */
		$(document).on('preview-rendered', 'body', function(e){ 
			$('.afg-star-ratings').afgStarRatings();
		});
		
		/* Triggered by FLBuilder after every content update */
		$(window).resize( function(e){
			$('.afg-star-ratings').afgStarRatings(); 
		});
		
		/*Page Builder by SiteOrigin triggers this */
		$(document).on('panelsopen', function(e) {
		    var dialog = $(e.target);
		    //console.log('panelsopen', e);
		    // Check that this is for our widget class
		    if( !dialog.has('.afg-star-ratings') ) return;

		    $('.afg-star-ratings').afgStarRatings();
		});
		
		$(document).on('form_loaded', function(e1, e2) {
			//console.log('form_loaded', e1, e2);
		});

		/* Event on the standard widgets page at /wp-admin/widgets.php */
		$(document).on('widget-updated widget-added', function(e){
			//console.log(e);
		    $('.afg-star-ratings').afgStarRatings();
		});			
		
	});
		    
})( jQuery );