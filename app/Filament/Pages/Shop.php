<?php

namespace App\Filament\Pages;

use App\Models\Product;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class Shop extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string $view = 'filament.pages.shop';
    protected static ?string $navigationLabel = 'Shop';
    protected static ?string $title = 'Shop';

    public function getProducts()
    {
        // Only return visible products owned by the current user
        return Product::where('user_id', Auth::id())
            ->where('is_visible', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    public function getProductsByCategory()
    {
        // Group visible products by category but only for the current user
        return Product::where('user_id', Auth::id())
            ->where('is_visible', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');
    }
    
    /**
     * Get the actual price of a product accounting for promotions
     * 
     * @param Product $product
     * @return float
     */
    public function getProductPrice($product)
    {
        if ($product->is_promo_active && $product->promo_price !== null) {
            return $product->promo_price;
        }
        
        return $product->price;
    }
    
    /**
     * Calculate discount percentage for a product
     * 
     * @param Product $product
     * @return int
     */
    public function getDiscountPercent($product)
    {
        if ($product->is_promo_active && $product->promo_price !== null) {
            return round((1 - ($product->promo_price / $product->price)) * 100);
        }
        
        return 0;
    }
    
    public function getCartItems(): array
    {
        $cart = session()->get('cart', []);
        $productIds = array_column($cart, 'id');
        
        // Only fetch visible products owned by the current user
        $products = Product::whereIn('id', $productIds)
            ->where('user_id', Auth::id())
            ->where('is_visible', true)
            ->get();

        $cartItems = [];

        foreach ($products as $product) {
            foreach ($cart as $cartItem) {
                if ($cartItem['id'] === $product->id) {
                    $price = $this->getProductPrice($product);
                    
                    $cartItems[] = [
                        'product' => $product,
                        'quantity' => $cartItem['quantity'],
                        'subtotal' => $price * $cartItem['quantity'],
                    ];
                }
            }
        }

        // Clean up cart by removing items that are no longer visible
        $this->cleanupCart($productIds, $products);

        return $cartItems;
    }

    /**
     * Remove products from cart that are no longer visible
     * 
     * @param array $cartProductIds
     * @param \Illuminate\Database\Eloquent\Collection $visibleProducts
     */
    private function cleanupCart(array $cartProductIds, $visibleProducts): void
    {
        $visibleProductIds = $visibleProducts->pluck('id')->toArray();
        $productsToRemove = array_diff($cartProductIds, $visibleProductIds);
        
        if (count($productsToRemove) > 0) {
            $cart = session()->get('cart', []);
            $cart = array_filter($cart, function($item) use ($visibleProductIds) {
                return in_array($item['id'], $visibleProductIds);
            });
            session()->put('cart', array_values($cart));
            
            // Notify user that some items were removed from cart
            if (count($productsToRemove) > 0) {
                Notification::make()
                    ->title('Cart Updated')
                    ->warning()
                    ->body('Some items in your cart are no longer available and have been removed.')
                    ->send();
            }
        }
    }

    public function addToCart($productId)
    {
        // Verify product belongs to current user and is visible
        $product = Product::where('id', $productId)
            ->where('user_id', Auth::id())
            ->where('is_visible', true)
            ->first();

        if (!$product) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Produk tidak ditemukan atau tidak tersedia.')
                ->send();
            return;
        }

        // Check if product has stock management enabled and is out of stock
        if ($product->stock !== null && $product->stock <= 0) {
            Notification::make()
                ->title('Stok Habis')
                ->danger()
                ->body('Produk ini sedang tidak tersedia.')
                ->send();
            return;
        }

        $cart = session()->get('cart', []);

        // Check if product already in cart
        $exists = false;
        foreach ($cart as &$item) {
            if ($item['id'] == $productId) {
                // Check if adding more would exceed available stock
                if ($product->stock !== null && $item['quantity'] >= $product->stock) {
                    Notification::make()
                        ->title('Stok Terbatas')
                        ->warning()
                        ->body("Hanya tersedia {$product->stock} item.")
                        ->send();
                    return;
                }
                
                $item['quantity']++;
                $exists = true;
                break;
            }
        }

        // If not exists, add new item
        if (!$exists) {
            $cart[] = [
                'id' => $productId,
                'quantity' => 1
            ];
        }

        session()->put('cart', $cart);

        // Customize notification message based on promotion
        $notificationMessage = 'Produk ditambahkan ke keranjang.';
        if ($product->promo_text) {
            $notificationMessage = 'Produk ditambahkan ke keranjang. ' . $product->promo_text;
        }

        Notification::make()
            ->title('Berhasil')
            ->success()
            ->body($notificationMessage)
            ->send();
    }
    
    public function incrementQuantity($productId)
    {
        // This method is incorrectly named - it's not being used in the template
        // Changing to match the method name used in the blade template (increaseQuantity)
        $this->increaseQuantity($productId);
    }
    
    public function increaseQuantity($productId)
    {
        // Check if product belongs to current user, is visible, and has stock
        $product = $this->getVisibleProduct($productId);
        if (!$product) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Produk tidak ditemukan atau tidak tersedia.')
                ->send();
            return;
        }

        $cart = session()->get('cart', []);
        foreach ($cart as &$item) {
            if ($item['id'] == $productId) {
                // Check if incrementing would exceed available stock
                if ($product->stock !== null && $item['quantity'] >= $product->stock) {
                    Notification::make()
                        ->title('Stok Terbatas')
                        ->warning()
                        ->body("Hanya tersedia {$product->stock} item.")
                        ->send();
                    return;
                }
                
                $item['quantity']++;
            }
        }
        session()->put('cart', $cart);
    }

    public function decreaseQuantity($productId)
    {
        // Check if product belongs to current user and is visible
        if (!$this->getVisibleProduct($productId)) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Produk tidak ditemukan atau tidak tersedia.')
                ->send();
            return;
        }

        $cart = session()->get('cart', []);
        foreach ($cart as &$item) {
            if ($item['id'] == $productId && $item['quantity'] > 1) {
                $item['quantity']--;
            }
        }
        session()->put('cart', $cart);
    }

    public function removeFromCart($productId)
    {
        $cart = session()->get('cart', []);
        $cart = array_filter($cart, fn($item) => $item['id'] != $productId);
        session()->put('cart', array_values($cart));
    }

    public function clearCart()
    {
        session()->forget('cart');
    }
    
    /**
     * Verify that a product belongs to the current user and is visible
     * 
     * @param int $productId
     * @return bool
     */
    private function verifyProductOwnership($productId): bool
    {
        return Product::where('id', $productId)
            ->where('user_id', Auth::id())
            ->where('is_visible', true)
            ->exists();
    }
    
    /**
     * Get a product that belongs to the current user and is visible
     * 
     * @param int $productId
     * @return Product|null
     */
    private function getVisibleProduct($productId)
    {
        return Product::where('id', $productId)
            ->where('user_id', Auth::id())
            ->where('is_visible', true)
            ->first();
    }
}