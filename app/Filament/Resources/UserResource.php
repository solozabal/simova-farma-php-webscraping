<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Recurso Filament para gerenciamento de usuários/assinantes.
 *
 * Módulo CRM: visualize e gerencie os assinantes, altere status,
 * gere links de vinculação com o Telegram, visualize dados de pagamento.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon  = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Assinantes';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int    $navigationSort  = 1;

    protected static ?string $modelLabel       = 'Assinante';
    protected static ?string $pluralModelLabel = 'Assinantes';

    // -------------------------------------------------------------------------
    // Formulário
    // -------------------------------------------------------------------------

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados Pessoais')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->nullable()
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Acesso e Status')->schema([
                Forms\Components\Select::make('role')
                    ->label('Papel')
                    ->options([
                        'admin'      => 'Administrador',
                        'subscriber' => 'Assinante',
                    ])
                    ->required()
                    ->default('subscriber'),

                Forms\Components\Select::make('status')
                    ->label('Status da Assinatura')
                    ->options([
                        'lead'      => 'Lead (Interessado)',
                        'pending'   => 'Pendente (Aguardando Pagamento)',
                        'active'    => 'Ativo',
                        'cancelled' => 'Cancelado',
                        'test'      => 'Teste Interno',
                    ])
                    ->required()
                    ->default('lead'),
            ])->columns(2),

            Forms\Components\Section::make('Telegram')->schema([
                Forms\Components\TextInput::make('telegram_id')
                    ->label('ID Telegram')
                    ->nullable()
                    ->maxLength(50),

                Forms\Components\TextInput::make('telegram_username')
                    ->label('Username Telegram')
                    ->nullable()
                    ->prefix('@')
                    ->maxLength(100),

                Forms\Components\DateTimePicker::make('telegram_linked_at')
                    ->label('Vinculado em')
                    ->nullable()
                    ->disabled(),
            ])->columns(3),

            Forms\Components\Section::make('Pagamento')->schema([
                Forms\Components\TextInput::make('payment_provider')
                    ->label('Provedor')
                    ->nullable()
                    ->disabled(),

                Forms\Components\TextInput::make('payment_customer_id')
                    ->label('ID do Cliente')
                    ->nullable()
                    ->disabled(),

                Forms\Components\TextInput::make('payment_subscription_id')
                    ->label('ID da Assinatura')
                    ->nullable()
                    ->disabled(),

                Forms\Components\DateTimePicker::make('activated_at')
                    ->label('Ativado em')
                    ->nullable()
                    ->disabled(),

                Forms\Components\DateTimePicker::make('cancelled_at')
                    ->label('Cancelado em')
                    ->nullable()
                    ->disabled(),
            ])->columns(3),
        ]);
    }

    // -------------------------------------------------------------------------
    // Tabela
    // -------------------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'lead',
                        'warning'   => 'pending',
                        'success'   => 'active',
                        'danger'    => 'cancelled',
                        'primary'   => 'test',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'lead'      => 'Lead',
                        'pending'   => 'Pendente',
                        'active'    => 'Ativo',
                        'cancelled' => 'Cancelado',
                        'test'      => 'Teste',
                        default     => $state,
                    }),

                Tables\Columns\IconColumn::make('telegram_id')
                    ->label('Telegram')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('payment_subscription_id')
                    ->label('Assinatura MP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('activated_at')
                    ->label('Ativado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'lead'      => 'Lead',
                        'pending'   => 'Pendente',
                        'active'    => 'Ativo',
                        'cancelled' => 'Cancelado',
                        'test'      => 'Teste',
                    ]),

                Tables\Filters\SelectFilter::make('role')
                    ->label('Papel')
                    ->options([
                        'admin'      => 'Admin',
                        'subscriber' => 'Assinante',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Ação para gerar link de vinculação com o Telegram
                Tables\Actions\Action::make('gerar_link_telegram')
                    ->label('Link Telegram')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->action(function (User $record) {
                        $token  = $record->generateTelegramLinkToken();
                        $botUrl = config('services.telegram.bot_username')
                            ? "https://t.me/" . config('services.telegram.bot_username') . "?start={$token}"
                            : "Token: {$token}";

                        \Filament\Notifications\Notification::make()
                            ->title('Link de vinculação gerado!')
                            ->body("Envie este link para o assinante:\n{$botUrl}")
                            ->success()
                            ->persistent()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Gerar Link de Vinculação Telegram')
                    ->modalDescription('Isso gerará um novo link de vinculação válido por 24 horas.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // -------------------------------------------------------------------------
    // Páginas
    // -------------------------------------------------------------------------

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
