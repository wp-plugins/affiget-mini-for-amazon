;(function($, jQuery, afg){

	//afg.starter.loadCSS( afg.config['pluginDir'] + 'dialog/css/affiget-infobar.css' , true );
	
	afg.starter.requireJS( afg.config['pluginDir'] + 'dialog/js/affiget-infobar.js' + '?v='+afg.config['afg_ver'], function(){

		var $container = $('#afg-container'), $infobar;
		
		afg.view = afg.view || (function(){
			afg.starter.hideLoader();
			afg.starter.recoverJQ();
			
			if( ! $container.length ){
				$container = $('<div id="afg-container">');
				$('body').append( $container );
			}
			
			$infobar = $('#afg-toolbar');
			if( ! $infobar.length ){
				$infobar = $('<div id="afg-toolbar">').appendTo( $container );
			}
			if( ! $infobar.hasClass('afg-infobar') ){
				$infobar = $('#afg-toolbar').afgInfobar({ label : 'afg2', location : 'top' });
			}
			
			return {
				open: function(){ $infobar.afgInfobar('open'); },
				isOpen: function(){ return $infobar.afgInfobar('isOpen'); },
				close: function(){ $infobar.afgInfobar('close'); }
			};
		})();		
	});
	
})(window.affiget.jQuery, window.affiget.jQuery, window.affiget);