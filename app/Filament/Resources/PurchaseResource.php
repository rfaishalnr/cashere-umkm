<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Models\Purchase;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Riwayat Pembelian';

    public static ?string $label = 'Riwayat Pembelian';
    
    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        // Define all available columns
        $columns = [
            'customer_id' => Tables\Columns\TextColumn::make('customer_id')
                ->label('ID Pembeli')
                ->searchable()
                ->sortable(),

            'customer_name' => Tables\Columns\TextColumn::make('customer_name')
                ->label('Nama Pembeli')
                ->searchable(),

            'product_name' => Tables\Columns\TextColumn::make('product_name')
                ->label('Produk')
                ->searchable(),

            'price' => Tables\Columns\TextColumn::make('price')
                ->money('IDR'),

            'quantity' => Tables\Columns\TextColumn::make('quantity')
                ->label('Qty'),

            'total_price' => Tables\Columns\TextColumn::make('total_price')
                ->money('IDR'),

            'order_type' => Tables\Columns\TextColumn::make('order_type')
                ->label('Tipe Order'),

            'purchased_at' => Tables\Columns\TextColumn::make('purchased_at')
                ->label('Tanggal')
                ->dateTime(),
        ];

        // Get visible columns from session or set defaults
        $visibleColumns = Session::get('purchase_visible_columns', [
            'customer_id' => true,
            'product_name' => true,
            'price' => true,
            'quantity' => true,
            'total_price' => true,
            'order_type' => true,
            'purchased_at' => true,
            'customer_name' => false,
        ]);

        // Filter columns based on visibility settings
        $activeColumns = [];
        foreach ($columns as $key => $column) {
            if (isset($visibleColumns[$key]) && $visibleColumns[$key]) {
                $activeColumns[] = $column;
            }
        }

        return $table
            ->columns($activeColumns)
            ->defaultSort('purchased_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('customer_id')
                    ->label('Filter ID Pembeli')
                    ->form([
                        TextInput::make('customer_id')
                            ->label('ID Pembeli')
                            ->placeholder('Masukkan ID Pembeli'),
                    ])
                    ->query(fn (Builder $query, array $data) => 
                        $data['customer_id'] 
                        ? $query->where('customer_id', $data['customer_id']) 
                        : $query
                    ),
                    
// Column visibility filter
Tables\Filters\Filter::make('column_visibility')
    ->label('Tampilkan Kolom')
    ->form([
        Section::make('Pengaturan Kolom')
            ->schema([
                // Remove the Grid component to display checkboxes vertically
                Checkbox::make('show_customer_id')
                    ->label('ID Pembeli')
                    ->default(function() {
                        return Session::get('purchase_visible_columns.customer_id', true);
                    }),
                Checkbox::make('show_customer_name')
                    ->label('Nama Pembeli')
                    ->default(function() {
                        return Session::get('purchase_visible_columns.customer_name', false);
                    }),
                Checkbox::make('show_product_name')
                    ->label('Produk')
                    ->default(function() {
                        return Session::get('purchase_visible_columns.product_name', true);
                    }),
                Checkbox::make('show_price')
                    ->label('Harga')
                    ->default(function() {
                        return Session::get('purchase_visible_columns.price', true);
                    }),
                Checkbox::make('show_quantity')
                    ->label('Qty')
                    ->default(function() {
                        return Session::get('purchase_visible_columns.quantity', true);
                    }),
                Checkbox::make('show_total_price')
                    ->label('Total Harga')
                    ->default(function() {
                        return Session::get('purchase_visible_columns.total_price', true);
                    }),
                Checkbox::make('show_order_type')
                    ->label('Tipe Order')
                    ->default(function() {
                        return Session::get('purchase_visible_columns.order_type', true);
                    }),
                Checkbox::make('show_purchased_at')
                    ->label('Tanggal')
                    ->default(function() {
                        return Session::get('purchase_visible_columns.purchased_at', true);
                    }),
            ]),
    ])                    ->query(function (Builder $query, array $data) {
                        // Store column visibility preferences in session
                        $visibleColumns = [
                            'customer_id' => $data['show_customer_id'] ?? true,
                            'customer_name' => $data['show_customer_name'] ?? false,
                            'product_name' => $data['show_product_name'] ?? true,
                            'price' => $data['show_price'] ?? true,
                            'quantity' => $data['show_quantity'] ?? true,
                            'total_price' => $data['show_total_price'] ?? true,
                            'order_type' => $data['show_order_type'] ?? true,
                            'purchased_at' => $data['show_purchased_at'] ?? true,
                        ];
                        
                        Session::put('purchase_visible_columns', $visibleColumns);
                        
                        // This doesn't actually filter the query, it just stores preferences
                        return $query;
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        $allColumnsVisibility = [
                            'show_customer_id' => ['label' => 'ID Pembeli', 'default' => true],
                            'show_customer_name' => ['label' => 'Nama Pembeli', 'default' => false],
                            'show_product_name' => ['label' => 'Produk', 'default' => true],
                            'show_price' => ['label' => 'Harga', 'default' => true],
                            'show_quantity' => ['label' => 'Qty', 'default' => true],
                            'show_total_price' => ['label' => 'Total Harga', 'default' => true],
                            'show_order_type' => ['label' => 'Tipe Order', 'default' => true],
                            'show_purchased_at' => ['label' => 'Tanggal', 'default' => true],
                        ];
                        
                        // Check each column and add indicator if it's different from default
                        foreach ($allColumnsVisibility as $key => $column) {
                            $isVisible = $data[$key] ?? $column['default'];
                            
                            if (!$isVisible) {
                                $indicators[] = $column['label'] . ' disembunyikan';
                            }
                        }
                        
                        return $indicators;
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('Download PDF')
                    ->label('Download PDF')
                    ->url(route('purchase.downloadPdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('Cetak Invoice')
                    ->url(fn (Purchase $record) => route('purchase.invoice', $record))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-printer'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('printInvoices')
                    ->label('Cetak Invoice')
                    ->icon('heroicon-o-printer')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->toArray();
                        $encodedIds = implode(',', $ids);
                        return redirect()->route('purchase.bulk-invoice', ['ids' => $encodedIds]);
                    })
                    ->deselectRecordsAfterCompletion(),
                
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
        ];
    }

    /**
     * Filter query to only show purchases associated with the current user
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }

    protected static function mutateFormDataBeforeSave(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }
}