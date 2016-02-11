<?php
// *************************************************************************
// *                                                                       *
// * SimplePay Gateway 													   *
// * Copyright 2016 SimplePay Ltd. All rights reserved.                    *
// * Version: 1.0.1 					                                   *
// * Build Date: 11 Feb 2016                                               *
// *                                                                       *
// *************************************************************************
// *                                                                       *
// * Email: support@simplepay.ng                                           *
// * Website: http://www.simplepay.ng                                      *
// *                                                                       *
// *************************************************************************

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// Retrieve data returned in payment gateway callback
$invoiceId = $_POST["invoiceId"];
$reference = $_POST["reference"];
$amount = $_POST["amount"];
$token = $_POST["token"];

if ($gatewayParams['testMode'] == 'on') { 
	$privateKey = $gatewayParams['privateTestKey'];

} else  { 
	$privateKey = $gatewayParams['privateLiveKey'];
}

/**
 * Verify SimplePay transaction.
 */
$data = array (
	'token' => $token
);
$dataString = json_encode($data); 

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://checkout.simplepay.ng/v1/payments/verify/');
curl_setopt($ch, CURLOPT_USERPWD, $privateKey . ':');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
    'Content-Type: application/json',                                                                                
    'Content-Length: ' . strlen($dataString)                                                                       
));       

curl_exec($ch);
$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($responseCode == '200') {
	$success = true;
} else {
	$output = "Transaction ID: " . $reference 
	. "\r\nInvoice ID: " . $invoiceId 
	. "\r\nStatus: failed";
	logTransaction($gatewayModuleName, $output, "Unsuccessful");
	$success = false;
}

if ($success) {
	/**
	 * Validate Callback Invoice ID.
	 *
	 * Checks invoice ID is a valid invoice number.
	 *
	 * Performs a die upon encountering an invalid Invoice ID.
	 *
	 * Returns a normalised invoice ID.
	 */
	$invoiceId = checkCbInvoiceID($invoiceId, $gatewayModuleName);

	/**
	 * Log Transaction.
	 *
	 * Add an entry to the Gateway Log for debugging purposes.
	 *
	 * The debug data can be a string or an array. In the case of an
	 * array it will be
	 *
	 * @param string $gatewayName        Display label
	 * @param string|array $debugData    Data to log
	 * @param string $transactionStatus  Status
	 */
	$output = "Transaction ID: " . $reference 
			. "\r\nInvoice ID: " . $invoiceId 
			. "\r\nStatus: success";
	logTransaction($gatewayModuleName, $output, "Successful");

	/**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
	addInvoicePayment($invoiceId, $reference, $amount, 0, $gatewayModuleName);
}
?>
