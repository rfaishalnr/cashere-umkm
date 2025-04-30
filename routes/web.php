<?php

use Illuminate\Support\Facades\Route;
use App\Models\Purchase;
use App\Exports\PurchaseExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;


// Route::get('/', function () {
//     return view('welcome');
// });

Route::redirect('/', '/admin');

Route::get('/purchase/invoice/{purchase}', function (Purchase $purchase) {
    // Make sure the purchase belongs to the current user
    if (Auth::check() && $purchase->user_id !== Auth::id()) {
        abort(403, 'Unauthorized action.');
    }
    return view('invoice', compact('purchase'));
})->name('purchase.invoice');

// Route untuk bulk invoice dengan query string
Route::get('/purchase/bulk-invoice', function (\Illuminate\Http\Request $request) {
    $ids = explode(',', $request->query('ids')); // Ambil array dari query string
    
    // Only get purchases belonging to the current user
    if (Auth::check()) {
        $purchases = Purchase::whereIn('id', $ids)
            ->where('user_id', Auth::id())
            ->get();
        
        if ($purchases->isEmpty()) {
            abort(404, 'No purchases found or you are not authorized to access them.');
        }
        
        return Purchase::bulkInvoice($purchases);
    }
    
    return redirect()->route('login'); // Redirect to login if not authenticated
})->name('purchase.bulk-invoice');


Route::get('/admin/purchases/download-pdf', function () {
    // Only get purchases for the current authenticated user
    $purchases = Auth::check() 
        ? Purchase::where('user_id', Auth::id())->latest()->get()
        : collect(); // Empty collection if not authenticated
        
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

Route::get('purchases/bulk-invoice/{ids}', function($ids) {
    $idArray = explode(',', $ids);
    
    // Only get purchases belonging to the current user
    if (Auth::check()) {
        $purchases = Purchase::whereIn('id', $idArray)
            ->where('user_id', Auth::id())
            ->get();
        
        if ($purchases->isEmpty()) {
            abort(404, 'No purchases found or you are not authorized to access them.');
        }
        
        return Purchase::bulkInvoice($purchases);
    }
    
    return redirect()->route('login'); // Redirect to login if not authenticated
})->name('purchase.bulk-invoice-alt');