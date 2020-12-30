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

	jQuery('#title').change(function() {
		var title = $('#title').val();
		if(title != '') {
			ifcpcheck(title);
		}
	});

	editor.codemirror.on('change',function(cMirror){
	  jQuery('#canuckcp-icons-pw-inner').html(editor.codemirror.getValue());
	});

});