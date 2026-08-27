<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    /**
     * Enregistre une demande de devis envoyée depuis la section contact.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:40'],
            'subject' => ['nullable', 'string', 'max:60'],
            'message' => ['required', 'string', 'max:2000'],
        ], attributes: [
            'name' => 'nom',
            'phone' => 'téléphone',
            'message' => 'message',
        ]);

        ContactMessage::create([
            ...$data,
            'user_id' => $request->user()?->id,
        ]);

        return back()->with('success', 'Message envoyé. Nous vous recontactons sous 24h.');
    }
}
