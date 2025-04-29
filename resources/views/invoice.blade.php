<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $purchase->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        .invoice-box { border: 1px solid #eee; padding: 20px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <h2>Invoice #{{ $purchase->id }}</h2>
        <p>Tanggal: {{ $purchase->purchased_at->format('d/m/Y H:i') }}</p>

        <hr>

        <p>Produk: <strong>{{ $purchase->product_name }}</strong></p>
        <p>Harga: Rp{{ number_format($purchase->price, 0, ',', '.') }}</p>
        <p>Jumlah: {{ $purchase->quantity }}</p>
        <p>Total: <strong>Rp{{ number_format($purchase->total_price, 0, ',', '.') }}</strong></p>

        <hr>

        <p>Terima kasih atas pembelian Anda.</p>
    </div>

    <script>
        window.print();
    </script>
</body>
</html>
