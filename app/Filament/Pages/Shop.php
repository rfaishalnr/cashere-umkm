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
        // Use the currentPrice attribute from the model
        return $product->current_price;
    }
    
    /**
     * Calculate discount percentage for a product
     * 
     * @param Product $product
     * @return int
     */
    public function getDiscountPercent($product)
    {
        // Use the discountPercentage attribute from the model
        return $product->discount_percentage;
    }
    
    /**
     * Check if a product uses stock management
     * 
     * @param Product $product
     * @return bool
     */
    public function usesStockManagement($product)
    {
        return $product->stock !== null;
    }
    
    /**
     * Check if a product is available based on stock
     * 
     * @param Product $product
     * @param int $requestedQuantity
     * @return bool
     */
    public function isProductAvailable($product, $requestedQuantity = 1)
    {
        // If product doesn't use stock management, it's always available
        if (!$this->usesStockManagement($product)) {
            return true;
        }
        
        // Check if there's enough stock in the database
        return $product->stock >= $requestedQuantity;
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
                    
                    // Check if quantity exceeds available stock (for products using stock management)
                    $quantity = $cartItem['quantity'];
                    if ($this->usesStockManagement($product) && $quantity > $product->stock) {
                        // Adjust quantity to available stock
                        $quantity = max(1, $product->stock);
                        
                        // Update the cart with corrected quantity
                        $this->updateCartItemQuantity($cartItem['id'], $quantity);
                        
                        Notification::make()
                            ->title('Jumlah Disesuaikan')
                            ->warning()
                            ->body("Kuantitas {$product->name} disesuaikan dengan stok yang tersedia ({$product->stock}).")
                            ->send();
                    }
                    
                    $cartItems[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'subtotal' => $price * $quantity,
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

    /**
     * Update a specific item's quantity in the cart
     * 
     * @param int $productId
     * @param int $quantity
     * @return void
     */
    private function updateCartItemQuantity($productId, $quantity)
    {
        $cart = session()->get('cart', []);
        
        foreach ($cart as &$item) {
            if ($item['id'] == $productId) {
                $item['quantity'] = $quantity;
                break;
            }
        }
        
        session()->put('cart', $cart);
    }

    /**
     * Update product stock when it's added to cart.
     * This function directly updates the stock in the database.
     * 
     * @param Product $product
     * @param int $quantity
     * @return void
     */
    private function handleProductStock($product, $quantity = 1)
    {
        // If product doesn't use stock management, do nothing
        if (!$this->usesStockManagement($product)) {
            return;
        }
        
        // Update the stock directly in the database
        $product->stock = max(0, $product->stock - $quantity);
        $product->save();
    }
    
    /**
     * Get the currently available stock for a product
     * 
     * @param Product $product
     * @return int|null
     */
    public function getCurrentStock($product)
    {
        // Return stock directly from database
        return $product->stock;
    }
    
    public function addToCart($productId)
    {
        // Verify product belongs to current user and is visible
        $product = $this->getVisibleProduct($productId);

        if (!$product) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Produk tidak ditemukan atau tidak tersedia.')
                ->send();
            return;
        }

        // Check if product has stock management enabled and is out of stock
        if ($this->usesStockManagement($product)) {
            if ($product->stock <= 0) {
                Notification::make()
                    ->title('Stok Habis')
                    ->danger()
                    ->body('Produk ini sedang tidak tersedia.')
                    ->send();
                return;
            }
        }

        $cart = session()->get('cart', []);

        // Check if product already in cart
        $exists = false;
        foreach ($cart as &$item) {
            if ($item['id'] == $productId) {
                // If product uses stock management, check if adding more would exceed available stock
                if ($this->usesStockManagement($product)) {
                    if ($product->stock <= 0) {
                        Notification::make()
                            ->title('Stok Terbatas')
                            ->warning()
                            ->body("Stok sudah habis.")
                            ->send();
                        return;
                    }
                }
                
                // Increase quantity and update stock if needed
                $item['quantity']++;
                
                // Handle stock reduction in the database
                if ($this->usesStockManagement($product)) {
                    $this->handleProductStock($product, 1);
                }
                
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
            
            // Handle stock reduction for new item in the database
            if ($this->usesStockManagement($product)) {
                $this->handleProductStock($product, 1);
            }
        }

        session()->put('cart', $cart);

        // Refresh the product data from the database to get the updated stock
        $product->refresh();

        // Customize notification message based on promotion
        $notificationMessage = 'Produk ditambahkan ke keranjang.';
        if ($product->promo_text) {
            $notificationMessage = 'Produk ditambahkan ke keranjang. ' . $product->promo_text;
        }
        
        // Add stock information to notification if stock management is enabled
        if ($this->usesStockManagement($product)) {
            if ($product->stock <= 5 && $product->stock > 0) {
                $notificationMessage .= " Sisa stok: {$product->stock}.";
            }
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
        // Check if product belongs to current user and is visible
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
                // If product uses stock management, check if incrementing would exceed available stock
                if ($this->usesStockManagement($product)) {
                    if ($product->stock <= 0) {
                        Notification::make()
                            ->title('Stok Terbatas')
                            ->warning()
                            ->body("Stok sudah habis.")
                            ->send();
                        return;
                    }
                }
                
                // Increase quantity
                $item['quantity']++;
                
                // Handle stock reduction in the database
                if ($this->usesStockManagement($product)) {
                    $this->handleProductStock($product, 1);
                    
                    // Refresh the product to get the updated stock
                    $product->refresh();
                    
                    // Add stock information to notification
                    $notificationMsg = "Jumlah ditambah.";
                    if ($product->stock <= 5 && $product->stock > 0) {
                        $notificationMsg .= " Sisa stok: {$product->stock}.";
                    }
                    
                    Notification::make()
                        ->title('Berhasil')
                        ->success()
                        ->body($notificationMsg)
                        ->send();
                }
                
                break;
            }
        }
        session()->put('cart', $cart);
    }

    public function decreaseQuantity($productId)
    {
        // Check if product belongs to current user and is visible
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
            if ($item['id'] == $productId && $item['quantity'] > 1) {
                $item['quantity']--;
                
                // If product uses stock management, return one item to stock in the database
                if ($this->usesStockManagement($product)) {
                    // Return item to stock by increasing the stock in the database
                    $product->stock = $product->stock + 1;
                    $product->save();
                }
            }
        }
        session()->put('cart', $cart);
    }

    public function removeFromCart($productId)
    {
        $cart = session()->get('cart', []);
        
        // Find the item to be removed and restore its stock if stock management is enabled
        foreach ($cart as $item) {
            if ($item['id'] == $productId) {
                $product = $this->getVisibleProduct($productId);
                
                if ($product && $this->usesStockManagement($product)) {
                    // Restore the quantity back to stock in the database
                    $product->stock = $product->stock + $item['quantity'];
                    $product->save();
                }
                
                break;
            }
        }
        
        // Remove the item from cart
        $cart = array_filter($cart, fn($item) => $item['id'] != $productId);
        session()->put('cart', array_values($cart));
    }

    public function clearCart()
    {
        $cart = session()->get('cart', []);
        
        // Restore stock for all items in the cart
        foreach ($cart as $item) {
            $product = $this->getVisibleProduct($item['id']);
            
            if ($product && $this->usesStockManagement($product)) {
                // Restore the quantity back to stock in the database
                $product->stock = $product->stock + $item['quantity'];
                $product->save();
            }
        }
        
        // Clear the cart
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