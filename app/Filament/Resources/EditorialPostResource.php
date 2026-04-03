<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditorialPostResource\Pages;
use App\Models\EditorialPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EditorialPostResource extends Resource
{
    protected static ?string $model = EditorialPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Conteúdo';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Post Editorial';
    protected static ?string $pluralModelLabel = 'Posts Editoriais';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')->options([
                'fixed' => 'Fixo (recorrente)',
                'auto_digest' => 'Auto Digest',
                'auto_alert_summary' => 'Resumo de Alertas',
                'manual' => 'Manual',
            ])->required(),
            Forms\Components\Select::make('channel')->options([
                'telegram' => 'Telegram',
                'blog' => 'Blog',
                'both' => 'Ambos',
            ])->required(),
            Forms\Components\Select::make('status')->options([
                'active' => 'Ativo',
                'paused' => 'Pausado',
            ])->required(),
            Forms\Components\TextInput::make('title')->columnSpanFull(),
            Forms\Components\TextInput::make('title_template')->label('Template de Título')->columnSpanFull(),
            Forms\Components\Textarea::make('content')->rows(4)->columnSpanFull(),
            Forms\Components\Textarea::make('content_template')->label('Template de Conteúdo')->rows(4)->columnSpanFull(),
            Forms\Components\TextInput::make('schedule_rule')->label('Regra de Agendamento (ex: morning|afternoon|daily)'),
            Forms\Components\DateTimePicker::make('publish_at')->label('Publicar em'),
            Forms\Components\DateTimePicker::make('next_run_at')->label('Próxima execução'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('Tipo'),
                Tables\Columns\TextColumn::make('title')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('channel')->label('Canal'),
                Tables\Columns\BadgeColumn::make('status')->colors(['success' => 'active', 'warning' => 'paused']),
                Tables\Columns\TextColumn::make('schedule_rule')->label('Regra'),
                Tables\Columns\TextColumn::make('last_run_at')->dateTime('d/m/Y H:i')->label('Última exec.'),
                Tables\Columns\TextColumn::make('next_run_at')->dateTime('d/m/Y H:i')->label('Próxima exec.'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEditorialPosts::route('/'),
            'create' => Pages\CreateEditorialPost::route('/create'),
            'edit' => Pages\EditEditorialPost::route('/{record}/edit'),
        ];
    }
}
