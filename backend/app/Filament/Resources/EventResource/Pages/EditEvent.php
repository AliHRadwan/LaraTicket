<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Jobs\ProcessEventImage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->after(function () {
                    if ($this->record->image_url) {
                        Storage::disk('public')->delete($this->record->image_url);
                    }
                }),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('image_url') && $this->record->image_url) {
            ProcessEventImage::dispatch($this->record->id, $this->record->image_url);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
