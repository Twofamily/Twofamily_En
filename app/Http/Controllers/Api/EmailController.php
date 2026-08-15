<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EmailController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'to'         => 'required|email',
            'subject'    => 'required|string|max:255',
            'body'       => 'required|string',
            'attachment' => 'nullable|file|max:10240', // max 10 MB
        ]);

        $attachmentPath = null;

        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('email-attachments', 'local');
        }

        Mail::raw($request->body, function ($message) use ($request, $attachmentPath) {
            $message->to($request->to)
                    ->subject($request->subject);

            if ($attachmentPath) {
                $message->attach(Storage::disk('local')->path($attachmentPath));
            }
        });

        if ($attachmentPath) {
            Storage::disk('local')->delete($attachmentPath);
        }

        return response()->json([
            'ok'      => true,
            'message' => 'Email sent successfully.',
        ]);
    }
}
