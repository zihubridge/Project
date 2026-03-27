<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactFormMail;
use Exception;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    public function send(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'message' => 'required|string|max:5000',
        ]);

        try {
            Mail::to('support@zihubridge.com')
                ->send(new ContactFormMail($validated));

            return back()->with('success', 'Your message has been sent! We will get back to you within 24 hours.');

        } catch (Exception $e) {

            // Log the real error (don’t hide it like amateurs)
            Log::error('Mail sending failed: ' . $e->getMessage());

            return back()->with('error', 'Failed to send message. Please try again later.');
        }
    }
}