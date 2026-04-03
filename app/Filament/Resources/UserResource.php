<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados Pessoais')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')->password()->dehydrateStateUsing(fn ($s) => bcrypt($s))->dehydrated(fn ($s) => filled($s))->required(fn ($c) => $c->getOperation() === 'create'),
            ])->columns(2),
            Forms\Components\Section::make('Assinatura')->schema([
                Forms\Components\Select::make('status')->options([
                    'lead' => 'Lead',
                    'pending' => 'Pendente',
                    'active' => 'Ativo',
                    'cancelled' => 'Cancelado',
                    'test' => 'Teste',
                ])->required(),
                Forms\Components\Select::make('plan_type')->options([
                    'monthly' => 'Mensal',
                    'yearly' => 'Anual',
                ])->nullable(),
                Forms\Components\TextInput::make('payment_subscription_id')->label('ID da Assinatura MP')->nullable(),
                Forms\Components\DateTimePicker::make('activated_at')->nullable(),
                Forms\Components\DateTimePicker::make('cancelled_at')->nullable(),
            ])->columns(2),
            Forms\Components\Section::make('Telegram')->schema([
                Forms\Components\TextInput::make('telegram_id')->nullable(),
                Forms\Components\TextInput::make('telegram_username')->nullable(),
                Forms\Components\DateTimePicker::make('telegram_linked_at')->nullable(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'success' => 'active',
                    'warning' => ['pending', 'lead'],
                    'danger' => 'cancelled',
                    'info' => 'test',
                ]),
                Tables\Columns\TextColumn::make('telegram_id')->label('Telegram ID'),
                Tables\Columns\TextColumn::make('activated_at')->dateTime('d/m/Y H:i')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'lead' => 'Lead', 'pending' => 'Pendente', 'active' => 'Ativo',
                    'cancelled' => 'Cancelado', 'test' => 'Teste',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
