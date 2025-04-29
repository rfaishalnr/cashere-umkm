<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Multiple Invoices</title>
    <style>
        body { 
            font-family: 'Arial', sans-serif; 
            margin: 30px; 
            background-color: #f9f9f9; 
        }
        .invoice-box { 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px; 
            background: white; 
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            page-break-after: always; 
        }
        .invoice-box:last-child {
            page-break-after: avoid; 
        }
        h2 { 
            color: #333; 
            font-size: 24px; 
            margin: 0 0 10px; 
        }
        p { 
            color: #555; 
            line-height: 1.6; 
        }
        .product-details {
            margin: 15px 0; 
            padding: 10px; 
            border: 1px solid #eee; 
            border-radius: 5px; 
            background: #f5f5f5; 
        }
        hr { 
            border: 0.5px solid #eee; 
            margin: 20px 0; 
        }
        .total { 
            font-weight: bold; 
            font-size: 18px; 
            color: #333; 
        }
        .footer { 
            margin-top: 20px; 
            text-align: center; 
            font-size: 14px; 
            color: #777; 
        }
    </style>
</head>
<body>
    @foreach ($purchases as $purchase)
    <div class="invoice-box">
        <h2>Invoice #{{ $purchase->id }}</h2>
        <p>Tanggal: {{ $purchase->purchased_at->format('d/m/Y H:i') }}</p>

        <hr>

        <div class="product-details">
            <p>Produk: <strong>{{ $purchase->product_name }}</strong></p>
            <p>Harga: Rp{{ number_format($purchase->price, 0, ',', '.') }}</p>
            <p>Jumlah: {{ $purchase->quantity }}</p>
            <p class="total">Total: Rp{{ number_format($purchase->total_price, 0, ',', '.') }}</p>
        </div>

        <hr>

        <p class="footer">Terima kasih atas pembelian Anda.</p>
    </div>
    @endforeach

    <script>
        window.print();
    </script>
</body>
</html>