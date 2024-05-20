<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\HotelDetail;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HotelController extends Controller
{

    public function getMyHotels()
    {
        try {
            $hotels = Hotel::join('hotel_details', 'hotel_details.hotel_id', '=', 'hotels.id')
                        ->join('locations', 'hotels.location_id', '=', 'locations.id')
                        ->where('hotels.partner_id', Auth::user()->partner_id)
                        ->where('hotels.deleted_at', null)
                        ->select(
                            'hotels.name', 'hotels.description',
                            'locations.country', 'locations.state', 'locations.city', 'locations.zip_code',
                            'locations.complete_address', 'locations.gmaps',
                            'hotel_details.max_visitor', 'hotel_details.room_sizes',
                            'hotel_details.smoking_allowed', 'hotel_details.facilities',
                            'hotel_details.hotel_photos', 'hotel_details.overnight_prices',
                            'hotel_details.total_room', 'hotel_details.total_booked'
                        )->get();

            return response()->json([
                'status' => 'success',
                'message' => 'hotels retrieved successfully',
                'data' => $hotels
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed while processing your request',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function searchHotel(Request $request)
    {
        try {

            $search = $request->get('search');

            $hotels = Hotel::join('hotel_details', 'hotel_details.hotel_id', '=', 'hotels.id')
                ->join('locations', 'hotels.location_id', '=', 'locations.id')
                ->where('hotels.deleted_at', null)
                ->where('hotels.name', 'like', '%' . $search . '%')
                ->orWhere('locations.state', 'like', '%' . $search . '%')
                ->orWhere('locations.city', 'like', '%' . $search . '%')
                ->select(
                    'hotels.id', 'hotels.name', 'hotels.description',
                    'locations.country', 'locations.state', 'locations.city', 'locations.zip_code',
                    'locations.complete_address', 'locations.gmaps',
                    'hotel_details.max_visitor', 'hotel_details.room_sizes',
                    'hotel_details.smoking_allowed', 'hotel_details.facilities',
                    'hotel_details.hotel_photos', 'hotel_details.overnight_prices',
                    'hotel_details.total_room', 'hotel_details.total_booked'
                )
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'hotels retrieved successfully',
                'data' => $hotels
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed while processing your request',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getHotelById($id)
    {
        try {
            $hotels = Hotel::join('hotel_details', 'hotel_details.hotel_id', '=', 'hotels.id')
                ->join('locations', 'hotels.location_id', '=', 'locations.id')
                ->where(['hotels.deleted_at' => null, 'hotels.id' => $id])
                ->select(
                    'hotels.id', 'hotels.name', 'hotels.description',
                    'locations.country', 'locations.state', 'locations.city', 'locations.zip_code',
                    'locations.complete_address', 'locations.gmaps',
                    'hotel_details.max_visitor', 'hotel_details.room_sizes',
                    'hotel_details.smoking_allowed', 'hotel_details.facilities',
                    'hotel_details.hotel_photos', 'hotel_details.overnight_prices',
                    'hotel_details.total_room', 'hotel_details.total_booked'
                )
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'hotels retrieved successfully',
                'data' => $hotels
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed while processing your request',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function createHotel(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'name' => 'required|unique:hotels,name',
                'description' => 'required',
                'max_visitor' => 'required',
                'room_sizes' => 'required',
                'smoking_allowed' => 'required|boolean',
                'facilities' => 'required',
                'hotel_photos' => 'required',
                'overnight_prices' => 'required|numeric',
                'total_room' => 'required|numeric',
                'country' => 'required',
                'state' => 'required',
                'city' => 'required',
                'zip_code' => 'required',
                'complete_address' => 'required',
                'gmaps' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'validation errors',
                    'errors' => $validator->errors()
                ], Response::HTTP_BAD_REQUEST);
            }

            $location = Location::create([
                'country' => $request->country,
                'state' => $request->state,
                'city' => $request->city,
                'zip_code' => $request->zip_code,
                'complete_address' => $request->complete_address,
                'gmaps' => $request->gmaps
            ]);

            $hotel = Hotel::create([
                'partner_id' => Auth::user()->partner_id,
                'location_id' => $location->id,
                'name' => $request->name,
                'description' => $request->description
            ]);

            $detailHotel = HotelDetail::create([
                'hotel_id' => $hotel->id,
                'max_visitor' => $request->max_visitor,
                'room_sizes' => $request->room_sizes,
                'smoking_allowed' => $request->smoking_allowed,
                'facilities' => json_encode($request->facilities),
                'hotel_photos' => json_encode($request->hotel_photos),
                'overnight_prices' => $request->overnight_prices,
                'total_room' => $request->total_room,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Hotel created successfully',
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
}
