<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_name',
        'price',
        'quantity',
        'total_price',
        'payment_method',
        'order_type',
        'customer_name',
        'customer_id',
        'purchased_at',
        'user_id',
    ];

    public $timestamps = true;

    protected $casts = [
        'purchased_at' => 'datetime',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    /**
     * Scope a query to filter purchases owned by a user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $userId
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @throws \Exception
     */
    public function scopeOwnedBy($query, $userId = null)
    {
        $userId = $userId ?? (Auth::check() ? Auth::id() : null);
        if (is_null($userId)) {
            throw new \Exception('No authenticated user found.');
        }

        return $query->where('user_id', $userId);
    }

    /**
     * Relationship: Purchase belongs to a User.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Auto-generate customer_id when creating a Purchase.
     */
    protected static function booted()
    {
        static::creating(function ($purchase) {
            if (empty($purchase->customer_id)) {
                if (!empty($purchase->customer_name)) {
                    $initials = strtoupper(substr($purchase->customer_name, 0, 1) .
                        (str_contains($purchase->customer_name, ' ') ?
                            substr($purchase->customer_name, strpos($purchase->customer_name, ' ') + 1, 1) :
                            substr($purchase->customer_name, 1, 1))
                    );
                    $purchase->customer_id = $initials . strtoupper(Str::random(6));
                } else {
                    $purchase->customer_id = 'CUST' . strtoupper(Str::random(6));
                }
            }
        });
    }
}
