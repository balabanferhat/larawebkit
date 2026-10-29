<?php

namespace App\Modules\Forms\Http;

use App\Modules\Forms\FormSubmitter;
use App\Modules\Forms\Models\Form;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FormController
{
    public function submit(Request $request, string $slug, FormSubmitter $submitter): RedirectResponse
    {
        $form = Form::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $submitter->submit($form, $request);

        $message = filled($form->success_message) ? $form->success_message : __('larawebkit-forms.sent');

        return redirect()->to(url()->previous().'#form-'.$form->slug)->with('form_status.'.$form->slug, $message);
    }
}
