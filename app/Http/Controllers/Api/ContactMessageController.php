<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function store(Request $request): JsonResponse
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

        ContactMessage::create($data);

        return response()->json([
            'message' => 'Message envoyé. Nous vous recontacterons sous 24h.',
        ], 201);
    }
}
