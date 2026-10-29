<?php

namespace App\Modules\Forms\Filament\RelationManagers;

use App\Modules\Forms\Filament\Resources\FormSubmissions\FormSubmissionResource;
use App\Modules\Forms\SubmissionExport;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('larawebkit-forms.submissions');
    }

    public function table(Table $table): Table
    {
        return FormSubmissionResource::table($table)
            ->headerActions([
                Action::make('export')->label(__('larawebkit-forms.export'))->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn () => SubmissionExport::csv($this->getOwnerRecord())),
            ]);
    }
}
