<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GHNController extends Controller
{
    /**
     * Get wards for a specific district from GHN API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @OA\Get(
     *     path="/api/ghn/provinces",
     *     tags={"Shipping"},
     *     summary="Get provinces list",
     *     description="Get all provinces from GHN API for shipping address",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Provinces retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="ProvinceID", type="integer"),
     *                 @OA\Property(property="ProvinceName", type="string")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=500, description="GHN API Error")
     * )
     */
    public function getProvinces(Request $request): \Illuminate\Http\JsonResponse
    {
        // GHN API endpoint
        $url = "https://online-gateway.ghn.vn/shiip/public-api/master-data/province";

        // Initialize Guzzle client
        $client = new Client();

        $token = config('services.ghn.api_token');

        try {
            // Make the API request to GHN
            $response = $client->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Token' => $token, // Use your GHN API token from .env
                ],
            ]);

            // Decode the JSON response
            $data = json_decode($response->getBody(), true);

            // Return the response data
            return response()->json($data);
        } catch (\Exception $e) {
            // Handle errors
            Log::error('Error fetching provinces from GHN API: ' . $e->getMessage());

            return response()->json([
                'success' => 'Failed to fetch data from GHN API',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/ghn/districts",
     *     tags={"Shipping"},
     *     summary="Get districts list",
     *     description="Get districts by province ID from GHN API",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="province_id",
     *         in="query",
     *         description="Province ID from GHN",
     *         required=true,
     *         @OA\Schema(type="integer", example=202)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Districts retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=500, description="GHN API Error")
     * )
     */
    public function getDistricts(Request $request)
    {
        // Validate the request
        $request->validate([
            'province_id' => 'required|integer',
        ]);

        // Get the province ID from the request
        $provinceId = $request->input('province_id');

        // GHN API endpoint
        $url = "https://online-gateway.ghn.vn/shiip/public-api/master-data/district?province_id={$provinceId}";

        // Initialize Guzzle client
        $client = new Client();

        $token = config('services.ghn.api_token');

        try {
            // Make the API request to GHN
            $response = $client->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Token' => $token, // Use your GHN API token from .env
                ],
            ]);

            // Decode the JSON response
            $data = json_decode($response->getBody(), true);

            // Return the response data
            return response()->json($data);
        } catch (\Exception $e) {
            // Handle errors
            return response()->json([
                'error' => 'Failed to fetch data from GHN API',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/ghn/wards",
     *     tags={"Shipping"},
     *     summary="Get wards list",
     *     description="Get wards by district ID from GHN API",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="district_id",
     *         in="query",
     *         description="District ID from GHN",
     *         required=true,
     *         @OA\Schema(type="integer", example=1542)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Wards retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=500, description="GHN API Error")
     * )
     */
    public function getWards(Request $request)
    {
        // Validate the request
        $request->validate([
            'district_id' => 'required|integer',
        ]);

        // Get the district ID from the request
        $districtId = $request->input('district_id');

        // GHN API endpoint
        $url = "https://online-gateway.ghn.vn/shiip/public-api/master-data/ward?district_id={$districtId}";

        // Initialize Guzzle client
        $client = new \GuzzleHttp\Client();

        $token = config('services.ghn.api_token');

        try {
            // Make the API request to GHN
            $response = $client->get($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Token' => $token, // Use your GHN API token from .env
                ],
            ]);

            // Decode the JSON response
            $data = json_decode($response->getBody(), true);

            // Return the response data
            return response()->json($data);
        } catch (\Exception $e) {
            // Handle errors
            return response()->json([
                'error' => 'Failed to fetch data from GHN API',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/ghn/shipping-fee",
     *     tags={"Shipping"},
     *     summary="Calculate shipping fee",
     *     description="Calculate shipping fee from GHN based on destination",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="team_id",
     *         in="query",
     *         description="Team/Branch ID (from location)",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="to_district_id",
     *         in="query",
     *         description="Destination District ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="to_ward_code",
     *         in="query",
     *         description="Destination Ward Code",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="insurance_value",
     *         in="query",
     *         description="Order value for insurance",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shipping fee calculated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="code", type="integer", example=200),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total", type="integer", example=50000)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation Error"),
     *     @OA\Response(response=500, description="GHN API Error")
     * )
     */
    public function getShippingFee(Request $request)
    {
        $request->validate([
            'team_id' => 'required|string',
            'to_district_id' => 'required|string',
            'to_ward_code' => 'required|string',
            'insurance_value' => 'required',
        ]);

        $client = new Client();
        $url = 'https://online-gateway.ghn.vn/shiip/public-api/v2/shipping-order/fee';
        $team = Team::find($request->input('team_id'));
        try {
            $json = [
                'from_district_id' => (int) $team->state,
                'from_ward_code' => $team->ward,
                'service_id' => 53320,
                'service_type_id' => null,
                'to_district_id' => (int) $request->input('to_district_id'),
                'to_ward_code' => (string) "21109",
                "height"=>50,
                "length"=>20,
                "weight"=>200,
                "width"=>20,
                'insurance_value' => (int) $request->input('insurance_value'),
                'cod_failed_amount' => 1000,
                'coupon' => null,
                "items" => [
                    [
                        "name"=> "TEST1",
                      "quantity"=> 1,
                      "height"=> 16,
                      "weight"=> 16,
                      "length"=> 15,
                      "width"=> 15
                    ]
              ]
            ];
            $response = $client->post($url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Token' => env('GHN_API_TOKEN'),
                    'ShopId' => '5530810',
                ],
                'json' => $json,
            ]);

            $data = json_decode($response->getBody(), true);
            $data['data']['total'] += rand(1000, 5000);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch shipping fee',
                'message' => $e->getMessage(),
            ], 500);
        }
    }}
