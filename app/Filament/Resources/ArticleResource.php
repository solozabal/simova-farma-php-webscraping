<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Conteúdo';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->columnSpanFull(),
            Forms\Components\TextInput::make('url')->url()->required(),
            Forms\Components\TextInput::make('source_key')->required(),
            Forms\Components\Select::make('category')->options([
                'regulamentacao' => 'Regulamentação',
                'varejo' => 'Varejo',
                'tecnologia' => 'Tecnologia',
                'marketing' => 'Marketing',
                'macro' => 'Macro',
                'geral' => 'Geral',
            ])->required(),
            Forms\Components\Select::make('language')->options(['pt' => 'Português', 'en' => 'English'])->default('pt'),
            Forms\Components\TextInput::make('score')->numeric()->minValue(0)->maxValue(10),
            Forms\Components\TextInput::make('impact_label'),
            Forms\Components\Textarea::make('content')->rows(3)->columnSpanFull(),
            Forms\Components\Textarea::make('insight')->rows(3)->columnSpanFull(),
            Forms\Components\Select::make('editorial_status')->options([
                'queued' => 'Fila', 'approved' => 'Aprovado',
                'rejected' => 'Rejeitado', 'published' => 'Publicado',
            ]),
            Forms\Components\DateTimePicker::make('published_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('source_key')->label('Fonte'),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('score')->sortable(),
                Tables\Columns\BadgeColumn::make('editorial_status')->colors([
                    'warning' => 'queued', 'success' => 'approved',
                    'danger' => 'rejected', 'info' => 'published',
                ]),
                Tables\Columns\IconColumn::make('is_sent_alert')->boolean()->label('Alerta'),
                Tables\Columns\IconColumn::make('is_sent_digest')->boolean()->label('Digest'),
                Tables\Columns\TextColumn::make('published_at')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('score', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('editorial_status')->options([
                    'queued' => 'Fila', 'approved' => 'Aprovado',
                    'rejected' => 'Rejeitado', 'published' => 'Publicado',
                ]),
                Tables\Filters\SelectFilter::make('category')->options([
                    'regulamentacao' => 'Regulamentação', 'varejo' => 'Varejo',
                    'tecnologia' => 'Tecnologia', 'marketing' => 'Marketing', 'geral' => 'Geral',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
