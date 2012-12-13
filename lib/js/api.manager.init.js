
jQuery(document).ready( function($) {

// **************************************************************
//  add more fields
// **************************************************************

	$('input#api-clone').on('click', function() {

		// remove any existing messages
		$('#wpbody div#message').remove();

		// clone the fields
		var newfield = $( 'tr.api-empty-row.screen-reader-text' ).clone(true);

		// make it visible
		newfield.removeClass( 'api-empty-row screen-reader-text' );

		// and now insert it
		newfield.insertAfter( 'table#api-key-rows tr.api-key-row:last' );

		// add the class
		newfield.addClass('api-key-row');

	});

//********************************************************
// remove fields
//********************************************************

	$('input.remove-key').on('click', function() {
		$(this).parents('tr.api-key-row').find('input [type="text"]').val('');
		$(this).parents('tr.api-key-row').remove();
	});

//********************************************************
// you're still here? it's over. go home.
//********************************************************

});
