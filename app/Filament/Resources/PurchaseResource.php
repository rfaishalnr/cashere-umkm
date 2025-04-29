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
use Illuminate\Support\Facades\Auth;

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Riwayat Pembelian';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer_id')
                    ->label('ID Pembeli')
                    ->searchable()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('customer_name')
                //     ->label('Nama Pembeli')
                //     ->searchable(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Produk')
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty'),

                Tables\Columns\TextColumn::make('total_price')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('order_type')
                    ->label('Tipe Order'),

                Tables\Columns\TextColumn::make('purchased_at')
                    ->label('Tanggal')
                    ->dateTime(),
            ])
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