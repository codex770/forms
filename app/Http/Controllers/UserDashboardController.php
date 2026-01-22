<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserDashboardController extends Controller
{
    /**
     * Display the user dashboard with form overview.
     */
    public function index(): Response
    {
        // Define all stations with their display names
        $allStations = [
            'bigfm' => 'BigFM',
            'rpr1' => 'RPR1',
            'regenbogen' => 'Radio Regenbogen',
            'rockfm' => 'ROCK FM',
            'bigkarriere' => 'BigKarriere',
        ];

        // Get all unique webforms with their counts, grouped by station and type
        $webforms = ContactSubmission::selectRaw('
                station,
                webform_id,
                submission_form,
                COUNT(*) as count
            ')
            ->whereNotNull('webform_id')
            ->groupBy('station', 'webform_id', 'submission_form')
            ->orderBy('station')
            ->orderBy('submission_form')
            ->get();

        // Initialize all stations (even if they have no forms)
        $groupedForms = [];
        foreach ($allStations as $stationKey => $stationName) {
            $groupedForms[$stationKey] = [
                'station' => $stationKey,
                'stationName' => $stationName,
                'types' => [],
                'totalCount' => 0,
            ];
        }

        // Group forms by station → type → forms
        foreach ($webforms as $form) {
            $station = $form->station ?? 'unknown';
            $submissionForm = $form->submission_form ?? 'Uncategorized';

            // If station doesn't exist in our predefined list, add it
            if (!isset($groupedForms[$station])) {
                $groupedForms[$station] = [
                    'station' => $station,
                    'stationName' => ucfirst($station),
                    'types' => [],
                    'totalCount' => 0,
                ];
            }

            // Initialize type if it doesn't exist
            if (!isset($groupedForms[$station]['types'][$submissionForm])) {
                $groupedForms[$station]['types'][$submissionForm] = [
                    'type' => $submissionForm,
                    'forms' => [],
                    'totalCount' => 0,
                ];
            }

            // Add form to type
            $groupedForms[$station]['types'][$submissionForm]['forms'][] = [
                'webform_id' => $form->webform_id,
                'name' => $form->submission_form ?? $form->webform_id,
                'count' => $form->count,
            ];

            $groupedForms[$station]['types'][$submissionForm]['totalCount'] += $form->count;
            $groupedForms[$station]['totalCount'] += $form->count;
        }

        // Convert types from associative array to indexed array
        foreach ($groupedForms as $stationKey => $stationData) {
            $groupedForms[$stationKey]['types'] = array_values($stationData['types']);
        }

        return Inertia::render('UserDashboard', [
            'stations' => array_values($groupedForms),
        ]);
    }

    /**
     * Display all forms for a specific sender/station.
     */
    public function overview(Request $request, string $station): Response
    {
        // Define all stations with their display names
        $allStations = [
            'bigfm' => 'BigFM',
            'rpr1' => 'RPR1',
            'regenbogen' => 'Radio Regenbogen',
            'rockfm' => 'ROCK FM',
            'bigkarriere' => 'BigKarriere',
        ];

        // Validate station
        if (!isset($allStations[$station])) {
            abort(404, 'Station not found');
        }

        $stationName = $allStations[$station];

        // Get search and filter parameters
        $search = $request->get('search');
        $sortColumn = $request->get('sort_column', 'submission_form');
        $sortDirection = $request->get('sort_direction', 'asc');
        $perPage = $request->get('per_page', 25);

        // Validate sort column
        $allowedSortColumns = ['submission_form', 'webform_id', 'created_at', 'updated_at', 'entry_count'];
        if (!in_array($sortColumn, $allowedSortColumns)) {
            $sortColumn = 'submission_form';
        }

        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'asc';
        }

        // Query to get all unique forms for this station
        $query = ContactSubmission::selectRaw('
                webform_id,
                submission_form,
                MIN(created_at) as created_at,
                MAX(updated_at) as updated_at,
                COUNT(*) as entry_count
            ')
            ->where('station', $station)
            ->whereNotNull('webform_id')
            ->groupBy('webform_id', 'submission_form');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('webform_id', 'LIKE', "%{$search}%")
                    ->orWhere('submission_form', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        if ($sortColumn === 'entry_count') {
            $query->orderBy('entry_count', $sortDirection);
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        // Paginate results
        $forms = $query->paginate($perPage)->withQueryString();

        // Get total entry count for this station (all entries across all forms)
        $totalEntryCount = ContactSubmission::where('station', $station)
            ->whereNotNull('webform_id')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('webform_id', 'LIKE', "%{$search}%")
                        ->orWhere('submission_form', 'LIKE', "%{$search}%");
                });
            })
            ->count();

        // Transform the data for the frontend
        $formsData = $forms->map(function ($form) {
            return [
                'webform_id' => $form->webform_id,
                'name' => $form->submission_form ?? $form->webform_id,
                'submission_form' => $form->submission_form,
                'entry_count' => $form->entry_count,
                'created_at' => $form->created_at,
                'updated_at' => $form->updated_at,
            ];
        });

        return Inertia::render('SenderFormsOverview', [
            'station' => $station,
            'stationName' => $stationName,
            'forms' => [
                'data' => $formsData,
                'current_page' => $forms->currentPage(),
                'last_page' => $forms->lastPage(),
                'per_page' => $forms->perPage(),
                'total' => $forms->total(),
                'from' => $forms->firstItem(),
                'to' => $forms->lastItem(),
            ],
            'totalEntryCount' => $totalEntryCount,
            'filters' => [
                'search' => $search,
            ],
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }
}
