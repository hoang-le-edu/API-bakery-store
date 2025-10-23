<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Bakery Store API Documentation",
 *     version="1.0.0",
 *     description="API documentation for Bakery Store E-commerce Platform. This API provides endpoints for managing products, orders, carts, customers, and more.",
 *     @OA\Contact(
 *         email="leminhhoang.working@gmail.com",
 *         name="Le Minh Hoang"
 *     )
 * )
 * 
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server (Auto-detected from APP_URL)"
 * )
 * 
 * @OA\Server(
 *     url="https://api-bakery-store-mobile-btfrg4gqevhveyfy.eastasia-01.azurewebsites.net",
 *     description="Production Server (Azure)"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Local Development Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="firebaseAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter Firebase JWT token"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication and authorization"
 * )
 * 
 * @OA\Tag(
 *     name="Products",
 *     description="API Endpoints for product management"
 * )
 * 
 * @OA\Tag(
 *     name="Cart",
 *     description="API Endpoints for shopping cart management"
 * )
 * 
 * @OA\Tag(
 *     name="Orders",
 *     description="API Endpoints for order management"
 * )
 * 
 * @OA\Tag(
 *     name="Categories",
 *     description="API Endpoints for category management"
 * )
 * 
 * @OA\Tag(
 *     name="Customers",
 *     description="API Endpoints for customer management"
 * )
 * 
 * @OA\Tag(
 *     name="Vouchers",
 *     description="API Endpoints for voucher management"
 * )
 * 
 * @OA\Tag(
 *     name="Shipping",
 *     description="API Endpoints for shipping and delivery"
 * )
 * 
 * @OA\Tag(
 *     name="Payment",
 *     description="API Endpoints for payment processing"
 * )
 * 
 * @OA\Tag(
 *     name="Reports",
 *     description="API Endpoints for reports and analytics"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
