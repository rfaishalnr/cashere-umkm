<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'promo_price',
        'is_promo_active',
        'category',
        'description',
        'image',
        'user_id',
        'stock',
        'is_visible'
    ];

    protected static function booted()
    {
        static::creating(function ($product) {
            if (empty($product->user_id)) {
                $product->user_id = Auth::id();
            }
        });
    }

    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /**
     * Mendapatkan harga saat ini dari produk.
     * Akan mengembalikan harga promo jika promo aktif, atau harga normal jika tidak.
     */
    public function getCurrentPriceAttribute()
    {
        return ($this->is_promo_active && $this->promo_price !== null) ? $this->promo_price : $this->price;
    }

    /**
     * Menghitung persentase diskon dari harga normal ke harga promo.
     */
    public function getDiscountPercentageAttribute()
    {
        if ($this->is_promo_active && $this->promo_price !== null && $this->price > 0) {
            $discount = (($this->price - $this->promo_price) / $this->price) * 100;
            return round($discount);
        }
        return 0;
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOwnedBy($query, $userId = null)
    {
        $userId = $userId ?? (Auth::check() ? Auth::id() : null);

        if (is_null($userId)) {
            throw new \Exception('No authenticated user found.');
        }

        return $query->where('user_id', $userId);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}