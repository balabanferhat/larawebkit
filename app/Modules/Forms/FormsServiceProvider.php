<?php

namespace App\Modules\Forms;

use App\Modules\Blocks\BlockForms;
use App\Modules\Core\ModuleRegistry;
use App\Modules\Forms\Http\FormController;
use App\Modules\Forms\Models\Form;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Admin-defined forms (contact, quote request, application...). The "form" section block
 * can embed any active form.
 */
class FormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FormSubmitter::class);
    }

    public function boot(): void
    {
        Route::middleware(['web', 'public-form'])->post('/forms/{slug}', [FormController::class, 'submit'])
            ->where('slug', '[a-z0-9-]+')->name('forms.submit');

        if (ModuleRegistry::enabled('blocks')) {
            BlockForms::resolver(function () {
                try {
                    return Form::where('is_active', true)->orderBy('name')->get(['name', 'slug'])
                        ->mapWithKeys(fn (Form $form) => [$form->slug => ['label' => $form->name, 'view' => 'components.lwk.forms.form']])->all();
                } catch (Throwable) {
                    return [];
                }
            });
        }
    }
}
