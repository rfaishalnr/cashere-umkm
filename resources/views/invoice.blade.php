<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-5 text-gray-800 font-sans">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-8 border-b-2 border-gray-800 pb-3">
            <h1 class="text-2xl font-bold mb-1">Invoice Pembelian</h1>
            <p>Tanggal Cetak: {{ date('d F Y') }}</p>
        </div>

        @php
            $totalAmount = $purchase->total_price;
        @endphp

        <div class="bg-gray-100 border border-gray-300 rounded-lg p-4 mb-8">
            <div class="flex justify-between mb-2">
                <span>Total Invoice:</span>
                <span>1</span>
            </div>
            <div class="flex justify-between">
                <span>Total Nilai:</span>
                <span>Rp{{ number_format($totalAmount, 0, ',', '.') }}</span>
            </div>
        </div>
        
        <div class="border border-gray-300 rounded-lg p-5 mb-10">
            <div class="flex justify-between border-b border-gray-300 pb-3 mb-4">
                <div>
                    <h2 class="text-xl font-bold">INVOICE</h2>
                    <p>No. #{{ $purchase->id }}</p>
                </div>
                <div class="text-right">
                    <p>Tanggal: {{ $purchase->purchased_at->format('d F Y') }}</p>
                    <p>Waktu: {{ $purchase->purchased_at->format('H:i') }} WIB</p>
                </div>
            </div>

            <div class="mb-4">
                @if($purchase->customer_name)
                    <p class="mb-1"><strong>Nama Pelanggan:</strong> {{ $purchase->customer_name }} ({{ $purchase->customer_id }})</p>
                @endif
                
                @if($purchase->payment_method)
                    <p class="mb-1"><strong>Metode Pembayaran:</strong> {{ $purchase->payment_method }}</p>
                @endif
                
                @if($purchase->order_type)
                    <p><strong>Tipe Pesanan:</strong> {{ $purchase->order_type }}</p>
                @endif
            </div>

            <table class="w-full border-collapse mb-4">
                <thead>
                    <tr>
                        <th class="border border-gray-300 p-2 text-left bg-gray-100">Produk</th>
                        <th class="border border-gray-300 p-2 text-left bg-gray-100">Harga Satuan</th>
                        <th class="border border-gray-300 p-2 text-left bg-gray-100">Jumlah</th>
                        <th class="border border-gray-300 p-2 text-left bg-gray-100">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border border-gray-300 p-2">{{ $purchase->product_name }}</td>
                        <td class="border border-gray-300 p-2">Rp{{ number_format($purchase->price, 0, ',', '.') }}</td>
                        <td class="border border-gray-300 p-2">{{ $purchase->quantity }} pcs</td>
                        <td class="border border-gray-300 p-2">Rp{{ number_format($purchase->total_price, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="font-bold bg-gray-100">
                        <td colspan="3" class="border border-gray-300 p-2 text-right"><strong>TOTAL PEMBAYARAN</strong></td>
                        <td class="border border-gray-300 p-2">Rp{{ number_format($purchase->total_price, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="border-t border-gray-300 pt-3 text-center italic">
                <p>Terima Kasih Atas Pembelian Anda</p>
                {{-- <p>Jika ada pertanyaan mengenai invoice ini, silakan hubungi customer service kami.</p> --}}
            </div>
        </div>
    </div>
</body>
</html>