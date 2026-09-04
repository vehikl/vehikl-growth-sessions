<?php

namespace App\Http\Controllers;

use App\Models\GrowthSession;
use App\Models\Series;
use App\Support\SeriesAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeriesController extends Controller
{
    /** The names of the series this member owns — the only ones they may file a session under. */
    public function index(Request $request): JsonResponse
    {
        return response()->json(Series::namesOwnedBy($request->user()));
    }

    /**
     * File one growth session under a series, or take it out of one.
     *
     * Separate from `update`, which the policy closes on past sessions.
     * {@see \App\Policies\GrowthSessionPolicy::fileInSeries()}
     */
    public function file(Request $request, GrowthSession $growthSession): JsonResponse
    {
        $validated = $request->validate([
            'series_name' => 'present|nullable|string|max:45',
        ]);

        SeriesAssignment::for($growthSession, $request->user())->file($validated['series_name']);
        $growthSession->save();

        return response()->json(['series_name' => $growthSession->series_name]);
    }
}
