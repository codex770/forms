<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
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
}
