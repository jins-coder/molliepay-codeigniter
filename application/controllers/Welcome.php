<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require 'vendor/mollie-api-php/examples/initialize.php';
class Welcome extends CI_Controller {

   public function __construct()
    {
        parent::__construct();
        $this->load->helper('file');
    }
	public function index()
	{
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey("test_Cy43QDreBpB9rVgKJ9RraKeSrGWnay");
        $orderId = strtotime(date('H:i:s'));
	    try{
            $payment = $mollie->payments->create([
                "amount" => [
                    "currency" => "EUR",
                    "value" => "10.00"
                ],
                "description" => "My first API payment",
                "redirectUrl" => "http://7515b1c08c8e.ngrok.io/molliepayment/welcome/returnURL/".$orderId,
                "webhookUrl"  => "http://7515b1c08c8e.ngrok.io/molliepayment/welcome/webhookURL",
                "metadata" => ["order_id" => $orderId]
             ]);
            redirect($payment->getCheckoutUrl(),'refresh',303);
        }catch(Exception $e){
	        echo  $e;
        }

	}

    public function returnURL($orderID='')
    {
        sleep(2);
       $this->data['orderID'] = $orderID;
        $this->data['status'] = $this->database_read($orderID);
       $this->load->view('welcome_message',$this->data);
	}
	public function webhookURL(){
       try {
           $mollie = new \Mollie\Api\MollieApiClient();
           $mollie->setApiKey("test_Cy43QDreBpB9rVgKJ9RraKeSrGWnay");
           $id = $this->input->post('id');
           $payment = $mollie->payments->get($id);
           $orderId = $payment->metadata->order_id;
           $status = '';
       if ($payment->isPaid() && !$payment->hasRefunds() && !$payment->hasChargebacks()) {
            /*
             * The payment is paid and isn't refunded or charged back.
             * At this point you'd probably want to start the process of delivering the product to the customer.
             */
           $status = 'Paid';
        } elseif ($payment->isOpen()) {
            /*
             * The payment is open.
             */
           $status = 'Open';
        } elseif ($payment->isPending()) {
            /*
             * The payment is pending.
             */
           $status = 'Pending';
        } elseif ($payment->isFailed()) {
            /*
             * The payment has failed.
             */
           $status = 'Failed';
        } elseif ($payment->isExpired()) {
            /*
             * The payment is expired.
             */
           $status = 'Expired';
        } elseif ($payment->isCanceled()) {
            /*
             * The payment has been canceled.
             */
           $status = 'Canceled';
        } elseif ($payment->hasRefunds()) {
            /*
             * The payment has been (partially) refunded.
             * The status of the payment is still "paid"
             */
           $status = 'Partially Refunded';
        } elseif ($payment->hasChargebacks()) {
            /*
             * The payment has been (partially) charged back.
             * The status of the payment is still "paid"
             */
           $status = 'Partially Charged back';
        }
           $this->database_write($orderId, $status,$payment->id);
    } catch (\Mollie\Api\Exceptions\ApiException $e) {
        echo "API call failed: " . \htmlspecialchars($e->getMessage());
    }
 }

    public function database_write($orderId,$status,$id)
    {
          $orderId = \intval($orderId);
           $database = FCPATH . "assets\order-{$orderId}.txt";
          \file_put_contents($database, $status);
   }

    public function database_read($orderId)
    {
        $orderId = \intval($orderId);
        $database = FCPATH . "assets\order-{$orderId}.txt";
        $status = @\file_get_contents($database);
        return $status ? $status : "unknown order";
    }

}
