<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Recurso Filament para visualização de artigos coletados.
 *
 * Os artigos são coletados automaticamente pelas fontes (RSS/API)
 * e aparecem aqui para auditoria e curadoria.
 */
class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon  = 'heroicon-o-newspaper';
    protected static ?string $navigationLabel = 'Artigos';
    protected static ?string $navigationGroup = 'Conteúdo';
    protected static ?int    $navigationSort  = 10;

    protected static ?string $modelLabel       = 'Artigo';
    protected static ?string $pluralModelLabel = 'Artigos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Dados do Artigo')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(500)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->url()
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('content')
                    ->label('Conteúdo/Resumo')
                    ->rows(4)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('source_key')
                    ->label('Chave da Fonte')
                    ->maxLength(100),

                Forms\Components\TextInput::make('source_label')
                    ->label('Nome da Fonte')
                    ->maxLength(200),

                Forms\Components\Select::make('category')
                    ->label('Categoria')
                    ->options([
                        'regulamentacao' => 'Regulamentação',
                        'varejo'         => 'Varejo',
                        'tecnologia'     => 'Tecnologia',
                        'marketing'      => 'Marketing',
                        'geral'          => 'Geral',
                    ])
                    ->required(),

                Forms\Components\Select::make('language')
                    ->label('Idioma')
                    ->options(['pt' => 'Português', 'en' => 'Inglês'])
                    ->required(),

                Forms\Components\TextInput::make('score')
                    ->label('Score (0-10)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(10)
                    ->required(),

                Forms\Components\TextInput::make('impact_label')
                    ->label('Rótulo de Impacto')
                    ->maxLength(200),
            ])->columns(2),

            Forms\Components\Section::make('Insight')->schema([
                Forms\Components\Textarea::make('insight')
                    ->label('Insight Gerado')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Controle de Entrega')->schema([
                Forms\Components\Toggle::make('alert_sent')
                    ->label('Alerta Enviado'),

                Forms\Components\Toggle::make('digest_sent')
                    ->label('Digest Enviado'),

                Forms\Components\Toggle::make('blog_published')
                    ->label('Publicado no Blog'),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Publicado em (Fonte)'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 8 => 'danger',
                        $state >= 5 => 'warning',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'regulamentacao' => 'Regulamentação',
                        'varejo'         => 'Varejo',
                        'tecnologia'     => 'Tecnologia',
                        'marketing'      => 'Marketing',
                        default          => 'Geral',
                    }),

                Tables\Columns\TextColumn::make('source_label')
                    ->label('Fonte')
                    ->searchable(),

                Tables\Columns\IconColumn::make('alert_sent')
                    ->label('Alerta')
                    ->boolean(),

                Tables\Columns\IconColumn::make('digest_sent')
                    ->label('Digest')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publicado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Categoria')
                    ->options([
                        'regulamentacao' => 'Regulamentação',
                        'varejo'         => 'Varejo',
                        'tecnologia'     => 'Tecnologia',
                        'marketing'      => 'Marketing',
                        'geral'          => 'Geral',
                    ]),

                Tables\Filters\TernaryFilter::make('digest_sent')
                    ->label('Digest Enviado'),

                Tables\Filters\TernaryFilter::make('alert_sent')
                    ->label('Alerta Enviado'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('score', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit'   => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
