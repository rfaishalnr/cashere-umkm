<div class="p-4 bg-gray-50 rounded-xl shadow mt-4">
    <p><strong>Total Pembelian Bulan Ini:</strong> Rp{{ number_format($totalThisMonth, 0, ',', '.') }}</p>
    <p><strong>Produk Paling Sering Dibeli:</strong> {{ $topProduct->product_name ?? 'Belum ada data' }}</p>
</div>
