<?php

namespace App\Services;

use App\Models\Order;
use App\Services\Midtrans;
use Midtrans\Notification;

class CallbackService extends Midtrans
{
    protected $notification;
    protected $order;
    protected $serverKey;

    public function __construct()
    {
        parent::__construct();
        $this->serverKey = config('midtrans.serverKey');
        $this->_handleNotification();
    }

    public function isSignatureKeyVerified()
    {
        return ($this->_createLocalSignatureKey() == $this->notification->signature_key);
    }

    public function isSuccess()
    {
        $status_code = $this->notification->status_code;
        $transaction_status = $this->notification->transaction_status;
        $fraud_status = !empty($this->notification->fraud_status) ?
            ($this->notification->fraud_status == 'accept') : true;

        return ($status_code == 200 && $fraud_status &&
            ($transaction_status == 'capture' || $transaction_status == 'settlement'));
    }

    public function isExpired()
    {
        return ($this->notification->transaction_status == 'expire');
    }

    public function isCancelled()
    {
        return ($this->notification->transaction_status == 'cancel');
    }

    public function getNotification()
    {
        return $this->notification;
    }

    public function getOrder()
    {
        return $this->order;
    }

    protected function _createLocalSignatureKey()
    {
        $order_id = $this->order->id;
        $status_code = $this->notification->status_code;
        $gross_amount = $this->order->total_price;
        $server_key = $this->serverKey;
        $input = $order_id . $status_code . $gross_amount . $server_key;
        $signature = openssl_digest($input, 'sha512');

        return $signature;
    }

    protected function _handleNotification()
    {
        $notification = new Notification();

        $order = Order::findOrFail($notification->order_id);

        $this->notification = $notification;
        $this->order = $order;
    }
}
