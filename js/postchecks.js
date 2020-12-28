jQuery(document).ready(function($){

	function ifcpcheck(title) {
		var data = {
			action: 'ifcp_postcheck',
			post_title: title,
			nonce: nonce.nonce,
		};

		$.ajax( {
			url		: ajaxurl,
			data	: data,
			dataType: 'json',
		} ).done( function ( data ) {

			$('#message').remove();
			$('#poststuff').prepend('<div id="message" class="fade ' + data.status + '"><p>' + data.message + '</p></div>');

			if(data.proceed){
				jQuery('#publish').removeClass('button-primary-disabled');
				jQuery('#publish').prop("disabled",false);
			} else {
				jQuery('#publish').addClass('button-primary-disabled');
				jQuery('#publish').prop("disabled",true);
			}

		} );

	}

	$('#title').change(function() {
		var title = $('#title').val();
		if(title != '')
		{
			ifcpcheck(title);
		}
	});

	  wp.codeEditor.initialize($('#content'), cm_settings);

});
