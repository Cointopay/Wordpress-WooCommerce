jQuery(function($) {
	if($('input[id="cointopay_crypto_merchant_id"]').length>0){
		var merchant_idd = $('input[id="cointopay_crypto_merchant_id"]').val();
		if(merchant_idd != ''){
		var length_idd = merchant_idd.length;
		
			$.ajax ({
				url: ajaxurlctp.ajaxurl,
				showLoader: true,
				data: {merchant: merchant_idd, action: "getCTPMerchantCoinsByAjax"},
				type: "POST",
				success: function(result) {
					$('select[id="cointopay_crypto_alt_coin"]').html('');
					if (result.length) {
							$('select[id="cointopay_crypto_alt_coin"]').html(result);
						
					} else {
					}
				}
			});
	
	$('input[id="cointopay_crypto_merchant_id"]').on('change', function () {
		var merchant_id = $(this).val();
		var length_id = merchant_id.length;
		
			$.ajax ({
				url: ajaxurlctp.ajaxurl,
				showLoader: true,
				data: {merchant: merchant_id, action: "getCTPMerchantCoinsByAjax"},
				type: "POST",
				success: function(result) {
					$('select[id="cointopay_crypto_alt_coin"]').html('');
					if (result.length) {
						$('select[id="cointopay_crypto_alt_coin"]').html(result);
					} else {
					}
				}
			});
		
	});
            var a = 'input[name="payment_method"]',
                b = a + ':checked',
                c = '#cointopay_crypto_alt_coin_field'; // The checkout field <p> container selector

            // Function that shows or hide checkout fields
            

            // Initialising: Hide if choosen payment method is "cod"
            if( $(b).val() !== 'cointopay' )
                $(c).hide();
            else
                $(c).show();

            // Live event (When payment method is changed): Show or Hide based on "cod"
            $( 'form.checkout' ).on( 'change', a, function() {
                if( $(b).val() !== 'cointopay' )
                    $(c).hide();
                else
                    $(c).show();
            });
	}
	}
});