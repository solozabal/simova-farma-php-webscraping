<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemLogResource\Pages;
use App\Models\SystemLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SystemLogResource extends Resource
{
    protected static ?string $model = SystemLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int $navigationSort = 10;
    protected static ?string $modelLabel = 'Log do Sistema';
    protected static ?string $pluralModelLabel = 'Logs do Sistema';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('type')->disabled(),
            Forms\Components\TextInput::make('level')->disabled(),
            Forms\Components\Textarea::make('message')->disabled()->columnSpanFull(),
            Forms\Components\KeyValue::make('context')->disabled()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i:s')->sortable()->label('Data'),
                Tables\Columns\BadgeColumn::make('type')->colors([
                    'info' => 'collector',
                    'warning' => 'telegram',
                    'danger' => 'payment',
                    'success' => 'editorial',
                ]),
                Tables\Columns\BadgeColumn::make('level')->colors([
                    'success' => 'info',
                    'warning' => 'warning',
                    'danger' => 'error',
                ]),
                Tables\Columns\TextColumn::make('message')->limit(80)->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options([
                    'collector' => 'Coletor', 'telegram' => 'Telegram',
                    'payment' => 'Pagamento', 'editorial' => 'Editorial',
                ]),
                Tables\Filters\SelectFilter::make('level')->options([
                    'info' => 'Info', 'warning' => 'Aviso', 'error' => 'Erro',
                ]),
            ])
            ->actions([Tables\Actions\ViewAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function canCreate(): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemLogs::route('/'),
            'view' => Pages\ViewSystemLog::route('/{record}'),
        ];
    }
}
