<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class CalendarFeedController extends Controller
{
    public function show(string $token)
    {
        $user = User::where('calendar_token', $token)->firstOrFail();

        $sessions = $user->allSessions()
            ->withPivot('user_type_id')
            ->where('date', '>=', now()->subDays(30)->format('Y-m-d'))
            ->orderBy('date')
            ->get();

        $calendar = Calendar::create('Vehikl Growth Sessions')
            ->refreshInterval(60);

        foreach ($sessions as $session) {
            $start = Carbon::parse(
                $session->date->format('Y-m-d') . ' ' . $session->start_time->format('H:i'),
                'America/Toronto'
            );
            $end = Carbon::parse(
                $session->date->format('Y-m-d') . ' ' . $session->end_time->format('H:i'),
                'America/Toronto'
            );

            $isWatcher = $session->pivot->user_type_id === UserType::WATCHER_ID;
            $title = $isWatcher ? "[Watcher] {$session->title}" : $session->title;

            $event = Event::create($title)
                ->uniqueIdentifier("growth-session-{$session->id}@growth-sessions.vehikl.com")
                ->startsAt($start)
                ->endsAt($end);

            if ($session->topic) {
                $event->description($session->topic);
            }

            if ($session->location) {
                $event->address($session->location);
            }

            $calendar->event($event);
        }

        return response($calendar->get(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="growth-sessions.ics"',
        ]);
    }

    public function generateToken(Request $request)
    {
        $token = $request->user()->generateCalendarToken();

        return back();
    }
}
