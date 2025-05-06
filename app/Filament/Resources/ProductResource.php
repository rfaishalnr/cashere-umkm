<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $navigationLabel = 'Tambah Produk';

    public static ?string $label = 'Tambah Produk';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('image')
                ->label('Foto Produk')
                ->image()
                ->directory('products')
                ->disk('public')
                ->downloadable()
                ->openable()
                ->maxSize(1024)
                ->columnSpanFull(),

            Forms\Components\TextInput::make('name')
                ->label('Nama Produk')
                ->required(),

            Forms\Components\TextInput::make('price')
                ->label('Harga Normal')
                ->numeric()
                ->required()
                ->minValue(1),

            Forms\Components\Section::make('Pengaturan Promo')
                ->schema([
                    Forms\Components\TextInput::make('promo_price')
                        ->label('Harga Promo')
                        ->helperText('Harga spesial/promosi untuk produk ini')
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),

                    Forms\Components\Toggle::make('is_promo_active')
                        ->label('Aktifkan Harga Promo')
                        ->helperText('Aktifkan untuk menampilkan harga promo kepada pelanggan')
                        ->reactive()
                        ->default(false),
                ])
                ->collapsible(),

            Forms\Components\TextInput::make('stock')
                ->label('Stok Produk (Jika diperlukan)')
                ->numeric()
                ->minValue(0)
                ->nullable(),

            Forms\Components\Toggle::make('is_visible')
                ->label('Tampilkan Produk')
                ->helperText('Produk akan ditampilkan jika diaktifkan')
                ->default(true),

            Forms\Components\Select::make('category')
                ->label('Kategori')
                ->options(function() {
                    // Only get categories from the current user's products
                    return Product::where('user_id', Auth::id())
                        ->distinct()
                        ->pluck('category')
                        ->filter()
                        ->mapWithKeys(function ($category) {
                            return [$category => $category];
                        })
                        ->toArray();
                })
                ->searchable()
                ->createOptionForm([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Kategori')
                        ->required(),
                ])
                ->createOptionUsing(fn(array $data) => $data['name']),

            Forms\Components\Textarea::make('description')
                ->label('Deskripsi')
                ->rows(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Foto')
                    ->square()
                    ->size(100),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga Normal')
                    ->money('IDR'),
                    
                Tables\Columns\TextColumn::make('promo_price')
                    ->label('Harga Promo')
                    ->money('IDR')
                    ->default('-'),
                    
                IconColumn::make('is_promo_active')
                    ->label('Promo')
                    ->boolean()
                    ->trueIcon('heroicon-o-tag')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Diskon')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "-{$state}%" : '-')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                    
                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->default('-')
                    ->toggleable(),
                    
                IconColumn::make('is_visible')
                    ->label('Ditampilkan')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Filter Kategori')
                    ->options(function() {
                        // Filter categories by current user
                        return Product::where('user_id', Auth::id())
                            ->pluck('category', 'category')
                            ->unique()
                            ->toArray();
                    }),
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Visibility')
                    ->placeholder('Semua Produk')
                    ->trueLabel('Produk Ditampilkan')
                    ->falseLabel('Produk Disembunyikan'),
                Tables\Filters\TernaryFilter::make('is_promo_active')
                    ->label('Status Promo')
                    ->placeholder('Semua Produk')
                    ->trueLabel('Promo Aktif')
                    ->falseLabel('Promo Tidak Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('toggleVisibility')
                    ->label(fn (Product $record): string => $record->is_visible ? 'Sembunyikan' : 'Tampilkan')
                    ->icon(fn (Product $record): string => $record->is_visible ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn (Product $record): string => $record->is_visible ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (Product $record): void {
                        $record->update(['is_visible' => !$record->is_visible]);
                        
                        $status = $record->is_visible ? 'ditampilkan' : 'disembunyikan';
                        
                        Notification::make()
                            ->title("Produk berhasil {$status}")
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\Action::make('togglePromo')
                    ->label(fn (Product $record): string => $record->is_promo_active ? 'Nonaktifkan Promo' : 'Aktifkan Promo')
                    ->icon(fn (Product $record): string => $record->is_promo_active ? 'heroicon-o-x-mark' : 'heroicon-o-tag')
                    ->color(fn (Product $record): string => $record->is_promo_active ? 'danger' : 'success')
                    ->hidden(fn (Product $record): bool => $record->promo_price === null)
                    ->requiresConfirmation()
                    ->action(function (Product $record): void {
                        if ($record->promo_price === null) {
                            Notification::make()
                                ->title("Gagal mengubah status promo")
                                ->body("Harga promo belum diatur. Silakan atur harga promo terlebih dahulu.")
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        $record->update(['is_promo_active' => !$record->is_promo_active]);
                        
                        $status = $record->is_promo_active ? 'diaktifkan' : 'dinonaktifkan';
                        
                        Notification::make()
                            ->title("Promo berhasil {$status}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('updateCategory')
                        ->label('Ubah Kategori')
                        ->icon('heroicon-o-pencil')
                        ->form([
                            Forms\Components\Select::make('new_category')
                                ->label('Kategori Baru')
                                ->options(function() {
                                    // Filter categories by current user
                                    return Product::where('user_id', Auth::id())
                                        ->distinct()
                                        ->pluck('category')
                                        ->filter()
                                        ->mapWithKeys(function ($category) {
                                            return [$category => $category];
                                        })
                                        ->toArray();
                                })
                                ->searchable()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nama Kategori')
                                        ->required(),
                                ])
                                ->createOptionUsing(fn(array $data) => $data['name'])
                                ->required(),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'category' => $data['new_category'],
                                ]);
                            }

                            Notification::make()
                                ->title('Kategori produk berhasil diperbarui')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deleteCategory')
                        ->label('Hapus Kategori')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Kategori')
                        ->modalDescription('Semua produk dengan kategori ini akan menjadi "Tanpa Kategori". Lanjutkan?')
                        ->modalSubmitActionLabel('Ya, Hapus Kategori')
                        ->action(function ($records) {
                            $category = $records->first()->category;

                            if ($records->pluck('category')->unique()->count() > 1) {
                                Notification::make()
                                    ->title('Gagal menghapus kategori')
                                    ->body('Semua produk yang dipilih harus dari kategori yang sama.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            // Only update products that belong to the current user
                            Product::where('category', $category)
                                ->where('user_id', Auth::id())
                                ->update([
                                    'category' => 'Tanpa Kategori',
                                ]);

                            Notification::make()
                                ->title("Kategori '{$category}' berhasil dihapus")
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('toggleVisibility')
                        ->label('Toggle Visibility')
                        ->icon('heroicon-o-eye')
                        ->form([
                            Forms\Components\Toggle::make('visibility')
                                ->label('Tampilkan Produk')
                                ->default(true),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'is_visible' => $data['visibility'],
                                ]);
                            }
                            
                            $status = $data['visibility'] ? 'ditampilkan' : 'disembunyikan';
                            
                            Notification::make()
                                ->title("Produk berhasil {$status}")
                                ->success()
                                ->send();
                        }),
                        
                    Tables\Actions\BulkAction::make('togglePromo')
                        ->label('Toggle Promo')
                        ->icon('heroicon-o-tag')
                        ->form([
                            Forms\Components\Toggle::make('is_promo_active')
                                ->label('Aktifkan Promo')
                                ->default(true),
                        ])
                        ->action(function (array $data, $records) {
                            $failedProducts = [];
                            
                            foreach ($records as $record) {
                                if ($record->promo_price === null) {
                                    $failedProducts[] = $record->name;
                                    continue;
                                }
                                
                                $record->update([
                                    'is_promo_active' => $data['is_promo_active'],
                                ]);
                            }
                            
                            $status = $data['is_promo_active'] ? 'diaktifkan' : 'dinonaktifkan';
                            
                            if (!empty($failedProducts)) {
                                Notification::make()
                                    ->title("Beberapa produk gagal diubah status promonya")
                                    ->body("Produk berikut tidak memiliki harga promo: " . implode(', ', $failedProducts))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title("Promo berhasil {$status}")
                                    ->success()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
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

    // This ensures that users can only see their own products in the resource
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Auth::id());
    }
}