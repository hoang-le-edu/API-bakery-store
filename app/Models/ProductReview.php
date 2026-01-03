<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'product_id',
        'order_id',
        'rating',
        'review_text',
        'media_files',
        'is_verified_purchase',
        'is_approved',
        'reviewed_at',
    ];

    protected $casts = [
        'media_files' => 'array',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'reviewed_at' => 'datetime',
        'rating' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    // Accessors
    public function getHasMediaAttribute()
    {
        return !empty($this->media_files);
    }

    public function getMediaCountAttribute()
    {
        return count($this->media_files ?? []);
    }

    // Static methods
    public static function getAverageRating($productId)
    {
        return static::where('product_id', $productId)
            ->approved()
            ->avg('rating');
    }

    public static function getReviewCount($productId)
    {
        return static::where('product_id', $productId)
            ->approved()
            ->count();
    }

    public static function getRatingDistribution($productId)
    {
        $distribution = static::where('product_id', $productId)
            ->approved()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Fill missing ratings with 0
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($distribution[$i])) {
                $distribution[$i] = 0;
            }
        }

        ksort($distribution);
        return $distribution;
    }
}
