;(function( afg ){  
  afg.starter = afg.starter || new function(){
	
	this.showToolbarPlaceholder = function(location){
		
		function animate(elem,style,unit,from,to,time) {
		    if( !elem) return;
		    var start = new Date().getTime(),
		        timer = setInterval( function(){
		            var step = Math.min(1,(new Date().getTime()-start)/time);
		            elem.style[style] = (from+step*(to-from))+unit;
		            if( step == 1) clearInterval(timer);
		        }, 25);
		    elem.style[style] = from+unit;
		}		
		var b = document.body, 
			container = document.getElementById('afg-container'), 
			toolbar   = document.getElementById('afg-toolbar');
		
		this.loadCSS( afg.config['pluginDir'] + 'dialog/css/affiget-infobar.css' );
		
		location = location  || 'top';		
		
		if( ! container ){
			container = document.createElement('div');
			container.setAttribute('id', 'afg-container');
			b.appendChild( container );
		}		
		if( ! toolbar ){
			toolbar = document.createElement('div');
			toolbar.setAttribute('id', 'afg-toolbar');
			toolbar.className = 'afg-toolbar '+location;
			container.appendChild( toolbar );
		}		
		toolbar.innerHTML = '<div class="bar"><a class="icon logo" /><a class="info">'+afg.msg['initializing']+'</a><a class="close" />Close &nbsp;<span>&#10005;</span></div>';
		animate(toolbar, 'top', 'px', -31, 0, 400);
		//toolbar.style['top'] = 0;
	}
	  
	this.showModalLoader = function($){
		
		var that = this, $container, $loader = $('#afg-loader');
		
		return;
	
		if ( $loader.length ){
			$loader.show();
			return;
		}
		
		$container = document.getElementById('afg-container');
		if( ! $container ){
			$container = $('body').append('<div id="afg-container"></div>');
		}
		
		$container.append('<div id="afg-loader"></div>');
		$('#afg-loader').append('<div id="afg-loader-overlay"></div><img id="afg-loader-img" />')
		  .css({
			display: 'block',
			position: 'absolute',
			top: 0,
			left: 0,
			width: '100%',
			height: '100%',
			'z-index': '2001'
		});
		$('#afg-loader-overlay').css({
			position: 'fixed',
			display: 'block',
			top: 0,
			left: 0,
			width: '100%',
			height: '100%',
			'background-color': 'white',
			'-moz-opacity': '0.5',
			opacity: '.50',
			filter: 'alpha(opacity=50)',
			'z-index': '2002'
		});
		$('#afg-loader-img').css({
			position: 'relative',
			display: 'block',
			margin: 'auto',
			'margin-top': '100px',
			'z-index': '2003',
			'box-shadow': '0px 0px 10px -3px rgba(0, 0, 0, 0.3)'
		}).attr('src', afg.config['pluginDir'] + 'public/img/loader.gif');
	
	}; /* showModalLoader */
	
	this.hideLoader = function(){
		var loader = document.getElementById('afg-loader');
		if( !loader ){
			//loader = document.getElementById('afg-toolbar');
		}
		if( loader ){
			loader.style.display = 'none';
		}
	};
	
	this.freshUrl = function(query){		
		var rand = 'r=' + Math.floor( Math.random() * 99999999 );
		if( query === '' ){
			return '&' + rand;
		}
		if( query.indexOf('?') === -1 ){
			return query + '?' + rand;
		} else {
			return query + '&' + rand;
		}
	}; /* freshUrl */
	
	this.requireJS = function(src, callback, fresh){
		
		var that = this, s, ss = document.scripts, r = false, t, max;
		
		if( ! src ){ /* empty src provided */ callback.call( this ); return; }
		
		for( var i = 0, max = ss.length; i < max; i++ ){
			if( ss[i].src && ss[i].src.indexOf( src ) != -1 ){ /* already loaded */ callback.call( this ); return; }
		}
	  
		s = document.createElement('script');
		s.type = 'text/javascript';
		  
		fresh = fresh || false; 
		s.src = fresh ? this.freshUrl( src ) : src;
		s.onload = s.onreadystatechange = function() {
		  if( !r && (!this.readyState || this.readyState == 'complete')){
		    r = true;
		    if( typeof callback == 'function' ){
		    	callback.call( that );
		    }
		  }
		};
		t = document.getElementsByTagName('script')[0];
		t.parentNode.insertBefore(s, t);
	} /* loadSrc */
	
	this.loadCSS = function( src, fresh ) {
		
	    var ss = document.styleSheets, k, max;
	    
	    if( ! src ) return;
	    
	    for( var i = 0, max = ss.length; i < max; i++ ){
	        if( ss[i].href && ss[i].href.indexOf( src ) != -1 ){
	            return; /* already loaded */
	        }
	    }
	    fresh = fresh || false;
	    k = document.createElement("link");
	    k.rel = "stylesheet";
	    k.href = fresh ? this.freshUrl( src ) : src;
	    document.getElementsByTagName("head")[0].appendChild(k);
	}
	
	var findCanonicalLink = function(){
	      var canonical = '', links = document.getElementsByTagName('link');
	      
          for( var i = 0; i < links.length; i++ ){
              if( 'canonical' === links[i].getAttribute('rel')){
                  canonical = links[i].getAttribute('href');
              }
          }
          return canonical;
	}

	var loadDialogConfig = function(){
		
		var that = this; 
		
		console.time('Afg+ time: config loaded in'); /*star timer*/
		this.requireJS(
			this.freshUrl( afg.config['wpAjaxUrl'] 
				+ '?action=afg_get_current_config' 
				+ '&afg_user_key=' + afg.config['userKey']
				+ '&canonical_link=' + encodeURIComponent( findCanonicalLink() )
			), 
			function(){
				console.timeEnd('Afg+ time: config loaded in'); /*stop timer*/
				if( typeof afg.config != 'undefined' ){
					if( typeof afg.config['error'] != 'undefined' ){
						alert( afg.config['error'] );
						afg.status = 'unlocked';
						that.hideLoader();
					} else {
						loadTheRest.call( that );
					}
				}
			}
		);
	}; /* loadDialogConfig */
	
	var loadTheRest = function(){
		var that = this, jqv = '?v=' + afg.config['jQUI_ver'];
		if( ! afg.config['canEditPosts'] ){
			if( typeof window.jQuery != 'undefined' ){
				jQuery('#afg-loader').hide();
				this.recoverJQ();
			}
			alert( afg.msg['contactAdmin1'] + '\n\n' + afg.config['homeUrl'] + '\n\n' + afg.msg['contactAdmin2'] );
			afg.status = 'unlocked';
			return;
		}
		
		this.loadCSS( afg.config['jQUI_css_url'] );
		this.loadCSS( afg.config['base_css_url'], true );		
		
		console.time('Afg+ time: scripts loaded in');
		if( typeof window.jQuery === 'undefined' || window.jQuery.fn.jquery != afg.config['jQ_ver'] ){
			//console.log('Current jQ ver. ', window.jQuery.fn.jquery );
			this._jQuery = window.jQuery;
			//console.log('Will load jQ ver. %s from %s', afg.config['jQ_ver'], afg.config['jQ_js_url']);
			this.requireJS(
				afg.config['jQ_js_url'] + jqv, 
				function(){
					var jQ = jQuery;
					
					afg.config['jQ_ver'] = window.jQuery.fn.jquery;
					
					try {
						//console.log('Current jQ ver. ', window.jQuery.fn.jquery );
						
						that.showModalLoader( jQ );
						
						jQ.fn.animateRotate = function(angle, duration, easing, complete) {
							  var args = jQ.speed(duration, easing, complete);
							  var step = args.step;
							  return this.each(function(i, e) {
							    args.complete = jQ.proxy(args.complete, e);
							    args.step = function(now) {
							    	jQ.style(e, 'transform', 'rotate(' + now + 'deg)');
							    	if (step) return step.apply(e, arguments);
							    };

							    jQ({deg: 0}).animate({deg: angle}, args);
							  });
						};
						
						jQ('#afg-toolbar .logo').animateRotate(360, 2000, 'swing');
						
						//afg.jQuery = jQuery.noConflict(true); //we need jQuery to hold currently loaded version of jQ until jqui is initialized
						afg.jQuery = jQ;

						//console.log('Loaded AffiGet jQ ver.'+afg.jQuery.fn.jquery);
						that.requireJS( afg.config['jQUI_js_root'] + 'core.min.js' + jqv, function(){
							that.requireJS( afg.config['jQUI_js_root'] + 'widget.min.js' + jqv, function(){
								that.requireJS( afg.config['jQUI_js_root'] + 'effect.min.js' + jqv, function(){
									jQ.each( afg.config['jQUI_js_parts'], function(index, part){
										that.requireJS( afg.config['jQUI_js_root'] + part + jqv);										
									});
									loadBaseScript.call( that );
								});
							});
						});
					} catch (e){
						console.error(e);
						that.recoverJQ();
					}
				}
			);
		} else {
			afg.jQuery = afg.jQuery || jQuery;
			loadBaseScript.call( that );
		}
	};/* loadTheRest */
	
	var loadBaseScript = function(){
		var that = this;
		try {
			if( typeof afg.view == 'undefined' ){
				this.requireJS(
					afg.config['base_js_url'] + '?v=' + afg.config['afg_ver'],
					function(){
						try {
							console.timeEnd('Afg+ time: scripts loaded in');
						} catch (e){
							console.error(e);
						}
						that.recoverJQ();					
					}
				);
			} else {
				//console.log(window.affiget);
			}
		} catch (e){
			console.error(e);
			this.recoverJQ();
		}
	}; /* loadBaseScript */
	
	this.recoverJQ = function(){
		afg.status = 'unlocked';
		if( typeof this._jQuery !== 'undefined' && this._jQuery.fn.jquery !== window.jQuery.fn.jquery ){
			//console.log('Releasing jQ v.'  + window.jQuery.fn.jquery );
			jQuery.noConflict( true );
			//console.log('Restored jQ to v.'  + window.jQuery.fn.jquery );
		} else {
			//console.log('Already restored to jQ v.'  + window.jQuery.fn.jquery );
		}
	};/* recoverJQ */
	
	this.run = function(callback){
		
		var that = this;
		
		if( window.location.href.indexOf("http://www.amazon.") < 0 && window.location.href.indexOf("https://www.amazon.") < 0 ){
			alert( afg.msg['notAmazonSite'] );
			//window.location.href = 'http://www.amazon.com';
			return;
		}
		
		document.addEventListener('keyup', function(ev){
			if( ev.keyCode == 27 ){
				that.hideLoader();
			}
		});		
		
		if( typeof afg.status === 'undefined' || afg.status == 'unlocked' ){
			try {
				if( typeof afg.view != 'undefined' ){
					if( afg.view.isOpen()){
						console.info('--> Dropping Afg+ call [b] (Dialog already open) <--');
						return;
	
					} else {
						afg.view.open();
						return;
					}
				}
			} catch( e ){
				console.error( e );
			}
			afg.status = 'starting';
			console.info('--> Starting Afg+ call <--');
		} else {
			if( afg.status == 'starting' || afg.status == 2 ){
				console.info( afg.status );
				console.info('--> Dropping Afg+ call [a] (Dialog still starting) <--');
				return;
			}
		}
		//console.clear();
		this.showToolbarPlaceholder('top');
		console.time('Afg+ time: showup time');/*start the timers!*/
		console.time('Afg+ time: started in');
		console.time('Afg+ time: total boot time');
		if( typeof window.jQuery != 'undefined' ){
			/*in case we already have some version of jQ, show loader right away!*/ 
			/*this.showModalLoader( jQuery );*/
		}
		loadDialogConfig.call( this );
	};/* run */ 
	
  }; /* starter */

  afg.starter.run();
  
})(window.affiget = window.affiget || {});