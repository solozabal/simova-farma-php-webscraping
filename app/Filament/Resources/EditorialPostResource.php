<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditorialPostResource\Pages;
use App\Models\EditorialPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Recurso Filament para gerenciamento de posts editoriais.
 *
 * Calendário editorial:
 *   - Slot morning:   09:10 (America/Sao_Paulo)
 *   - Slot afternoon: 17:40 (America/Sao_Paulo)
 *
 * Fluxo de aprovação:
 *   draft → approved → (enviado automaticamente) → sent
 */
class EditorialPostResource extends Resource
{
    protected static ?string $model = EditorialPost::class;

    protected static ?string $navigationIcon  = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Calendário Editorial';
    protected static ?string $navigationGroup = 'Conteúdo';
    protected static ?int    $navigationSort  = 20;

    protected static ?string $modelLabel       = 'Post Editorial';
    protected static ?string $pluralModelLabel = 'Posts Editoriais';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Conteúdo do Post')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título (interno, não vai para o Telegram)')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('content')
                    ->label('Conteúdo da Mensagem Telegram (HTML)')
                    ->helperText('Suporta tags HTML básicas: <b>, <i>, <a href="...">, etc.')
                    ->rows(8)
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('content_fallback')
                    ->label('Texto Fallback (caso o conteúdo principal esteja vazio)')
                    ->rows(4)
                    ->nullable()
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Agendamento')->schema([
                Forms\Components\Select::make('slot')
                    ->label('Slot Editorial')
                    ->options([
                        'morning'   => 'Manhã (09:10)',
                        'afternoon' => 'Tarde (17:40)',
                    ])
                    ->required()
                    ->default('morning'),

                Forms\Components\DateTimePicker::make('scheduled_at')
                    ->label('Data/Hora de Envio')
                    ->required()
                    ->timezone('America/Sao_Paulo')
                    ->helperText('Use 09:10 para slot manhã ou 17:40 para slot tarde'),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Rascunho',
                        'approved'  => 'Aprovado para Envio',
                        'sent'      => 'Enviado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->required()
                    ->default('draft'),

                Forms\Components\Select::make('article_id')
                    ->label('Artigo de Origem (opcional)')
                    ->relationship('article', 'title')
                    ->searchable()
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Informações de Envio (somente leitura)')->schema([
                Forms\Components\TextInput::make('recipients_count')
                    ->label('Destinatários')
                    ->disabled(),

                Forms\Components\DateTimePicker::make('sent_at')
                    ->label('Enviado em')
                    ->disabled(),
            ])->columns(2)->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray'    => 'draft',
                        'success' => 'approved',
                        'primary' => 'sent',
                        'danger'  => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft'     => 'Rascunho',
                        'approved'  => 'Aprovado',
                        'sent'      => 'Enviado',
                        'cancelled' => 'Cancelado',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\BadgeColumn::make('slot')
                    ->label('Slot')
                    ->colors([
                        'warning' => 'morning',
                        'info'    => 'afternoon',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'morning'   => 'Manhã 09:10',
                        'afternoon' => 'Tarde 17:40',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Agendado para')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('recipients_count')
                    ->label('Destinatários')
                    ->numeric(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Rascunho',
                        'approved'  => 'Aprovado',
                        'sent'      => 'Enviado',
                        'cancelled' => 'Cancelado',
                    ]),

                Tables\Filters\SelectFilter::make('slot')
                    ->options([
                        'morning'   => 'Manhã',
                        'afternoon' => 'Tarde',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // Aprovar rascunho diretamente da listagem
                Tables\Actions\Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (EditorialPost $record) => $record->status === 'draft')
                    ->action(function (EditorialPost $record) {
                        $record->update([
                            'status'      => 'approved',
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Post aprovado para envio!')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->defaultSort('scheduled_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEditorialPosts::route('/'),
            'create' => Pages\CreateEditorialPost::route('/create'),
            'edit'   => Pages\EditEditorialPost::route('/{record}/edit'),
        ];
    }
}
