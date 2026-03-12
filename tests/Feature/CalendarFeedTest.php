<?php

namespace Tests\Feature;

use App\Models\GrowthSession;
use App\Models\User;
use App\Models\UserType;
use Tests\TestCase;

class CalendarFeedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setTestNowToASafeWednesday();
    }

    public function testValidTokenReturnsIcalResponse()
    {
        $user = User::factory()->create();
        $user->generateCalendarToken();

        $response = $this->get(route('calendar.feed', ['token' => $user->calendar_token]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $response->getContent());
    }

    public function testInvalidTokenReturns404()
    {
        $this->get('/calendar/' . str_repeat('a', 64) . '.ics')
            ->assertNotFound();
    }

    public function testFeedContainsSessionsWhereUserIsAttendee()
    {
        $user = User::factory()->create();
        $user->generateCalendarToken();

        $session = GrowthSession::factory()->create(['title' => 'Attending Session']);
        $session->attendees()->attach($user, ['user_type_id' => UserType::ATTENDEE_ID]);

        $response = $this->get(route('calendar.feed', ['token' => $user->calendar_token]));

        $this->assertStringContainsString('Attending Session', $response->getContent());
    }

    public function testFeedContainsSessionsWhereUserIsOwner()
    {
        $user = User::factory()->create();
        $user->generateCalendarToken();

        $session = GrowthSession::factory()->create(['title' => 'Owned Session']);
        $session->owners()->attach($user, ['user_type_id' => UserType::OWNER_ID]);

        $response = $this->get(route('calendar.feed', ['token' => $user->calendar_token]));

        $this->assertStringContainsString('Owned Session', $response->getContent());
    }

    public function testFeedIncludesWatcherSessionsWithPrefix()
    {
        $user = User::factory()->create();
        $user->generateCalendarToken();

        $session = GrowthSession::factory()->create(['title' => 'Watched Session']);
        $session->watchers()->attach($user, ['user_type_id' => UserType::WATCHER_ID]);

        $response = $this->get(route('calendar.feed', ['token' => $user->calendar_token]));

        $this->assertStringContainsString('[Watcher] Watched Session', $response->getContent());
    }

    public function testFeedDoesNotPrefixAttendeeSessionTitles()
    {
        $user = User::factory()->create();
        $user->generateCalendarToken();

        $session = GrowthSession::factory()->create(['title' => 'Regular Session']);
        $session->attendees()->attach($user, ['user_type_id' => UserType::ATTENDEE_ID]);

        $response = $this->get(route('calendar.feed', ['token' => $user->calendar_token]));
        $content = $response->getContent();

        $this->assertStringContainsString('SUMMARY:Regular Session', $content);
        $this->assertStringNotContainsString('[Watcher] Regular Session', $content);
    }

    public function testFeedExcludesUnrelatedSessions()
    {
        $user = User::factory()->create();
        $user->generateCalendarToken();

        GrowthSession::factory()->create(['title' => 'Unrelated Session']);

        $response = $this->get(route('calendar.feed', ['token' => $user->calendar_token]));

        $this->assertStringNotContainsString('Unrelated Session', $response->getContent());
    }

    public function testFeedExcludesOldSessions()
    {
        $user = User::factory()->create();
        $user->generateCalendarToken();

        $session = GrowthSession::factory()->create([
            'title' => 'Old Session',
            'date' => now()->subDays(60),
        ]);
        $session->attendees()->attach($user, ['user_type_id' => UserType::ATTENDEE_ID]);

        $response = $this->get(route('calendar.feed', ['token' => $user->calendar_token]));

        $this->assertStringNotContainsString('Old Session', $response->getContent());
    }

    public function testFeedIncludesRecentPastSessions()
    {
        $user = User::factory()->create();
        $user->generateCalendarToken();

        $session = GrowthSession::factory()->create([
            'title' => 'Recent Past Session',
            'date' => now()->subDays(15),
        ]);
        $session->attendees()->attach($user, ['user_type_id' => UserType::ATTENDEE_ID]);

        $response = $this->get(route('calendar.feed', ['token' => $user->calendar_token]));

        $this->assertStringContainsString('Recent Past Session', $response->getContent());
    }

    public function testAuthenticatedUserCanGenerateToken()
    {
        $user = User::factory()->create();
        $this->assertNull($user->calendar_token);

        $this->actingAs($user)
            ->post(route('calendar.generate-token'))
            ->assertRedirect();

        $this->assertNotNull($user->fresh()->calendar_token);
    }

    public function testRegeneratingTokenInvalidatesOldToken()
    {
        $user = User::factory()->create();
        $oldToken = $user->generateCalendarToken();

        $this->actingAs($user)
            ->post(route('calendar.generate-token'));

        $newToken = $user->fresh()->calendar_token;
        $this->assertNotEquals($oldToken, $newToken);

        $this->get('/calendar/' . $oldToken . '.ics')->assertNotFound();
        $this->get('/calendar/' . $newToken . '.ics')->assertOk();
    }

    public function testUnauthenticatedUserCannotGenerateToken()
    {
        $this->post(route('calendar.generate-token'))
            ->assertRedirect();
    }

    public function testFeedReturnsValidIcalFormat()
    {
        $user = User::factory()->create();
        $user->generateCalendarToken();

        $session = GrowthSession::factory()->create([
            'title' => 'Test Session',
            'topic' => 'Test topic description',
            'location' => 'Test Location',
        ]);
        $session->attendees()->attach($user, ['user_type_id' => UserType::ATTENDEE_ID]);

        $response = $this->get(route('calendar.feed', ['token' => $user->calendar_token]));
        $content = $response->getContent();

        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('END:VEVENT', $content);
        $this->assertStringContainsString('SUMMARY:Test Session', $content);
        $this->assertStringContainsString('DESCRIPTION:Test topic description', $content);
        $this->assertStringContainsString("UID:growth-session-{$session->id}@growth-sessions.vehikl.com", $content);
    }
}
