<?php
// *************************************************************************
// *                                                                       *
// * SimplePay Gateway 													   *
// * Copyright 2016 SimplePay Ltd. All rights reserved.                    *
// * Version: 1.0.0 					                                   *
// * Build Date: 29 Jan 2016                                               *
// *                                                                       *
// *************************************************************************
// *                                                                       *
// * Email: support@simplepay.ng                                           *
// * Website: http://www.simplepay.ng                                      *
// *                                                                       *
// *************************************************************************

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define SimplePay gateway configuration options.
 *
 * @return array
 */
function simplepay_config() {
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Master Card, Visa and Verve (Processed securely by SimplePay)'
        ),
        'testMode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode'
        ),
        'privateLiveKey' => array(
            'FriendlyName' => 'Private Live Key',
            'Type' => 'text',
            'Size' => '32',
            'Default' => ''
        ),
        'publicLiveKey' => array(
            'FriendlyName' => 'Public Live Key',
            'Type' => 'text',
            'Size' => '32',
            'Default' => ''
        ),
        'privateTestKey' => array(
            'FriendlyName' => 'Private Test Key',
            'Type' => 'text',
            'Size' => '32',
            'Default' => ''
        ),
        'publicTestKey' => array(
            'FriendlyName' => 'Public Test Key',
            'Type' => 'text',
            'Size' => '32',
            'Default' => ''
        ),
        'customDescription' => array(
            'FriendlyName' => 'Description',
            'Type' => 'text',
            'Size' => '100%',
            'Description' => '<br/>The description that will be shown on the payment dialog with the order ID in the end.'
        ),
        'customImage' => array(
            'FriendlyName' => 'Custom Image URL',
            'Type' => 'text',
            'Size' => '100%',
            'Description' => '<br/>A URL pointing to a square image of your brand or product. The recommended minimum size is 128x128px'
        )
    );
}

/**
 * Payment link.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @return string
 */
function simplepay_link($params) {
	// Invoice
	$invoiceId = $params['invoiceid'];
	$description = $params["description"];
    $amount = $params['amount'];
    $currency = $params['currency'];

    // Client
	$email = $params['clientdetails']['email'];
	$phone = $params['clientdetails']['phonenumber'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$postalCode = $params['clientdetails']['postcode'];
	$city = $params['clientdetails']['city'];
	$country = $params['clientdetails']['country'];

	// System
	$companyName = $params['companyname'];

	// Config Options
	if ($params['testMode'] == 'on') {
		$publicKey = $params['publicTestKey'];
		$privateKey = $params['privateTestKey'];
	} else  {
		$publicKey = $params['publicLiveKey'];
		$privateKey = $params['privateLiveKey'];
	}

	// Redirect If Checkout From Cart
	$cart = $_REQUEST['a'];
	if ($cart == 'complete') {
		header('Location: viewinvoice.php?id='.$invoiceId.'&simplepay');
	} 
	
	// Check if SimplePay handle can be opened
	if (isset($_REQUEST['simplepay'])) {
		$startSimplePay = true;
	} else {
		$startSimplePay = false;
	}
	
	$code = "
	<script src='https://ajax.googleapis.com/ajax/libs/jquery/2.2.0/jquery.min.js'></script>
	<script src='https://checkout.simplepay.ng/simplepay.js'></script>
	<script>
		var invoiceId = '" . $invoiceId . "';
		var description = '" . $description . "';
		var amount = '" . $amount . "';
		var currency = '" . $currency . "';

		var publicKey = '" . $publicKey . "';
		var privateKey = '" . $privateKey . "';
		var email = '" . $email . "';
		var phone = '" . $phone . "';
		var address1 = '" . $address1 . "';
		var address2 = '" . $address2 . "';
		var postalCode = '" . $postalCode . "';
		var city = '" . $city . "';
		var country = '" . $country . "';

		var customDescription = '" . $params['customDescription'] . "';
		var companyName = '" . $companyName . "';
		var image = '" . $params['customImage'] . "';
		
		function formatAmount(amount) {
			var strAmount = amount.toString().split('.');
			var decimalPlaces = (strAmount[1] === undefined) ? 0: strAmount[1].length;
			var formattedAmount = strAmount[0];
			
			if (decimalPlaces === 0) {
				formattedAmount += '00';
			
			} else if (decimalPlaces === 1) {
				formattedAmount += strAmount[1] + '0';
			
			} else if (decimalPlaces === 2) {
				formattedAmount += strAmount[1];
			}

			return formattedAmount;
		}

		// Payment popup
		var handler = SimplePay.configure({
			token: function(token) {
				var url = document.URL;
				url = url.substring(0, url.lastIndexOf('/') + 1);

				$.ajax({
					method: 'POST',
					url: url + 'modules/gateways/callback/simplepay.php',
					data: {
						invoiceId: invoiceId,
						amount: amount,
						token: token
					}
				}).success(function (data) {
					if (data === 'success') {
						location.reload();
					}
				});
			},
			key: publicKey,
			platform: 'WHMCS',
			image: image
		});
		
		var dialogDescription = customDescription;
		if (!dialogDescription) {
			dialogDescription = companyName;
		}
		var paymentData = {
			email: email,
			phone: phone,
			description: dialogDescription + ' - Order #' + invoiceId,
			address: address1 + ' ' + address2,
			postal_code: postalCode,
			city: city,
			country: country,
			amount: formatAmount(amount),
			currency: currency
		};
	</script>
	";
	
	if ($startSimplePay == true) {
		$code = $code . "
		<script>
			handler.open(SimplePay.CHECKOUT, paymentData);
		</script>
		";	
	}

	return $code;
}
?>
