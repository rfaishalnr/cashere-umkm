<x-filament::page>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2">
            <x-filament::icon name="heroicon-o-shopping-cart" class="w-6 h-6 text-primary-500" />
            <h1 class="text-2xl font-bold">Checkout</h1>
        </div>
        <x-filament::button color="primary" icon="heroicon-o-arrow-left" tag="a"
            href="{{ route('filament.admin.pages.shop') }}">
            Kembali ke Toko
        </x-filament::button>
    </div>

    {{-- Notifikasi --}}
    @if (session()->has('success'))
        <div class="p-4 bg-green-100 text-green-700 rounded-xl mb-4 shadow-md flex items-center gap-2">
            <x-filament::icon name="heroicon-o-check-circle" class="w-5 h-5" />
            <span>{{ session('success') }}</span>
        </div>
    @elseif (session()->has('error'))
        <div class="p-4 bg-red-100 text-red-700 rounded-xl mb-4 shadow-md flex items-center gap-2">
            <x-filament::icon name="heroicon-o-exclamation-circle" class="w-5 h-5" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Daftar Item --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 bg-gray-50 border-b">
                    <h2 class="font-medium text-lg">Daftar Pesanan</h2>
                </div>

                @forelse ($this->getCartItems() as $item)
                    <div class="p-4 border-b last:border-0 hover:bg-gray-50 transition-colors">
                        <div class="flex items-center gap-4">
                            {{-- Gambar Produk --}}
                            @if ($item['product']->image)
                                <img src="{{ asset('storage/' . $item['product']->image) }}"
                                    alt="{{ $item['product']->name }}" class="w-16 h-16 rounded-lg object-cover" />
                            @else
                                <div
                                    class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                                    <x-filament::icon name="heroicon-o-photograph" class="w-8 h-8" />
                                </div>
                            @endif

                            <div class="flex-grow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-semibold text-base">{{ $item['product']->name }}</h3>
                                        <p class="text-sm text-gray-500">
                                            {{ $item['product']->category ?? '' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-red-600">
                                            Rp{{ number_format($item['subtotal'], 0, ',', '.') }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            @if (isset($item['is_promo']) && $item['is_promo'])
                                                <span class="line-through text-gray-400">
                                                    Rp{{ number_format($item['original_price'], 0, ',', '.') }}
                                                </span>
                                                <span class="text-green-600 font-semibold ml-1">
                                                    Rp{{ number_format($item['price'], 0, ',', '.') }}
                                                </span> ×
                                                {{ $item['quantity'] }}
                                                <span class="ml-1 bg-green-100 text-green-700 px-1 py-0.5 rounded text-xs">Promo</span>
                                            @else
                                                Rp{{ number_format($item['price'], 0, ',', '.') }} ×
                                                {{ $item['quantity'] }}
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-2 flex justify-between items-center">
                                    <div></div>
                                    <div class="flex gap-2 items-center">
                                        <div class="inline-flex items-center rounded-lg border border-gray-300">
                                            <button type="button"
                                                wire:click="decrementQuantity('{{ $item['product']->id }}')"
                                                class="px-2 py-1 text-gray-500 hover:text-primary-500">
                                                <x-filament::icon name="heroicon-o-minus" class="w-4 h-4" />
                                            </button>
                                            <span class="px-3 py-1">{{ $item['quantity'] }}</span>
                                            <button type="button"
                                                wire:click="incrementQuantity('{{ $item['product']->id }}')"
                                                class="px-2 py-1 text-gray-500 hover:text-primary-500">
                                                <x-filament::icon name="heroicon-o-plus" class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <button type="button"
                                            wire:click="removeFromCart('{{ $item['product']->id }}')"
                                            class="p-1 text-gray-400 hover:text-red-500">
                                            <x-filament::icon name="heroicon-o-trash" class="w-5 h-5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <div class="inline-flex rounded-full bg-gray-100 p-4 mb-4">
                            <x-filament::icon name="heroicon-o-shopping-cart" class="w-6 h-6 text-gray-500" />
                        </div>
                        <p class="text-gray-500 mb-4">Tidak ada item di keranjang.</p>
                        <x-filament::button tag="a" href="{{ route('filament.admin.pages.shop') }}"
                            color="secondary">
                            Mulai Belanja
                        </x-filament::button>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Ringkasan & Form Pembayaran --}}
        <div class="lg:col-span-1">
            @if (count($this->getCartItems()) > 0)
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="p-4 bg-gray-50 border-b">
                        <h2 class="font-medium text-lg">Ringkasan Pesanan</h2>
                    </div>

                    <div class="p-4 space-y-4">
                        {{-- Input Nama Pelanggan --}}
                        {{-- <div>
                            <label for="customerName" class="block text-sm font-medium text-gray-700">Nama
                                Pelanggan</label>
                            <input type="text" wire:model="customerName" id="customerName"
                                class="w-full mt-1 border-gray-300 rounded-lg shadow-sm" placeholder="(opsional)" />
                        </div> --}}

                        {{-- Select Payment Method --}}
                        <div>
                            <label for="paymentMethod" class="block text-sm font-medium text-gray-700">Metode
                                Pembayaran</label>
                            <select id="paymentMethod" wire:model="paymentMethod"
                                class="w-full mt-1 border-gray-300 rounded-lg shadow-sm">
                                <option value="cash">Cash</option>
                                {{-- <option value="qris">QRIS</option> --}}
                            </select>
                        </div>

                        {{-- Select Order Type --}}
                        <div>
                            <label for="orderType" class="block text-sm font-medium text-gray-700">Tipe Pesanan</label>
                            <select id="orderType" wire:model="orderType"
                                class="w-full mt-1 border-gray-300 rounded-lg shadow-sm">
                                <option value="Makan di Tempat">Makan di Tempat</option>
                                <option value="takeaway">Takeaway</option>
                            </select>
                        </div>

                        {{-- Ringkasan Total --}}
                        @php
                            $subtotal = collect($this->getCartItems())->sum(fn($item) => $item['subtotal']);
                            // $tax = $subtotal * 0.1;
                            $total = $subtotal;
                            $totalSavings = collect($this->getCartItems())
                                ->filter(fn($item) => isset($item['is_promo']) && $item['is_promo'])
                                ->sum(fn($item) => ($item['original_price'] - $item['price']) * $item['quantity']);
                        @endphp

                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span>Rp{{ number_format($subtotal, 0, ',', '.') }}</span>
                            </div>
                            
                            @if($totalSavings > 0)
                            <div class="flex justify-between text-sm">
                                <span class="text-green-600">Penghematan</span>
                                <span class="text-green-600">-Rp{{ number_format($totalSavings, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            
                            {{-- <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Pajak (10%)</span>
                                <span>Rp{{ number_format($tax, 0, ',', '.') }}</span>
                            </div> --}}
                            <div class="border-t pt-3 mt-3">
                                <div class="flex justify-between font-bold">
                                    <span>Total</span>
                                    <span class="text-primary-600">
                                        Rp{{ number_format($total, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <x-filament::button wire:click="completeCheckout" class="w-full py-3 text-base">
                            <x-filament::icon name="heroicon-s-cash" class="w-5 h-5 mr-1" />
                            Bayar Sekarang
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Sukses --}}
    <x-filament::modal id="successModal" 
        :open="$showSuccessModal" 
        width="md" 
        align="center"
        :close-button="false">
        <x-slot name="header">
            <div class="flex items-center gap-2">
                <x-filament::icon name="heroicon-o-check-circle" class="w-8 h-8 text-success-500" />
                <h2 class="text-xl font-bold text-success-700">Pembayaran Berhasil!</h2>
            </div>
        </x-slot>

        <div class="py-4 text-center">
            <div class="flex justify-center mb-4">
                <div class="rounded-full bg-success-100 p-4">
                    <x-filament::icon name="heroicon-o-check-circle" class="w-12 h-12 text-success-500" />
                </div>
            </div>

            <p class="text-lg mb-4">Pesanan berhasil diproses</p>

            <div class="bg-gray-50 p-4 rounded-lg mb-4">
                <p class="text-sm text-gray-500 mb-1">Total Pembayaran</p>
                <p class="text-2xl font-bold text-primary-600">Rp{{ number_format($lastOrderTotal, 0, ',', '.') }}</p>
            </div>

            <div class="text-sm text-gray-500 mb-4">
                <p>Terima kasih atas pembelian Anda!</p>
                <p>Silakan tunggu pesanan Anda akan segera diproses.</p>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-center">
                <x-filament::button color="success" wire:click="closeSuccessModalAndRedirect" class="w-full">
                    <x-filament::icon name="heroicon-o-shopping-bag" class="w-5 h-5 mr-1" />
                    Kembali ke Toko
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
</x-filament::page>