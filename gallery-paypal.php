<?php
namespace OrderGallery;
defined('ABSPATH') or die('No script kiddies please!');

// Perform includes
include include_dir() . '/Psr/Log/LogLevel.php';
include include_dir() . '/Psr/Log/LoggerInterface.php';
include include_dir() . '/Psr/Log/AbstractLogger.php';
include paypal_dir() . 'Log/PayPalLogger.php';
include paypal_dir() . 'Log/PayPalLogFactory.php';
include paypal_dir() . 'Log/PayPalDefaultLogFactory.php';
include paypal_dir() . 'Core/PayPalConstants.php';
include paypal_dir() . 'Common/PayPalUserAgent.php';
include paypal_dir() . 'Exception/PayPalInvalidCredentialException.php';
include paypal_dir() . 'Exception/PayPalConnectionException.php';
include paypal_dir() . 'Handler/IPayPalHandler.php';
include paypal_dir() . 'Handler/RestHandler.php';
include paypal_dir() . 'Handler/OauthHandler.php';
include paypal_dir() . 'Core/PayPalConfigManager.php';
include paypal_dir() . 'Core/PayPalLoggingManager.php';
include paypal_dir() . 'Core/PayPalHttpConfig.php';
include paypal_dir() . 'Core/PayPalCredentialManager.php';
include paypal_dir() . 'Core/PayPalHttpConnection.php';
include paypal_dir() . 'Converter/FormatConverter.php';
include paypal_dir() . 'Validation/NumericValidator.php';
include paypal_dir() . 'Validation/UrlValidator.php';
include paypal_dir() . 'Validation/ArgumentValidator.php';
include paypal_dir() . 'Rest/IResource.php';
include paypal_dir() . 'Rest/ApiContext.php';
include paypal_dir() . 'Transport/PayPalRestCall.php';
include paypal_dir() . 'Common/ArrayUtil.php';
include paypal_dir() . 'Common/ReflectionUtil.php';
include paypal_dir() . 'Common/PayPalModel.php';
include paypal_dir() . 'Api/PayerInfo.php';
include paypal_dir() . 'Api/Links.php';
include paypal_dir() . 'Common/PayPalResourceModel.php';
include paypal_dir() . 'Security/Cipher.php';
include paypal_dir() . 'Validation/JsonValidator.php';
include paypal_dir() . 'Cache/AuthorizationCache.php';
include paypal_dir() . 'Auth/OAuthTokenCredential.php';
include paypal_dir() . 'Api/RedirectUrls.php';
include paypal_dir() . 'Api/CartBase.php';
include paypal_dir() . 'Api/TransactionBase.php';
include paypal_dir() . 'Api/Transaction.php';
include paypal_dir() . 'Api/Amount.php';
include paypal_dir() . 'Api/Details.php';
include paypal_dir() . 'Api/ItemList.php';
include paypal_dir() . 'Api/Payer.php';
include paypal_dir() . 'Api/Item.php';
include paypal_dir() . 'Api/Payment.php';
include paypal_dir() . 'Api/BaseAddress.php';
include paypal_dir() . 'Api/Address.php';
include paypal_dir() . 'Api/ShippingAddress.php';
include paypal_dir() . 'Api/Payee.php';
include paypal_dir() . 'Api/PaymentExecution.php';
include paypal_dir() . 'Api/RelatedResources.php';
include paypal_dir() . 'Api/Order.php';

use PayPal\Api\Amount as Amount;
use PayPal\Api\Details as Details;
use PayPal\Api\Item as Item;
use PayPal\Api\ItemList as ItemList;
use PayPal\Api\Payer as Payer;
use PayPal\Api\Payment as Payment;
use PayPal\Api\RedirectUrls as RedirectUrls;
use PayPal\Api\Transaction as Transaction;
use PayPal\Api\PaymentExecution as PaymentExecution;

use PayPal\Rest\ApiContext as ApiContext;
use PayPal\Auth\OAuthTokenCredential as OAuthTokenCredential;

function get_api_context(){
    $gallery_options = get_option('order_gallery_settings');


    $clientId = $gallery_options['paypal_client_id'];
    $secretId = $gallery_options['paypal_secret'];

	$apiContext = new ApiContext(new OAuthTokenCredential($clientId, $secretId));

    $apiContext->setConfig(
        array(
            'mode' => $gallery_options['paypal_mode'],
            'log.LogEnabled' => true,
            'log.FileName' => './PayPal.log',
            'log.LogLevel' => 'DEBUG', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
            'cache.enabled' => true,
            'http.CURLOPT_CONNECTTIMEOUT' => 300

        )
    );

    return $apiContext;
}

function create_payment($order_id, $order_sku, $order_name, $order_description, $order_price, $order_tax, $order_fields, $returning_page, $quantity = 1, $currency = "CAD"){
	// USING EXAMPLE: http://paypal.github.io/PayPal-PHP-SDK/sample/doc/payments/CreatePaymentUsingPayPal.html

	// Create the Payer
	$payer = new Payer();
	$payer->setPaymentMethod("paypal");
	
	// Get the Item Information
	// TODO: Add field data
	$item = new Item();
	$item->setName($order_name)
	     ->setCurrency($currency)
		 ->setQuantity($quantity)
		 ->setSku($order_sku)
		 ->setPrice($order_price);
		 
	$itemList = new ItemList();
	$itemList->setItems(array($item));
	
	$details = new Details();
	$details->setTax($order_tax)
			->setSubtotal($order_price);
			
	$amount = new Amount();
	$amount->setCurrency($currency)
		   ->setTotal($order_price + $order_tax)
		   ->setDetails($details);
		   
	$transaction = new Transaction();
	$transaction->setAmount($amount)
				->setItemList($itemList)
				->setDescription($order_description)
				->setInvoiceNumber($order_id);

	$redirectUrls = new RedirectUrls();
	$redirectUrls->setReturnUrl($returning_page . '?order=' . htmlentities($order_id))
				 ->setCancelUrl($returning_page . '?cancel=' . htmlentities($order_id));
				 
	$payment = new Payment();
	$payment->setIntent("order")
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions(array($transaction));

    $request = clone $payment;
				
	try {
		$payment->create(get_api_context());
	}catch(Exception $ex){
		return array('payment' => $payment, 'approvalUrl' => null, 'hasError' => true, 'error' => $ex);
	}
	
	$approvalUrl = $payment->getApprovalLink();
	
	return array('payment' => $payment, 'approvalUrl' => $approvalUrl, 'hasError' => false, 'error' => null);
}

function handle_payment($order, $payment_id, $payer_id){
    $apiContext = get_api_context();
    $payment = Payment::get($payment_id, $apiContext);

    $execution = new PaymentExecution();
    $execution->setPayerId($payer_id);

    try {
            $result = $payment->execute($execution, $apiContext);
            $order['status'] = $result->getState() == 'approved' ? STATUS_APPROVED : STATUS_DECLINED;

            try{
                $payment = Payment::get($payment_id, $apiContext);
            }catch(Exception $ex){
                $order['error'] = 'An error occured while getting the resulting payment.';
                $order['message'] = $ex->getMessage();
            }
    }catch(Exception $ex){
        $order['status'] = STATUS_DECLINED;
        $order['error'] = 'An error occured while getting the resulting payment.';
        $order['message'] = $ex->getMessage();
    }

    return $order;
}

function get_payment_status($payment_id){
    $apiContext = get_api_context();
    $payment = Payment::get($payment_id, $apiContext);

    return $payment;
}