<!-- resources/views/pdf/purchases.blade.php -->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; }
        .header { margin-bottom: 20px; }
        .footer { margin-top: 20px; font-size: 10px; text-align: center; color: #666; }
        .user-info { margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h3>Riwayat Pembelian</h3>
        <div class="user-info">
            <!-- Show user information -->
            @if(Auth::check())
            <p>
                <strong>Akun:</strong> {{ Auth::user()->name }}<br>
                <strong>Email:</strong> {{ Auth::user()->email }}<br>
                <strong>Tanggal Cetak:</strong> {{ now()->format('d/m/Y H:i') }}
            </p>
            @endif
        </div>
    </div>

    @if($purchases->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($purchases as $index => $purchase)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $purchase->product_name }}</td>
                        <td>Rp{{ number_format($purchase->price, 0, ',', '.') }}</td>
                        <td>{{ $purchase->quantity }}</td>
                        <td>Rp{{ number_format($purchase->total_price, 0, ',', '.') }}</td>
                        <td>{{ $purchase->purchased_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right;"><strong>Total:</strong></td>
                    <td colspan="2"><strong>Rp{{ number_format($purchases->sum('total_price'), 0, ',', '.') }}</strong></td>
                </tr>
            </tfoot>
        </table>
    @else
        <p>Tidak ada riwayat pembelian untuk ditampilkan.</p>
    @endif
    
    <div class="footer">
        <p>Dokumen ini dicetak pada {{ now()->format('d F Y H:i:s') }} dan hanya menampilkan transaksi dari akun Anda.</p>
    </div>
</body>
</html>