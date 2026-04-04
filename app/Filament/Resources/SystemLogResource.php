<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemLogResource\Pages;
use App\Models\SystemLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Recurso Filament para visualização de logs do sistema.
 * Somente leitura — os logs são criados automaticamente pela aplicação.
 */
class SystemLogResource extends Resource
{
    protected static ?string $model = SystemLog::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Logs do Sistema';
    protected static ?string $navigationGroup = 'Sistema';
    protected static ?int    $navigationSort  = 90;

    protected static ?string $modelLabel       = 'Log';
    protected static ?string $pluralModelLabel = 'Logs do Sistema';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('level')->label('Nível')->disabled(),
            Forms\Components\TextInput::make('channel')->label('Canal')->disabled(),
            Forms\Components\Textarea::make('message')->label('Mensagem')->disabled()->columnSpanFull(),
            Forms\Components\KeyValue::make('context')->label('Contexto')->disabled()->columnSpanFull(),
            Forms\Components\TextInput::make('ip_address')->label('IP')->disabled(),
            Forms\Components\DateTimePicker::make('created_at')->label('Data/Hora')->disabled(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('level')
                    ->label('Nível')
                    ->colors([
                        'gray'    => 'debug',
                        'primary' => 'info',
                        'warning' => 'warning',
                        'danger'  => 'error',
                    ]),

                Tables\Columns\TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('message')
                    ->label('Mensagem')
                    ->searchable()
                    ->limit(80)
                    ->tooltip(fn ($record) => $record->message),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->options([
                        'debug'   => 'Debug',
                        'info'    => 'Info',
                        'warning' => 'Warning',
                        'error'   => 'Error',
                    ]),

                Tables\Filters\SelectFilter::make('channel')
                    ->options([
                        'telegram'    => 'Telegram',
                        'mercadopago' => 'Mercado Pago',
                        'scraper'     => 'Scraper',
                        'scheduler'   => 'Scheduler',
                        'app'         => 'App',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Atualiza a cada 30 segundos
    }

    public static function canCreate(): bool
    {
        return false; // Logs são somente leitura
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemLogs::route('/'),
        ];
    }
}
