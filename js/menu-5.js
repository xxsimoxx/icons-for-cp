(function() {
	tinymce.PluginManager.add('ifcp_mce_menu', function( editor ) {

		for (var ifcp_icon in ifcp_mce_menu_icons) {
			editor.ui.registry.addIcon(ifcp_icon, ifcp_mce_menu_icons[ifcp_icon]);
		}

		editor.ui.registry.addMenuButton( 'ifcp_mce_menu', {
			text:  ifcp_mce_menu_name,
			icon:  'sharpen',
			fetch: function (callback) {
				callback(ifcp_mce_menu_content);
			}
		});

		return {
			getMetadata: function () {
				return {
					name: 'Icons for CP',
					url: 'https://software.gieffeedizioni.it/plugin/icons-for-cp/',
				};
			}
		}

	});

})();