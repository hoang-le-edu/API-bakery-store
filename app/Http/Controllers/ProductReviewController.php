<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class ProductReviewController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/products/{product_id}/reviews",
     *     tags={"Product Reviews"},
     *     summary="Get all reviews for a product",
     *     description="Get paginated reviews for a specific product with user info and media",
     *     @OA\Parameter(
     *         name="product_id",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="rating",
     *         in="query",
     *         required=false,
     *         description="Filter by rating (1-5)",
     *         @OA\Schema(type="integer", minimum=1, maximum=5)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         required=false,
     *         description="Sort order",
     *         @OA\Schema(type="string", enum={"newest", "oldest", "highest_rating", "lowest_rating"}, default="newest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reviews retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="reviews", type="object",
     *                     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 ),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="average_rating", type="number"),
     *                     @OA\Property(property="total_reviews", type="integer"),
     *                     @OA\Property(property="rating_distribution", type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getProductReviews(Request $request, $productId)
    {
        try {
            $product = Product::findOrFail($productId);

            $query = ProductReview::with(['user', 'order'])
                ->where('product_id', $productId)
                ->approved();

            // Filter by rating if specified
            if ($request->has('rating') && $request->rating >= 1 && $request->rating <= 5) {
                $query->where('rating', $request->rating);
            }

            // Sort
            $sortOrder = $request->get('sort', 'newest');
            switch ($sortOrder) {
                case 'oldest':
                    $query->orderBy('reviewed_at', 'asc');
                    break;
                case 'highest_rating':
                    $query->orderBy('rating', 'desc')->orderBy('reviewed_at', 'desc');
                    break;
                case 'lowest_rating':
                    $query->orderBy('rating', 'asc')->orderBy('reviewed_at', 'desc');
                    break;
                default: // newest
                    $query->orderBy('reviewed_at', 'desc');
            }

            $reviews = $query->paginate(10);

            // Format reviews
            $reviews->getCollection()->transform(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'review_text' => $review->review_text,
                    'media_files' => $review->media_files ? collect($review->media_files)->map(function ($file) {
                        return [
                            'url' => Storage::url($file['path']),
                            'type' => $file['type'] ?? 'image',
                            'name' => $file['name'] ?? basename($file['path'])
                        ];
                    }) : [],
                    'reviewed_at' => $review->reviewed_at->format('Y-m-d H:i:s'),
                    'is_verified_purchase' => $review->is_verified_purchase,
                    'user' => [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                        'avatar' => $review->user->avatar ?? null
                    ],
                    'helpful_count' => 0, // TODO: Add helpful votes feature later
                ];
            });

            // Get review summary - Use cached values for better performance
            $summary = [
                'average_rating' => (float) $product->avg_rating,
                'total_reviews' => $product->review_count,
                'rating_distribution' => $product->rating_distribution
            ];

            return $this->sendResponse([
                'reviews' => $reviews,
                'summary' => $summary
            ], 'Product reviews retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting product reviews: ' . $e->getMessage());
            return $this->sendError('Failed to retrieve reviews', [], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/products/{product_id}/reviews",
     *     tags={"Product Reviews"},
     *     summary="Create a new review",
     *     description="Create a review for a product (only for completed orders)",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="product_id",
     *         in="path",
     *         required=true,
     *         description="Product ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="order_id", type="string", format="uuid", description="Order ID that contains this product"),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, description="Rating from 1-5 stars"),
     *             @OA\Property(property="review_text", type="string", description="Review text"),
     *             @OA\Property(property="media_files", type="array", @OA\Items(type="string"), description="Array of media file paths")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Review created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Review created successfully")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error or business logic error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Product or Order not found")
     * )
     */
    public function createReview(Request $request, $productId)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|uuid|exists:orders,id',
                'rating' => 'required|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:1000',
                'media_files' => 'nullable|array|max:5',
                'media_files.*' => 'string' // File paths from previous upload
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 400);
            }

            $product = Product::findOrFail($productId);
            $order = Order::findOrFail($request->order_id);

            // Business logic validations
            $validations = $this->validateReviewEligibility($user, $product, $order);
            if ($validations !== true) {
                return $this->sendError($validations, [], 400);
            }

            // Check if user already reviewed this product for this order
            $existingReview = ProductReview::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->where('order_id', $request->order_id)
                ->first();

            if ($existingReview) {
                return $this->sendError('You have already reviewed this product for this order', [], 400);
            }

            // Process media files
            $mediaFiles = [];
            if ($request->has('media_files') && is_array($request->media_files)) {
                foreach ($request->media_files as $filePath) {
                    if (Storage::exists($filePath)) {
                        $mediaFiles[] = [
                            'path' => $filePath,
                            'type' => $this->getFileType($filePath),
                            'name' => basename($filePath),
                            'uploaded_at' => now()->toISOString()
                        ];
                    }
                }
            }

            // Create review
            $review = ProductReview::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'order_id' => $request->order_id,
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'media_files' => empty($mediaFiles) ? null : $mediaFiles,
                'is_verified_purchase' => true,
                'is_approved' => true, // Auto approve for now
                'reviewed_at' => now(),
            ]);

            // Update product's cached rating and review count
            $summary = $product->updateRatingCache();

            // Load relationships for response
            $review->load(['user', 'product', 'order']);

            return $this->sendResponse([
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'review_text' => $review->review_text,
                    'media_files' => $review->media_files ? collect($review->media_files)->map(function ($file) {
                        return [
                            'url' => Storage::url($file['path']),
                            'type' => $file['type'],
                            'name' => $file['name']
                        ];
                    }) : [],
                    'reviewed_at' => $review->reviewed_at->format('Y-m-d H:i:s'),
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name
                    ],
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name
                    ]
                ],
                'summary' => $summary
            ], 'Review created successfully', 201);
        } catch (\Exception $e) {
            Log::error('Error creating review: ' . $e->getMessage());
            return $this->sendError('Failed to create review', [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/reviews/my-reviews",
     *     tags={"Product Reviews"},
     *     summary="Get current user's reviews",
     *     description="Get all reviews created by the authenticated user",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="User reviews retrieved successfully"
     *     )
     * )
     */
    public function getMyReviews(Request $request)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $reviews = ProductReview::with(['product', 'order'])
                ->where('user_id', $user->id)
                ->orderBy('reviewed_at', 'desc')
                ->paginate(10);

            $reviews->getCollection()->transform(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'review_text' => $review->review_text,
                    'media_files' => $review->media_files ? collect($review->media_files)->map(function ($file) {
                        return [
                            'url' => Storage::url($file['path']),
                            'type' => $file['type'],
                            'name' => $file['name']
                        ];
                    }) : [],
                    'reviewed_at' => $review->reviewed_at->format('Y-m-d H:i:s'),
                    'is_approved' => $review->is_approved,
                    'product' => [
                        'id' => $review->product->id,
                        'name' => $review->product->name,
                        'image' => $review->product->image
                    ],
                    'order' => [
                        'id' => $review->order->id,
                        'order_number' => $review->order->order_number
                    ]
                ];
            });

            return $this->sendResponse($reviews, 'User reviews retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting user reviews: ' . $e->getMessage());
            return $this->sendError('Failed to retrieve reviews', [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{order_id}/reviewable-products",
     *     tags={"Product Reviews"},
     *     summary="Get products that can be reviewed",
     *     description="Get list of products from a completed order that can be reviewed",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reviewable products retrieved successfully"
     *     )
     * )
     */
    public function getReviewableProducts(Request $request, $orderId)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $order = Order::findOrFail($orderId);

            // Check if user owns this order
            if ($order->host_id !== $user->customer->id ?? null) {
                return $this->sendError('You can only review products from your own orders', [], 403);
            }

            // Check if order is completed
            if ($order->order_status !== 'Completed') {
                return $this->sendError('You can only review products from completed orders', [], 400);
            }

            // Get products from this order
            $orderDetails = OrderDetail::with(['product'])
                ->whereHas('customerOrder', function ($query) use ($orderId) {
                    $query->where('order_id', $orderId);
                })
                ->where('parent_id', null) // Only main products, not toppings
                ->get();

            $reviewableProducts = [];
            foreach ($orderDetails as $detail) {
                $product = $detail->product;

                // Check if already reviewed
                $existingReview = ProductReview::where('user_id', $user->id)
                    ->where('product_id', $product->id)
                    ->where('order_id', $orderId)
                    ->first();

                $reviewableProducts[] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'image' => $product->image,
                    'price' => $product->price,
                    'quantity' => $detail->quantity,
                    'size' => $detail->size,
                    'already_reviewed' => !is_null($existingReview),
                    'review' => $existingReview ? [
                        'id' => $existingReview->id,
                        'rating' => $existingReview->rating,
                        'review_text' => $existingReview->review_text,
                        'reviewed_at' => $existingReview->reviewed_at->format('Y-m-d H:i:s')
                    ] : null
                ];
            }

            return $this->sendResponse([
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_status' => $order->order_status,
                    'created_at' => $order->created_at->format('Y-m-d H:i:s')
                ],
                'products' => $reviewableProducts
            ], 'Reviewable products retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting reviewable products: ' . $e->getMessage());
            return $this->sendError('Failed to retrieve reviewable products', [], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/reviews/upload-media",
     *     tags={"Product Reviews"},
     *     summary="Upload media files for review",
     *     description="Upload images or videos for product review",
     *     security={{"firebaseAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="media", type="string", format="binary", description="Image or video file")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Media uploaded successfully"
     *     )
     * )
     */
    public function uploadMedia(Request $request)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $validator = Validator::make($request->all(), [
                'media' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,mp4,mov,avi', // 10MB max
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error', $validator->errors(), 400);
            }

            $file = $request->file('media');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('reviews/media', $fileName, 'public');

            return $this->sendResponse([
                'file_path' => $filePath,
                'file_url' => Storage::url($filePath),
                'file_type' => $this->getFileType($filePath),
                'file_name' => $fileName,
                'file_size' => $file->getSize()
            ], 'Media uploaded successfully');
        } catch (\Exception $e) {
            Log::error('Error uploading review media: ' . $e->getMessage());
            return $this->sendError('Failed to upload media', [], 500);
        }
    }

    // Helper methods
    private function validateReviewEligibility($user, $product, $order)
    {
        // Check if order belongs to user
        if ($order->host_id !== $user->customer->id ?? null) {
            return 'You can only review products from your own orders';
        }

        // Check if order is completed
        if ($order->order_status !== 'Completed') {
            return 'You can only review products from completed orders';
        }

        // Check if product was in this order
        $orderContainsProduct = OrderDetail::whereHas('customerOrder', function ($query) use ($order) {
            $query->where('order_id', $order->id);
        })->where('product_id', $product->id)->exists();

        if (!$orderContainsProduct) {
            return 'This product was not in the specified order';
        }

        return true;
    }

    private function getFileType($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $videoExtensions = ['mp4', 'mov', 'avi', 'mkv', 'webm'];

        if (in_array(strtolower($extension), $imageExtensions)) {
            return 'image';
        } elseif (in_array(strtolower($extension), $videoExtensions)) {
            return 'video';
        }

        return 'unknown';
    }

    private function formatMediaFiles($mediaFiles)
    {
        if (!$mediaFiles || !is_array($mediaFiles)) {
            return [];
        }

        return array_map(function ($file) {
            return [
                'url' => isset($file['url']) ? $file['url'] : (isset($file['path']) ? asset('storage/' . $file['path']) : null),
                'type' => isset($file['type']) ? $file['type'] : $this->getFileType($file['path'] ?? ''),
                'name' => isset($file['name']) ? $file['name'] : (isset($file['path']) ? basename($file['path']) : 'unknown')
            ];
        }, $mediaFiles);
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{order_id}/my-review",
     *     tags={"Product Reviews"},
     *     summary="Get my review for a specific order",
     *     description="Get user's review details for a specific order",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User review retrieved successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Review not found"
     *     )
     * )
     */
    public function getMyReviewForOrder(Request $request, $orderId)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $order = Order::findOrFail($orderId);

            // Check if user owns this order
            if ($order->host_id !== $user->customer->id ?? null) {
                return $this->sendError('You can only view reviews from your own orders', [], 403);
            }

            // Get user's review for this order
            $review = ProductReview::where('user_id', $user->id)
                ->where('order_id', $orderId)
                ->with(['product:id,name,image', 'user:id,name,email'])
                ->first();

            if (!$review) {
                return $this->sendError('No review found for this order', [], 404);
            }

            $reviewData = [
                'id' => $review->id,
                'rating' => $review->rating,
                'review_text' => $review->review_text,
                'reviewed_at' => $review->created_at->format('Y-m-d H:i:s'),
                'media_files' => $this->formatMediaFiles($review->media_files),
                'is_approved' => $review->is_approved,
                'product' => [
                    'id' => $review->product->id,
                    'name' => $review->product->name,
                    'image' => $review->product->image ? asset('storage/build/assets/' . $review->product->image) : null,
                ],
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->order_status,
                ]
            ];

            return $this->sendResponse($reviewData, 'User review for order retrieved successfully');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Order not found', [], 404);
        } catch (Exception $e) {
            Log::error('Error getting user review for order: ' . $e->getMessage());
            return $this->sendError('Failed to retrieve review', [], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/orders/{order_id}/reviews",
     *     tags={"Product Reviews"},
     *     summary="Delete my review for a specific order",
     *     description="Delete user's review for a specific order and update product rating cache",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Review not found"
     *     )
     * )
     */
    public function deleteMyReviewForOrder(Request $request, $orderId)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            $order = Order::findOrFail($orderId);

            // Check if user owns this order
            if ($order->host_id !== $user->customer->id ?? null) {
                return $this->sendError('You can only delete reviews from your own orders', [], 403);
            }

            // Find user's review for this order
            $review = ProductReview::where('user_id', $user->id)
                ->where('order_id', $orderId)
                ->first();

            if (!$review) {
                return $this->sendError('No review found for this order', [], 404);
            }

            $productId = $review->product_id;

            // Delete media files if exists
            if ($review->media_files) {
                foreach ($review->media_files as $mediaFile) {
                    if (isset($mediaFile['path'])) {
                        Storage::disk('public')->delete($mediaFile['path']);
                    }
                }
            }

            // Delete the review
            $review->delete();

            // Update product rating cache
            $product = Product::find($productId);
            if ($product) {
                $product->updateRatingCache();
            }

            return $this->sendResponse([], 'Review deleted successfully');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Order not found', [], 404);
        } catch (Exception $e) {
            Log::error('Error deleting review for order: ' . $e->getMessage());
            return $this->sendError('Failed to delete review', [], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/orders/{order_id}/reviews",
     *     tags={"Product Reviews"},
     *     summary="Update my review for a specific order",
     *     description="Update user's review rating and text for a specific order",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="Order ID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating"},
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4),
     *             @OA\Property(property="review_text", type="string", maxLength=1000, example="Updated review text"),
     *             @OA\Property(property="media_files", type="array", @OA\Items(type="string"), maxItems=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Review updated successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Review not found"
     *     )
     * )
     */
    public function updateMyReviewForOrder(Request $request, $orderId)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized', [], 401);
            }

            // Validate request data
            $validator = Validator::make($request->all(), [
                'rating' => 'required|integer|min:1|max:5',
                'review_text' => 'nullable|string|max:1000',
                'media_files' => 'nullable|array|max:5',
                'media_files.*' => 'string'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 400);
            }

            $order = Order::findOrFail($orderId);

            // Check if user owns this order
            if ($order->host_id !== $user->customer->id ?? null) {
                return $this->sendError('You can only update reviews from your own orders', [], 403);
            }

            // Find user's review for this order
            $review = ProductReview::where('user_id', $user->id)
                ->where('order_id', $orderId)
                ->first();

            if (!$review) {
                return $this->sendError('No review found for this order', [], 404);
            }

            // Prepare media files data
            $mediaFiles = null;
            if ($request->has('media_files') && is_array($request->media_files)) {
                $mediaFiles = [];
                foreach ($request->media_files as $filePath) {
                    if (Storage::disk('public')->exists($filePath)) {
                        $mediaFiles[] = [
                            'path' => $filePath,
                            'url' => asset('storage/' . $filePath),
                            'type' => $this->getFileType($filePath),
                            'name' => basename($filePath)
                        ];
                    }
                }
            }

            // Update review
            $review->update([
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'media_files' => $mediaFiles,
            ]);

            // Update product rating cache
            $product = Product::find($review->product_id);
            if ($product) {
                $product->updateRatingCache();

                // Get updated summary
                $summary = [
                    'average_rating' => (float) $product->avg_rating,
                    'total_reviews' => $product->review_count,
                    'rating_distribution' => $product->rating_distribution
                ];
            }

            // Prepare response
            $reviewData = [
                'id' => $review->id,
                'rating' => $review->rating,
                'review_text' => $review->review_text,
                'reviewed_at' => $review->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $review->updated_at->format('Y-m-d H:i:s'),
                'media_files' => $this->formatMediaFiles($review->media_files),
                'product' => [
                    'id' => $review->product->id,
                    'name' => $review->product->name,
                ],
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                ]
            ];

            $responseData = [
                'review' => $reviewData,
                'summary' => $summary ?? null
            ];

            return $this->sendResponse($responseData, 'Review updated successfully');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Order not found', [], 404);
        } catch (Exception $e) {
            Log::error('Error updating review for order: ' . $e->getMessage());
            return $this->sendError('Failed to update review', [], 500);
        }
    }

    private function checkFirebaseUser(Request $request)
    {
        return auth()->user();
    }
}
