<?php

namespace App\Http\Controllers;

use App\Models\GrowthSession;
use App\Models\Series;
use App\Support\SeriesAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeriesController extends Controller
{
    /**
     * The series this member is running, for the picker in the growth session form.
     *
     * Only their own: a thread is somebody's to run, so those are the only ones they can file a
     * session under. Names rather than records, because a series is spoken of by its name - the
     * picker offers the threads they already have going, and typing anything else starts one.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(Series::namesOwnedBy($request->user()));
    }

    /**
     * File one growth session under a series, or take it out of one.
     *
     * A seam of its own rather than a field on the update: an owner looking back over the dashboard
     * files a session that has already happened, which {@see \App\Policies\GrowthSessionPolicy}
     * refuses to let them edit. {@see \App\Policies\GrowthSessionPolicy::fileInSeries()}
     *
     * The route only lets the session's owner through, so the series the name resolves to is one
     * of theirs - a name somebody else's thread goes by starts a thread of their own instead.
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
