<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Jobs\ProcessEventImage;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateEvent extends CreateRecord
{
    protected static string $resource = EventResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['available_tickets'] = $data['total_tickets'];

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->dispatchImageJob($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function dispatchImageJob(Model $record): void
    {
        if (! empty($record->image_url)) {
            ProcessEventImage::dispatch($record->id, $record->image_url);
        }
    }
}
