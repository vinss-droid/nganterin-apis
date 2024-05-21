<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\Order;
use App\Models\Payment;
use App\Services\CallbackService;
use App\Services\CreateSnapTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'product_id' => 'required|exists:hotels,id',
                'overnight_stays' => 'required|integer|min:1',
            ]);

            $start_time = date('Y-m-d H:i:s O');
            $date_expired = Date::createFromFormat('Y-m-d H:i:s O', $start_time);
            $hotel = Hotel::join('hotel_details', 'hotel_details.hotel_id', '=', 'hotels.id')
                        ->findOrFail($request->product_id);
            $total_price = intval($request->overnight_stays) * intval($hotel->overnight_prices);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $order = Order::create([
                'customer_id' => Auth::user()->id,
                'product_id' => $request->product_id,
                'code' => 'INV/' . date('Ymd') . '/NGGA/' . 'HTL/' .
                    date('hi/') . date('y') . random_int(100000, 999999),
                'quantity' => intval($request->quantity)
            ]);

            $snap_token_data = [
                'id' => $order->id,
                'total_price' => $total_price,
            ];

            $midtrans = new CreateSnapTokenService($snap_token_data, $start_time);

            $snap_token = $midtrans->getSnapToken();

            $payments = Payment::create([
                'order_id' => $order->id,
                'snap_token' => $snap_token,
                'snap_token_expiration' => $date_expired->addMinutes(30),
                'total_price' => $total_price,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'order created successfully',
                'data' => [
                    'order_id' => $order->id,
                    'snap_token' => $payments->snap_token,
                    'snap_token_expiration' => $payments->snap_token_expiration,
                ]
            ], Response::HTTP_OK);

        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function updateStatusPayment(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'transaction_status' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $orderStatus = Order::findOrFail($request->order_id)->update([
                'is_completed' => $request->transaction_status === 'settlement' ? 1 : 0
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'order status updated successfully',
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function receiveStatus()
    {
        $callback = new CallbackService();

        if ($callback->isSignatureKeyVerified()) {
            $notification = $callback->getNotification();
            $order = $callback->getOrder();

            if ($callback->isSuccess()) {
                Payment::where('order_id', $order->id)->update([
                    'status' => 'success'
                ]);
                Order::findOrFail($order->id)->update([
                    'is_completed' => true
                ]);
            }

            if ($callback->isExpired()) {
                Payment::where('order_id', $order->id)->update([
                    'status' => 'expire'
                ]);
            }

            if ($callback->isCancelled()) {
                Payment::where('order_id', $order->id)->update([
                    'status' => 'cancel'
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'order received successfully',
            ], Response::HTTP_OK);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Invalid signature key'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function historyCheckout()
    {
        try {
            $orders = Order::join('hotels', 'hotels.id', '=', 'orders.product_id')
                    ->join('hotel_details', 'hotel_details.hotel_id', '=', 'hotels.id')
                    ->join('payments', 'payments.order_id', '=', 'orders.id')
                    ->where('orders.customer_id', Auth::user()->id)
                    ->select(
                        'hotels.name AS hotel_name', 'hotel_details.hotel_photos', 'orders.code AS invoice',
                        'orders.created_at', 'payments.total_price', 'orders.is_completed'
                    )
                    ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'orders received successfully',
                'data' => $orders
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'errors' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
