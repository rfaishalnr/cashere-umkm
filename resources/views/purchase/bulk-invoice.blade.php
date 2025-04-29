<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin-bottom: 5px;
        }
        .summary-box {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 30px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .invoice {
            margin-bottom: 40px;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
            page-break-inside: avoid;
        }
        .invoice-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
        }
        .invoice-details {
            margin-bottom: 15px;
        }
        .invoice-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .invoice-items th, .invoice-items td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .invoice-items th {
            background-color: #f5f5f5;
        }
        .total-row {
            font-weight: bold;
            background-color: #f5f5f5;
        }
        .invoice-footer {
            border-top: 1px solid #ddd;
            padding-top: 10px;
            text-align: center;
            font-style: italic;
        }
        .page-break {
            page-break-before: always;
        }
        .actions {
            margin-bottom: 30px;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <h1>Invoice Pembelian</h1>
            <p>Tanggal Cetak: {{ date('d F Y') }}</p>
        </div>

        @php
            $invoices = isset($purchase) ? collect([$purchase]) : $purchases;
            $totalInvoices = $invoices->count();
            $totalAmount = $invoices->sum('total_price');
        @endphp

        <div class="summary-box">
            <div class="summary-row">
                <span>Total Invoice:</span>
                <span>{{ $totalInvoices }}</span>
            </div>
            <div class="summary-row">
                <span>Total Nilai:</span>
                <span>Rp{{ number_format($totalAmount, 0, ',', '.') }}</span>
            </div>
        </div>

        @if(request()->is('*admin*') || request()->is('*purchases/bulk-invoice*'))
        <div class="actions">
            <a href="#" onclick="window.print()" class="btn">Cetak Semua</a>
            <a href="{{ route('purchase.downloadPdf') }}" class="btn">Download PDF</a>
        </div>
        @endif

        @foreach($invoices as $index => $loopPurchase)
            @if($index > 0)
                <div class="page-break"></div>
            @endif
            
            <div class="invoice">
                <div class="invoice-header">
                    <div>
                        <h2>INVOICE</h2>
                        <p>No. #{{ $loopPurchase->id }}</p>
                    </div>
                    <div style="text-align: right;">
                        <p>Tanggal: {{ $loopPurchase->purchased_at->format('d F Y') }}</p>
                        <p>Waktu: {{ $loopPurchase->purchased_at->format('H:i') }} WIB</p>
                    </div>
                </div>

                <div class="invoice-details">
                    @if($loopPurchase->customer_name)
                        <p><strong>Nama Pelanggan:</strong> {{ $loopPurchase->customer_name }} ({{ $loopPurchase->customer_id }})</p>
                    @endif
                    
                    @if($loopPurchase->payment_method)
                        <p><strong>Metode Pembayaran:</strong> {{ $loopPurchase->payment_method }}</p>
                    @endif
                    
                    @if($loopPurchase->order_type)
                        <p><strong>Tipe Pesanan:</strong> {{ $loopPurchase->order_type }}</p>
                    @endif
                </div>

                <table class="invoice-items">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga Satuan</th>
                            <th>Jumlah</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $loopPurchase->product_name }}</td>
                            <td>Rp{{ number_format($loopPurchase->price, 0, ',', '.') }}</td>
                            <td>{{ $loopPurchase->quantity }} pcs</td>
                            <td>Rp{{ number_format($loopPurchase->total_price, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;"><strong>TOTAL PEMBAYARAN</strong></td>
                            <td>Rp{{ number_format($loopPurchase->total_price, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="invoice-footer">
                    <p>Terima Kasih Atas Pembelian Anda</p>
                    {{-- <p>Jika ada pertanyaan mengenai invoice ini, silakan hubungi customer service kami.</p> --}}
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>