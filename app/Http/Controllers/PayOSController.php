<?php

namespace App\Http\Controllers;

use App\Models\Order;
use AWS\CRT\Log;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use PayOS\PayOS;
use Endroid\QrCode\QrCode;

class PayOSController extends Controller
{
    private $payos = null;
    public function __construct()
    {
        $this->payos = new PayOS(
            config('services.payos.client_id'),
            config('services.payos.api_key'),
            config('services.payos.checksum_key')
        );
    }
    /**
     * @OA\Post(
     *     path="/api/payos/create-payment-link",
     *     tags={"Payment"},
     *     summary="Create PayOS payment link",
     *     description="Create a payment link for order using PayOS. Returns checkout URL and QR code for payment.",
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id"},
     *             @OA\Property(property="order_id", type="string", example="9d4a5c8e-1234-5678-abcd-123456789abc")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment link created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="integer", example=0),
     *             @OA\Property(property="message", type="string", example="Success"),
     *             @OA\Property(property="checkoutUrl", type="string", example="https://pay.payos.vn/web/..."),
     *             @OA\Property(property="qrCode", type="string", example="data:image/png;base64,iVBORw0KGgo..."),
     *             @OA\Property(property="paymentLinkId", type="string", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="accountNumber", type="string", example="1234567890"),
     *             @OA\Property(property="accountName", type="string", example="NGUYEN VAN A"),
     *             @OA\Property(property="amount", type="integer", example=50000)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Order not found"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function createPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'required|string',
            ]);
        } catch (\Throwable $th) {
            return [
                "success" => false,
                "message" => $th->getMessage(),
            ];
        }

        try {
            $order = Order::findOrfail($validated['order_id']);
            if(!$order) {
                return response()->json([
                    "error" => 1,
                    "message" => "Order not found",
                ]);
            }

            if($order->payment_link) {
                // Get payment info to retrieve QR code
                try {
                    $orderCode = intVal(str_replace('ORD', '', $order->order_number));
                    $paymentInfo = $this->payos->getPaymentLinkInformation($orderCode);
                    
                    return response()->json([
                        "error" => 0,
                        "message" => "Success",
                        "checkoutUrl" => $order->payment_link,
                        "qrCode" => $paymentInfo["qrCode"] ?? null,
                        "paymentLinkId" => $paymentInfo["id"] ?? null,
                        "accountNumber" => $paymentInfo["accountNumber"] ?? null,
                        "accountName" => $paymentInfo["accountName"] ?? null,
                        "amount" => $paymentInfo["amount"] ?? null,
                    ]);
                } catch (\Throwable $th) {
                    // If can't get payment info, just return checkout URL
                    return response()->json([
                        "error" => 0,
                        "message" => "Success",
                        "checkoutUrl" => $order->payment_link
                    ]);
                }
            }

            // Initialize PayOS with your credentials
            $orderCode = intVal(str_replace('ORD', '', $order->order_number));
            // Prepare payment data
            $paymentData = [
                'orderCode' => $orderCode, // Unique order code
                'amount' => intval($order->order_total), // Payment amount
                'description' => '#' . $order->order_number, // Payment description
                'returnUrl' => 'https://b9c5-2402-800-63ac-8a09-f954-92d2-5a6c-bf2a.ngrok-free.app', // Redirect URL after payment
                'cancelUrl' => 'https://b9c5-2402-800-63ac-8a09-f954-92d2-5a6c-bf2a.ngrok-free.app', // Redirect URL if payment is canceled
            ];

            try {
                $response = $this->payos->createPaymentLink($paymentData);
                $order->payment_link = $response["checkoutUrl"];
                $order->save();
                return response()->json([
                    "error" => 0,
                    "message" => "Success",
                    "checkoutUrl" => $response["checkoutUrl"],
                    "qrCode" => $response["qrCode"] ?? null,
                    "paymentLinkId" => $response["paymentLinkId"] ?? null,
                    "accountNumber" => $response["accountNumber"] ?? null,
                    "accountName" => $response["accountName"] ?? null,
                    "amount" => $response["amount"] ?? null,
                ]);
            } catch (\Throwable $th) {
                \Illuminate\Support\Facades\Log::debug($th->getMessage());
                return response()->json([
                    "error" => 1,
                    "message" => $th->getMessage(),
                ]);
            }
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::debug('PayOS Webhook test received');
            return response()->json([
                "error" => 1,
                "message" => $th->getMessage(),
            ]);
        }
    }
    public function genPayment($order_id)
    {
        try {
            $order = Order::findOrfail($order_id);
            if(!$order) {
                return response()->json([
                    "error" => 1,
                    "message" => "Order not found",
                ]);
            }

            if($order->payment_link) {
                // Get payment info to retrieve QR code
                try {
                    $orderCode = intVal(str_replace('ORD', '', $order->order_number));
                    $paymentInfo = $this->payos->getPaymentLinkInformation($orderCode);
                    
                    return [
                        "success" => true,
                        "checkoutUrl" => $order->payment_link,
                        "qrCode" => $paymentInfo["qrCode"] ?? null,
                    ];
                } catch (\Throwable $th) {
                    return [
                        "success" => true,
                        "checkoutUrl" => $order->payment_link
                    ];
                }
            }
            // Initialize PayOS with your credentials
            $orderCode = intVal(str_replace('ORD', '', $order->order_number));
            // Prepare payment data
            $paymentData = [
                'orderCode' => $orderCode, // Unique order code
                'amount' => intval($order->order_total), // Payment amount
                'description' => '#' . $order->order_number, // Payment description
                'returnUrl' => 'https://b9c5-2402-800-63ac-8a09-f954-92d2-5a6c-bf2a.ngrok-free.app', // Redirect URL after payment
                'cancelUrl' => 'https://b9c5-2402-800-63ac-8a09-f954-92d2-5a6c-bf2a.ngrok-free.app', // Redirect URL if payment is canceled
            ];

            try {
                $response = $this->payos->createPaymentLink($paymentData);
                $order->payment_link = $response["checkoutUrl"];
                $order->save();
                return [
                    "success" => true,
                    "checkoutUrl" => $response["checkoutUrl"],
                    "qrCode" => $response["qrCode"] ?? null,
                ];
            } catch (\Throwable $th) {
                return response()->json([
                    "error" => 1,
                    "message" => $th->getMessage(),
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                "error" => 1,
                "message" => $th->getMessage(),
            ]);
        }
    }
    public function getPaymentLinkInfoOfOrder(string $id)
    {
        try {
            $response = $this->payos->getPaymentLinkInformation($id);
            return response()->json([
                "error" => 0,
                "message" => "Success",
                "data" => $response["data"]
            ]);
        } catch (\Throwable $th) {
            return $this->handleException($th);
        }
    }
    public function cancelPaymentLinkOfOrder(Request $request, string $id)
    {
        $body = json_decode($request->getContent(), true);
        $cancelBody = is_array($body) && $body["cancellationReason"] ? $body : null;

        try {
            $response = $this->payos->cancelPaymentLink($id, $cancelBody);
            return response()->json([
                "error" => 0,
                "message" => "Success",
                "data" => $response["data"]
            ]);
        } catch (\Throwable $th) {
            return $this->handleException($th);
        }
    }
}
