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

        // Get all unique webforms with their counts, grouped by station
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
                'forms' => [],
                'totalCount' => 0,
            ];
        }

        // Add forms to their respective stations
        foreach ($webforms as $form) {
            $station = $form->station ?? 'unknown';
            
            // If station doesn't exist in our predefined list, add it
            if (!isset($groupedForms[$station])) {
                $groupedForms[$station] = [
                    'station' => $station,
                    'stationName' => ucfirst($station),
                    'forms' => [],
                    'totalCount' => 0,
                ];
            }
            
            $groupedForms[$station]['forms'][] = [
                'webform_id' => $form->webform_id,
                'name' => $form->submission_form ?? $form->webform_id,
                'count' => $form->count,
            ];
            
            $groupedForms[$station]['totalCount'] += $form->count;
        }

        return Inertia::render('UserDashboard', [
            'stations' => array_values($groupedForms),
        ]);
    }
}
