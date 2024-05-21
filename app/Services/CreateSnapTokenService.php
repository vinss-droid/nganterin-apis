<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use App\Services\Midtrans;
use Illuminate\Support\Facades\Auth;
use Midtrans\Snap;

class CreateSnapTokenService extends Midtrans
{
    protected $order;
    protected $start_time;

    public function __construct($order, $start_time)
    {
        parent::__construct();

        $this->order = $order;
        $this->start_time = $start_time;
    }

    public function getSnapToken()
    {
        $user = User::leftJoin('user_details', 'user_details.user_id', '=', 'users.id')
                    ->select('users.name', 'users.email', 'user_details.phone_number')
                    ->findOrFail(Auth::user()->id);

        $params = [
            'transaction_details' => [
                'order_id' => $this->order['id'],
                'gross_amount' => intval($this->order['total_price']),
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone_number,
            ],
            'expiry' => [
                'start_time' => $this->start_time,
                'unit' => 'minute',
                'duration' => 30
            ],
            'enabled_payments' => [
                'other_qris', 'shopeepay', 'gopay',
                'bca_va', 'bni_va', 'echannel', 'bri_va', 'other_va'
            ]
        ];

        $snapToken = Snap::getSnapToken($params);

        return $snapToken;
    }
}
