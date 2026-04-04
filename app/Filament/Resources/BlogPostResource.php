<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Recurso Filament para gerenciamento de posts do blog.
 *
 * IMPORTANTE: Posts são criados como RASCUNHO (draft) por padrão.
 * A publicação exige aprovação manual — não há auto-publicação.
 */
class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Blog';
    protected static ?string $navigationGroup = 'Conteúdo';
    protected static ?int    $navigationSort  = 30;

    protected static ?string $modelLabel       = 'Post do Blog';
    protected static ?string $pluralModelLabel = 'Posts do Blog';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Conteúdo')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(300)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                        $set('slug', \Illuminate\Support\Str::slug($state ?? ''));
                    })
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug (URL)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(300)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('excerpt')
                    ->label('Resumo (para listagem e SEO)')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),

                Forms\Components\RichEditor::make('content')
                    ->label('Conteúdo')
                    ->required()
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('SEO')->schema([
                Forms\Components\TextInput::make('meta_title')
                    ->label('Meta Title')
                    ->maxLength(70)
                    ->helperText('Deixe vazio para usar o título do post'),

                Forms\Components\Textarea::make('meta_description')
                    ->label('Meta Description')
                    ->rows(2)
                    ->maxLength(160),
            ])->columns(1)->collapsed(),

            Forms\Components\Section::make('Classificação')->schema([
                Forms\Components\TextInput::make('category')
                    ->label('Categoria')
                    ->maxLength(100),

                Forms\Components\TagsInput::make('tags')
                    ->label('Tags')
                    ->nullable(),
            ])->columns(2),

            Forms\Components\Section::make('Publicação')->schema([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft'     => 'Rascunho',
                        'published' => 'Publicado',
                        'archived'  => 'Arquivado',
                    ])
                    ->required()
                    ->default('draft')
                    ->helperText('ATENÇÃO: apenas mude para "Publicado" quando o conteúdo estiver pronto e aprovado.'),

                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Data de Publicação')
                    ->nullable()
                    ->timezone('America/Sao_Paulo'),

                Forms\Components\Select::make('author_id')
                    ->label('Autor')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->nullable(),
            ])->columns(3),
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
                        'success' => 'published',
                        'danger'  => 'archived',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'draft'     => 'Rascunho',
                        'published' => 'Publicado',
                        'archived'  => 'Arquivado',
                        default     => $state,
                    }),

                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Autor')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Publicado em')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Rascunho',
                        'published' => 'Publicado',
                        'archived'  => 'Arquivado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit'   => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
