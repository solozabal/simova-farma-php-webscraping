<?php
namespace App\Filament\Resources\EditorialPostResource\Pages;
use App\Filament\Resources\EditorialPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditEditorialPost extends EditRecord {
    protected static string $resource = EditorialPostResource::class;
    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
