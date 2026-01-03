<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductRecommendationService
{
    /**
     * Get customer order history data for AI recommendation
     */
    public function getCustomerOrderHistory($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return null;
        }

        // Get completed orders with product details
        $orderHistory = DB::select("
            SELECT 
                p.name as product_name,
                p.description as product_description,
                c.name as category_name,
                od.quantity,
                od.size,
                od.total_price,
                o.created_at as order_date,
                o.rate as order_rating
            FROM customers_orders co
            JOIN orders o ON co.order_id = o.id
            JOIN order_details od ON co.id = od.customer_order_id
            JOIN products p ON od.product_id = p.id
            JOIN category_product cp ON p.id = cp.product_id
            JOIN categories c ON cp.category_id = c.id
            WHERE co.customer_id = ?
            AND o.order_status IN ('Completed', 'Delivered', 'Wait For Approval')
            AND od.parent_id IS NULL
            ORDER BY o.created_at DESC
            LIMIT 20
        ", [$customerId]);

        // Get categories the customer has ordered from
        $customerCategories = collect($orderHistory)
            ->groupBy('category_name')
            ->map(function ($items, $categoryName) {
                return [
                    'category_name' => $categoryName,
                    'order_count' => $items->count(),
                    'total_spent' => $items->sum('total_price'),
                    'products' => $items->pluck('product_name')->unique()->values()
                ];
            })
            ->sortByDesc('order_count')
            ->values();

        return [
            'customer_name' => $customer->full_name,
            'order_history' => $orderHistory,
            'favorite_categories' => $customerCategories->take(5),
            'total_orders' => count($orderHistory)
        ];
    }

    /**
     * Get available products for recommendation (exclude already ordered)
     */
    public function getAvailableProducts($customerId)
    {
        // Get products customer hasn't ordered yet or ordered long time ago
        $orderedProductIds = DB::select("
            SELECT DISTINCT p.id
            FROM customers_orders co
            JOIN orders o ON co.order_id = o.id
            JOIN order_details od ON co.id = od.customer_order_id
            JOIN products p ON od.product_id = p.id
            WHERE co.customer_id = ?
            AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", [$customerId]);

        $orderedIds = collect($orderedProductIds)->pluck('id');

        return Product::with(['categories'])
            ->whereNotIn('id', $orderedIds)
            ->where('status', 'active')
            ->where('is_topping', false)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'categories' => $product->categories->pluck('name')->join(', ')
                ];
            });
    }

    /**
     * Generate AI-powered product recommendations
     */
    public function generateAIRecommendations($customerId, $apiKey = null, $provider = 'openai')
    {
        try {
            $orderHistory = $this->getCustomerOrderHistory($customerId);

            if (!$orderHistory || empty($orderHistory['order_history'])) {
                return $this->getFallbackRecommendations();
            }

            $availableProducts = $this->getAvailableProducts($customerId);

            if ($availableProducts->isEmpty()) {
                return $this->getFallbackRecommendations();
            }

            // Prepare data for AI
            $prompt = $this->buildPrompt($orderHistory, $availableProducts);

            // Call AI API
            $aiResponse = $this->callAIAPI($prompt, $apiKey, $provider);

            if ($aiResponse) {
                return $this->processAIResponse($aiResponse, $availableProducts);
            }

            return $this->getFallbackRecommendations();
        } catch (\Exception $e) {
            Log::error('Error generating AI recommendations: ' . $e->getMessage());
            return $this->getFallbackRecommendations();
        }
    }

    /**
     * Build prompt for AI
     */
    private function buildPrompt($orderHistory, $availableProducts)
    {
        $customerData = json_encode([
            'customer_name' => $orderHistory['customer_name'],
            'favorite_categories' => $orderHistory['favorite_categories'],
            'recent_orders' => array_slice($orderHistory['order_history'], 0, 10)
        ], JSON_PRETTY_PRINT);

        $productsData = json_encode($availableProducts->toArray(), JSON_PRETTY_PRINT);

        return "
You are a product recommendation system for a bakery store. 

Customer Profile:
{$customerData}

Available Products to Recommend:
{$productsData}

Based on the customer's order history and preferences, recommend 5-8 products that they would most likely enjoy. 
Focus on:
1. Products from categories they frequently order
2. Products similar to what they've enjoyed before
3. Popular products they haven't tried
4. Seasonal or new products that match their taste profile

Return ONLY valid JSON.
Do NOT use markdown.
Return an array with AT MOST 5 items.

Each item:
- product_id: string
- reason: SHORT sentence (max 15 words).

Example reason:
\"Matches favorite category\";
";
    }

    /**
     * Call AI API (OpenAI or Gemini)
     */
    private function callAIAPI($prompt, $apiKey, $provider = 'openai')
    {
        if (!$apiKey) {
            Log::warning("No API key provided for {$provider}");
            return null;
        }

        try {
            Log::info("Calling {$provider} API with key: " . substr($apiKey, 0, 10) . "...");

            if ($provider === 'openai') {
                return $this->callOpenAI($prompt, $apiKey);
            } elseif ($provider === 'gemini') {
                return $this->callGemini($prompt, $apiKey);
            }
        } catch (\Exception $e) {
            Log::error("AI API call failed ({$provider}): " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            return null;
        }
    }

    /**
     * Call OpenAI API
     */
    private function callOpenAI($prompt, $apiKey)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 500,
            'temperature' => 0.7
        ]);

        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        }

        return null;
    }

    /**
     * Call Gemini API
     */
    private function callGemini($prompt, $apiKey)
    {
        Log::info("Sending request to Gemini API");

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-lite-latest:generateContent?key={$apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 500
            ]

        ]);

        Log::info("Gemini API response status: " . $response->status());

        if ($response->successful()) {
            $data = $response->json();
            Log::info("Gemini API response: " . json_encode($data));
            return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
        } else {
            Log::error("Gemini API error: " . $response->body());
            return null;
        }
    }

    /**
     * Process AI response
     */
    private function processAIResponse($aiResponse, $availableProducts)
    {
        try {
            if (empty($aiResponse)) {
                Log::warning('AI response is empty');
                return $this->getFallbackRecommendations();
            }

            Log::info("Raw AI response (first 500 chars): " . substr($aiResponse, 0, 500));

            // 1️⃣ Clean markdown fences ```json ```
            $cleanResponse = trim($aiResponse);
            $cleanResponse = preg_replace('/```json|```/i', '', $cleanResponse);
            $cleanResponse = trim($cleanResponse);

            Log::info("Cleaned AI response (first 500 chars): " . substr($cleanResponse, 0, 500));

            // 2️⃣ Decode JSON trực tiếp (KHÔNG substring bằng [ ])
            $recommendations = json_decode($cleanResponse, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error: ' . json_last_error_msg());
                return $this->getFallbackRecommendations();
            }

            if (!is_array($recommendations)) {
                Log::warning('AI response is not an array');
                return $this->getFallbackRecommendations();
            }

            // 3️⃣ Map product_id → product
            $validRecommendations = [];

            foreach ($recommendations as $rec) {
                if (!isset($rec['product_id'])) {
                    continue;
                }

                $product = $availableProducts->firstWhere('id', $rec['product_id']);

                if ($product) {
                    $validRecommendations[] = [
                        'product' => $product,
                        'reason' => $rec['reason'] ?? 'AI recommended based on your preferences',
                    ];
                }
            }

            Log::info("Valid AI recommendations count: " . count($validRecommendations));

            return !empty($validRecommendations)
                ? $validRecommendations
                : $this->getFallbackRecommendations();
        } catch (\Throwable $e) {
            Log::error('Error processing AI response: ' . $e->getMessage());
            return $this->getFallbackRecommendations();
        }
    }

    /**
     * Fallback recommendations based on popularity and categories
     */
    private function getFallbackRecommendations()
    {
        $popularProducts = Product::with(['categories'])
            ->where('status', 'active')
            ->where('is_topping', false)
            ->orderBy('priority', 'desc')
            ->take(6)
            ->get();

        return $popularProducts->map(function ($product) {
            return [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'categories' => $product->categories->pluck('name')->join(', ')
                ],
                'reason' => 'Popular choice among our customers'
            ];
        })->toArray();
    }

    /**
     * Get simple category-based recommendations
     */
    public function getCategoryBasedRecommendations($customerId, $limit = 8)
    {
        $customerCategories = DB::select("
            SELECT 
                c.id,
                c.name,
                COUNT(*) as order_count
            FROM customers_orders co
            JOIN orders o ON co.order_id = o.id
            JOIN order_details od ON co.id = od.customer_order_id
            JOIN products p ON od.product_id = p.id
            JOIN category_product cp ON p.id = cp.product_id
            JOIN categories c ON cp.category_id = c.id
            WHERE co.customer_id = ?
            AND o.order_status IN ('Completed', 'Delivered', 'Wait For Approval')
            GROUP BY c.id, c.name
            ORDER BY order_count DESC
            LIMIT 3
        ", [$customerId]);

        if (empty($customerCategories)) {
            return $this->getFallbackRecommendations();
        }

        $categoryIds = collect($customerCategories)->pluck('id');

        $recommendations = Product::with(['categories'])
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->where('status', 'active')
            ->where('is_topping', false)
            ->inRandomOrder()
            ->take($limit)
            ->get();

        return $recommendations->map(function ($product) use ($customerCategories) {
            $categoryName = collect($customerCategories)
                ->firstWhere('id', $product->categories->first()->id)
                ->name ?? 'recommended category';

            return [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->price,
                    'image' => $product->image,
                    'categories' => $product->categories->pluck('name')->join(', ')
                ],
                'reason' => "Based on your preference for {$categoryName}"
            ];
        })->toArray();
    }
}
