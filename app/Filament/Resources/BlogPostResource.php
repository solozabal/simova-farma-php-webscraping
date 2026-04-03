<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $navigationGroup = 'Conteúdo';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Post do Blog';
    protected static ?string $pluralModelLabel = 'Posts do Blog';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Conteúdo')->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($s, callable $set) => $set('slug', Str::slug($s)))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->columnSpanFull(),
                Forms\Components\Textarea::make('excerpt')->rows(2)->columnSpanFull(),
                Forms\Components\Textarea::make('content')->rows(10)->required()->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Publicação')->schema([
                Forms\Components\Select::make('status')->options([
                    'draft' => 'Rascunho',
                    'scheduled' => 'Agendado',
                    'published' => 'Publicado',
                ])->required(),
                Forms\Components\DateTimePicker::make('publish_at')->label('Publicar em'),
            ])->columns(2),
            Forms\Components\Section::make('SEO')->schema([
                Forms\Components\TextInput::make('seo_title')->label('Título SEO'),
                Forms\Components\Textarea::make('seo_description')->label('Descrição SEO')->rows(2),
            ])->columns(1)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('slug')->toggleable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'draft',
                    'info' => 'scheduled',
                    'success' => 'published',
                ]),
                Tables\Columns\TextColumn::make('publish_at')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('author.name')->label('Autor'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Rascunho', 'scheduled' => 'Agendado', 'published' => 'Publicado',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
