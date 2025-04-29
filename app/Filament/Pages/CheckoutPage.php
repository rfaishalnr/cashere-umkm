<?php

namespace App\Filament\Pages;

use App\Models\Product;
use App\Models\Purchase;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CheckoutPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'filament.pages.checkout-page';
    protected static ?string $navigationLabel = 'Checkout';
    protected static ?string $title = 'Checkout Page';

    public string $customerName = '';
    public string $paymentMethod = 'cash';
    public string $orderType = 'Makan di tempat';
    public bool $showSuccessModal = false;
    public float $lastOrderTotal = 0;

    public function getCartItems(): array
    {
        $cart = session()->get('cart', []);
        $productIds = array_column($cart, 'id');
        
        // Only fetch products owned by the current user
        $products = Product::whereIn('id', $productIds)
            ->where('user_id', Auth::id())
            ->get();

        $cartItems = [];

        foreach ($products as $product) {
            foreach ($cart as $cartItem) {
                if ($cartItem['id'] === $product->id) {
                    // Check if product has active promo and promo price
                    $price = ($product->is_promo_active && $product->promo_price > 0) 
                        ? $product->promo_price 
                        : $product->price;
                    
                    $cartItems[] = [
                        'product' => $product,
                        'quantity' => $cartItem['quantity'],
                        'price' => $price, // Store the active price
                        'subtotal' => $price * $cartItem['quantity'],
                        'is_promo' => ($product->is_promo_active && $product->promo_price > 0),
                        'original_price' => $product->price, // Store original price for reference
                    ];
                }
            }
        }

        return $cartItems;
    }

    public function incrementQuantity($productId)
    {
        // Check if product belongs to current user before incrementing
        if (!$this->verifyProductOwnership($productId)) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Produk tidak ditemukan.')
                ->send();
            return;
        }

        $cart = session()->get('cart', []);
        foreach ($cart as &$item) {
            if ($item['id'] == $productId) {
                $item['quantity']++;
            }
        }
        session()->put('cart', $cart);
    }

    public function decrementQuantity($productId)
    {
        // Check if product belongs to current user before decrementing
        if (!$this->verifyProductOwnership($productId)) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Produk tidak ditemukan.')
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

    public function completeCheckout()
    {
        $cartItems = $this->getCartItems();
        if (empty($cartItems)) {
            session()->flash('error', 'Keranjang kosong!');
            return;
        }

        if ($this->paymentMethod !== 'cash') {
            session()->flash('error', 'Saat ini hanya pembayaran tunai yang tersedia.');
            return;
        }

        $subtotal = collect($cartItems)->sum(fn($item) => $item['subtotal']);
        $tax = $subtotal * 0.1;
        $total = $subtotal + $tax;
        $this->lastOrderTotal = $total;

        foreach ($cartItems as $item) {
            $price = $item['price']; // Use the active price (regular or promo)
            
            Purchase::create([
                'product_name' => $item['product']->name,
                'price' => $price,
                'quantity' => $item['quantity'],
                'total_price' => $item['subtotal'],
                'payment_method' => $this->paymentMethod,
                'order_type' => $this->orderType,
                'customer_name' => $this->customerName,
                'purchased_at' => now(),
                'user_id' => Auth::id(), // Set the user_id for the purchase
                'is_promo' => $item['is_promo'] ?? false, // Save whether this was a promo price
            ]);
        }

        session()->forget('cart');

        // Tampilkan notifikasi sukses
        Notification::make()
            ->title('Pesanan Berhasil!')
            ->success()
            ->body('Pesanan pelanggan telah berhasil diproses.')
            ->persistent()
            ->send();

        // Redirect to the Shop page
        return redirect()->route('filament.admin.pages.shop');
    }

    protected function getViewData(): array
    {
        return [
            'showSuccessModal' => $this->showSuccessModal,
            'lastOrderTotal' => $this->lastOrderTotal,
        ];
    }

    /**
     * Verify that a product belongs to the current user
     * 
     * @param int $productId
     * @return bool
     */
    private function verifyProductOwnership($productId): bool
    {
        return Product::where('id', $productId)
            ->where('user_id', Auth::id())
            ->exists();
    }
}