jQuery(document).ready(function($){

	function ifcpcheck(title) {
		var data = {
			action: 'ifcp_postcheck',
			post_title: title,
		};

		$.ajax( {
			url		: ajaxurl,
			data	: data,
			dataType: 'json'
		} ).done( function ( data ) {

			$('#message').remove();
			$('#poststuff').prepend('<div id="message" class="fade ' + data.status + '"><p>' + data.message + '</p></div>');

		} );

	};

	$('#title').change(function() {
		var title = $('#title').val();
		if(title != '')
		{
			ifcpcheck(title);
		}
	});

});
