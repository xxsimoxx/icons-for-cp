(function() {
	tinymce.PluginManager.add('ifcp_mce_menu', function( editor ) {
	editor.addButton( 'ifcp_mce_menu', {
		 text: ifcp_mce_menu_name,
		 icon: 'code',
		 type: 'menubutton',
		 menu: ifcp_mce_menu_content
	});
	});
})();