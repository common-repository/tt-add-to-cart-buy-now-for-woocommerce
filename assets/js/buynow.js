(function ($) {
	$( 'input.variation_id' ).change( function(){
	    if( '' != $(this).val() ) {
			var var_id = $(this).val();

			var qty_no = $('input.qty').val();

			var existingBuyNow = document.getElementById('buy-now-variation');
			if(existingBuyNow){
		  		existingBuyNow.remove();
			}

		  	document.getElementById('main').getElementsByClassName('quantity')[0].innerHTML += '<a href=\"$checkout_url?quantity='+qty_no+'&add-to-cart='+var_id+'\" id=\"buy-now-variation\" class=\"button\">$buy_now_button_text</a>';

	    } else {
	    	var existingBuyNow = document.getElementById('buy-now-variation');
			if(existingBuyNow)
		  		existingBuyNow.remove();
	    }
	 });

	 $( 'input.qty' ).change( function(){
	 	var var_id = $( 'input.variation_id' ).val();

			var qty_no = $(this).parent( '.quantity' ).find( '.qty' ).val();

			var existingBuyNow = document.getElementById('buy-now-variation');
		if(existingBuyNow){
	  		existingBuyNow.remove();
		}

	  	document.getElementById('main').getElementsByClassName('quantity')[0].innerHTML += '<a href=\"$checkout_url?quantity='+qty_no+'&add-to-cart='+var_id+'\" id=\"buy-now-variation\" class=\"button\">$buy_now_button_text</a>';
	 });


	 $(document).on( 'change', 'input.qty', function() {

			var var_id = $( 'input.variation_id' ).val();

			var qty_no = $(this).parent( '.quantity' ).find( '.qty' ).val();


			var existingBuyNow = document.getElementById('buy-now-variation');
		if(existingBuyNow){
	  		existingBuyNow.remove();
		}

	  	document.getElementById('main').getElementsByClassName('quantity')[0].innerHTML += '<a href=\"$checkout_url?quantity='+qty_no+'&add-to-cart='+var_id+'\" id=\"buy-now-variation\" class=\"button\">$buy_now_button_text</a>';

	  	var qty = 'div.quantity > input.qty';
	    $(qty).val(qty_no);

	 });

	 $( 'a.reset_variations' ).click( function(){
	    var existingBuyNow = document.getElementById('buy-now-variation');
		if(existingBuyNow)
	  		existingBuyNow.remove();
	 });


})(jQuery)