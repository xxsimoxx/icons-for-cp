var editor = wp.codeEditor.initialize(jQuery('#content'), cm_settings);

jQuery(document).ready(function($){

	function ifcpcheck(title) {
		var data = {
			action     : 'ifcp_postcheck',
			post_title : title,
			nonce      : external.nonce,
			postid     : external.postid,
		};

		jQuery.ajax( {
			url		   : ajaxurl,
			data	   : data,
			dataType   : 'json',
		} ).done( function (data) {

			jQuery('#message').remove();
			jQuery('#poststuff').prepend('<div id="message" class="fade ' + data.status + '"><p>' + data.message + '</p></div>');

			if(data.proceed){
				jQuery('#publish').removeClass('button-primary-disabled');
				jQuery('#publish').prop('disabled',false);
			} else {
				jQuery('#publish').addClass('button-primary-disabled');
				jQuery('#publish').prop('disabled',true);
			}

		} );

	}

	function importicon(url) {

		var data = {
			action     : 'ifcp_import',
			remote_url : url,
			nonce      : external.nonce,
		};

		jQuery.ajax( {
			url		   : ajaxurl,
			data	   : data,
			dataType   : 'json',
		} ).done( function (data) {
		jQuery("#ifcp-import-spinner").removeClass("is-active");

		if(data.bad){
			jQuery('#message').remove();
			jQuery('#poststuff').prepend('<div id="message" class="fade error"><p>' + data.error + '</p></div>');
		} else {
			jQuery('#message').remove();
			editor.codemirror.setValue(data.icon);
		}

		} );

	}

	jQuery('#title').change(function() {
		var title = $('#title').val();
		if(title != '') {
			ifcpcheck(title);
		}
	});

	jQuery('#ifcp-import-do').click(function() {
		var url = $('#ifcp-import-url').val();
		jQuery("#ifcp-import-spinner").addClass("is-active");
		if(url != '') {
			importicon(url);
		}
	});

	editor.codemirror.on('change',function(){
	  jQuery('#canuckcp-icons-pw-inner').html(editor.codemirror.getValue());
	});

});