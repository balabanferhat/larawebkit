<?php

namespace App\Modules\Contact\Http;

use App\Http\Controllers\Controller;
use App\Modules\Contact\Mail\ContactMessageReceived;
use App\Modules\Contact\Models\ContactMessage;
use App\Modules\Logs\Http\Middleware\LogAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function send(Request $request): RedirectResponse
    {
        abort_unless(setting('features.contact_form', true), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'subject' => ['nullable', 'string', 'max:200'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        $message = ContactMessage::create($data + ['ip_hash' => LogAccess::hashIp((string) $request->ip())]);

        if ($to = setting('general.contact_email')) {
            Mail::to($to)->send(new ContactMessageReceived($message));
        }

        return back()->with('status', __('larawebkit-front.contact.sent'));
    }
}
