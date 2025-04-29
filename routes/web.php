<?php

use Illuminate\Support\Facades\Route;
use App\Models\Purchase;
use App\Exports\PurchaseExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;


Route::get('/', function () {
    return view('welcome');
});


Route::get('/purchase/invoice/{purchase}', function (Purchase $purchase) {
    return view('invoice', compact('purchase'));
})->name('purchase.invoice');

Route::get('/purchases/bulk-invoice/{ids}', function (string $ids) {
    $purchaseIds = explode(',', $ids);
    $purchases = \App\Models\Purchase::whereIn('id', $purchaseIds)->get();
    
    return view('purchase.bulk-invoice', compact('purchases'));
})->name('purchase.bulk-invoice');

Route::get('/admin/purchases/download-pdf', function () {
    $purchases = Purchase::latest()->get();
    $pdf = Pdf::loadView('pdf.purchases', ['purchases' => $purchases]);
    return $pdf->download('riwayat_pembelian.pdf');
})->name('purchase.downloadPdf');

Route::get('/debug-image/{filename}', function ($filename) {
    $path = storage_path('app/public/products/' . $filename);
    
    if (!file_exists($path)) {
        return 'File not found: ' . $path;
    }
    
    return response()->file($path);
});

Route::get('purchases/bulk-invoice/{ids}', [Purchase::class, 'bulkInvoice'])->name('purchase.bulk-invoice');

