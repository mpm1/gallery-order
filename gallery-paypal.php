<?php
namespace OrderGallery;
defined('ABSPATH') or die('No script kiddies please!');

use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;

function get_api_context(){
	//TODO: Get the settings
	$apiContext = new ApiContext(new OAuthTokenCredential(
		'AQ_o1JwXwROICc6hJXsGB8tADXsUMQ71EAkox2lojsuA1-AMI-JagfKYvZLVqVfHbCjr2GlZzLjXU2wL', //Client ID
		'EEcb8LYWo9He__SlOkxVglo1_HfvJ8QH9_-uq6icXEAKBHQUFGYUnWTBgZ_zRpmPWoRSN8BW7wKY6BV5', // Client Secret
	));
}

function create_payment($order_sku, $order_name, $order_description, $order_price, $order_tax, $order_fields, $returning_page, $quantity = 1, $currency = "CDN"){
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
				->setInvoiceNumber(uniqid());
				
	$reutnUrl = $returning_page; //TODO: add success and fail variables
	$cancelUrl = $returning_page; //TODO: add cancel variables
	$redirectUrls = new RedirectUrls();
	$redirectUrls->setReturnUrl($returnUrl)
				 ->setCancelUrl($cancelUrl);
				 
	$payment = new Payment();
	$payment->setIntent("sale")
			->setPayer($payer)
			->setRedirectUrls($redirectUrls)
			->setTransactions(array($transaction));
				
	try {
		$payment->create(get_api_context());
	}catch(Exception $ex){
		return array('payment' => $payment, 'approvalUrl' => null, 'hasError' => true, 'error' => $ex);
	}
	
	$approvalUrl = $payment->getApprovalLink();
	
	return array('payment' => $payment, 'approvalUrl' => $approvalUrl, 'hasError' => false, 'error' => null);
}