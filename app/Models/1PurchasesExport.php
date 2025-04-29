<?php

// namespace App\Exports;

// use App\Models\Purchase;
// use Maatwebsite\Excel\Concerns\FromCollection;
// use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithMapping;

// class PurchasesExport implements FromCollection, WithHeadings, WithMapping
// {
//     public function collection()
//     {
//         return Purchase::all();
//     }

//     public function headings(): array
//     {
//         return [
//             'ID',
//             'Produk',
//             'Harga',
//             'Jumlah',
//             'Total',
//             'Tanggal Pembelian'
//         ];
//     }

//     public function map($purchase): array
//     {
//         return [
//             $purchase->id,
//             $purchase->product_name,
//             $purchase->price,
//             $purchase->quantity,
//             $purchase->total_price,
//             $purchase->purchased_at->format('d/m/Y H:i'),
//         ];
//     }
// }