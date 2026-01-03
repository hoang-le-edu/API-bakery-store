<?php

namespace App\Http\Controllers;

use App\Services\ProductRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecommendationController extends BaseController
{
    protected ProductRecommendationService $recommendationService;

    public function __construct(ProductRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    /**
     * @OA\Get(
     *     path="/api/recommendations/ai-suggestions",
     *     tags={"Recommendations"},
     *     summary="Get AI-powered product recommendations",
     *     description="Get personalized product recommendations using AI based on customer order history",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="provider",
     *         in="query",
     *         required=false,
     *         description="AI provider: openai or gemini",
     *         @OA\Schema(type="string", enum={"openai", "gemini"}, default="openai")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="AI recommendations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="AI recommendations generated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recommendations", type="array", @OA\Items(type="object",
     *                     @OA\Property(property="product", type="object",
     *                         @OA\Property(property="id", type="string"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="description", type="string"),
     *                         @OA\Property(property="price", type="number"),
     *                         @OA\Property(property="categories", type="string")
     *                     ),
     *                     @OA\Property(property="reason", type="string")
     *                 )),
     *                 @OA\Property(property="recommendation_type", type="string", example="ai_powered"),
     *                 @OA\Property(property="customer_profile", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function getAIRecommendations(Request $request)
    {
        try {
            // Get authenticated user and customer
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized or User not found.', [], 401);
            }

            $customer = $user->customer;
            if (!$customer) {
                return $this->sendError('Customer not found.', [], 404);
            }

            $provider = $request->query('provider', 'openai');
            $apiKey = $this->getAIApiKey($provider);

            // Get order history for customer profile
            $orderHistory = $this->recommendationService->getCustomerOrderHistory($customer->id);

            // Generate AI recommendations
            $recommendations = $this->recommendationService->generateAIRecommendations(
                $customer->id,
                $apiKey,
                $provider
            );

            return $this->sendResponse([
                'recommendations' => $recommendations,
                'recommendation_type' => $apiKey ? 'ai_powered' : 'fallback',
                'customer_profile' => $orderHistory ? [
                    'total_orders' => $orderHistory['total_orders'],
                    'favorite_categories' => $orderHistory['favorite_categories']
                ] : null
            ], 'AI recommendations generated successfully');
        } catch (\Exception $e) {
            Log::error('Error getting AI recommendations: ' . $e->getMessage());
            return $this->sendError('Failed to generate recommendations', [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/recommendations/category-based",
     *     tags={"Recommendations"},
     *     summary="Get category-based product recommendations",
     *     description="Get product recommendations based on customer's favorite categories",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of recommendations to return",
     *         @OA\Schema(type="integer", minimum=1, maximum=20, default=8)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category-based recommendations retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Category-based recommendations generated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recommendations", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="recommendation_type", type="string", example="category_based")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function getCategoryBasedRecommendations(Request $request)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized or User not found.', [], 401);
            }

            $customer = $user->customer;
            if (!$customer) {
                return $this->sendError('Customer not found.', [], 404);
            }

            $limit = $request->query('limit', 8);
            $limit = max(1, min(20, intval($limit))); // Ensure limit is between 1-20

            $recommendations = $this->recommendationService->getCategoryBasedRecommendations(
                $customer->id,
                $limit
            );

            return $this->sendResponse([
                'recommendations' => $recommendations,
                'recommendation_type' => 'category_based'
            ], 'Category-based recommendations generated successfully');
        } catch (\Exception $e) {
            Log::error('Error getting category recommendations: ' . $e->getMessage());
            return $this->sendError('Failed to generate recommendations', [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/recommendations/customer-profile",
     *     tags={"Recommendations"},
     *     summary="Get customer order profile",
     *     description="Get customer's order history and preferences for recommendations",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Customer profile retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Customer profile retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="customer_name", type="string"),
     *                 @OA\Property(property="total_orders", type="integer"),
     *                 @OA\Property(property="favorite_categories", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="recent_orders", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Customer not found")
     * )
     */
    public function getCustomerProfile(Request $request)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized or User not found.', [], 401);
            }

            $customer = $user->customer;
            if (!$customer) {
                return $this->sendError('Customer not found.', [], 404);
            }

            $orderHistory = $this->recommendationService->getCustomerOrderHistory($customer->id);

            if (!$orderHistory) {
                return $this->sendResponse([
                    'customer_name' => $customer->full_name,
                    'total_orders' => 0,
                    'favorite_categories' => [],
                    'recent_orders' => []
                ], 'Customer profile retrieved successfully');
            }

            return $this->sendResponse([
                'customer_name' => $orderHistory['customer_name'],
                'total_orders' => $orderHistory['total_orders'],
                'favorite_categories' => $orderHistory['favorite_categories'],
                'recent_orders' => array_slice($orderHistory['order_history'], 0, 10)
            ], 'Customer profile retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting customer profile: ' . $e->getMessage());
            return $this->sendError('Failed to retrieve customer profile', [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/recommendations/debug-profile",
     *     tags={"Recommendations"},
     *     summary="Debug customer profile and data",
     *     description="Get detailed debug information about customer data for recommendations",
     *     security={{"firebaseAuth": {}}},
     *     @OA\Response(response=200, description="Debug info retrieved successfully")
     * )
     */
    public function debugCustomerProfile(Request $request)
    {
        try {
            $user = $this->checkFirebaseUser($request);
            if (!$user) {
                return $this->sendError('Unauthorized or User not found.', [], 401);
            }

            $customer = $user->customer;
            if (!$customer) {
                return $this->sendError('Customer not found.', [], 404);
            }

            // Get all orders for this customer (any status)
            $allOrders = \DB::select("
                SELECT 
                    o.id,
                    o.order_number,
                    o.order_status,
                    o.created_at,
                    p.name as product_name,
                    c.name as category_name
                FROM customers_orders co
                JOIN orders o ON co.order_id = o.id
                JOIN order_details od ON co.id = od.customer_order_id
                JOIN products p ON od.product_id = p.id
                JOIN category_product cp ON p.id = cp.product_id
                JOIN categories c ON cp.category_id = c.id
                WHERE co.customer_id = ?
                ORDER BY o.created_at DESC
            ", [$customer->id]);

            $orderHistory = $this->recommendationService->getCustomerOrderHistory($customer->id);
            $availableProducts = $this->recommendationService->getAvailableProducts($customer->id);

            return $this->sendResponse([
                'customer_id' => $customer->id,
                'customer_name' => $customer->full_name,
                'all_orders_count' => count($allOrders),
                'all_orders' => $allOrders,
                'processed_order_history' => $orderHistory,
                'available_products_count' => $availableProducts->count(),
                'available_products_sample' => $availableProducts->take(3)
            ], 'Debug information retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting debug profile: ' . $e->getMessage());
            return $this->sendError('Failed to get debug info: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Get AI API key from environment or config
     */
    private function getAIApiKey($provider)
    {
        $configKey = $provider === 'gemini' ? 'GEMINI_API_KEY' : 'OPENAI_API_KEY';
        return env($configKey);
    }

    /**
     * Check Firebase authenticated user
     */
    private function checkFirebaseUser(Request $request)
    {
        return auth()->user();
    }
}
