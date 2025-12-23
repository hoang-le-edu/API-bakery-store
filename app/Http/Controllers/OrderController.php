<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\Customer;
use App\Models\CustomerOrder;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\Voice\Pay;

class OrderController extends Controller
{
    protected $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }
    /**
     * @OA\Get(
     *     path="/api/admin/orders/all",
     *     tags={"Orders"},
     *     summary="Get all orders (Admin)",
     *     description="Get all orders with customer and creator information for admin panel",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Orders retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Orders fetched successfully."),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="order_number", type="string"),
     *                 @OA\Property(property="receiver_name", type="string"),
     *                 @OA\Property(property="receiver_address", type="string"),
     *                 @OA\Property(property="payment_method", type="string"),
     *                 @OA\Property(property="order_status", type="string"),
     *                 @OA\Property(property="order_total", type="number"),
     *                 @OA\Property(property="created_at", type="string"),
     *                 @OA\Property(property="customers", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="creator", type="object")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * Display a listing of the orders.
     */
    public function index()
    {
        $orders = Order::with(['customers', 'creator'])->get();
        return response()->json(['message' => 'Orders fetched successfully.', 'data' => $orders]);
    }
    /**
     * @OA\Delete(
     *     path="/api/cart/deleteCart/{id}",
     *     tags={"Cart"},
     *     summary="Delete entire cart",
     *     description="Delete a cart (draft order) with all its items and details",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID (cart ID)",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function deleteCart($orderId)
    {
        try {
            // Find the order by ID
            $order = Order::findOrFail($orderId);

            // Fetch all related pivot records (customers_orders) for this order
            $customerOrders = CustomerOrder::where('order_id', $orderId)->get();

            // Loop through each pivot record and delete the associated order details
            foreach ($customerOrders as $customerOrder) {
                // Delete toppings first (if any)
                foreach ($customerOrder->orderDetails()->where('parent_id', null)->get() as $orderDetail) {
                    // Delete toppings associated with this order detail
                    OrderDetail::where('parent_id', $orderDetail->id)->delete();
                }

                // Delete the order details
                $customerOrder->orderDetails()->delete();
            }

            // Detach all customers from the order (this will remove the pivot records)
            $order->customers()->detach();

            // Then delete the main order
            $order->delete();

            return response()->json(['message' => 'Order deleted successfully.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Order not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/cart/createCart",
     *     tags={"Cart"},
     *     summary="Create or get user's cart",
     *     description="Get existing cart or create a new one if it doesn't exist. Only one cart per user is allowed.",
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="custom_name", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart created or retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function createCart(Request $request)
    {
        $currentUser = auth()->user();
        $customer = Customer::where('user_id', $currentUser->id)->first();

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Check if cart already exists
        $existingCart = Order::where('host_id', $customer->id)
            ->where('order_status', 'Draft')
            ->first();

        if ($existingCart) {
            return response()->json([
                'message' => 'Cart already exists.',
                'data' => $existingCart
            ]);
        }

        // Create a new cart
        $order = Order::create([
            'order_number' => 'ORD' . time(),
            'receiver_name' => $customer->full_name,
            'receiver_address' => '',
            'payment_method' => 'Cash',
            'order_status' => 'Draft',
            'type' => 'Personal',
            'custom_name' => $request->get('custom_name'),
            'source' => 'Online',
            'customer_feedback' => '',
            'order_total' => 0,
            'host_id' => $customer->id,
        ]);

        // Attach the customer to the order using the pivot table
        $order->customers()->attach($customer->id);

        return response()->json(['message' => 'Cart created successfully.', 'data' => $order]);
    }

    /**
     * @OA\Get(
     *     path="/api/loadCustomerOrders",
     *     tags={"Orders"},
     *     summary="Get customer order history",
     *     description="Get all orders (except Draft) for authenticated customer, grouped by status",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Orders fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Orders fetched successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="Wait For Approval", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="In Progress", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="Delivering", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="Completed", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="Cancelled", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function loadCustomerOrders(Request $request)
    {
        //        $currentUser = auth()->user();
        //        if($currentUser->user_type == 'user') {
        //            $customer = Customer::find($_GET["customerId"]);
        //        } else {
        //            $customer = Customer::where('user_id', $currentUser->id)->first();
        //        }

        $user = $this->checkFirebaseUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized or User not found.'], 401);
        }

        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Fetch all orders that belong to the customer
        $orders = Order::where('order_status', '<>', 'Draft')
            ->whereHas('customers', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);
            })
            ->orderBy('updated_at', 'DESC')
            ->get();

        $return_data = [];
        foreach ($orders as $order) {
            $orderCustomer = $order->customers()->where('customer_id', $customer->id)->first();

            $orderDetails = $orderCustomer->pivot->orderDetails()
                ->where('parent_id', null)
                ->with('toppings.product') // Include topping product details
                ->get();

            $data = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'date_created' => $order->created_at,
                'host_id' => $order->host_id,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'receiver_name' => $order->receiver_name,
                'status' => $order->order_status,
                'count_product' => $orderDetails->count(),
                'total_price' => $order->order_total,
                'order_date' => $order->updated_at,
                'rate' => $order->rate,
                'feedback' => $order->customer_feedback,
                'note' => $order->note,
            ];
            foreach ($orderDetails as $orderDetail) {
                $data['order_detail'][] = [
                    'id' => $orderDetail->id,
                    'order_detail_number' => $orderDetail->order_detail_number,
                    'product_id' => $orderDetail->product->id,
                    'product_name' => $orderDetail->product->name,
                    'product_price' => $orderDetail->product->price,
                    'size' => $orderDetail->size,
                    'quantity' => $orderDetail->quantity,
                    'image' => $orderDetail->product->image ? asset('storage/build/assets/' . $orderDetail->product->image) : null,
                    'note' => $orderDetail->note,
                    'total_price' => $orderDetail->total_price,
                    'count_topping' => $orderDetail->toppings->count(),
                ];
            }
            $return_data[$order->order_status][] = $data;
        }

        return response()->json([
            'message' => 'Orders fetched successfully.',
            'data' => empty($return_data) ? (object)[] : $return_data,
        ]);
    }
    public function loadCustomerOrdersHistory(Request $request)
    {
        //        return response()->json([
        //            'message' => 'Orders fetched successfully.',
        //            'data' => Auth::user()->customer,
        //        ]);
        $customer = Auth::user()->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Fetch all orders that belong to the customer
        $orders = Order::where('order_status', '<>', 'Draft')
            ->whereHas('customers', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id);
            })
            ->orderBy('updated_at', 'DESC')
            ->get();

        $return_data = [];
        foreach ($orders as $order) {
            $orderCustomer = $order->customers()->where('customer_id', $customer->id)->first();

            $orderDetails = $orderCustomer->pivot->orderDetails()
                ->where('parent_id', null)
                ->with('toppings.product') // Include topping product details
                ->get();

            $data = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'date_created' => $order->created_at,
                'host_id' => $order->host_id,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'receiver_name' => $order->receiver_name,
                'status' => $order->order_status,
                'count_product' => $orderDetails->count(),
                'total_price' => $order->order_total,
                'order_date' => $order->updated_at,
                'rate' => $order->rate,
                'feedback' => $order->customer_feedback,
            ];
            $return_data[] = $data;
        }

        return response()->json([
            'message' => 'Orders fetched successfully.',
            'data' => $return_data,
        ]);
    }

    public function loadOrderDetail(Request $request, $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);

            $currentUser = auth()->user();

            if ($currentUser->user_type == 'user') {
                $customer_id = $order->host_id;
            } else {
                $customer_id = Customer::where('user_id', $currentUser->id)->first()->id;

                if (!$customer_id) {
                    return response()->json(['message' => 'Customer not found.'], 404);
                }
            }

            // Fetch the order
            $orderCustomer = $order->customers()->where('customer_id', $customer_id)->first();

            if ($orderCustomer) {
                // Get order details if the relationship exists
                $orderDetails = $orderCustomer->pivot->orderDetails()
                    ->where('parent_id', null)
                    ->with('toppings.product') // Include topping product details
                    ->get();

                $customer = Customer::find($order->host_id);
                $data = [
                    'type' => $order->type,
                    'order_number' => !empty($order->custom_name) ? $order->custom_name : $order->order_number,
                    'order_id' => $order->id,
                    'date_created' => $order->created_at,
                    'host_id' => $order->host_id,
                    'status' => $order->order_status,
                    'order_total' => $order->order_total - $this->calculateDiscount($order),
                    'count_product' => $orderDetails->count() ?? 0,
                    'order_detail' => [],
                    'customer_name' => $order->receiver_name,
                    'customer_phone' => $customer->phone_number,
                    'customer_level' => $customer->rank,
                    'to_name' => $order->receiver_name,
                    'to_address' => $order->receiver_address,
                    'shipping_fee' => $order->shipping_fee,
                    'discount' => $this->calculateDiscount($order),
                    'payment_method' => $order->payment_method,
                    'feedback' => [
                        'rating' => $order->rate ?? 0,
                        'content' => $order->customer_feedback,
                        'feedback_time' => $order->updated_at,
                    ],
                    'vouchers' => $order->vouchers->map(function ($voucher) {
                        return [
                            'id' => $voucher->id,
                            'voucher_code' => $voucher->vourcher_code,
                            'discount_amount' => $voucher->discount_amount,
                            'discount_percent' => $voucher->discount_percent,
                            'discount_type' => $voucher->discount_type,
                            'apply_type' => $voucher->apply_type,
                        ];
                    }),
                ];
                $total_price = 0;
                foreach ($orderDetails as $orderDetail) {
                    $total_price += $orderDetail->total_price;
                    $data['order_detail'][] = [
                        'order_detail_number' => $orderDetail->order_detail_number,
                        'product_id' => $orderDetail->product->id,
                        'product_name' => $orderDetail->product->name,
                        'product_price' => $orderDetail->product->price,
                        'size' => $orderDetail->size,
                        'quantity' => $orderDetail->quantity,
                        'image' => $orderDetail->product->image ? 'https://weevil-exotic-thankfully.ngrok-free.app/storage/' . $orderDetail->product->image : 'https://weevil-exotic-thankfully.ngrok-free.app/resources/assets/images/empty-image.jpg',
                        'note' => $orderDetail->note,
                        'total_price' => $orderDetail->total_price,
                        'count_topping' => $orderDetail->toppings->count(),
                        'toppings' => $orderDetail->toppings->map(function ($topping) {
                            return [
                                'topping_id' => $topping->id,
                                'name' => $topping->product->name,
                                'price' => $topping->product->price,
                            ];
                        }),
                    ];
                }
                $data['total_price'] = $total_price;
                $return_data[] = $data;
            }

            return response()->json([
                'message' => 'Cart details fetched successfully.',
                'data' => $return_data
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Order not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/orders/proceed",
     *     summary="Proceed with order checkout",
     *     description="Finalize order with shipping details, payment method, and vouchers. Requires Firebase authentication.",
     *     tags={"Orders"},
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id", "receiver_name", "receiver_address", "payment_method", "province", "district", "ward", "street", "phone_number", "shipping_fee"},
     *             @OA\Property(property="order_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="receiver_name", type="string", example="Nguyen Van A"),
     *             @OA\Property(property="receiver_address", type="string", example="123 Nguyen Hue, District 1"),
     *             @OA\Property(property="payment_method", type="string", enum={"Banking", "Cash"}, example="Banking"),
     *             @OA\Property(property="voucher", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174001"),
     *             @OA\Property(property="voucher_shipping", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174002"),
     *             @OA\Property(property="note", type="string", example="Giao sau 5h"),
     *             @OA\Property(property="province", type="string", example="Ho Chi Minh"),
     *             @OA\Property(property="district", type="string", example="District 1"),
     *             @OA\Property(property="ward", type="string", example="Ben Nghe"),
     *             @OA\Property(property="street", type="string", example="123 Nguyen Hue"),
     *             @OA\Property(property="phone_number", type="string", example="0901234567"),
     *             @OA\Property(property="shipping_fee", type="number", example=25000),
     *             @OA\Property(property="discount_number", type="number", example=10000),
     *             @OA\Property(property="order_total", type="number", example=150000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order proceeded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order proceeded successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Order does not belong to customer"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    /**
     * @OA\Post(
     *     path="/api/orders/proceed",
     *     tags={"Orders"},
     *     summary="Proceed with checkout",
     *     description="Checkout selected products from cart. Creates a new order with selected items and removes them from cart.",
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_detail_ids", "receiver_name", "receiver_address", "payment_method", "province", "district", "ward", "street", "phone_number", "shipping_fee"},
     *             @OA\Property(property="order_detail_ids", type="array", @OA\Items(type="string"), description="Array of order detail IDs to checkout"),
     *             @OA\Property(property="receiver_name", type="string"),
     *             @OA\Property(property="receiver_address", type="string"),
     *             @OA\Property(property="payment_method", type="string", enum={"Cash", "Banking"}),
     *             @OA\Property(property="voucher", type="string", nullable=true),
     *             @OA\Property(property="voucher_shipping", type="string", nullable=true),
     *             @OA\Property(property="note", type="string", nullable=true),
     *             @OA\Property(property="province", type="string"),
     *             @OA\Property(property="district", type="string"),
     *             @OA\Property(property="ward", type="string"),
     *             @OA\Property(property="street", type="string"),
     *             @OA\Property(property="phone_number", type="string"),
     *             @OA\Property(property="shipping_fee", type="number"),
     *             @OA\Property(property="discount_number", type="number", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function proceedOrder(StoreOrderRequest $request)
    {
        $validated = $request->validated();

        $user = $this->checkFirebaseUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized or User not found.'], 401);
        }

        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Get the Draft cart
        $cartOrder = Order::where('host_id', $customer->id)
            ->where('order_status', 'Draft')
            ->first();

        if (!$cartOrder) {
            return response()->json(['message' => 'Cart not found.'], 404);
        }

        // Get customer order relationship
        $cartCustomerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $cartOrder->id)
            ->first();

        if (!$cartCustomerOrder) {
            return response()->json(['message' => 'Invalid cart.'], 403);
        }

        // Fetch selected order details from cart
        $selectedOrderDetails = OrderDetail::whereIn('id', $validated['order_detail_ids'])
            ->where('customer_order_id', $cartCustomerOrder->id)
            ->where('parent_id', null)
            ->with('toppings')
            ->get();

        if ($selectedOrderDetails->isEmpty()) {
            return response()->json(['message' => 'No valid products selected.'], 400);
        }

        // Calculate base order totals
        $subtotal = $selectedOrderDetails->sum('total_price');
        $shippingFee = $validated['shipping_fee'];

        // Validate and calculate voucher discounts SERVER-SIDE using voucher CODES
        $productDiscount = 0;
        $shippingDiscount = 0;
        $appliedVouchers = [];

        // Create temporary order object for validation
        $tempOrder = new Order();
        $tempOrder->order_total = $subtotal;
        $tempOrder->shipping_fee = $shippingFee;

        // Validate product discount voucher CODE
        if (!empty($validated['voucher_code'])) {
            $voucherResult = $this->voucherService->validateVoucherByCode(
                $validated['voucher_code'],
                $tempOrder,
                'discount',
                $selectedOrderDetails
            );

            if (!$voucherResult['valid']) {
                return response()->json([
                    'message' => 'Voucher validation failed',
                    'error' => $voucherResult['message']
                ], 422);
            }

            $productDiscount = $voucherResult['discount'];
            $appliedVouchers[] = $voucherResult['voucher']->id; // Store voucher ID for attachment
        }

        // Validate shipping voucher CODE
        if (!empty($validated['voucher_shipping_code'])) {
            $shippingResult = $this->voucherService->validateVoucherByCode(
                $validated['voucher_shipping_code'],
                $tempOrder,
                'shipping_fee',
                $selectedOrderDetails
            );

            if (!$shippingResult['valid']) {
                return response()->json([
                    'message' => 'Shipping voucher validation failed',
                    'error' => $shippingResult['message']
                ], 422);
            }

            $shippingDiscount = $shippingResult['discount'];
            $appliedVouchers[] = $shippingResult['voucher']->id; // Store voucher ID for attachment
        }

        // Calculate final order total with validated discounts
        $orderTotal = $subtotal + $shippingFee - $productDiscount - $shippingDiscount;
        $orderTotal = max(0, $orderTotal); // Ensure non-negative

        // Create a new order for checkout
        $newOrder = Order::create([
            'order_number' => 'ORD' . time(),
            'receiver_name' => $validated['receiver_name'],
            'receiver_address' => $validated['receiver_address'],
            'receiver_phone' => $validated['phone_number'],
            'payment_method' => $validated['payment_method'],
            'order_status' => 'Wait For Approval',
            'order_total' => $orderTotal,
            'note' => $validated['note'] ?? '',
            'province' => $validated['province'],
            'district' => $validated['district'],
            'ward' => $validated['ward'],
            'street' => $validated['street'],
            'phone_number' => $validated['phone_number'],
            'shipping_fee' => $validated['shipping_fee'],
            'source' => 'Online',
            'host_id' => $customer->id,
            'customer_feedback' => '',
        ]);

        // Attach customer to new order
        $newOrder->customers()->attach($customer->id);
        $newCustomerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $newOrder->id)
            ->first();

        // Copy selected order details to new order
        foreach ($selectedOrderDetails as $oldDetail) {
            $newDetail = $newCustomerOrder->orderDetails()->create([
                'order_detail_number' => 'OD' . time() . rand(100, 999),
                'customer_order_id' => $newCustomerOrder->id,
                'product_id' => $oldDetail->product_id,
                'parent_id' => null,
                'size' => $oldDetail->size,
                'quantity' => $oldDetail->quantity,
                'note' => $oldDetail->note,
                'total_price' => $oldDetail->total_price,
            ]);

            // Copy toppings
            foreach ($oldDetail->toppings as $topping) {
                $newDetail->toppings()->create([
                    'order_detail_number' => 'ODTP' . time() . rand(100, 999),
                    'customer_order_id' => $newCustomerOrder->id,
                    'product_id' => $topping->product_id,
                    'size' => $topping->size,
                    'quantity' => $topping->quantity,
                    'total_price' => $topping->total_price,
                    'note' => $topping->note,
                    'parent_id' => $newDetail->id,
                ]);
            }

            // Remove from cart: delete toppings first, then the detail
            $oldDetail->toppings()->delete();
            $oldDetail->delete();
        }

        // Update cart total
        $remainingTotal = OrderDetail::where('customer_order_id', $cartCustomerOrder->id)
            ->where('parent_id', null)
            ->sum('total_price');
        $cartOrder->order_total = $remainingTotal;
        $cartOrder->save();

        // Attach validated vouchers to order
        if (!empty($appliedVouchers)) {
            $newOrder->vouchers()->attach($appliedVouchers);
        }

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => [
                'order' => $newOrder,
                'discounts' => [
                    'product_discount' => $productDiscount,
                    'shipping_discount' => $shippingDiscount,
                    'total_discount' => $productDiscount + $shippingDiscount,
                ],
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/customer/markReceived",
     *     summary="Mark order as received by customer",
     *     description="Customer marks order as completed/delivered. Requires Firebase authentication.",
     *     tags={"Orders"},
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id"},
     *             @OA\Property(property="order_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order marked as delivered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order marked as delivered successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Order does not belong to customer"),
     *     @OA\Response(response=404, description="Customer or Order not found")
     * )
     */
    function markReceived(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
        ]);

        $currentUser = auth()->user();
        $customer = Customer::where('user_id', $currentUser->id)->first();

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Fetch the order
        $order = Order::findOrFail($validated['order_id']);

        // Ensure the order belongs to the current customer
        $customerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->first();

        if (!$customerOrder) {
            return response()->json(['message' => 'Unauthorized or invalid order.'], 403);
        }

        // Update the order status to 'Delivered'
        $order->update([
            'order_status' => 'Completed',
        ]);

        return response()->json([
            'message' => 'Order marked as delivered successfully.',
            'data' => $order,
        ]);
    }
    /**
     * @OA\Post(
     *     path="/api/customer/giveFeedback",
     *     summary="Give feedback and rating for completed order",
     *     description="Customer provides rating (1-5 stars) and feedback for their order. Requires Firebase authentication.",
     *     tags={"Orders"},
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id", "rate"},
     *             @OA\Property(property="order_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000"),
     *             @OA\Property(property="rate", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="customer_feedback", type="string", example="Sản phẩm rất ngon!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Feedback submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Feedback submitted successfully."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Order does not belong to customer"),
     *     @OA\Response(response=404, description="Customer or Order not found")
     * )
     */
    function giveFeedback(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'rate' => 'required|integer|min:1|max:5',
            'feedback' => 'required|string|max:255',
        ]);

        $currentUser = auth()->user();
        $customer = Customer::where('user_id', $currentUser->id)->first();

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Fetch the order
        $order = Order::findOrFail($validated['order_id']);

        // Ensure the order belongs to the current customer
        $customerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->first();

        if (!$customerOrder) {
            return response()->json(['message' => 'Unauthorized or invalid order.'], 403);
        }

        // Update the order with the feedback
        $order->update([
            'rate' => $validated['rate'],
            'customer_feedback' => $validated['feedback'],
        ]);

        return response()->json([
            'message' => 'Feedback submitted successfully.',
            'data' => $order,
        ]);
    }

    public function getCustomFields($data)
    {
        $customFields = [];
        try {
            foreach ($data as $item) {
                $customFields[$item->id] = [
                    'count_product' => 0,
                ];
                $orderCustomer = $item->customers()->where('customer_id', $item->host_id)->first();
                if ($orderCustomer) {
                    $oderDetail = $orderCustomer->pivot->orderDetails()
                        ->where('parent_id', null)
                        ->with('toppings.product') // Include topping product details
                        ->get();
                    if ($oderDetail) {
                        $customFields[$item->id] = [
                            'count_product' => $oderDetail->count(),
                        ];
                    }
                }
            }
            return $customFields;
        } catch (\Exception $e) {
            return $customFields;
        }
    }

    function calculateDiscount($order)
    {
        $totalDiscount = 0;
        foreach ($order->vouchers as $voucher) {
            if ($voucher->apply_type == 'shipping_fee') continue;
            if ($voucher->discount_type == 'percent') {
                $totalDiscount += $order->order_total * $voucher->discount_percent / 100;
            } else {
                $totalDiscount += $voucher->discount_amount;
            }
        }
        return $totalDiscount;
    }

    /**
     * Calculate discount for admin order detail based on total_price
     * @param object $order Order object with vouchers
     * @param float $total_price Total price from order details
     * @return float Total discount amount
     */
    function calculateDiscountForAdmin($order, $total_price)
    {
        $totalDiscount = 0;
        foreach ($order->vouchers as $voucher) {
            if ($voucher->apply_type == 'shipping_fee') continue;
            if ($voucher->discount_type == 'percent') {
                $totalDiscount += $total_price * $voucher->discount_percent / 100;
            } else {
                $totalDiscount += $voucher->discount_amount;
            }
        }
        return $totalDiscount;
    }
    /**
     * @OA\Post(
     *     path="/api/cart/addProductToCart",
     *     tags={"Cart"},
     *     summary="Add product to cart",
     *     description="Add a product with optional toppings to one or multiple shopping carts",
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product"},
     *             @OA\Property(property="product", type="object",
     *                 @OA\Property(property="product_id", type="integer"),
     *                 @OA\Property(property="size", type="string", example="M"),
     *                 @OA\Property(property="quantity", type="integer", example=1),
     *                 @OA\Property(property="toppings_id", type="array", @OA\Items(type="integer")),
     *                 @OA\Property(property="note", type="string"),
     *                 @OA\Property(property="total_price", type="number")
     *             ),
     *             @OA\Property(property="order_ids", type="array", @OA\Items(type="integer"), description="Array of order IDs to add product to")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product added to cart successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Product or Customer not found")
     * )
     */
    /**
     * @OA\Post(
     *     path="/api/cart/addProductToCart",
     *     tags={"Cart"},
     *     summary="Add product to cart",
     *     description="Add a product to user's single cart. Automatically creates or uses existing Draft order.",
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product"},
     *             @OA\Property(property="product", type="object",
     *                 @OA\Property(property="product_id", type="string", format="uuid"),
     *                 @OA\Property(property="size", type="string", example="M"),
     *                 @OA\Property(property="quantity", type="integer", example=2),
     *                 @OA\Property(property="toppings_id", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="note", type="string"),
     *                 @OA\Property(property="total_price", type="number")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product added to cart successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Product or Customer not found")
     * )
     */
    public function addProductToCart(Request $request)
    {
        $validated = $request->validate([
            'product' => 'required',
            'product.product_id' => 'required',
            'product.size' => 'required',
            'product.quantity' => 'required',
            'product.toppings_id' => 'nullable|array',
            'product.note' => 'nullable',
            'product.total_price' => 'required',
        ]);

        // Check if product exists
        $product = Product::findOrFail($validated['product']['product_id']);

        if (!$product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $user = $this->checkFirebaseUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized or User not found.'], 401);
        }

        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Find or create a single Draft order for this customer
        $order = Order::where('host_id', $customer->id)
            ->where('order_status', 'Draft')
            ->first();

        if (!$order) {
            // Create a new cart if none exists
            $order = Order::create([
                'order_number' => 'ORD' . time(),
                'receiver_name' => $customer->full_name,
                'receiver_address' => '',
                'payment_method' => 'Cash',
                'order_status' => 'Draft',
                'type' => 'Personal',
                'source' => 'Online',
                'customer_feedback' => '',
                'order_total' => 0,
                'host_id' => $customer->id,
            ]);
            $order->customers()->attach($customer->id);
        }

        // Get the customer order relationship
        $customerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->first();

        if (!$customerOrder) {
            return response()->json(['message' => 'Cart relationship not found.'], 500);
        }

        // Normalize toppings array for comparison
        $toppingsIds = isset($validated['product']['toppings_id'])
            ? array_values(array_unique($validated['product']['toppings_id']))
            : [];
        sort($toppingsIds);

        // Check if this exact product (same product_id, size, note, and toppings) already exists in cart
        $existingOrderDetails = $customerOrder->orderDetails()
            ->where('product_id', $product->id)
            ->where('size', $validated['product']['size'])
            ->where('note', $validated['product']['note'] ?? '')
            ->where('parent_id', null)
            ->with('toppings')
            ->get();

        $orderDetail = null;
        foreach ($existingOrderDetails as $detail) {
            // Get existing toppings IDs
            $existingToppings = $detail->toppings->pluck('product_id')->toArray();
            sort($existingToppings);

            // Compare toppings
            if ($existingToppings === $toppingsIds) {
                $orderDetail = $detail;
                break;
            }
        }

        if ($orderDetail) {
            // Update existing order detail - increase quantity and total price
            $orderDetail->quantity += $validated['product']['quantity'];
            $orderDetail->total_price += $validated['product']['total_price'];
            $orderDetail->save();

            // Update toppings quantities
            foreach ($orderDetail->toppings as $topping) {
                $topping->quantity += $validated['product']['quantity'];
                $topping->total_price += ($topping->total_price / ($orderDetail->quantity - $validated['product']['quantity'])) * $validated['product']['quantity'];
                $topping->save();
            }

            $message = 'Product quantity updated in cart successfully.';
        } else {
            // Create new order detail
            $orderDetail = $customerOrder->orderDetails()->create([
                'order_detail_number' => 'OD' . time() . rand(100, 999),
                'customer_order_id' => $customerOrder->id,
                'product_id' => $product->id,
                'parent_id' => null,
                'size' => $validated['product']['size'],
                'quantity' => $validated['product']['quantity'],
                'note' => $validated['product']['note'] ?? '',
                'total_price' => $validated['product']['total_price'],
            ]);

            // Add toppings if provided
            if (!empty($toppingsIds)) {
                foreach ($toppingsIds as $toppingId) {
                    $topping = Product::findOrFail($toppingId);

                    $extra_price = $topping->productsToppingThis()
                        ->where('product_id', $product->id)
                        ->first()->pivot->extra_price;

                    $orderDetail->toppings()->create([
                        'order_detail_number' => 'ODTP' . time() . rand(100, 999),
                        'customer_order_id' => $customerOrder->id,
                        'product_id' => $topping->id,
                        'size' => 'S',
                        'quantity' => $validated['product']['quantity'],
                        'total_price' => $extra_price * $validated['product']['quantity'],
                        'note' => '',
                        'parent_id' => $orderDetail->id,
                    ]);
                }
            }

            $message = 'Product added to cart successfully.';
        }

        // Update order total
        $order->order_total += $validated['product']['total_price'];
        $order->save();

        return response()->json([
            'message' => $message,
            'data' => [
                'order_id' => $order->id,
                'order_detail' => [
                    'id' => $orderDetail->id,
                    'order_detail_number' => $orderDetail->order_detail_number,
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                    ],
                    'size' => $orderDetail->size,
                    'quantity' => $orderDetail->quantity,
                    'note' => $orderDetail->note,
                    'total_price' => $orderDetail->total_price,
                    'toppings' => $orderDetail->toppings,
                ],
            ],
        ]);
    }
    /**
     * @OA\Get(
     *     path="/api/cart/getExistedCart",
     *     tags={"Cart"},
     *     summary="Check if cart exists",
     *     description="Get basic information about user's cart if it exists",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart information retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Cart not found or empty")
     * )
     */
    public function getExistedCart(Request $request)
    {
        $currentUser = auth()->user();
        $customer = Customer::where('user_id', $currentUser->id)->first();

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Get the single Draft order (cart) for the customer
        $order = Order::where('host_id', $customer->id)
            ->where('order_status', 'Draft')
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Cart is empty.'], 404);
        }

        $data = [
            'order_id' => $order->id,
            'name' => $order->custom_name ? $order->custom_name : $order->order_number,
            'created_at' => $order->created_at,
            'host_id' => $order->host_id,
            'type' => 'Personal',
        ];

        return response()->json([
            'message' => 'Cart fetched successfully.',
            'data' => $data
        ]);
    }
    public function loadCartDetail(Request $request, $id)
    {
        $currentUser = auth()->user();
        $customer = Customer::where('user_id', $currentUser->id)->first();

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Fetch the order
        $order = Order::findOrFail($id);

        // Ensure the order belongs to the current customer and is in 'Draft' status
        $customerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('id', $order->host_id)
            ->whereHas('order', function ($query) {
                $query->where('order_status', 'Draft');
            })
            ->first();

        if (!$customerOrder) {
            return response()->json(['message' => 'Unauthorized or invalid order.'], 403);
        }

        $orderDetails = $customerOrder->orderDetails()
            ->where('parent_id', null)
            ->with('toppings.product') // Include topping product details
            ->get();

        $return_data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'host_id' => $order->host_id,
            'order_detail' => []
        ];

        foreach ($orderDetails as $orderDetail) {
            $return_data['order_detail'][] = [
                'id' => $orderDetail->id,
                'order_detail_number' => $orderDetail->order_detail_number,
                'product_name' => $orderDetail->product->name,
                'product_price' => $orderDetail->product->price,
                'size' => $orderDetail->size,
                'quantity' => $orderDetail->quantity,
                'note' => $orderDetail->note,
                'total_price' => $orderDetail->total_price,
                'toppings' => $orderDetail->toppings->map(function ($topping) {
                    return [
                        'id' => $topping->id,
                        'name' => $topping->product->name,
                        'price' => $topping->product->price,
                    ];
                }),
            ];
        }

        return response()->json([
            'message' => 'Cart details fetched successfully.',
            'data' => $return_data
        ]);
    }

    public function checkFirebaseUser(Request $request)
    {
        $firebaseUser = $request->attributes->get('firebaseUser');

        if (!$firebaseUser) {
            return null;
        }

        // Tìm user trong database dựa vào Firebase UID
        return User::where('firebase_uid', $firebaseUser['sub'])->first();
    }


    /**
     * @OA\Get(
     *     path="/api/cart/fetchCart",
     *     tags={"Cart"},
     *     summary="Fetch user's shopping cart",
     *     description="Get the single cart (Draft order) with all items for the authenticated customer",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cart fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cart fetched successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order_id", type="string"),
     *                 @OA\Property(property="order_number", type="string"),
     *                 @OA\Property(property="count_product", type="integer"),
     *                 @OA\Property(property="total_price", type="number"),
     *                 @OA\Property(property="order_detail", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Customer or cart not found")
     * )
     */
    public function fetchCart(Request $request)
    {
        $user = $this->checkFirebaseUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized or User not found.'], 401);
        }

        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Get the single Draft order (cart) for the customer
        $order = Order::where('host_id', $customer->id)
            ->where('order_status', 'Draft')
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Cart is empty.',
                'data' => null
            ]);
        }

        // Fetch order details
        $orderCustomer = $order->customers()->where('customer_id', $customer->id)->first();

        if (!$orderCustomer) {
            return response()->json(['message' => 'Cart not found.'], 404);
        }

        // Get order details
        $orderDetails = $orderCustomer->pivot->orderDetails()
            ->where('parent_id', null)
            ->with('toppings.product')
            ->get();

        $total_price = 0;
        $orderDetailData = [];

        foreach ($orderDetails as $orderDetail) {
            $total_price += $orderDetail->total_price;
            $orderDetailData[] = [
                'id' => $orderDetail->id,
                'order_detail_number' => $orderDetail->order_detail_number,
                'product_id' => $orderDetail->product->id,
                'product_name' => $orderDetail->product->name,
                'product_price' => $orderDetail->product->price,
                'size' => $orderDetail->size,
                'quantity' => $orderDetail->quantity,
                'image' => $orderDetail->product->image ? asset('/storage/build/assets/' . $orderDetail->product->image) : null,
                'note' => $orderDetail->note,
                'total_price' => $orderDetail->total_price,
                'count_topping' => $orderDetail->toppings->count(),
                'toppings' => $orderDetail->toppings->map(function ($topping) {
                    return [
                        'id' => $topping->id,
                        'topping_id' => $topping->product->id,
                        'name' => $topping->product->name,
                        'price' => $topping->total_price,
                    ];
                }),
            ];
        }

        $data = [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'date_created' => $order->created_at,
            'host_id' => $order->host_id,
            'count_product' => $orderDetails->count(),
            'total_price' => $total_price,
            'order_detail' => $orderDetailData
        ];

        return response()->json([
            'message' => 'Cart fetched successfully.',
            'data' => $data
        ]);
    }
    /**
     * @OA\Put(
     *     path="/api/cart/updateProductInCart",
     *     tags={"Cart"},
     *     summary="Update product in cart",
     *     description="Update quantity, size, toppings, and note for a product in cart",
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id", "order_detail_id", "size", "quantity", "total_price"},
     *             @OA\Property(property="order_id", type="string", format="uuid"),
     *             @OA\Property(property="order_detail_id", type="string", format="uuid"),
     *             @OA\Property(property="size", type="string", example="M"),
     *             @OA\Property(property="quantity", type="integer", minimum=1, example=2),
     *             @OA\Property(property="toppings_id", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="note", type="string", example="Ít đường"),
     *             @OA\Property(property="total_price", type="number", example=75000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Order or Order Detail not found")
     * )
     */
    public function updateProductInCart(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_detail_id' => 'required|exists:order_details,id',
            'size' => 'required',
            'toppings_id' => 'nullable|array', // Ensure toppings_id is an array
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string',
            'total_price' => 'required|numeric|min:0',
        ]);

        $user = $this->checkFirebaseUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized or User not found.'], 401);
        }

        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Fetch the order
        $order = Order::findOrFail($validated['order_id']);

        // Ensure the order belongs to the current customer
        $customerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->first();

        if (!$customerOrder) {
            return response()->json(['message' => 'Unauthorized or invalid order.'], 403);
        }

        // Fetch the order detail to update
        $orderDetail = OrderDetail::findOrFail($validated['order_detail_id']);

        // Ensure the order detail belongs to the specified order
        if ($orderDetail->customer_order_id !== $customerOrder->id) {
            return response()->json(['message' => 'Unauthorized or invalid order detail.'], 403);
        }

        // Calculate the difference in total price before and after update
        $previousTotalPrice = $orderDetail->total_price;
        $newTotalPrice = $validated['total_price'];
        $priceDifference = $newTotalPrice - $previousTotalPrice;

        // Update the order total
        $order->order_total += $priceDifference;
        $order->save();

        // Update the order detail
        $orderDetail->update([
            'size' => $validated['size'],
            'quantity' => $validated['quantity'],
            'note' => $validated['note'] ?? '',
            'total_price' => $validated['total_price'],
        ]);

        // Remove all existing toppings
        $orderDetail->toppings()->delete();


        // Add new toppings if provided
        if (isset($validated['toppings_id'])) {
            foreach ($validated['toppings_id'] as $toppingId) {
                $topping = Product::findOrFail($toppingId);

                $extra_price = $topping->productsToppingThis()
                    ->where('product_id', $orderDetail->product_id)
                    ->first()->pivot->extra_price;

                $orderDetail->toppings()->create([
                    'order_detail_number' => 'ODTP' . time(),
                    'customer_order_id' => $customerOrder->id,
                    'product_id' => $topping->id,
                    'size' => 'S',
                    'quantity' => $validated['quantity'],
                    'total_price' => $extra_price * $validated['quantity'],
                    'note' => '',
                    'parent_id' => $orderDetail->id,
                ]);
            }
        }

        // Prepare the response data
        $updatedProduct = [
            'order_id' => $order->id,
            'order_detail' => [
                'id' => $orderDetail->id,
                'order_detail_number' => $orderDetail->order_detail_number,
                'product' => [
                    'id' => $orderDetail->product->id,
                    'name' => $orderDetail->product->name,
                    'price' => $orderDetail->product->price,
                ],
                'size' => $orderDetail->size,
                'quantity' => $orderDetail->quantity,
                'note' => $orderDetail->note,
                'total_price' => $orderDetail->total_price,
                'toppings' => $orderDetail->toppings->map(function ($topping) use ($orderDetail) {
                    return [
                        'id' => $topping->id,
                        'name' => $topping->product->name,
                        'price' => $topping->product->productsToppingThis()
                            ->where('product_id', $orderDetail->product_id)
                            ->first()->pivot->extra_price,
                    ];
                }),
            ],
        ];

        return response()->json([
            'message' => 'Product updated in cart successfully.',
            'data' => $updatedProduct,
        ]);
    }
    /**
     * @OA\Post(
     *     path="/api/cart/removeProductFromCart",
     *     tags={"Cart"},
     *     summary="Remove product from cart",
     *     description="Remove a product (order detail) from shopping cart",
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cart_id", "order_detail_id"},
     *             @OA\Property(property="cart_id", type="string", format="uuid", description="Order ID (cart ID)"),
     *             @OA\Property(property="order_detail_id", type="string", format="uuid", description="Order detail ID to remove")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Product removed from cart successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Cart or Product not found")
     * )
     */
    public function removeProductFromCart(Request $request)
    {
        $validated = $request->validate([
            'cart_id' => 'required | exists:orders,id', // Ensure the cart_id (order_id) exists
            'order_detail_id' => 'required | exists:order_details,id',
        ]);

        $user = $this->checkFirebaseUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized or User not found.'], 401);
        }

        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Fetch the order (cart)
        $order = Order::findOrFail($validated['cart_id']);

        // Ensure the order belongs to the current customer
        $customerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->first();

        if (!$customerOrder) {
            return response()->json(['message' => 'Unauthorized or invalid order.'], 403);
        }

        // Fetch the order detail to delete
        $orderDetail = OrderDetail::findOrFail($validated['order_detail_id']);

        // Ensure the order detail belongs to the specified order
        if ($orderDetail->customer_order_id !== $customerOrder->id) {
            return response()->json(['message' => 'Unauthorized or invalid order detail.'], 403);
        }

        // Delete related toppings (if any)
        $orderDetail->toppings()->delete();

        // Update the order total
        $order->order_total -= $orderDetail->total_price;
        $order->save();

        // Delete the order detail
        $orderDetail->delete();

        return response()->json(['message' => 'Product removed from cart successfully.']);
    }
    /**
     * @OA\Post(
     *     path="/api/cart/removeToppingFromCart",
     *     tags={"Cart"},
     *     summary="Remove topping from product in cart",
     *     description="Remove a specific topping from a product in shopping cart",
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cart_id", "order_detail_id", "topping_id"},
     *             @OA\Property(property="cart_id", type="string", format="uuid", description="Order ID (cart ID)"),
     *             @OA\Property(property="order_detail_id", type="string", format="uuid", description="Order detail ID (parent product)"),
     *             @OA\Property(property="topping_id", type="string", format="uuid", description="Topping ID to remove")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Topping removed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Topping removed from cart successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Cart, Product, or Topping not found")
     * )
     */
    public function removeToppingFromCart(Request $request)
    {
        $validated = $request->validate([
            'cart_id' => 'required | exists:orders,id', // Ensure the cart_id (order_id) exists
            'order_detail_id' => 'required | exists:order_details,id',
            'topping_id' => 'required | exists:order_details,id',
        ]);

        $user = $this->checkFirebaseUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized or User not found.'], 401);
        }

        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Fetch the order (cart)
        $order = Order::findOrFail($validated['cart_id']);

        // Ensure the order belongs to the current customer
        $customerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->first();

        if (!$customerOrder) {
            return response()->json(['message' => 'Unauthorized or invalid order.'], 403);
        }

        // Fetch the order detail
        $orderDetail = OrderDetail::findOrFail($validated['order_detail_id']);

        // Ensure the order detail belongs to the specified order
        if ($orderDetail->customer_order_id !== $customerOrder->id) {
            return response()->json(['message' => 'Unauthorized or invalid order detail.'], 403);
        }

        // Fetch the topping to delete
        $topping = OrderDetail::findOrFail($validated['topping_id']);

        // Ensure the topping belongs to the specified order detail
        if ($topping->parent_id !== $orderDetail->id) {
            return response()->json(['message' => 'Unauthorized or invalid topping.'], 403);
        }

        // Update the order total
        $order->order_total -= $topping->total_price;
        $order->save();

        // update the order detail total price
        $orderDetail->total_price -= $topping->total_price;
        $orderDetail->save();

        // Delete the topping
        $topping->delete();

        return response()->json(['message' => 'Topping removed from cart successfully.']);
    }
    /**
     * @OA\Post(
     *     path="/api/customer/cancelOrder",
     *     summary="Cancel an order",
     *     description="Customer cancels their order. Requires Firebase authentication.",
     *     tags={"Orders"},
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id"},
     *             @OA\Property(property="order_id", type="string", format="uuid", example="123e4567-e89b-12d3-a456-426614174000")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order cancelled successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order cancelled successfully.")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Forbidden - Order does not belong to customer"),
     *     @OA\Response(response=404, description="Customer or Order not found")
     * )
     */
    public function cancelOrder(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required | exists:orders,id',
        ]);

        $user = $this->checkFirebaseUser($request);

        if (!$user) {
            return response()->json(['message' => 'Unauthorized or User not found.'], 401);
        }

        $customer = $user->customer;

        if (!$customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        // Fetch the order
        $order = Order::findOrFail($validated['order_id']);

        // Ensure the order belongs to the current customer
        $customerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->first();

        if (!$customerOrder) {
            return response()->json(['message' => 'Unauthorized or invalid order.'], 403);
        }

        // Update the order status to 'Cancelled'
        $order->update(['order_status' => 'Cancelled']);

        return response()->json(['message' => 'Order cancelled successfully.']);
    }
    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_number' => 'required | string | unique:orders,order_number | max:255',
            'receiver_name' => 'required | string | max:255',
            'receiver_address' => 'required | string | max:255',
            'payment_method' => 'required | in:Banking,Cash',
            'payment_status' => 'required | in:pending,paid',
            'order_status' => 'required | in:Wait for Approval, In Progress, Delivering, Delivered, Completed, Cancelled',
            'date_created' => 'required | date',
            'order_total' => 'nullable | numeric | min:0',
            'rate' => 'nullable | integer | min:0 | max:5',
            'customer_feedback' => 'nullable | string | max:255',
            'host_id' => 'required | uuid | exists:customers,id',
            'manually_created_by' => 'nullable | uuid | exists:users,id',
            'source' => 'required | in:Offline,Online',
            'team_id' => 'required | uuid | exists:teams,id',
            'created_by' => 'required | uuid | exists:users,id',
        ]);

        $order = Order::create($validated);

        return response()->json(['message' => 'Order created successfully . ', 'data' => $order], 201);
    }

    /**
     * Display the specified order.
     */
    public function show(string $id)
    {
        $order = Order::with(['customer', 'createdBy', 'manuallyCreatedBy', 'team'])->findOrFail($id);

        return response()->json($order);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/orders/update/{id}",
     *     tags={"Orders"},
     *     summary="Update order (Admin)",
     *     description="Update order details including status, payment, and customer information",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="order_number", type="string"),
     *             @OA\Property(property="receiver_name", type="string"),
     *             @OA\Property(property="receiver_address", type="string"),
     *             @OA\Property(property="payment_method", type="string", enum={"Banking", "Cash"}),
     *             @OA\Property(property="payment_status", type="string", enum={"pending", "paid"}),
     *             @OA\Property(property="order_status", type="string"),
     *             @OA\Property(property="order_total", type="number"),
     *             @OA\Property(property="rate", type="integer", minimum=0, maximum=5),
     *             @OA\Property(property="customer_feedback", type="string"),
     *             @OA\Property(property="host_id", type="string", format="uuid"),
     *             @OA\Property(property="source", type="string", enum={"Offline", "Online"}),
     *             @OA\Property(property="team_id", type="string", format="uuid")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order updated successfully . "),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Order not found"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     * Update the specified order in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'order_number' => 'nullable | string | unique:orders,order_number,' . $id . ' | max:255',
            'receiver_name' => 'nullable | string | max:255',
            'receiver_address' => 'nullable | string | max:255',
            'payment_method' => 'nullable | in:Banking,Cash',
            'payment_status' => 'nullable | in:pending,paid',
            'order_status' => 'nullable',
            'date_created' => 'nullable | date',
            'order_total' => 'nullable | numeric | min:0',
            'rate' => 'nullable | integer | min:0 | max:5',
            'customer_feedback' => 'nullable | string | max:255',
            'host_id' => 'nullable | uuid | exists:customers,id',
            'manually_created_by' => 'nullable | uuid | exists:users,id',
            'source' => 'nullable | in:Offline,Online',
            'team_id' => 'nullable | uuid | exists:teams,id',
            'created_by' => 'nullable | uuid | exists:users,id',
            'toppings' => 'nullable | array',
        ]);

        $order = Order::findOrFail($id);
        $order->update($validated);



        return response()->json(['message' => 'Order updated successfully . ', 'data' => $order]);
    }

    /**
     * @OA\Post(
     *     path="/api/orders/status/{id}",
     *     summary="Update order status (Admin/Staff)",
     *     description="Update the status of an order. Requires Firebase authentication.",
     *     tags={"Orders"},
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_status"},
     *             @OA\Property(property="order_status", type="string", enum={"Wait For Approval", "In Progress", "Delivering", "Delivered", "Completed", "Cancelled"}, example="In Progress")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order updated successfully . "),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'order_status' => 'required',
            'note' => 'nullable|string',
        ]);

        $order = Order::findOrFail($id);

        // Save old status for history tracking
        $oldStatus = $order->order_status;
        $newStatus = $validated['order_status'];

        // Only create history if status actually changed
        if ($oldStatus !== $newStatus) {
            // Create status history record
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $newStatus,
                'changed_by' => auth()->id(),
                'note' => $validated['note'] ?? null,
            ]);
        }

        $order->update($validated);

        return response()->json(['message' => 'Order updated successfully . ', 'data' => $order]);
    }

    /**
     * Remove the specified order from storage (soft delete).
     */
    public function destroy(string $id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();

            return response()->json(['message' => 'Order deleted successfully . ']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Order not found . '], 404);
        }
    }

    /**
     * Restore a soft-deleted order.
     */
    public function restore(string $id)
    {
        $order = Order::withTrashed()->findOrFail($id);
        $order->restore();

        return response()->json(['message' => 'Order restored successfully . ']);
    }

    /**
     * Permanently delete a soft-deleted order.
     */
    public function forceDelete(string $id)
    {
        $order = Order::withTrashed()->findOrFail($id);
        $order->forceDelete();

        return response()->json(['message' => 'Order permanently deleted . ']);
    }

    /**
     * Create a new order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminCreateOrder(Request $request)
    {
        // Validate the request data
        $validated = $request->validate([
            'payment_method' => 'required|in:Cash,Banking',
            'customer_id' => 'required|exists:customers,id'
        ]);

        // Fetch the customer
        $customer = Customer::findOrFail($validated['customer_id']);

        // Get the current user
        $currentUser = auth()->user();

        // Create the order
        $order = Order::create([
            'order_number' => 'ORD' . time(),
            'receiver_name' => $customer->full_name,
            'receiver_address' => $customer->province . ', ' . $customer->district . ', ' . $customer->ward . ', ' . $customer->street,
            'payment_method' => $validated['payment_method'],
            'order_status' => 'In Progress',
            'type' => 'Personal',
            'custom_name' => '',
            'source' => 'Offline',
            'order_total' => 0,
            'host_id' => $customer->id,
            //            'team_id' => $currentUser->team_id,
            'created_by' => $currentUser->id,
            'customer_feedback' => '',
        ]);

        // Attach the customer to the order using the pivot table
        $order->customers()->attach($customer->id);

        // Retrieve the customer_order_id from the pivot
        $customerOrder = CustomerOrder::where('customer_id', $customer->id)
            ->where('order_id', $order->id)
            ->first();

        $orderTotal = 0;

        // Add order details
        foreach ($request->get('order_details') as $detail) {
            // Add order detail using the CustomerOrder pivot
            $orderDetail = $customerOrder->orderDetails()->create([
                'order_detail_number' => 'OD' . time(),
                'customer_order_id' => $customerOrder->id,
                'product_id' => $detail['product_id'],
                'parent_id' => null,
                'size' => $detail['size'],
                'quantity' => $detail['quantity'],
                'note' => $detail['note'] ?? '',
                'total_price' => $detail['total_price'],
            ]);

            // Add toppings if provided
            if (isset($detail['toppings'])) {
                foreach ($detail['toppings'] as $toppingObject) {
                    $topping = Product::findOrFail($toppingObject['product_id']);
                    $orderDetail->toppings()->create([
                        'order_detail_number' => 'ODTP' . time(),
                        'customer_order_id' => $customerOrder->id,
                        'product_id' => $topping->id,
                        'size' => 'S',
                        'quantity' => 1,
                        'note' => '',
                        'parent_id' => $orderDetail->id,
                    ]);
                }
            }

            $orderTotal += $detail['total_price'];
        }

        // Update the order total
        if ($request->get('voucher_id')) {
            $order->vouchers()->attach($request->get('voucher_id'));
        }
        $order->order_total = $orderTotal;
        $order->save();
        if ($validated['payment_method'] == 'Banking') {
            $payos = new PayOSController();
            $result = $payos->genPayment($order->id);
            if ($result['success']) $order['payment'] = $result['checkoutUrl'];
        }
        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $order,
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/orders/detail/{id}",
     *     tags={"Orders"},
     *     summary="Get order detail for admin",
     *     description="Get detailed order information including products, customer info, vouchers, creator, and status history",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Order detail retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Order detail fetched successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="order_id", type="string"),
     *                 @OA\Property(property="order_number", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="order_total", type="number"),
     *                 @OA\Property(property="customer_info", type="object"),
     *                 @OA\Property(property="order_detail", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="vouchers", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="creator", type="object"),
     *                 @OA\Property(property="status_history", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    /**
     * Helper method to format order detail
     */
    private function formatOrderDetail($order)
    {
        try {
            // Get the host customer's order details
            $customer_id = $order->host_id;
            $orderCustomer = $order->customers()->where('customer_id', $customer_id)->first();

            if (!$orderCustomer) {
                return null;
            }

            // Get order details
            $orderDetails = $orderCustomer->pivot->orderDetails()
                ->where('parent_id', null)
                ->with('toppings.product')
                ->get();

            $team = $order->team;
            $customer = $order->host;

            $data = [
                'type' => $order->type,
                'order_number' => !empty($order->custom_name) ? $order->custom_name : $order->order_number,
                'order_id' => $order->id,
                'date_created' => $order->created_at,
                'host_id' => $order->host_id,
                'status' => $order->order_status,
                'order_total' => $order->order_total,
                'count_product' => $orderDetails->sum('quantity') ?? 0,
                'order_detail' => [],

                // Customer information
                'customer_info' => [
                    'customer_id' => $customer->id,
                    'customer_name' => $order->receiver_name,
                    'customer_phone' => $customer->phone_number,
                    'customer_email' => $customer->email,
                    'customer_level' => $customer->rank ?? 'N/A',
                ],

                // Shipping information
                'shipping_info' => [
                    'from_name' => $team->name ?? 'N/A',
                    'from_address' => $team->address ?? 'N/A',
                    'to_name' => $order->receiver_name,
                    'to_address' => $order->receiver_address,
                    'receiver_phone' => $order->receiver_phone,
                    'province' => $order->province,
                    'district' => $order->district,
                    'ward' => $order->ward,
                    'street' => $order->street,
                    'shipping_fee' => $order->shipping_fee,
                ],

                // Payment information
                'payment_info' => [
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status ?? 'pending',
                    'payment_link' => $order->payment_link,
                ],

                'note' => $order->note,

                // Feedback information
                'feedback' => [
                    'rating' => $order->rate ?? 0,
                    'content' => $order->customer_feedback,
                    'feedback_time' => $order->updated_at,
                ],

                // Vouchers
                'vouchers' => $order->vouchers->map(function ($voucher) {
                    return [
                        'id' => $voucher->id,
                        'voucher_code' => $voucher->vourcher_code,
                        'discount_amount' => $voucher->discount_amount,
                        'discount_percent' => $voucher->discount_percent,
                        'discount_type' => $voucher->discount_type,
                        'apply_type' => $voucher->apply_type,
                    ];
                }),

                // Creator information
                'creator_info' => $order->creator ? [
                    'creator_id' => $order->creator->id,
                    'creator_name' => $order->creator->name,
                    'creator_email' => $order->creator->email,
                    'created_at' => $order->created_at,
                ] : null,

                // Status history
                'status_history' => $order->statusHistories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'status' => $history->status,
                        'changed_at' => $history->created_at,
                        'changed_by' => $history->changedBy ? [
                            'id' => $history->changedBy->id,
                            'name' => $history->changedBy->name,
                            'email' => $history->changedBy->email,
                        ] : null,
                        'note' => $history->note,
                    ];
                }),
            ];

            // Build order details
            $total_price = 0;
            foreach ($orderDetails as $orderDetail) {
                $total_price += $orderDetail->total_price;
                $data['order_detail'][] = [
                    'order_detail_number' => $orderDetail->order_detail_number,
                    'product_id' => $orderDetail->product->id,
                    'product_name' => $orderDetail->product->name,
                    'product_price' => $orderDetail->product->price,
                    'size' => $orderDetail->size,
                    'quantity' => $orderDetail->quantity,
                    'image' => $orderDetail->product->image ? asset('storage/' . $orderDetail->product->image) : asset('resources/assets/images/empty-image.jpg'),
                    'note' => $orderDetail->note,
                    'total_price' => $orderDetail->total_price,
                    'count_topping' => $orderDetail->toppings->count(),
                    'toppings' => $orderDetail->toppings->map(function ($topping) {
                        return [
                            'topping_id' => $topping->id,
                            'name' => $topping->product->name,
                            'price' => $topping->product->price,
                            'quantity' => $topping->quantity,
                            'total_price' => $topping->total_price,
                        ];
                    }),
                ];
            }
            $data['total_price'] = $total_price;
            $data['discount'] = $this->calculateDiscountForAdmin($order, $total_price);

            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function adminGetOrderDetail(Request $request, $orderId)
    {
        try {
            $order = Order::with(['creator', 'host', 'team', 'vouchers', 'statusHistories.changedBy'])->findOrFail($orderId);

            $data = $this->formatOrderDetail($order);

            if (!$data) {
                return response()->json(['message' => 'Order customer relationship not found.'], 404);
            }

            return response()->json([
                'message' => 'Order detail fetched successfully.',
                'data' => $data
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Order not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/orders/search",
     *     tags={"Admin Orders"},
     *     summary="Search orders by ID, customer name, or date range",
     *     description="Search orders with multiple filters: order ID, customer name, or creation date range. Returns detailed order information similar to order detail endpoint.",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="query",
     *         required=false,
     *         description="Order ID (partial match supported)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="customer_name",
     *         in="query",
     *         required=false,
     *         description="Customer name (partial match supported)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         description="Start date for order creation (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         description="End date for order creation (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Orders found successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Orders found successfully."),
     *             @OA\Property(property="total", type="integer"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid request parameters"),
     *     @OA\Response(response=404, description="No orders found")
     * )
     */
    public function searchAdminOrders(Request $request)
    {
        try {
            $validated = $request->validate([
                'order_id' => 'nullable|string',
                'customer_name' => 'nullable|string',
                'date_from' => 'nullable|date_format:Y-m-d',
                'date_to' => 'nullable|date_format:Y-m-d',
            ]);

            $query = Order::with(['creator', 'host', 'team', 'vouchers', 'statusHistories.changedBy', 'customers']);

            // Filter by order ID (partial match)
            if (!empty($validated['order_id'])) {
                $query->where('id', 'like', '%' . $validated['order_id'] . '%')
                    ->orWhere('order_number', 'like', '%' . $validated['order_id'] . '%');
            }

            // Filter by customer name (partial match)
            if (!empty($validated['customer_name'])) {
                $query->where('receiver_name', 'like', '%' . $validated['customer_name'] . '%')
                    ->orWhereHas('host', function ($q) {
                        $q->where('full_name', 'like', '%' . request('customer_name') . '%');
                    });
            }

            // Filter by date range
            if (!empty($validated['date_from'])) {
                $query->whereDate('created_at', '>=', $validated['date_from']);
            }

            if (!empty($validated['date_to'])) {
                $query->whereDate('created_at', '<=', $validated['date_to']);
            }

            // Execute query
            $orders = $query->orderBy('created_at', 'desc')->get();

            if ($orders->isEmpty()) {
                return response()->json([
                    'message' => 'No orders found matching the search criteria.',
                    'total' => 0,
                    'data' => []
                ], 404);
            }

            // Format each order using the helper method
            $formattedOrders = $orders->map(function ($order) {
                return $this->formatOrderDetail($order);
            })->filter(function ($item) {
                return $item !== null;
            })->values();

            return response()->json([
                'message' => 'Orders found successfully.',
                'total' => $formattedOrders->count(),
                'data' => $formattedOrders
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Invalid request parameters.',
                'errors' => $e->errors()
            ], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/admin/orders/customerInfo/{id}",
     *     tags={"Admin Orders"},
     *     summary="Get customer info by order ID",
     *     description="Get full customer information including all their orders and feedback for a specific order",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer information retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="string"),
     *                 @OA\Property(property="full_name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="phone_number", type="string"),
     *                 @OA\Property(property="date_registered", type="string"),
     *                 @OA\Property(property="date_of_birth", type="string"),
     *                 @OA\Property(property="gender", type="string"),
     *                 @OA\Property(property="province", type="string"),
     *                 @OA\Property(property="district", type="string"),
     *                 @OA\Property(property="ward", type="string"),
     *                 @OA\Property(property="street", type="string"),
     *                 @OA\Property(property="customer_number", type="string"),
     *                 @OA\Property(property="orders", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="current_order_feedback", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Order not found")
     * )
     */
    public function getCustomerInfoByOrder($orderId)
    {
        try {
            // Find order by ID
            $order = Order::findOrFail($orderId);

            // Get the first customer associated with this order
            $customer = $order->customers()->first();

            if (!$customer) {
                return response()->json(['message' => 'Customer not found for this order.'], 404);
            }

            // Get all orders for this customer with their feedback
            $customerOrders = $customer->orders()
                ->with('creator')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($ord) {
                    return [
                        'id' => $ord->id,
                        'order_number' => $ord->order_number,
                        'order_status' => $ord->order_status,
                        'order_total' => $ord->order_total,
                        'payment_method' => $ord->payment_method,
                        'payment_status' => $ord->payment_status,
                        'created_at' => $ord->created_at,
                        'feedback' => $ord->customer_feedback,
                        'rating' => $ord->rate
                    ];
                });

            // Build response with full customer info
            $data = [
                'id' => $customer->id,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'phone_number' => $customer->phone_number,
                'date_registered' => $customer->date_registered,
                'date_of_birth' => $customer->date_of_birth,
                'gender' => $customer->gender,
                'province' => $customer->province,
                'district' => $customer->district,
                'ward' => $customer->ward,
                'street' => $customer->street,
                'customer_number' => $customer->customer_number,
                'orders' => $customerOrders,
                'current_order_feedback' => $order->customer_feedback
            ];

            return response()->json(['data' => $data]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Order not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
