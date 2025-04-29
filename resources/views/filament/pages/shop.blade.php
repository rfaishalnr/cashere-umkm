@php
    $columns = 5;
    $perPageOptions = [10, 20, 30];
    $perPage = request()->get('perPage', 10);
    $categories = $this->getProducts()->groupBy('category');
    $activeCategory = request()->get('category', 'all');

    $displayProducts = $this->getProducts();
    if ($activeCategory !== 'all') {
        $displayProducts = $displayProducts->where('category', $activeCategory);
    }
    $paginatedProducts = $displayProducts->forPage(request()->get('page', 1), $perPage);
    $paginator = new Illuminate\Pagination\LengthAwarePaginator(
        $paginatedProducts,
        $displayProducts->count(),
        $perPage,
        request()->get('page', 1),
        ['path' => request()->url(), 'query' => request()->query()]
    );
@endphp

<x-filament::page>
    @if (session()->has('success'))
        <div class="bg-green-500 text-white p-4 rounded-lg shadow-md mb-4 flex items-center animate-bounce">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6 mx-4 sm:mx-6 lg:mx-8">
        {{-- Products Section --}}
        <div class="w-full lg:w-3/4">
            {{-- Category Tabs --}}
            <div class="mb-6">
                <div class="flex flex-wrap gap-2 mb-4">
                    <a href="{{ request()->fullUrlWithQuery(['category' => 'all']) }}" 
                       class="px-4 py-2 text-sm rounded-full {{ $activeCategory == 'all' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        Semua
                    </a>

                    @foreach($categories as $category => $products)
                        <a href="{{ request()->fullUrlWithQuery(['category' => $category]) }}" 
                           class="px-4 py-2 text-sm rounded-full {{ $activeCategory == $category ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                            {{ $category ?: 'Uncategorized' }}
                        </a>
                    @endforeach
                </div>

                {{-- Per Page Selector --}}
                <div class="flex items-center gap-2">
                    <label for="perPage" class="text-sm text-gray-600">Produk per halaman:</label>
                    <select id="perPage" onchange="location.href='{{ request()->url() }}?category={{ $activeCategory }}&perPage='+this.value" class="border-gray-300 rounded text-sm">
                        @foreach($perPageOptions as $option)
                            <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Product Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-{{ $columns }} gap-4">
                @foreach ($paginator as $product)
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-transform duration-200 transform hover:scale-105 overflow-hidden flex flex-col border border-gray-100 cursor-pointer"
                        wire:click="addToCart({{ $product->id }})">
                        {{-- Product Image --}}
                        <div class="relative w-full overflow-hidden flex-shrink-0" style="height: 150px;">
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                class="w-full h-full object-cover object-center" />

                            {{-- Category Badge --}}
                            @if($product->category)
                                <div class="absolute top-2 right-2">
                                    <span class="bg-primary-100 text-primary-700 text-xs px-2 py-1 rounded-full">
                                        {{ $product->category }}
                                    </span>
                                </div>
                            @endif
                            
                            {{-- Promotion Badge --}}
                            @if($product->is_featured)
                                <div class="absolute top-2 left-2">
                                    <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                                        Featured
                                    </span>
                                </div>
                            @endif
                            
                            {{-- Discount Badge --}}
                            @if($product->discount_percent > 0)
                                <div class="absolute bottom-2 left-2">
                                    <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                        -{{ $product->discount_percent }}%
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Product details --}}
                        <div class="p-3 flex flex-col justify-between flex-grow">
                            <div>
                                <h3 class="font-medium text-sm line-clamp-1 text-gray-800">{{ $product->name }}</h3>
                                
                                {{-- Enhanced Price Display --}}
                                @php
                                    $originalPrice = $product->original_price ?? $product->price * (100 / (100 - $product->discount_percent));
                                @endphp
                                
                                <div class="mt-1">
                                    @if($product->discount_percent > 0)
                                        <div class="flex items-center gap-2">
                                            <p class="text-gray-500 text-xs line-through">
                                                Rp{{ number_format($originalPrice, 0, ',', '.') }}
                                            </p>
                                            <p class="text-red-600 text-sm font-bold">
                                                Rp{{ number_format($product->price, 0, ',', '.') }}
                                            </p>
                                        </div>
                                        <p class="text-green-600 text-xs mt-0.5">Hemat Rp{{ number_format($originalPrice - $product->price, 0, ',', '.') }}</p>
                                    @else
                                        <p class="text-red-600 text-sm font-bold">
                                            Rp{{ number_format($product->price, 0, ',', '.') }}
                                        </p>
                                    @endif
                                </div>
                                
                                {{-- Promotion Text --}}
                                @if($product->promo_text)
                                    <div class="flex items-center gap-1 mt-1 text-green-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <p class="text-xs">{{ $product->promo_text }}</p>
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Stock Information --}}
                            @if($product->stock !== null)
                                <div class="mt-2">
                                    <p class="text-xs {{ $product->stock > 0 ? 'text-gray-500' : 'text-red-500' }}">
                                        @if($product->stock > 0)
                                            Stok: {{ $product->stock }}
                                        @else
                                            <span>Stok Habis</span>
                                        @endif
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Empty State --}}
            @if($displayProducts->isEmpty())
                <div class="py-10 flex flex-col items-center justify-center text-center bg-white rounded-lg shadow-sm p-6">
                    <div class="bg-gray-100 p-4 rounded-full mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h7" />
                        </svg>
                    </div>
                    <p class="text-gray-600 font-medium">Tidak ada produk dalam kategori ini</p>
                    <p class="text-gray-400 text-sm mt-1">Silakan pilih kategori lain atau lihat semua produk</p>
                </div>
            @endif

            {{-- Pagination Links --}}
            <div class="mt-6">
                {{ $paginator->withQueryString()->links('vendor.pagination.tailwind') }}
            </div>
        </div>

        {{-- Cart Section --}}
        <div class="lg:w-1/4 lg:min-w-[350px] sticky top-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col" style="min-height: 500px;">
                {{-- Cart Header --}}
                <div class="p-4 border-b border-gray-100 bg-gray-50">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <div class="bg-primary-100 text-primary-600 p-1.5 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <h2 class="text-gray-800 font-semibold">Keranjang Belanja</h2>
                        </div>
                    </div>
                </div>

                {{-- Cart Content --}}
                <div class="flex-grow overflow-y-auto" style="max-height: 350px;">
                    <div class="p-4 space-y-2">
                        @forelse ($this->getCartItems() as $item)
                            <div wire:key="cart-item-{{ $item['product']->id }}"
                                class="flex items-center gap-3 py-2 border-b border-dashed border-gray-100 last:border-b-0">
                                {{-- Product Info --}}
                                <div class="flex-grow">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-medium text-sm text-gray-800">{{ $item['product']->name }}</h4>
                                            @if($item['product']->category)
                                                <span class="text-xs text-gray-500">{{ $item['product']->category }}</span>
                                            @endif
                                            
                                            {{-- Show promotion text in cart --}}
                                            @if($item['product']->promo_text)
                                                <div class="flex items-center gap-1 mt-0.5 text-green-600">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <span class="text-xs">{{ $item['product']->promo_text }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <button wire:click="removeFromCart({{ $item['product']->id }})"
                                            class="text-gray-400 hover:text-red-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="flex justify-between items-center mt-1">
                                        <div class="flex items-center gap-2">
                                            <div class="flex border border-gray-200 rounded overflow-hidden">
                                                <button wire:click="decreaseQuantity({{ $item['product']->id }})"
                                                    class="px-2 py-1 bg-gray-50 hover:bg-gray-100 text-gray-600 text-xs">−</button>
                                                <span
                                                    class="w-8 py-1 flex items-center justify-center text-xs">{{ $item['quantity'] }}</span>
                                                <button wire:click="increaseQuantity({{ $item['product']->id }})"
                                                    class="px-2 py-1 bg-gray-50 hover:bg-gray-100 text-gray-600 text-xs">+</button>
                                            </div>
                                            
                                            {{-- Enhanced Price Display in Cart --}}
                                            @php
                                                $originalPrice = $item['product']->original_price ?? 
                                                    ($item['product']->discount_percent > 0 ? 
                                                    $item['product']->price * (100 / (100 - $item['product']->discount_percent)) : 
                                                    $item['product']->price);
                                            @endphp
                                            
                                            @if($item['product']->discount_percent > 0)
                                                <span class="text-xs text-gray-500">× 
                                                    <span class="line-through">Rp{{ number_format($originalPrice, 0, ',', '.') }}</span>
                                                    <span class="text-red-600">Rp{{ number_format($item['product']->price, 0, ',', '.') }}</span>
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-500">×
                                                    Rp{{ number_format($item['product']->price, 0, ',', '.') }}</span>
                                            @endif
                                        </div>
                                        <span class="text-sm font-medium">
                                            Rp{{ number_format($item['product']->price * $item['quantity'], 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-10 flex flex-col items-center justify-center text-center">
                                <div class="bg-gray-100 p-4 rounded-full mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-400" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                </div>
                                <p class="text-gray-600 font-medium">Keranjang belanja kosong</p>
                                <p class="text-gray-400 text-sm mt-1">Klik produk untuk menambahkan ke keranjang</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Cart Summary & Checkout --}}
                @if (count($this->getCartItems()) > 0)
                    <div class="border-t border-gray-100 p-4 bg-gray-50">
                        <div class="space-y-2">
                            {{-- Cart Summary with Savings Calculation --}}
                            @php
                                $subtotal = collect($this->getCartItems())->sum(fn($item) => $item['product']->price * $item['quantity']);
                                $originalSubtotal = collect($this->getCartItems())->sum(function($item) {
                                    $originalPrice = $item['product']->original_price ?? 
                                        ($item['product']->discount_percent > 0 ? 
                                            $item['product']->price * (100 / (100 - $item['product']->discount_percent)) : 
                                            $item['product']->price);
                                    return $originalPrice * $item['quantity'];
                                });
                                $totalSavings = $originalSubtotal - $subtotal;
                            @endphp
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Subtotal ({{ collect($this->getCartItems())->sum('quantity') }} item)</span>
                                <span class="font-medium">
                                    Rp{{ number_format($subtotal, 0, ',', '.') }}
                                </span>
                            </div>
                            
                            {{-- Show Savings --}}
                            @if($totalSavings > 0)
                                <div class="flex justify-between items-center text-green-600">
                                    <span class="text-sm">Hemat</span>
                                    <span class="font-medium">
                                        Rp{{ number_format($totalSavings, 0, ',', '.') }}
                                    </span>
                                </div>
                            @endif
                            
                            <div class="flex justify-between items-center pt-2 border-t border-dashed border-gray-200">
                                <span class="text-base font-medium">Total</span>
                                <span class="text-lg font-bold text-primary-700">
                                    Rp{{ number_format($subtotal, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 p-4">
                        <x-filament::button tag="a" href="{{ route('filament.admin.pages.checkout-page') }}"
                            color="primary" class="w-full justify-center py-3 text-base">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            Checkout
                        </x-filament::button>
                    </div>
                @else
                    <div class="mt-auto border-t border-gray-100 p-4">
                        <x-filament::button tag="button" color="primary" :disabled="true"
                            class="w-full justify-center py-3 opacity-50 cursor-not-allowed">
                            Checkout
                        </x-filament::button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament::page>