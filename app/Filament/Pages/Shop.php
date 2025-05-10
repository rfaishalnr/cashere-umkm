<?php

namespace App\Filament\Pages;

use App\Models\Product;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Session;

class Shop extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string $view = 'filament.pages.shop';
    protected static ?string $navigationLabel = 'Shop';
    protected static ?string $title = 'Shop';
    
    // Flag untuk mengontrol notifikasi
    protected $notificationsEnabled = true;
    
    // Properti untuk pelacakan penyesuaian stok
    protected $adjustedProducts = [];
    
    public function mount()
    {
        // Membersihkan flag penyesuaian yang mungkin tersimpan di session
        session()->forget('cart_adjusted');
    }

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
    
    /**
     * Menonaktifkan notifikasi sementara
     */
    private function disableNotifications()
    {
        $this->notificationsEnabled = false;
    }
    
    /**
     * Mengaktifkan notifikasi kembali
     */
    private function enableNotifications()
    {
        $this->notificationsEnabled = true;
    }
    
    /**
     * Mengirim notifikasi jika diizinkan
     */
    private function sendNotification($type, $title, $body)
    {
        if ($this->notificationsEnabled) {
            Notification::make()
                ->$type()
                ->title($title)
                ->body($body)
                ->send();
        }
    }
    
    public function getCartItems(): array
    {
        // Cek apakah penyesuaian sudah dilakukan di request ini
        if (session()->has('cart_adjusted')) {
            $cartItems = session()->get('adjusted_cart_items', []);
            return $cartItems;
        }
        
        $cart = session()->get('cart', []);
        $productIds = array_column($cart, 'id');
        
        // Only fetch visible products owned by the current user
        $products = Product::whereIn('id', $productIds)
            ->where('user_id', Auth::id())
            ->where('is_visible', true)
            ->get();

        $cartItems = [];
        $adjustmentsMade = false;
        $this->adjustedProducts = [];

        // Nonaktifkan notifikasi sementara selama proses penyesuaian
        $this->disableNotifications();

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
                        
                        // Track this product as adjusted
                        $adjustmentsMade = true;
                        $this->adjustedProducts[] = $product->name;
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
        
        // Menyimpan hasil di session untuk mencegah kalkulasi & notifikasi berulang
        session()->put('cart_adjusted', true);
        session()->put('adjusted_cart_items', $cartItems);
        
        // Aktifkan notifikasi kembali
        $this->enableNotifications();
        
        // Kirim notifikasi penyesuaian sekali saja
        if ($adjustmentsMade) {
            $message = count($this->adjustedProducts) > 1 
                ? "Beberapa produk disesuaikan dengan stok yang tersedia." 
                : "Kuantitas {$this->adjustedProducts[0]} disesuaikan dengan stok yang tersedia.";
                
            $this->sendNotification('warning', 'Jumlah Disesuaikan', $message);
        }

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
                $this->sendNotification('warning', 'Cart Updated', 'Some items in your cart are no longer available and have been removed.');
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
        
        // Hapus flag penyesuaian agar keranjang dihitung ulang
        session()->forget('cart_adjusted');
        session()->forget('adjusted_cart_items');
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
        
        // Hapus flag penyesuaian agar keranjang dihitung ulang
        session()->forget('cart_adjusted');
        session()->forget('adjusted_cart_items');
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
            $this->sendNotification('danger', 'Error', 'Produk tidak ditemukan atau tidak tersedia.');
            return;
        }

        // Check if product has stock management enabled and is out of stock
        if ($this->usesStockManagement($product)) {
            // If stock is 0, product is unavailable
            if ($product->stock <= 0) {
                $this->sendNotification('danger', 'Stok Habis', 'Produk ini sedang tidak tersedia.');
                return;
            }
        }

        $cart = session()->get('cart', []);

        // Check if product already in cart
        $exists = false;
        foreach ($cart as &$item) {
            if ($item['id'] == $productId) {
                // If product uses stock management, check if adding more is possible
                if ($this->usesStockManagement($product)) {
                    // If stock is 1 or less, prevent adding more
                    if ($product->stock <= 1) {
                        $this->sendNotification('warning', 'Stok Terbatas', 'Stok produk tidak cukup untuk menambah jumlah.');
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
            // Special check for stock=1 items to prevent adding new items with only 1 stock left
            if ($this->usesStockManagement($product) && $product->stock == 1) {
                $cart[] = [
                    'id' => $productId,
                    'quantity' => 1
                ];
                
                $this->handleProductStock($product, 1);
                
                session()->put('cart', $cart);
                
                $this->sendNotification('success', 'Berhasil', 'Produk terakhir ditambahkan ke keranjang. Stok produk sudah habis.');
                return;
            }
            
            // Normal flow for products with more than 1 stock or no stock management
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
        
        // Hapus flag penyesuaian agar keranjang dihitung ulang
        session()->forget('cart_adjusted');
        session()->forget('adjusted_cart_items');

        // Refresh the product data from the database to get the updated stock
        $product->refresh();

        // Customize notification message based on promotion
        $notificationMessage = 'Produk ditambahkan ke keranjang.';
        if ($product->promo_text) {
            $notificationMessage = 'Produk ditambahkan ke keranjang. ' . $product->promo_text;
        }
        
        // Add stock information to notification if stock management is enabled
        if ($this->usesStockManagement($product)) {
            if ($product->stock > 0 && $product->stock <= 5) {
                $notificationMessage .= " Sisa stok: {$product->stock}.";
            }
        }

        $this->sendNotification('success', 'Berhasil', $notificationMessage);
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
            $this->sendNotification('danger', 'Error', 'Produk tidak ditemukan atau tidak tersedia.');
            return;
        }

        // If product has stock management and stock is 0 or 1, prevent adding more
        if ($this->usesStockManagement($product) && $product->stock <= 1) {
            $this->sendNotification('warning', 'Stok Terbatas', 'Stok produk tidak cukup untuk menambah jumlah.');
            return;
        }

        $cart = session()->get('cart', []);
        $updated = false;
        
        foreach ($cart as &$item) {
            if ($item['id'] == $productId) {
                // Increase quantity
                $item['quantity']++;
                
                // Handle stock reduction in the database
                if ($this->usesStockManagement($product)) {
                    $this->handleProductStock($product, 1);
                    
                    // Refresh the product to get the updated stock
                    $product->refresh();
                }
                
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            session()->put('cart', $cart);
            
            // Hapus flag penyesuaian agar keranjang dihitung ulang
            session()->forget('cart_adjusted');
            session()->forget('adjusted_cart_items');
            
            // Only show notification if update was actually performed
            if ($this->usesStockManagement($product)) {
                $notificationMsg = "Jumlah ditambah.";
                if ($product->stock > 0 && $product->stock <= 5) {
                    $notificationMsg .= " Sisa stok: {$product->stock}.";
                }
                
                $this->sendNotification('success', 'Berhasil', $notificationMsg);
            } else {
                $this->sendNotification('success', 'Berhasil', 'Jumlah ditambah.');
            }
        }
    }

    public function decreaseQuantity($productId)
    {
        // Check if product belongs to current user and is visible
        $product = $this->getVisibleProduct($productId);
        if (!$product) {
            $this->sendNotification('danger', 'Error', 'Produk tidak ditemukan atau tidak tersedia.');
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
                
                // Hapus flag penyesuaian agar keranjang dihitung ulang
                session()->forget('cart_adjusted');
                session()->forget('adjusted_cart_items');
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
        
        // Hapus flag penyesuaian agar keranjang dihitung ulang
        session()->forget('cart_adjusted');
        session()->forget('adjusted_cart_items');
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
        
        // Hapus flag penyesuaian agar keranjang dihitung ulang
        session()->forget('cart_adjusted');
        session()->forget('adjusted_cart_items');
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