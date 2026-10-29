<?php

namespace App\Modules\Forms\Filament\Resources\FormSubmissions\Pages;

use App\Modules\Forms\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFormSubmission extends ViewRecord
{
    protected static string $resource = FormSubmissionResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        if ($this->record->status === 'new') {
            $this->record->update(['status' => 'read']);
        }
    }

    protected function getHeaderActions(): array
    {
        return [FormSubmissionResource::statusAction(), DeleteAction::make()];
    }
}
