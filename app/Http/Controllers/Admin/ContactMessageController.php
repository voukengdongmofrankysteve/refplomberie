<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ContactMessageController extends Controller
{
    public function index(): Response
    {
        $messages = ContactMessage::latest()->paginate(15);

        return Inertia::render('admin/messages/index', [
            'messages' => ContactMessageResource::collection($messages)
                ->response()
                ->getData(true),
            'statuses' => ContactMessageStatus::options(),
        ]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ContactMessageStatus::class)],
        ], attributes: ['status' => 'statut']);

        $message->update($data);

        return back()->with('success', 'Message mis à jour.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return back()->with('success', 'Message supprimé.');
    }
}
