<?php
namespace App\Filament\Resources\EditorialPostResource\Pages;
use App\Filament\Resources\EditorialPostResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListEditorialPosts extends ListRecords {
    protected static string $resource = EditorialPostResource::class;
    protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; }
}
