<?php

namespace App\Models;
use App\Models\Concerns\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'order_id',
        'status',
        'changed_by',
        'note',
    ];

    /**
     * Get the order that owns the status history
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who changed the status
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
