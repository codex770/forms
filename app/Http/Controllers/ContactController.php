<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Accepts any JSON data and stores it as-is in the database.
     */
    private function handleSubmission(Request $request, string $category): JsonResponse
    {
        try {
            // Get all request data (accepts any JSON structure)
            // Try to get JSON body first, fallback to all request data
            $data = $request->json()->all();
            
            // If JSON body is empty, try getting all request data (fallback for form-data)
            if (empty($data)) {
                $data = $request->all();
            }

            // Extract webform_id, submission_form, and station from the data
            $webformId = $data['webform_id'] ?? null;
            $submissionForm = $data['submission_form'] ?? null;
            $station = $data['station'] ?? $category; // Fallback to category if station not provided

            // Store all request data as JSON
            $submission = ContactSubmission::create([
                'category' => $category,
                'webform_id' => $webformId,
                'submission_form' => $submissionForm,
                'station' => $station,
                'data' => $data,
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact form submitted successfully',
                'submission_id' => $submission->id
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Form submission error: ' . $e->getMessage(), [
                'category' => $category,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your submission',
                'error' => config('app.debug') ? $e->getMessage() : null
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
                // Search across all JSON fields - search in common fields and also search the entire JSON string
                $q->where(function ($query) use ($search) {
                    // Search in name fields (multiple variations)
                    $query->whereJsonContains('data->name', $search)
                          ->orWhereJsonContains('data->fname', $search)
                          ->orWhereJsonContains('data->lname', $search)
                          ->orWhereJsonContains('data->first_name', $search)
                          ->orWhereJsonContains('data->last_name', $search)
                          ->orWhereJsonContains('data->full_name', $search)
                          // Search in email
                          ->orWhereJsonContains('data->email', $search)
                          // Search in message fields
                          ->orWhereJsonContains('data->description', $search)
                          ->orWhereJsonContains('data->message', $search)
                          ->orWhereJsonContains('data->message_long', $search)
                          ->orWhereJsonContains('data->message_short', $search)
                          // Search in other common fields
                          ->orWhereJsonContains('data->phone', $search)
                          ->orWhereJsonContains('data->city', $search)
                          ->orWhereJsonContains('data->zip', $search)
                          // Also search the entire JSON data column as text for broader search
                          ->orWhereRaw('CAST(data AS CHAR) LIKE ?', ["%{$search}%"]);
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
     * Show form detail view with all submissions for a specific webform_id.
     */
    public function showFormDetail(Request $request, string $webformId): Response
    {
        // Get the form information
        $formInfo = ContactSubmission::where('webform_id', $webformId)
            ->select('webform_id', 'submission_form', 'station')
            ->first();

        if (!$formInfo) {
            abort(404, 'Form not found');
        }

        $formName = $formInfo->submission_form ?? $webformId;
        $station = $formInfo->station ?? 'unknown';

        // Get search and filter parameters
        $search = $request->get('search');
        $status = $request->get('status', 'all'); // all, read, unread
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $ageMin = $request->get('age_min');
        $ageMax = $request->get('age_max');
        $birthYearMin = $request->get('birth_year_min');
        $birthYearMax = $request->get('birth_year_max');
        $zipCode = $request->get('zip_code');
        $city = $request->get('city');
        $gender = $request->get('gender');
        $radius = $request->get('radius');
        $radiusLat = $request->get('radius_lat');
        $radiusLng = $request->get('radius_lng');
        $sortColumn = $request->get('sort_column', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        // Query submissions for this webform_id
        $query = ContactSubmission::with(['readsWithUsers'])
            ->where('webform_id', $webformId)
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    // Search in name fields (multiple variations) - use JSON_UNQUOTE to remove quotes
                    $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.name")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.fname")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.lname")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.first_name")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.last_name")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.full_name")) LIKE ?', ["%{$search}%"])
                          // Search in email
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.email")) LIKE ?', ["%{$search}%"])
                          // Search in message fields
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.description")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.message")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.message_long")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.message_short")) LIKE ?', ["%{$search}%"])
                          // Search in other common fields
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.phone")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.city")) LIKE ?', ["%{$search}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.zip")) LIKE ?', ["%{$search}%"])
                          // Fallback: search entire JSON
                          ->orWhereRaw('CAST(data AS CHAR) LIKE ?', ["%{$search}%"]);
                });
            })
            // Date range filter
            ->when($dateFrom, function ($q, $dateFrom) {
                $q->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q, $dateTo) {
                $q->whereDate('created_at', '<=', $dateTo);
            })
            // Age range filter (calculate from birth year or birthday date)
            ->when($birthYearMin || $birthYearMax, function ($q) use ($birthYearMin, $birthYearMax) {
                $q->where(function ($query) use ($birthYearMin, $birthYearMax) {
                    // Check birth_year fields (if year is stored directly) OR extract from date fields
                    if ($birthYearMin && $birthYearMax) {
                        // Both min and max - need to match either field type within range
                        $query->where(function ($q) use ($birthYearMin, $birthYearMax) {
                            // Year fields
                            $q->where(function ($subQ) use ($birthYearMin, $birthYearMax) {
                                $subQ->whereRaw('CAST(JSON_EXTRACT(data, "$.birth_year") AS UNSIGNED) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax])
                                     ->orWhereRaw('CAST(JSON_EXTRACT(data, "$.birthYear") AS UNSIGNED) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax])
                                     ->orWhereRaw('CAST(JSON_EXTRACT(data, "$.year_of_birth") AS UNSIGNED) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax]);
                            })
                            // Date fields - extract year
                            ->orWhere(function ($subQ) use ($birthYearMin, $birthYearMax) {
                                $subQ->whereRaw('YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.bday")), "%Y-%m-%d")) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax])
                                     ->orWhereRaw('YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.birthday")), "%Y-%m-%d")) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax]);
                            });
                        });
                    } else {
                        // Only min or only max
                        if ($birthYearMin) {
                            $query->where(function ($q) use ($birthYearMin) {
                                $q->whereRaw('CAST(JSON_EXTRACT(data, "$.birth_year") AS UNSIGNED) >= ?', [$birthYearMin])
                                  ->orWhereRaw('CAST(JSON_EXTRACT(data, "$.birthYear") AS UNSIGNED) >= ?', [$birthYearMin])
                                  ->orWhereRaw('CAST(JSON_EXTRACT(data, "$.year_of_birth") AS UNSIGNED) >= ?', [$birthYearMin])
                                  ->orWhereRaw('YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.bday")), "%Y-%m-%d")) >= ?', [$birthYearMin])
                                  ->orWhereRaw('YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.birthday")), "%Y-%m-%d")) >= ?', [$birthYearMin]);
                            });
                        }
                        if ($birthYearMax) {
                            $query->where(function ($q) use ($birthYearMax) {
                                $q->whereRaw('CAST(JSON_EXTRACT(data, "$.birth_year") AS UNSIGNED) <= ?', [$birthYearMax])
                                  ->orWhereRaw('CAST(JSON_EXTRACT(data, "$.birthYear") AS UNSIGNED) <= ?', [$birthYearMax])
                                  ->orWhereRaw('CAST(JSON_EXTRACT(data, "$.year_of_birth") AS UNSIGNED) <= ?', [$birthYearMax])
                                  ->orWhereRaw('YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.bday")), "%Y-%m-%d")) <= ?', [$birthYearMax])
                                  ->orWhereRaw('YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.birthday")), "%Y-%m-%d")) <= ?', [$birthYearMax]);
                            });
                        }
                    }
                });
            })
            // Age range filter (if age is directly provided - calculate from birthday dates)
            ->when($ageMin || $ageMax, function ($q) use ($ageMin, $ageMax) {
                $currentYear = date('Y');
                $q->where(function ($query) use ($ageMin, $ageMax, $currentYear) {
                    // Apply age min (person must be at least this old = birth year must be <= max birth year)
                    if ($ageMin) {
                        $maxBirthYear = $currentYear - $ageMin;
                        $query->where(function ($q) use ($maxBirthYear) {
                            $q->whereRaw('(CAST(JSON_EXTRACT(data, "$.birth_year") AS UNSIGNED) <= ? AND JSON_EXTRACT(data, "$.birth_year") IS NOT NULL)', [$maxBirthYear])
                              ->orWhereRaw('(CAST(JSON_EXTRACT(data, "$.birthYear") AS UNSIGNED) <= ? AND JSON_EXTRACT(data, "$.birthYear") IS NOT NULL)', [$maxBirthYear])
                              ->orWhereRaw('(CAST(JSON_EXTRACT(data, "$.year_of_birth") AS UNSIGNED) <= ? AND JSON_EXTRACT(data, "$.year_of_birth") IS NOT NULL)', [$maxBirthYear])
                              ->orWhereRaw('(YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.bday")), "%Y-%m-%d")) <= ? AND JSON_EXTRACT(data, "$.bday") IS NOT NULL)', [$maxBirthYear])
                              ->orWhereRaw('(YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.birthday")), "%Y-%m-%d")) <= ? AND JSON_EXTRACT(data, "$.birthday") IS NOT NULL)', [$maxBirthYear]);
                        });
                    }
                    
                    // Apply age max (person must be at most this old = birth year must be >= min birth year)
                    if ($ageMax) {
                        $minBirthYear = $currentYear - $ageMax;
                        $query->where(function ($q) use ($minBirthYear) {
                            $q->whereRaw('(CAST(JSON_EXTRACT(data, "$.birth_year") AS UNSIGNED) >= ? AND JSON_EXTRACT(data, "$.birth_year") IS NOT NULL)', [$minBirthYear])
                              ->orWhereRaw('(CAST(JSON_EXTRACT(data, "$.birthYear") AS UNSIGNED) >= ? AND JSON_EXTRACT(data, "$.birthYear") IS NOT NULL)', [$minBirthYear])
                              ->orWhereRaw('(CAST(JSON_EXTRACT(data, "$.year_of_birth") AS UNSIGNED) >= ? AND JSON_EXTRACT(data, "$.year_of_birth") IS NOT NULL)', [$minBirthYear])
                              ->orWhereRaw('(YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.bday")), "%Y-%m-%d")) >= ? AND JSON_EXTRACT(data, "$.bday") IS NOT NULL)', [$minBirthYear])
                              ->orWhereRaw('(YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.birthday")), "%Y-%m-%d")) >= ? AND JSON_EXTRACT(data, "$.birthday") IS NOT NULL)', [$minBirthYear]);
                        });
                    }
                });
            })
            // ZIP code filter
            ->when($zipCode, function ($q, $zipCode) {
                $q->where(function ($query) use ($zipCode) {
                    $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.zip")) LIKE ?', ["%{$zipCode}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.zip_code")) LIKE ?', ["%{$zipCode}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.postal_code")) LIKE ?', ["%{$zipCode}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.plz")) LIKE ?', ["%{$zipCode}%"]);
                });
            })
            // City filter
            ->when($city, function ($q, $city) {
                $q->where(function ($query) use ($city) {
                    $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.city")) LIKE ?', ["%{$city}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.place")) LIKE ?', ["%{$city}%"])
                          ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.location")) LIKE ?', ["%{$city}%"]);
                });
            })
            // Gender filter
            ->when($gender, function ($q, $gender) {
                $q->where(function ($query) use ($gender) {
                    // Handle various gender formats - map filter value to possible data values
                    $genderMap = [
                        'm' => ['m', 'male', 'M', 'Male', 'MALE', 'masculine'],
                        'f' => ['f', 'female', 'F', 'Female', 'FEMALE', 'feminine'],
                        'd' => ['d', 'diverse', 'D', 'Diverse', 'DIVERSE', 'other'],
                    ];
                    
                    $searchValues = $genderMap[strtolower($gender)] ?? [strtolower($gender)];
                    
                    // Build query for each possible value - use case-insensitive matching with JSON_UNQUOTE
                    $first = true;
                    foreach ($searchValues as $value) {
                        if ($first) {
                            $query->where(function ($q) use ($value) {
                                $q->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.gender"))) = ?', [strtolower($value)])
                                  ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.sex"))) = ?', [strtolower($value)])
                                  ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.gender"))) LIKE ?', ["%".strtolower($value)."%"])
                                  ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.sex"))) LIKE ?', ["%".strtolower($value)."%"]);
                            });
                            $first = false;
                        } else {
                            $query->orWhere(function ($q) use ($value) {
                                $q->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.gender"))) = ?', [strtolower($value)])
                                  ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.sex"))) = ?', [strtolower($value)])
                                  ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.gender"))) LIKE ?', ["%".strtolower($value)."%"])
                                  ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.sex"))) LIKE ?', ["%".strtolower($value)."%"]);
                            });
                        }
                    }
                });
            })
            // Radius/distance filter (if coordinates provided)
            ->when($radius && $radiusLat && $radiusLng, function ($q) use ($radius, $radiusLat, $radiusLng) {
                // Using Haversine formula for distance calculation
                // This requires latitude and longitude in the JSON data
                $q->whereRaw('(
                    JSON_EXTRACT(data, "$.latitude") IS NOT NULL AND
                    JSON_EXTRACT(data, "$.longitude") IS NOT NULL AND
                    6371 * acos(
                        cos(radians(?)) * 
                        cos(radians(CAST(JSON_EXTRACT(data, "$.latitude") AS DECIMAL(10,8)))) * 
                        cos(radians(CAST(JSON_EXTRACT(data, "$.longitude") AS DECIMAL(10,8))) - radians(?)) + 
                        sin(radians(?)) * 
                        sin(radians(CAST(JSON_EXTRACT(data, "$.latitude") AS DECIMAL(10,8))))
                    )
                ) <= ?', [$radiusLat, $radiusLng, $radiusLat, $radius]);
            });

        // Filter by read status for current user
        if ($status === 'read') {
            $query->readByUser(auth()->id());
        } elseif ($status === 'unread') {
            $query->unreadByUser(auth()->id());
        }

        // Debug: Log the SQL query
        \Log::info('Filter Query SQL: ' . $query->toSql());
        \Log::info('Filter Query Bindings: ' . json_encode($query->getBindings()));
        \Log::info('Filter Parameters: ' . json_encode([
            'search' => $search,
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'birth_year_min' => $birthYearMin,
            'birth_year_max' => $birthYearMax,
            'zip_code' => $zipCode,
            'city' => $city,
            'gender' => $gender,
            'radius' => $radius,
        ]));

        $submissions = $query->orderBy($sortColumn, $sortDirection)->paginate(15)->withQueryString();

        // Get total count for this webform
        $totalCount = ContactSubmission::where('webform_id', $webformId)->count();

        return Inertia::render('Forms/Detail', [
            'webformId' => $webformId,
            'formName' => $formName,
            'station' => $station,
            'submissions' => $submissions,
            'totalCount' => $totalCount,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'age_min' => $ageMin,
                'age_max' => $ageMax,
                'birth_year_min' => $birthYearMin,
                'birth_year_max' => $birthYearMax,
                'zip_code' => $zipCode,
                'city' => $city,
                'gender' => $gender,
                'radius' => $radius,
                'radius_lat' => $radiusLat,
                'radius_lng' => $radiusLng,
            ],
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
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
