<?php

namespace App\Http\Controllers;

use App\Models\ContactMark;
use App\Models\ContactRetentionRule;
use App\Models\ContactSubmission;
use App\Models\FormColumnConfig;
use App\Helpers\FormDefaultsHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    private function resolveExportValue(array $data, string $field)
    {
        $field = strtolower(trim($field));
        if ($field === '') return null;

        $aliases = [
            'first_name' => ['first_name', 'fname', 'vorname'],
            'last_name' => ['last_name', 'lname', 'nachname'],
            'birthday' => ['birthday', 'bday', 'geburtstag', 'date_of_birth', 'dob'],
            'postal_code' => ['plz', 'zip', 'zip_code', 'postal_code', 'postcode'],
            'email' => ['email', 'email_address', 'e_mail'],
            'phone' => ['phone', 'phone_number', 'tel', 'telephone', 'mobile'],
            'birth_year' => ['birth_year', 'birthYear', 'year_of_birth'],
            // leave others as-is
        ];

        $keys = $aliases[$field] ?? [$field];
        foreach ($keys as $k) {
            if (array_key_exists($k, $data)) {
                $v = $data[$k];
                if ($v !== null && $v !== '') return $v;
            }
        }
        return null;
    }

    private function buildFormDetailQuery(string $webformId, Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all'); // all, read, unread, starred
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');
        $ageMin = $request->get('age_min');
        $ageMax = $request->get('age_max');
        $birthYearMin = $request->get('birth_year_min');
        $birthYearMax = $request->get('birth_year_max');
        $zipCode = $request->get('zip_code');
        $plzFrom = $request->get('plz_from');
        $plzTo = $request->get('plz_to');
        $city = $request->get('city');
        $gender = $request->get('gender');
        $radius = $request->get('radius');
        $radiusPlz = $request->get('radius_plz');

        // Resolve radius center from PLZ (best-effort; no external service)
        $radiusLat = null;
        $radiusLng = null;
        if ($radius && $radiusPlz) {
            $cacheKey = 'plz_center:' . preg_replace('/\\D+/', '', (string) $radiusPlz);
            $center = \Cache::remember($cacheKey, now()->addHours(12), function () use ($radiusPlz) {
                $plz = preg_replace('/\\D+/', '', (string) $radiusPlz);
                if ($plz === '') return null;

                $submission = ContactSubmission::query()
                    ->where(function ($q) use ($plz) {
                        $q->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.plz")) = ?', [$plz])
                            ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.zip")) = ?', [$plz])
                            ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.zip_code")) = ?', [$plz])
                            ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.postal_code")) = ?', [$plz]);
                    })
                    ->whereRaw('JSON_EXTRACT(data, "$.latitude") IS NOT NULL')
                    ->whereRaw('JSON_EXTRACT(data, "$.longitude") IS NOT NULL')
                    ->orderByDesc('created_at')
                    ->first(['data']);

                if (!$submission || !is_array($submission->data)) return null;
                $lat = $submission->data['latitude'] ?? null;
                $lng = $submission->data['longitude'] ?? null;
                if ($lat === null || $lng === null) return null;

                return ['lat' => (float) $lat, 'lng' => (float) $lng];
            });

            if (is_array($center) && isset($center['lat'], $center['lng'])) {
                $radiusLat = $center['lat'];
                $radiusLng = $center['lng'];
            }
        }

        $query = ContactSubmission::query()
            ->with([
                'readsWithUsers',
                'marks' => function ($q) {
                    $q->where('user_id', auth()->id());
                },
            ])
            ->where('webform_id', $webformId)
            ->when($search, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.name")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.fname")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.lname")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.first_name")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.last_name")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.full_name")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.email")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.description")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.message")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.message_long")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.message_short")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.phone")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.city")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.zip")) LIKE ?', ["%{$search}%"])
                        ->orWhereRaw('CAST(data AS CHAR) LIKE ?', ["%{$search}%"]);
                });
            })
            ->when($dateFrom, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($dateTo, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($birthYearMin || $birthYearMax, function ($q) use ($birthYearMin, $birthYearMax) {
                $q->where(function ($query) use ($birthYearMin, $birthYearMax) {
                    if ($birthYearMin && $birthYearMax) {
                        $query->where(function ($q) use ($birthYearMin, $birthYearMax) {
                            $q->where(function ($subQ) use ($birthYearMin, $birthYearMax) {
                                $subQ->whereRaw('CAST(JSON_EXTRACT(data, "$.birth_year") AS UNSIGNED) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax])
                                    ->orWhereRaw('CAST(JSON_EXTRACT(data, "$.birthYear") AS UNSIGNED) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax])
                                    ->orWhereRaw('CAST(JSON_EXTRACT(data, "$.year_of_birth") AS UNSIGNED) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax]);
                            })->orWhere(function ($subQ) use ($birthYearMin, $birthYearMax) {
                                $subQ->whereRaw('YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.bday")), "%Y-%m-%d")) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax])
                                    ->orWhereRaw('YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(data, "$.birthday")), "%Y-%m-%d")) BETWEEN ? AND ?', [$birthYearMin, $birthYearMax]);
                            });
                        });
                    } else {
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
            ->when($ageMin || $ageMax, function ($q) use ($ageMin, $ageMax) {
                $currentYear = date('Y');
                $q->where(function ($query) use ($ageMin, $ageMax, $currentYear) {
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
            ->when($zipCode, function ($q, $zipCode) {
                $q->where(function ($query) use ($zipCode) {
                    $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.zip")) LIKE ?', ["%{$zipCode}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.zip_code")) LIKE ?', ["%{$zipCode}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.postal_code")) LIKE ?', ["%{$zipCode}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.plz")) LIKE ?', ["%{$zipCode}%"]);
                });
            })
            ->when($plzFrom || $plzTo, function ($q) use ($plzFrom, $plzTo) {
                $from = $plzFrom !== null ? (int) preg_replace('/\\D+/', '', (string) $plzFrom) : null;
                $to = $plzTo !== null ? (int) preg_replace('/\\D+/', '', (string) $plzTo) : null;
                if (!$from && !$to) return;

                $q->where(function ($query) use ($from, $to) {
                    $expr = 'CAST(JSON_UNQUOTE(JSON_EXTRACT(data, "$.%s")) AS UNSIGNED)';
                    $keys = ['plz', 'zip', 'zip_code', 'postal_code'];
                    foreach ($keys as $i => $key) {
                        $col = sprintf($expr, $key);
                        $cb = function ($sub) use ($col, $from, $to) {
                            if ($from && $to) $sub->whereRaw("$col BETWEEN ? AND ?", [$from, $to]);
                            elseif ($from) $sub->whereRaw("$col >= ?", [$from]);
                            elseif ($to) $sub->whereRaw("$col <= ?", [$to]);
                        };
                        if ($i === 0) $query->where(fn ($sub) => $cb($sub));
                        else $query->orWhere(fn ($sub) => $cb($sub));
                    }
                });
            })
            ->when($city, function ($q, $city) {
                $q->where(function ($query) use ($city) {
                    $query->whereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.city")) LIKE ?', ["%{$city}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.place")) LIKE ?', ["%{$city}%"])
                        ->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.location")) LIKE ?', ["%{$city}%"]);
                });
            })
            ->when($gender, function ($q) use ($gender) {
                $q->where(function ($query) use ($gender) {
                    $genderMap = [
                        'm' => ['m', 'male', 'M', 'Male', 'MALE', 'masculine'],
                        'f' => ['f', 'female', 'F', 'Female', 'FEMALE', 'feminine'],
                        'd' => ['d', 'diverse', 'D', 'Diverse', 'DIVERSE', 'other'],
                    ];
                    $searchValues = $genderMap[strtolower($gender)] ?? [strtolower($gender)];
                    $first = true;
                    foreach ($searchValues as $value) {
                        if ($first) {
                            $query->where(function ($q) use ($value) {
                                $q->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.gender"))) = ?', [strtolower($value)])
                                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.sex"))) = ?', [strtolower($value)])
                                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.gender"))) LIKE ?', ["%" . strtolower($value) . "%"])
                                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.sex"))) LIKE ?', ["%" . strtolower($value) . "%"]);
                            });
                            $first = false;
                        } else {
                            $query->orWhere(function ($q) use ($value) {
                                $q->whereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.gender"))) = ?', [strtolower($value)])
                                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.sex"))) = ?', [strtolower($value)])
                                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.gender"))) LIKE ?', ["%" . strtolower($value) . "%"])
                                    ->orWhereRaw('LOWER(JSON_UNQUOTE(JSON_EXTRACT(data, "$.sex"))) LIKE ?', ["%" . strtolower($value) . "%"]);
                            });
                        }
                    }
                });
            })
            ->when($radius && $radiusLat !== null && $radiusLng !== null, function ($q) use ($radius, $radiusLat, $radiusLng) {
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

        if ($status === 'read') {
            $query->readByUser(auth()->id());
        } elseif ($status === 'unread') {
            $query->unreadByUser(auth()->id());
        } elseif ($status === 'starred') {
            $query->whereHas('marks', function ($q) {
                $q->where('user_id', auth()->id());
            });
        }

        return $query;
    }

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
                'ip_hash' => $this->hashIp($request->ip()),
                'dedupe_hash' => $this->computeDedupeHash($data),
            ]);

            // Detect new fields if webform_id exists (for field detection tracking)
            if ($webformId) {
                $this->detectNewFields($webformId, $data);
            }

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
     * Compute a stable duplicate-detection hash for a submission.
     * Criteria: email + phone + name (fname+lname / name) + PLZ + birth year.
     */
    private function computeDedupeHash(array $data): ?string
    {
        $key = (string) config('app.key', '');
        $key = preg_replace('/^base64:/', '', $key);
        $secret = base64_decode($key, true) ?: $key;
        if (!is_string($secret) || $secret === '') return null;

        $aliases = [
            'email'      => ['email', 'email_address', 'e_mail'],
            'phone'      => ['phone', 'phone_number', 'tel', 'telephone', 'mobile'],
            'first_name' => ['fname', 'first_name', 'vorname'],
            'last_name'  => ['lname', 'last_name', 'nachname'],
            'name'       => ['name', 'full_name', 'contact_name'],
            'plz'        => ['plz', 'zip', 'zip_code', 'postal_code'],
            'birth_year' => ['birth_year', 'birthYear', 'year_of_birth', 'geburtstag', 'birthday', 'dob', 'date_of_birth'],
        ];

        $parts = [];
        foreach ($aliases as $canonical => $keys) {
            $value = null;
            foreach ($keys as $k) {
                $v = $data[$k] ?? null;
                if ($v !== null && $v !== '') {
                    $value = strtolower(trim((string) $v));
                    break;
                }
            }
            if ($canonical === 'birth_year' && $value !== null && strlen($value) > 4) {
                if (preg_match('/(\d{4})/', $value, $m)) {
                    $value = $m[1];
                }
            }
            $parts[$canonical] = $value ?? '';
        }

        if (isset($parts['phone']) && $parts['phone'] !== '') {
            $parts['phone'] = preg_replace('/\D/', '', $parts['phone']);
        }

        $payload = implode('|', [
            $parts['email'],
            $parts['phone'],
            trim($parts['first_name'] . ' ' . $parts['last_name'] . ' ' . $parts['name']),
            $parts['plz'],
            $parts['birth_year'],
        ]);

        return hash_hmac('sha256', $payload, $secret);
    }

    private function hashIp(?string $ip): ?string
    {
        $ip = $ip ? trim($ip) : '';
        if ($ip === '') return null;

        $key = (string) config('app.key', '');
        if ($key === '') return null;

        $key = preg_replace('/^base64:/', '', $key);
        $secret = base64_decode($key, true) ?: $key;
        if (!is_string($secret) || $secret === '') return null;

        return hash_hmac('sha256', $ip, $secret);
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
     * Detect available fields from JSON data at different hierarchy levels.
     * 
     * Priority: Form-level (most specific) → Type-level → Station-level
     * Form-level shows only fields from THIS specific form (not union of all forms).
     */
    private function detectAvailableFields(?string $webformId = null, ?string $submissionForm = null, ?string $station = null): array
    {
        $query = ContactSubmission::query();

        // PRIORITY 1: Form-level detection (show only THIS form's fields)
        if ($webformId) {
            $query->where('webform_id', $webformId);
            // Get sample submissions from THIS form only (up to 100)
            $submissions = $query->limit(100)->get();
        }
        // PRIORITY 2: Type-level detection (fallback if form doesn't exist yet)
        elseif ($submissionForm && $station) {
            $query->where('submission_form', $submissionForm)
                ->where('station', $station);
            // Get sample submissions from all forms of this type (up to 100)
            $submissions = $query->limit(100)->get();
        }
        // PRIORITY 3: Station-level detection (fallback)
        elseif ($station) {
            $query->where('station', $station);
            $submissions = $query->limit(100)->get();
        } else {
            return [];
        }

        $fields = [];
        $fieldTypes = [];

        foreach ($submissions as $submission) {
            $data = $submission->data ?? [];

            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    // Skip system fields that are stored separately
                    if (in_array($key, ['webform_id', 'submission_form', 'station', 'category', 'latitude', 'longitude'])) {
                        continue;
                    }

                    if (!isset($fields[$key])) {
                        $fields[$key] = true;

                        // Detect field type
                        if (is_string($value)) {
                            // Check if it's a date
                            if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value) || strtotime($value) !== false) {
                                $fieldTypes[$key] = 'date';
                            } else {
                                $fieldTypes[$key] = 'string';
                            }
                        } elseif (is_int($value)) {
                            $fieldTypes[$key] = 'integer';
                        } elseif (is_float($value)) {
                            $fieldTypes[$key] = 'float';
                        } elseif (is_bool($value)) {
                            $fieldTypes[$key] = 'boolean';
                        } elseif (is_array($value) || is_object($value)) {
                            $fieldTypes[$key] = 'object';
                        } else {
                            $fieldTypes[$key] = 'string';
                        }
                    }
                }
            }
        }

        // Convert to array with metadata and remove duplicates by key
        $result = [];
        $seenKeys = [];
        foreach (array_keys($fields) as $field) {
            // Skip if we've already seen this key (prevent duplicates)
            if (in_array($field, $seenKeys)) {
                continue;
            }
            $seenKeys[] = $field;

            $result[] = [
                'key' => $field,
                'type' => $fieldTypes[$field] ?? 'string',
                'label' => $this->getFieldLabel($field),
            ];
        }

        // Sort by label for better UX
        usort($result, function ($a, $b) {
            return strcmp($a['label'], $b['label']);
        });

        return $result;
    }

    /**
     * Detect new fields in a submission and cache them for notification.
     * This helps track when new fields are added to existing forms.
     */
    private function detectNewFields(string $webformId, array $newData): void
    {
        // Get existing fields for this form
        $existingFields = $this->detectAvailableFields($webformId);
        $existingKeys = array_column($existingFields, 'key');

        // Find new fields (fields in newData but not in existingFields)
        $newFields = [];
        foreach (array_keys($newData) as $key) {
            // Skip system fields
            if (in_array($key, ['webform_id', 'submission_form', 'station', 'category', 'latitude', 'longitude'])) {
                continue;
            }

            // If field doesn't exist in existing fields, it's new
            if (!in_array($key, $existingKeys)) {
                $newFields[] = $key;
            }
        }

        // If new fields found, cache them for frontend notification
        // We'll use cache with a key that includes webform_id and user_id
        // This allows per-user notifications
        if (!empty($newFields)) {
            // Store in cache for 24 hours (users will see notification when they next visit)
            $cacheKey = "new_fields:{$webformId}";
            $existingNewFields = \Cache::get($cacheKey, []);
            $allNewFields = array_unique(array_merge($existingNewFields, $newFields));
            \Cache::put($cacheKey, $allNewFields, now()->addHours(24));
        }
    }

    /**
     * Get new fields for a webform (fields detected since last visit).
     */
    public function getNewFields(string $webformId): array
    {
        $cacheKey = "new_fields:{$webformId}";
        $newFieldKeys = \Cache::get($cacheKey, []);

        if (empty($newFieldKeys)) {
            return [];
        }

        // Get field metadata for new fields
        $allFields = $this->detectAvailableFields($webformId);
        $newFields = [];

        foreach ($allFields as $field) {
            if (in_array($field['key'], $newFieldKeys)) {
                $newFields[] = $field;
            }
        }

        return $newFields;
    }

    /**
     * Clear new fields notification for a webform.
     */
    public function clearNewFields(string $webformId): void
    {
        $cacheKey = "new_fields:{$webformId}";
        \Cache::forget($cacheKey);
    }

    /**
     * Get human-readable label for a field key (German).
     */
    private function getFieldLabel(string $key): string
    {
        // Labels from client form field reference (TITLE column)
        $labels = [
            'station' => 'Station',
            'userid' => 'User ID',
            'gender' => 'Anrede',
            'sex' => 'Anrede',
            'fname' => 'Vorname',
            'first_name' => 'Vorname',
            'vorname' => 'Vorname',
            'lname' => 'Nachname',
            'last_name' => 'Nachname',
            'nachname' => 'Nachname',
            'name' => 'Name',
            'full_name' => 'Name',
            'contact_name' => 'Name',
            'address' => 'Straße & Hausnummer',
            'street' => 'Straße & Hausnummer',
            'zip' => 'Postleitzahl',
            'zip_code' => 'Postleitzahl',
            'postal_code' => 'Postleitzahl',
            'postcode' => 'Postleitzahl',
            'plz' => 'Postleitzahl',
            'city' => 'Stadt',
            'ort' => 'Stadt',
            'place' => 'Ort',
            'location' => 'Ort',
            'phone' => 'Telefon',
            'phone_number' => 'Telefon',
            'tel' => 'Telefon',
            'telephone' => 'Telefon',
            'mobile' => 'Mobil',
            'telefon' => 'Telefon',
            'email' => 'E-Mail',
            'email_address' => 'E-Mail',
            'e_mail' => 'E-Mail',
            'birthday' => 'Geburtsdatum',
            'bday' => 'Geburtsdatum',
            'geburtstag' => 'Geburtsdatum',
            'date_of_birth' => 'Geburtsdatum',
            'dob' => 'Geburtsdatum',
            'birth_year' => 'Geburtsjahr',
            'birthYear' => 'Geburtsjahr',
            'year_of_birth' => 'Geburtsjahr',
            'message_long' => 'Nachricht (lang)',
            'message_short' => 'Nachricht (kurz)',
            'message' => 'Nachricht',
            'description' => 'Beschreibung',
            'content' => 'Inhalt',
            'text' => 'Text',
            'age' => 'Alter',
        ];

        $key = strtolower(trim($key));
        if (isset($labels[$key])) {
            return $labels[$key];
        }

        return ucwords(str_replace(['_', '-'], ' ', $key));
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
        $submissionForm = $formInfo->submission_form;

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
        $plzFrom = $request->get('plz_from');
        $plzTo = $request->get('plz_to');
        $city = $request->get('city');
        $gender = $request->get('gender');
        $radius = $request->get('radius');
        $radiusPlz = $request->get('radius_plz');
        $sortColumn = $request->get('sort_column', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $hideDuplicates = (bool) $request->get('hide_duplicates', false);

        $query = $this->buildFormDetailQuery($webformId, $request);

        // When hide_duplicates is active, keep only the earliest (min id) per dedupe_hash.
        // Rows with a NULL dedupe_hash are always shown individually.
        if ($hideDuplicates) {
            $representativeIds = \DB::table('contact_submissions')
                ->select(\DB::raw('MIN(id) as rep_id'))
                ->where('webform_id', $webformId)
                ->whereNotNull('dedupe_hash')
                ->groupBy('dedupe_hash')
                ->pluck('rep_id');

            // Also include rows with NULL dedupe_hash (they are always unique)
            $query->where(function ($q) use ($representativeIds) {
                $q->whereIn('id', $representativeIds)
                  ->orWhereNull('dedupe_hash');
            });
        }

        // Use composite index (webform_id, created_at) for efficient sorting
        $submissions = $query->orderBy($sortColumn, $sortDirection)->paginate(15)->withQueryString();

        // Attach duplicate_count to each row
        $hashes = $submissions->pluck('dedupe_hash')->filter()->unique()->values()->all();
        $countMap = [];
        if (!empty($hashes)) {
            $countMap = \DB::table('contact_submissions')
                ->select('dedupe_hash', \DB::raw('COUNT(*) as total'))
                ->where('webform_id', $webformId)
                ->whereIn('dedupe_hash', $hashes)
                ->groupBy('dedupe_hash')
                ->pluck('total', 'dedupe_hash')
                ->all();
        }
        // Transform paginator items to include duplicate_count
        $submissions->getCollection()->transform(function ($item) use ($countMap) {
            $item->duplicate_count = $item->dedupe_hash ? (int) ($countMap[$item->dedupe_hash] ?? 1) : 1;
            return $item;
        });

        // Get total count for this webform
        $totalCount = ContactSubmission::where('webform_id', $webformId)->count();

        // Detect available fields - PRIORITY: Form-level (show only THIS form's fields)
        $formFields = $this->detectAvailableFields($webformId);

        // Get type and station fields for reference (used for preference inheritance, not for display)
        $typeFields = $submissionForm ? $this->detectAvailableFields(null, $submissionForm, $station) : [];
        $stationFields = $this->detectAvailableFields(null, null, $station);

        // Get new fields notification (fields detected since last visit)
        $newFields = $this->getNewFields($webformId);

        // Get smart defaults: First 4 fields from THIS form's JSON data
        // This ensures each form shows its most relevant fields by default
        $smartDefaults = FormDefaultsHelper::getDefaultsForForm($webformId, $submissionForm, $station, 'list');

        return Inertia::render('Forms/Detail', [
            'webformId' => $webformId,
            'formName' => $formName,
            'station' => $station,
            'submissionForm' => $submissionForm,
            'submissions' => $submissions,
            'totalCount' => $totalCount,
            'availableFields' => $formFields, // Form-specific fields (primary)
            'typeFields' => $typeFields, // For reference/inheritance
            'stationFields' => $stationFields, // For reference/inheritance
            'newFields' => array_column($newFields, 'key'), // New fields for notification
            'smartDefaults' => $smartDefaults, // Smart defaults for this type
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
                'plz_from' => $plzFrom,
                'plz_to' => $plzTo,
                'city' => $city,
                'gender' => $gender,
                'radius' => $radius,
                'radius_plz' => $radiusPlz,
                'hide_duplicates' => $hideDuplicates,
            ],
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'retentionDays' => ContactRetentionRule::effectiveDaysFor($webformId),
        ]);
    }

    public function getRetentionRule(string $webformId): JsonResponse
    {
        $rule = ContactRetentionRule::where('webform_id', $webformId)->first();
        $globalRule = ContactRetentionRule::whereNull('webform_id')->first();

        return response()->json([
            'webform_id' => $webformId,
            'retention_days' => $rule?->retention_days,
            'notes' => $rule?->notes,
            'global_retention_days' => $globalRule?->retention_days,
            'effective_days' => ContactRetentionRule::effectiveDaysFor($webformId),
        ]);
    }

    public function saveRetentionRule(Request $request, string $webformId): JsonResponse
    {
        $validated = $request->validate([
            'retention_days' => 'nullable|integer|min:1|max:3650',
            'notes' => 'nullable|string|max:500',
        ]);

        $rule = ContactRetentionRule::updateOrCreate(
            ['webform_id' => $webformId],
            [
                'retention_days' => $validated['retention_days'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'retention_days' => $rule->retention_days,
            'notes' => $rule->notes,
        ]);
    }

    public function getColumnConfig(string $webformId): JsonResponse
    {
        $formInfo = ContactSubmission::where('webform_id', $webformId)
            ->select('submission_form', 'station')
            ->first();

        $config = FormColumnConfig::forWebform(
            $webformId,
            $formInfo?->submission_form,
            $formInfo?->station
        );

        return response()->json([
            'success' => true,
            'visible_columns' => $config?->visible_columns ?? [],
        ]);
    }

    public function saveColumnConfig(Request $request, string $webformId): JsonResponse
    {
        $validated = $request->validate([
            'visible_columns' => 'required|array',
            'visible_columns.*' => 'string|max:255',
        ]);

        $config = FormColumnConfig::saveForWebform(
            $webformId,
            array_values($validated['visible_columns'])
        );

        return response()->json([
            'success' => true,
            'visible_columns' => $config->visible_columns,
        ]);
    }

    public function exportFormSubmissions(Request $request, string $webformId)
    {
        $format = strtolower((string) $request->get('format', 'csv'));
        $ids = $request->input('ids');
        $ids = is_array($ids) ? array_values(array_filter($ids, fn ($v) => is_numeric($v))) : [];

        $sortColumn = $request->get('sort_column', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $query = $this->buildFormDetailQuery($webformId, $request)
            ->when(!empty($ids), fn ($q) => $q->whereIn('id', $ids))
            ->orderBy($sortColumn, $sortDirection);

        // Canonical keys list from request (optional); defaults to visible fields.
        $fields = $request->input('fields');
        $fields = is_array($fields)
            ? array_values(array_filter($fields, fn ($v) => is_string($v) && trim($v) !== ''))
            : [];

        if (empty($fields)) {
            $fields = ['first_name', 'last_name', 'email', 'phone', 'postal_code'];
        }

        $safeName = Str::slug("form-{$webformId}");
        $timestamp = now()->format('Ymd_His');
        $ext = $format === 'xlsx' ? 'xlsx' : 'csv';
        $filename = "{$safeName}_{$timestamp}.{$ext}";

        if ($format === 'xlsx') {
            if (!class_exists(\Box\Spout\Writer\Common\Creator\WriterEntityFactory::class)) {
                return response()->json([
                    'success' => false,
                    'message' => 'XLSX export dependency is not installed.',
                ], 500);
            }

            $tmp = tempnam(sys_get_temp_dir(), 'xlsx_export_') . '.xlsx';
            $writer = \Box\Spout\Writer\Common\Creator\WriterEntityFactory::createXLSXWriter();
            $writer->openToFile($tmp);

            $header = array_merge(['ID', 'Submitted At'], array_map(fn ($f) => $this->getFieldLabel($f), $fields));
            $writer->addRow(\Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($header));

            $query->chunk(500, function ($rows) use ($writer, $fields) {
                foreach ($rows as $submission) {
                    $data = is_array($submission->data) ? $submission->data : [];
                    $row = [
                        $submission->id,
                        optional($submission->created_at)->toDateTimeString(),
                    ];
                    foreach ($fields as $f) {
                        $row[] = $this->resolveExportValue($data, (string) $f);
                    }
                    $writer->addRow(\Box\Spout\Writer\Common\Creator\WriterEntityFactory::createRowFromArray($row));
                }
            });

            $writer->close();

            return response()->download($tmp, $filename)->deleteFileAfterSend(true);
        }

        return response()->streamDownload(function () use ($query, $fields) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_merge(['id', 'submitted_at'], $fields));
            $query->chunk(500, function ($rows) use ($out, $fields) {
                foreach ($rows as $submission) {
                    $data = is_array($submission->data) ? $submission->data : [];
                    $row = [
                        $submission->id,
                        optional($submission->created_at)->toDateTimeString(),
                    ];
                    foreach ($fields as $f) {
                        $row[] = $this->resolveExportValue($data, (string) $f);
                    }
                    fputcsv($out, $row);
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Show a specific contact submission.
     */
    public function show(ContactSubmission $submission): Response
    {
        // Mark as read by current user when opening the detail view (legacy behavior).
        // This is idempotent because markAsReadBy uses firstOrCreate.
        $submission->markAsReadBy(auth()->id());

        // Load with reads + current user's mark state so the frontend knows the current state.
        $submission->load([
            'readsWithUsers',
            'marks' => function ($q) {
                $q->where('user_id', auth()->id());
            },
        ]);

        // Detect available fields - Form-specific (from this submission's form)
        $formFields = $this->detectAvailableFields($submission->webform_id);
        $typeFields = $submission->submission_form ? $this->detectAvailableFields(null, $submission->submission_form, $submission->station) : [];
        $stationFields = $submission->station ? $this->detectAvailableFields(null, null, $submission->station) : [];

        // Get new fields notification
        $newFields = $submission->webform_id ? $this->getNewFields($submission->webform_id) : [];
        $newFieldKeys = array_column($newFields, 'key');

        // Get smart defaults: First 4 fields from THIS form's JSON data
        $smartDefaults = FormDefaultsHelper::getDefaultsForForm(
            $submission->webform_id ?? '',
            $submission->submission_form,
            $submission->station,
            'detail'
        );

        return Inertia::render('Contact/Show', [
            'submission' => $submission,
            'availableFields' => $formFields, // Form-specific fields
            'typeFields' => $typeFields,
            'stationFields' => $stationFields,
            'newFields' => $newFieldKeys, // New fields for notification
            'smartDefaults' => $smartDefaults, // Smart defaults for this type
        ]);
    }

    /**
     * Mark a submission as read/unread by current user.
     */
    public function toggleRead(Request $request, ContactSubmission $submission)
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

        // Reload reads with user info for accurate response
        $submission->load(['readsWithUsers']);

        // If the request expects JSON (AJAX), return the updated reads list
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'reads' => $submission->readsWithUsers->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'user_id' => $r->user_id,
                        'read_at' => $r->read_at ? $r->read_at->toDateTimeString() : null,
                        'user' => [
                            'id' => $r->user->id ?? null,
                            'name' => $r->user->name ?? null,
                        ],
                    ];
                })->all(),
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Mark/unmark (star) a submission for the current user.
     * Always returns JSON for XHR/fetch requests.
     */
    public function toggleMark(Request $request, ContactSubmission $submission): \Illuminate\Http\JsonResponse
    {
        $userId = auth()->id();
        $existing = $submission->marks()->where('user_id', $userId)->first();

        if ($existing) {
            $existing->delete();
            $message = 'Unmarked';
        } else {
            $submission->markBy($userId);
            $message = 'Marked';
        }

        $submission->load([
            'marks' => function ($q) use ($userId) {
                $q->where('user_id', $userId);
            },
        ]);

        return response()->json([
            'success' => true,
            'message' => $message,
            'marked' => $submission->marks->isNotEmpty(),
        ]);
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
