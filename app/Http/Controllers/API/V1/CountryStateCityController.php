<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

class CountryStateCityController extends Controller
{
    public function country()
    {
        try {
            $country = Http::withHeaders([
                'X-CSCAPI-KEY' => env('CSCAPI_KEY'),
            ])->get('https://api.countrystatecity.in/v1/countries');

            return response()->json([
                'status' => 'success',
                'data' => $country->json()
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function states($iso2)
    {
        try {
            $country = Http::withHeaders([
                'X-CSCAPI-KEY' => env('CSCAPI_KEY'),
            ])->get('https://api.countrystatecity.in/v1/countries/' . $iso2 . '/states');

            return response()->json([
                'status' => 'success',
                'data' => $country->json()
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function cities($iso2_country, $iso2_state)
    {
        try {
            $country = Http::withHeaders([
                'X-CSCAPI-KEY' => env('CSCAPI_KEY'),
            ])->get('https://api.countrystatecity.in/v1/countries/' . $iso2_country . '/states/' . $iso2_state . '/cities');

            return response()->json([
                'status' => 'success',
                'data' => $country->json()
            ], Response::HTTP_OK);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Error while processing data!',
                'error' => $exception->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
