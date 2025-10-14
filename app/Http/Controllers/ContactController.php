<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Show the public contact form.
     */
    public function showForm(): Response
    {
        return Inertia::render('Contact/Form', [
            'categories' => [
                'bigfm' => 'BigFM',
                'rpr1' => 'RPR1',
                'regenbogen' => 'Regenbogen',
                'rockfm' => 'RockFM',
                'bigkarriere' => 'BigKarriere',
            ]
        ]);
    }

    /**
     * Handle BigFM contact submissions.
     */
    public function submitBigfm(Request $request): JsonResponse
    {
        return $this->handleSubmission($request, 'bigfm');
    }

    /**
     * Handle RPR1 contact submissions.
     */
    public function submitRpr1(Request $request): JsonResponse
    {
        return $this->handleSubmission($request, 'rpr1');
    }

    /**
     * Handle Regenbogen contact submissions.
     */
    public function submitRegenbogen(Request $request): JsonResponse
    {
        return $this->handleSubmission($request, 'regenbogen');
    }

    /**
     * Handle RockFM contact submissions.
     */
    public function submitRockfm(Request $request): JsonResponse
    {
        return $this->handleSubmission($request, 'rockfm');
    }

    /**
     * Handle BigKarriere contact submissions.
     */
    public function submitBigkarriere(Request $request): JsonResponse
    {
        return $this->handleSubmission($request, 'bigkarriere');
    }

    /**
     * Handle the submission for any category.
     */
    private function handleSubmission(Request $request, string $category): JsonResponse
    {
        // Basic validation for required fields
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'description' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Store all request data as JSON
            $submission = ContactSubmission::create([
                'category' => $category,
                'data' => $request->all(),
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact form submitted successfully',
                'submission_id' => $submission->id
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your submission',
            ], 500);
        }
    }

    /**
     * Show contact messages dashboard for authenticated users.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $category = $request->get('category');
        $status = $request->get('status', 'all'); // all, read, unread

        $query = ContactSubmission::with(['readsWithUsers'])
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereJsonContains('data->name', $search)
                          ->orWhereJsonContains('data->email', $search)
                          ->orWhereJsonContains('data->description', $search);
                });
            })
            ->when($category, function ($q, $category) {
                $q->where('category', $category);
            });

        // Filter by read status for current user
        if ($status === 'read') {
            $query->readByUser(auth()->id());
        } elseif ($status === 'unread') {
            $query->unreadByUser(auth()->id());
        }

        $submissions = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Contact/Index', [
            'submissions' => $submissions,
            'filters' => [
                'search' => $search,
                'category' => $category,
                'status' => $status,
            ],
            'categories' => [
                'bigfm' => 'BigFM',
                'rpr1' => 'RPR1',
                'regenbogen' => 'Regenbogen',
                'rockfm' => 'RockFM',
                'bigkarriere' => 'BigKarriere',
            ]
        ]);
    }

    /**
     * Show a specific contact submission.
     */
    public function show(ContactSubmission $submission): Response
    {
        // Mark as read by current user
        $submission->markAsReadBy(auth()->id());

        // Load with reads and users
        $submission->load(['readsWithUsers']);

        return Inertia::render('Contact/Show', [
            'submission' => $submission
        ]);
    }

    /**
     * Mark a submission as read/unread by current user.
     */
    public function toggleRead(ContactSubmission $submission): RedirectResponse
    {
        $userId = auth()->id();
        $existingRead = $submission->reads()->where('user_id', $userId)->first();

        if ($existingRead) {
            // If already read, remove the read record (mark as unread)
            $existingRead->delete();
            $message = 'Marked as unread';
        } else {
            // If not read, mark as read
            $submission->markAsReadBy($userId);
            $message = 'Marked as read';
        }

        return back()->with('success', $message);
    }

    /**
     * Delete a contact submission (permanent deletion).
     */
    public function destroy(ContactSubmission $submission): RedirectResponse
    {
        try {
            $submission->delete();
            
            return redirect()->route('contact.index')->with('success', 'Contact submission deleted permanently.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete contact submission.');
        }
    }
}
