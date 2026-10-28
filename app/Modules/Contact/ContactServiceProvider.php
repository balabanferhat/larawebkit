<?php

namespace App\Modules\Contact;

use App\Modules\Contact\Http\ContactController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ContactServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'public-form'])->post('/contact', [ContactController::class, 'send'])->name('contact.send');
    }
}
