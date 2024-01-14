<?php

namespace Tests\Feature\GrowthSessions;

use App\GrowthSession;
use App\Tag;
use App\User;
use App\UserType;
use Illuminate\Http\Response;
use Tests\TestCase;

class GrowthSessionsUpdateTest extends TestCase
{
    public function testTheOwnerOfAGrowthSessionCanEditIt()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create([
                'is_public' => false
            ]);
        $newTopic = 'A brand new topic!';
        $newTitle = 'A whole new title!';
        $isPublic = true;

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'topic' => $newTopic,
            'title' => $newTitle,
            'is_public' => $isPublic
        ])->assertSuccessful();

        $this->assertEquals($newTopic, $growthSession->fresh()->topic);
        $this->assertEquals($newTitle, $growthSession->fresh()->title);
        $this->assertEquals($isPublic, $growthSession->fresh()->is_public);
    }

    public function testTheOwnerOfAGrowthSessionCanChangeTheAttendeeLimit()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['attendee_limit' => 5]);

        $newAttendeeLimit = 10;
        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertSuccessful();

        $this->assertEquals($newAttendeeLimit, $growthSession->fresh()->attendee_limit);
    }

    public function testAUserThatIsNotAnOwnerOfAGrowthSessionCannotEditIt()
    {
        $growthSession = GrowthSession::factory()->create();
        /** @var User $notTheOwner */
        $notTheOwner = User::factory()->create();

        $this->actingAs($notTheOwner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'topic' => 'Anything',
        ])->assertForbidden();
    }

    public function testAGrowthSessionCannotBeUpdatedWithAnAnydeskIdThatDoesNotExist()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create();

        $payload = ['anydesk_id' => 999999];

        $this->actingAs($growthSession->owner)->putJson(route('growth_sessions.update', $growthSession), $payload)
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['anydesk_id' => 'The selected anydesk id is invalid.']);
    }

    public function testTheNewAttendeeLimitHasToBeANumber()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['attendee_limit' => 5]);

        $newAttendeeLimit = 'bananas';
        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be an integer.']);
    }

    public function testItAllowsTheAttendeeLimitToBeUnset()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['attendee_limit' => 5]);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'attendee_limit' => null
        ])->assertSuccessful();

        $this->assertEquals(GrowthSession::NO_LIMIT, $growthSession->fresh()->attendee_limit);
    }

    public function testTheOwnerOfAGrowthSessionCanNotChangeTheAttendeeLimitBelowTheCurrentAttendeeCount()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['attendee_limit' => 6]);
        $users = User::factory()->times(5)->create();
        $growthSession->attendees()->attach($users->pluck('id'));

        $newAttendeeLimit = 4;
        $this->assertTrue($newAttendeeLimit < $growthSession->attendees()->count());
        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'attendee_limit' => $newAttendeeLimit
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['attendee_limit' => 'The attendee limit must be at least 5.']);
    }

    public function testTheOwnerCanChangeTheDateOfAnUpcomingGrowthSession()
    {
        $this->setTestNow('2020-01-01');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['date' => "2020-01-02"]);
        $newDate = '2020-01-10';

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'date' => $newDate,
        ])->assertSuccessful();

        $this->assertEquals($newDate, $growthSession->fresh()->toArray()['date']);
    }

    public function testTheDateOfTheGrowthSessionCannotBeSetToThePast()
    {
        $this->setTestNow('2020-01-05');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['date' => "2020-01-06"]);
        $newDate = '2020-01-03';

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'date' => $newDate,
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTheOwnerCannotUpdateAGrowthSessionThatAlreadyHappened()
    {
        $this->setTestNow('2020-01-05');
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['date' => "2020-01-01"]);
        $newDate = '2020-01-10';

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'date' => $newDate,
        ])->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function testTheOwnerCanUpdateTheWatcherFlag()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['allow_watchers' => true]);

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'allow_watchers' => false,
        ])->assertSuccessful();

        $this->assertFalse($growthSession->fresh()->allow_watchers);
    }

    public function testTheOwnerCanUpdateTheTags()
    {
        $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(), ['user_type_id' => UserType::OWNER_ID], 'owners')
            ->create(['allow_watchers' => true]);

        $tag = Tag::factory()->create();

        $this->actingAs($growthSession->owner)->putJson(route(
            'growth_sessions.update',
            ['growth_session' => $growthSession->id]
        ), [
            'tags' => [$tag->id],
        ])->assertSuccessful();

        $this->assertCount(1, $growthSession->fresh()->tags);
    }
}
