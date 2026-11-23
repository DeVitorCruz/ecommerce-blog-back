<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformContact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlatformContactController extends Controller
{   
    /**
     * GET /platform/staff/contacts
     * List all contact submissions, newest first.
     * 
     * @return JsonResponse 200, paginated contact list
     */
    public function index(): JsonResponse
    {
        $this->authorize('platform.contacts.view-any');

        $contacts = PlatformContact::with('user')
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($contacts);
    }

    /**
     * GET /platform/staff/contacts/{contact}
     * Show a single contact submission. Marks it as read.
     * 
     * @param PlatformContact $contact
     * @return JsonResponse 200, contact detail
     */
    public function show(PlatformContact $contact): JsonResponse
    {
        $this->authorize('platform.contacts.view-any');

        if ($contact->status === 'unread') {
            $contact->update(['status' => 'read']);
        }

        return response()->json($contact->fresh()->load('user'));
    }

    /**
     * POST /platform/staff/contacts/{contact}/reply
     * Store a staff reply and mark the contact as replied
     * 
     * @param Request $request
     * @param PlatformContact $contact
     * @return JsonResponse 200, contact updated
     *                      422, already replied
     */
    public function reply(Request $request, PlatformContact $contact): JsonResponse
    {
        $this->authorize('platform.contacts.reply');

        if ($contact->isReplied()) {
            return response()->json(
                ['message' => 'Contact already replied.'], 
            422);
        }

        $data = $request->validate([
            'reply' => 'required|string|max:5000',
        ]);

        $contact->update([
            'reply' => $data['reply'],
            'replied_by' => $request->user()->name,
            'replied_at' => now(),
            'status' => 'replied',
        ]);

        return response()->json([
            'message' => 'Reply saved.',
            'contact' => $contact->fresh()->load('user'),
        ]);
    }

    /**
     * DELETE /platform/staff/contacts/{contact}
     * Hard-delete a contact submission.
     * 
     * @param PlatformContact $contact
     * @return JsonResponse 200, contact deleted
     */
    public function destroy(PlatformContact $contact): JsonResponse
    {
        $this->authorize('platform.contacts.delete');

        $contact->delete();

        return response()->json(['message' => 'Contact deleted.']);
    }
}
