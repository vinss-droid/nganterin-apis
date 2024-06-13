<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RatingController extends Controller
{
    public function postRating(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'order_id' => 'required|exists:orders,id',
                'service_rating' => 'nullable|numeric|between:1,5',
                'cleanliness_rating' => 'nullable|numeric|between:1,5',
                'value_for_money_rating' => 'nullable|numeric|between:1,5',
                'location_rating' => 'nullable|numeric|between:1,5',
                'cozy_rating' => 'nullable|numeric|between:1,5',
                'comment' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            Rating::updateOrCreate([
                'order_id' => $request->order_id,
            ],[
                'order_id' => $request->order_id,
                'service_rating' => $request->service_rating,
                'cleanliness_rating' => $request->cleanliness_rating,
                'value_for_money_rating' => $request->value_for_money_rating,
                'location_rating' => $request->location_rating,
                'cozy_rating' => $request->cozy_rating,
                'comment' => $request->comment
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Rating created successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
