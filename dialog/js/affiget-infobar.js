/**
 * Infobar Widget for AffiGet Mini.
 */
;(function( jQuery, $ ){
	
  //console.log('jq in infobar.js', $.fn.jquery);
  
  $.widget( "affiget.afgInfobar", {
	defaultElement: '<div>',
    options: {
    	label: 'AffiGet Infobar',
        location: 'top',		  
        height: 32,
    },
    _defaultMessage: '',
    _create: function(){
    	
    	var that = this, $bar = this.element.find('.bar');
    	//console.log('_create');
        
    	this.element.addClass("afg-infobar " + this.options.location).uniqueId()
					.addClass( affiget.config['afg_ext'].join(',') );

    	$bar.find('.close')
    			.bind('click', function(){
    				if( ! $(this).is('.disabled')){
    					that.close();
    				}
    				return false;
				});
    	
    	//$bar = $('<div class="bar"><a class="icon logo" /><a class="info" /><a class="view" /><a class="edit" /><a class="publish" /><a class="delete" /><a class="close" /></div>').appendTo(this.element)
    	//$bar = $('<div class="bar"><a class="icon logo" /><a class="info" /><a class="close" /></div>').appendTo(this.element)
				
    	//console.dir(affiget.review);
    	
    },/* create */       
    
    _init: function(){
    	var that = this, $bar = this.element.find('.bar');
    	//console.log('_init');
    	
    	$bar.find('a').removeClass('disabled');
    	
    	$bar.find('.logo')
				.text( affiget.msg['logo'] )
				.attr('title', affiget.msg['logoHint'])
				.end()    	
			.find('.info')
				.html( that._defaultMessage )
				.end();
    	
    	if( affiget.request['productCode'] !== null ){
    		
    		if( ! $bar.find('.delete').length ){
    			$bar.find('.close').after('<a class="delete" /><a class="publish" /><a class="edit" /><a class="view" />')
			}
    		
    		$bar.find('.info')
					.attr('title', affiget.msg['editTitleHint'])
					/*.attr('target', '_view')*/
					.on('click', function(){
						/*console.log('title.click', this);*/
						if( ! $(this).is('.disabled')){
								that.editTitle($(this));
						}
			    		return false;
			    	})				
					.end()					    		   		
				.find('.view')
					.text( affiget.msg['viewReview'] )
					.attr('title', affiget.msg['viewReviewHint'])
					.attr('href', affiget.config['homeUrl']+'/?post_type=review&preview=true&p='+affiget.review['ID'])
					.attr('target', '_view')
					.end()    			
				.find('.edit')
					.text( affiget.msg['editReview'] )
					.attr('title', affiget.msg['editReviewHint'])    			
					.attr('href', affiget.config['adminDir']+'post.php?action=edit&post='+affiget.review['ID'])
					.attr('target', '_edit')
					.end()
				.find('.publish')
					.text( affiget.msg['publishReview'] )
					.attr('title', affiget.msg['publishReviewHint'])    			
					.one('click', function(){
						//console.log('publish', this);
						if( ! $(this).is('.disabled')){
								that.publish();
						}
			    		return false;
			    	})
			    	.end()
				.find('.delete')
					.text( affiget.msg['deleteReview'] )
					.attr('title', affiget.msg['deleteReviewHint'])
					.one('click', function(){
						if( ! $(this).is('.disabled')){
							if( confirm( affiget.msg['deleteReviewConfirm'] )){
								that.trash();
							}
						}
			    		return false;
			    	})
					.end();
    	}
    	
		$bar.find('.close')
			.text( affiget.msg['close'] )
			.append('&nbsp;<span>&#10005;</span>')
			.attr('title', affiget.msg['closeHint'])				
		.end(); /* back to $bar */    

		if( affiget.request['productCode'] !== null ){
			this._update();	    	
		}
		this.open(); //calls _update() when (re)initializing
    },
    
    open: function(){
    	var that    = this, 
    		reqFail = affiget.request['productCode'] === null,
    		$el     = this.element, 
    		$info   = $el.find('.info');
    	
    	if( !reqFail && typeof affiget.review === 'undefined' || affiget.review === null ){
    		that.prepareReview();
    		return;
    	}    	
    	
    	//if( typeof $el.data('first-open') === 'undefined' || reqFail ){
    		if( this.isOpen() ){
	        	if( reqFail ){
	    			that._updateInfo( affiget.msg['newReviewProblem'], 'problem', 3000, function(){
	    				that.close();
	    			});
	        	} else {
	        		$el.data('first-open', 'done').show().addClass('wide'); /* will apply css3 transition */
			    	/*$el.animate({
		    		'margin-left':  '-300px',
		    		'margin-right': '-300px',
		    		'width':        '600px'
	    			}, 1000);*/	        		
	        	}    			
    		} else {    			
    			this._unveilToolbar( function(){
		        	if( reqFail ){
		    			that._updateInfo( affiget.msg['newReviewProblem'], 'problem', 3000, function(){
		    				that.close();
		    			});
		        	}		
    			}); 		
    		}

	    	if( !reqFail ){
	    		if( affiget.review['isNew'] ){
		    		that._defaultMessage = '';
		    		this._updateInfo( affiget.msg['newReviewSuccess'], 'success', 2000, function(){
		    			/* we give 2s for the title to be resolved */
		    			that.getFreshReviewTitle();
		    		});
				} else {
					if( that._defaultMessage == affiget.msg['initializing'] ){
						//console.log('reinitialized');
					}
					that._defaultMessage = affiget.review['post_title'];
					this._updateInfo( affiget.msg['reviewAlreadyExists'], 'status', 2000 );
				}
	    	}
	    	
	    	return;  
    	//}
    },
    
    _unveilToolbar: function( callback ){
    	var that = this, $el = this.element, $b = $('body'), t = $b.scrollTop(), $nav = $('#nav-upnav');
    	
    	if( ! $nav.length ){
    		$nav = $('#navbar');
    	}
    	
    	if( 'top' == this.options.location ){
    		$el.show('drop', {'direction' : 'down', 'distance' : '-' + this.options.height+'px'}, 300, function(){
    			if( typeof callback == 'function' ){
    				callback.call( that );
    			}    			
    		});
    		if( t < 39 ){
    			$nav.animate({ 'padding-top': '+='+(this.options.height+7+'px') }, 300);
    		} else {
    			$nav.css({'padding-top':'+='+(this.options.height+7+'px')});
    			$b.scrollTop( t+this.options.height+7 );
    		}
       	} else {
       		$el.show('drop', {'direction' : 'up', 'distance':'0px'}, 300, function(){
    			if( typeof callback == 'function' ){
    				callback.call( that );
    			}       			
       		});
       		$nav.animate({ 'padding-bottom': '+='+this.options.height+7+'px' }, t < 100 ? 300 : 0);    		
    	}    	
    },

    isOpen: function(){
    	return this.element.is(':visible');
    },    
    
    close: function(){
    	var $el = this.element, $b = $('body'), t = $b.scrollTop(), $nav = $('#nav-upnav');
    	
    	if( ! $nav.length ){
    		$nav = $('#navbar');
    	}    	
    	
    	//console.log('close');
    	if( this.options.location == 'top' ){
    		$el.hide('drop', {'direction':'up'}, 200, function(){ });
    		if( t < this.options.height+7 ){	    		
    			$nav.animate({'padding-top': '0'}, 200 );
    		} else {
    			$nav.css({'padding-top':'0'});
    			$b.scrollTop( t-this.options.height-7 );
    		}
    	} else {
    		$el.hide('drop', {'direction':'down'}, 200, function(){ });
    		$nav.animate({'padding-bottom': '0'}, 200 );
    	}

    	/*this.element.hide('fade', {'direction' : 'up'}, 'fast',function(){
    		//console.log('slideOut done.');
    		$('body').css({'padding-top': '0'});
    	});*/
    },
    
    _clear: function(){
    	var that = this, $bar = this.element.find('.bar');
    	//console.log('clear');
    	$bar.find('a').addClass('disabled');
    	that._defaultMessage = affiget.msg['initializing'];
    	delete affiget['review']; /* to be prepared anew */
    	this.element.removeData('first-open');
    },
    
    _spinLogo: function(angle, duration, callback){
    	angle = angle || 360;
    	duration = duration || 3000;    	
    	this.element.find('.logo').animateRotate(angle, duration, 'swing', callback);
    },
    
    prepareReview: function(){
    	var that = this, $el = this.element, params;
    	params = {  
    		'action':           'afg_prepare_review',
    		'afg_user_key':     affiget.config['userKey'],    		
    		'afg_product_code': affiget.request['productCode'],
    		'_wpnonce':         affiget.request['nonce'],
    	};
    	
    	//console.log('prepareReview');
		$.ajax({
			url: affiget.config['wpAjaxUrl'],			
			dataType: 'jsonp',
			data: params,			
			type: 'GET',
			crossDomain: true,
			xhrFields: {
			   withCredentials: true
			},
			success: function( resp ){
				if( resp.success ){
			    	affiget.review = resp.data.reviews[0];
			    	that._init();/*calls open()*/
				} else {
			    	console.error('Review could not be prepared.');
			    	//console.dir(params);
			    	//console.dir(response);
				}
			},
			error: function( resp ){
		    	console.error('Review could not be prepared.');
		    	//console.dir(params);
		    	//console.dir(response);								
			},
			complete: function( resp ){}
		});        
    },    
    
    trash: function(){
    	var that = this, $el = this.element, params;
    	
    	that._spinLogo();
    	
    	params = {
    		'action':       'afg_delete_review',
    		'_wpnonce':     affiget.review['nonceDelete'],
    		'afg_user_key': affiget.config['userKey'],    		
    		'afg_post_id':  affiget.review['ID']
    	};
    	
		$.ajax({
			url: affiget.config['wpAjaxUrl'],			
			dataType: 'jsonp',
			data: params,			
			type: 'GET',
			crossDomain: true,
			xhrFields: {
			   withCredentials: true
			},
			success: function( resp ){
				if( resp.success && 'post-deleted' == resp.data['code']){
					affiget.review['post_status'] = 'trash';
					that._update();
					that._updateInfo( affiget.msg['deleteReviewSuccess'], 'success', 2000, function(){
						affiget.review = null;
						that._defaultMessage = '';
						that._updateInfo( affiget.msg['closing'], 'status', 1000, function(){
							that.close();
							that._clear();
						});			
					});
				} else {
					this._updateInfo( affiget.msg['deleteReviewProblem'], 'problem' );
				}
			},
			error: function( resp ){
				this._updateInfo( affiget.msg['deleteReviewProblem'], 'problem' );								
			},
			complete: function( resp ){
				//console.log( 'trash request complete', resp );			
			}
		});
    },
    
    publish: function(){
    	var that = this, $el = this.element, params;
    	params = {
    		'action':          'afg_update_review_field',
    		'_wpnonce':        affiget.review['nonceModify'],
    		'afg_user_key':    affiget.config['userKey'],    		
    		'afg_post_id':     affiget.review['ID'],
    		'field':           'post_status',
    		'post_status':     'publish'
    	}; 

    	that._spinLogo();
    	
		$.ajax({
			url: affiget.config['wpAjaxUrl'],			
			dataType: 'jsonp',
			data: params,			
			type: 'GET',
			crossDomain: true,
			xhrFields: {
			   withCredentials: true
			},
			success: function( resp ){
				if( resp.success && 'post-published' == resp.data['code']){
					affiget.review['post_status'] = resp.data['value'];
			    	that._updateInfo( affiget.msg['publishReviewSuccess'], 'success', 3000, function(){
			    		that._updateInfo( affiget.review['post_title'] );			    		
			    		that._update();	
			    	});
				} else {
			    	that._updateInfo( affiget.msg['publishReviewProblem'], 'problem' );
				}
			},
			error: function( resp ){
				that._updateInfo( affiget.msg['publishReviewProblem'], 'problem' );								
			},
			complete: function( resp ){
				//console.log( 'publish request complete', resp );			
			}
		});    	
    },
    
    getFreshReviewTitle: function(){
    	var that = this, $el = this.element, params;
    	
    	params = {
    		'action':          'afg_retrieve_review_field',
    		'afg_user_key':    affiget.config['userKey'],    		
    		'afg_post_id':     affiget.review['ID'],
    		'field':           'post_title'
    	}; 
    	
		$.ajax({
			url: affiget.config['wpAjaxUrl'],			
			dataType: 'jsonp',
			data: params,			
			type: 'GET',
			crossDomain: true,
			xhrFields: {
			   withCredentials: true
			},
			success: function( resp ){
				if( resp.success ){
					affiget.review['post_title'] = resp.data['value'];										
					that._updateInfo( affiget.review['post_title'] );
				}
			},
			error: function( resp ){
				console.error('Could not retrieve post title.');								
			},
			complete: function( resp ){}
		});
    },
    
    editTitle: function( $anchor ){
    	var that = this, $el = this.element, 
    		$input = $el.find('input[type="text"]'), $btn;

    	if( $anchor.find('.back').text() == $('<div/>').html( affiget.review['post_title'] ).text()){
    		//currently showing post title
	    	if( ! $input.length ){
	    		setupEdit();
	    	}
	    	startEdit();
    	}    	
    	
    	function setupEdit(){
    		$input = $('<input class="initial" type="text" />').on('keyup', function(ev){    			
				if( ev.keyCode == 27 ){
					cancelEdit();
				}    			    			
    		}).hide();    		
    		$btn = $('<span class="btnOk">&#10003</span>')
    			.attr('title', affiget.msg['changeTitle'])
    			.hide()
    			.on('click', applyEdit);
    		$anchor.after( $input );
    		$input.after( $btn );
    	}
    	
    	function startEdit(){
    		var val = '';
  		
    		if( typeof $input.data('cancelled-title') != 'undefined' && $input.data('cancelled-title') != null){
    			val = $input.data('cancelled-title');
    		} else {
    			val = $anchor.text()
    		}    		
    		$input.val( val );
    		$anchor.data('initial-title', $anchor.text()).hide();
    		unveilEdit();
    	}
    	
    	function unveilEdit(callback){
    		//console.log('width', $anchor.css('width')+30);

        	that.element.find('.view,.edit,.publish,.delete').hide();
        	
        	$input.show().addClass('wide').next().fadeIn(601).end().focus();
        	$input.one('webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend', function(e) {
 			    // code to execute after transition ends        		
        		//console.log('CSS3 transition ended.');
        	});
        	
        	if( typeof callback == 'function'){
	        	setTimeout( function(){				
					callback.call( that ); 
	        	}, 601);
    		}
    	}
    	
    	function hideEdit(callback){
    		/*$input.animate({'width': '368px'}, 500, function(){*/
    		
    		$input.next().fadeOut(500);
    		$input.removeClass('wide'); /*css transition*/
        	setTimeout( function(){
        		$input.hide();
        		that.element.find('.view,.edit,.publish,.delete').show();
    			if( typeof callback == 'function'){
    				callback.call( that );
    			}    			 
        	}, 500); /* wait for the css transition to complete */
    	}
    		
    	function applyEdit(){
			//console.log('apply title changes');
			if( '' == $.trim( $input.val()) ){
				cancelEdit( function(){
					that._updateInfo( affiget.msg['emptyTitleProblem'], 'problem' );
				});
				return;
			}
			if( '' != $.trim( $input.val()) && $.trim( $anchor.data('initial-title')) != $.trim( $input.val()) ){
				that._modifyReviewTitle( $input.val() );
			}
			hideEdit(function(){
				$anchor.find('.back').text( $input.val() ).end().show();	
			});
			$input.removeData('cancelled-title');						
    	}
    	
    	function cancelEdit( callback ){
    		//console.log('cancel title changes');
    		
    		if( $.trim( $input.val() ) != '' ){
    			$input.data('cancelled-title', $input.val());
    		} else {
    			$input.removeData('cancelled-title');
    		}
    		
    		hideEdit(function(){    		
    			$anchor.find('.back').text( $anchor.data('initial-title') ).end().show();
    			if( typeof callback == 'function'){
    				callback.call( that );
    			}    			
    		});
    	}    	
    },    
    
    _modifyReviewTitle: function( newTitle ){
    	
    	//console.log('_modifyReviewTitle: '+newTitle);
     	var that = this, $el = this.element, params;
    	params = {
    		'action':          'afg_update_review_field',
    		'_wpnonce':        affiget.review['nonceModify'],
    		'afg_user_key':    affiget.config['userKey'],    		
    		'afg_post_id':     affiget.review['ID'],
    		'field':           'post_title',
    		'post_title':      newTitle
    	}; 

    	that._spinLogo();
    	
		$.ajax({
			url: affiget.config['wpAjaxUrl'],			
			dataType: 'jsonp',
			data: params,			
			type: 'GET',
			crossDomain: true,
			xhrFields: {
			   withCredentials: true
			},
			success: function( resp ){
				if( resp.success && 'title-updated' == resp.data['code']){
					if( affiget.review['post_title'] != resp.data['value'] ){
						affiget.review['post_title'] = resp.data['value'];
						that._updateInfo( affiget.review['post_title'] );
					}
				} else {
			    	that._updateInfo( affiget.msg['changeTitleProblem'], 'problem' );
				}
			},
			error: function( resp ){
				that._updateInfo( affiget.msg['changeTitleProblem'], 'problem' );								
			},
			complete: function( resp ){
				//console.log( 'title change request complete', resp );			
			}
		});      	
    },
    
    _updateInfo: function(msg, msgType, duration, callback){
    	var that = this, $info = this.element.find('.info');
    	
    	msgType  = msgType  || 'default';
    	
    	if( 'default' == msgType ){
			that._defaultMessage = msg;
			duration = duration || 0;			
    	} else {
        	//console.error( msg );
    		duration = duration || 3000;
    	}
		that._flip( $info, msg, msgType, duration, callback );    	
    },
    
    _flip: function($el, msgNext, msgType, duration, callback){
    	var $fr = $el.find('.front'), msgNow = '', that = this;
    	
    	if( $fr.length ){	
    		msgNow = $fr.text();
    	}
    	  
    	if( $el.find(".back").length > 0 ) {
    		$el.html( $el.find(".back").html() );
    	}
    	$el.html("");
    	
    	$("<span class='front'>" + msgNow + "</span>").appendTo( $el );
    	$("<span class='back "+msgType+"'>" + msgNext + "</span>").appendTo( $el );
    	$el.wrapInner("<span class='rotating' />");
    	
    	$el.find(".rotating")
    			.hide()
    			.addClass("flip up")
    			.show()
    			.css({"transform": " rotateX(-180deg)"});

		setTimeout( function(){
			if( msgType != 'default'){
				/* revert to default */
				if( that._defaultMessage != '' ){ 
					that._flip($el, that._defaultMessage, 'default');
				}
			}
	    	if( typeof callback == 'function' ){
	    		callback.call( that )
	    	}
		}, duration );    	
    },
    
    _setOption: function( key, value ){
        this.options[ key ] = value;
        this._update();
        //$.Widget.prototype.setOption.call( this );
        this._superApply(arguments);
        
        // The widget factory doesn't fire an callback for options changes by default
        // In order to allow the user to respond, fire our own callback
        this._trigger( "setOption", null, {
            option: key,
            original: oldValue,
            current: value
        });        
    }, 
    
    _update: function(){
    	var that = this, $bar = this.element.find('.bar');
    	/* this function should not run any animatons and should not be changing content of a.info ! */
    	
    	//console.log('_update');
    	
    	if( ! $bar.length ) return;
    	
    	if( 'trash' == affiget.review['post_status'] ){
    		
    		$bar.find('a').not('.logo,.close').addClass('disabled');
    		
    	} else if( 'publish' == affiget.review['post_status'] ){
    		
			$bar.find('.info,.view')
					.attr('href', affiget.config['homeUrl']+'/?p='+affiget.review['ID'])
					.end()
				.find('.publish')
					.addClass('disabled')
					.attr('title', affiget.msg['publishReviewDisabledHint'])
					.end()
				/*.find('.delete')
					.addClass('disabled')
					.attr('title', affiget.msg['deleteReviewDisabledHint'])
					.end()*/;
		}
    },
 
    destroy: function(){
        this.element
            .removeClass( "afg-infobar" )
            .removeUniqueId()
            .text( "" )
            .empty();
        // Call the base destroy function.
        //$.Widget.prototype.destroy.call( this );
        this._super();
    } 
});
})( window.affiget.jQuery, window.affiget.jQuery );