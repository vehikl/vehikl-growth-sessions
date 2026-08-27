<?php

namespace App\Enums;

/**
 * What a member holds at a growth session: the pivot's `user_type_id`, and everything the rest of
 * the app used to work out for itself from a list of those ids.
 *
 * "An owner occupies a seat" is stated once, here, rather than restated by every relation, query
 * and matrix that has to agree with it - so a fifth role is a case and its answers, not a hunt for
 * the id lists that would otherwise have to be found and widened one by one.
 */
enum Role: int
{
    case Owner = 1;
    case Attendee = 2;
    case Watcher = 3;
    case Waitlisted = 4;

    /** The name the `user_types` table stores beside the id. */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'owner',
            self::Attendee => 'attendee',
            self::Watcher => 'watcher',
            self::Waitlisted => 'waitlisted',
        };
    }

    /**
     * Whether holding this costs one of the growth session's seats. Hosting takes a seat exactly
     * as attending does; spectating and waiting in line take none - which is also the line
     * "who was in the mob" draws, since watching is not mobbing.
     */
    public function occupiesASeat(): bool
    {
        return match ($this) {
            self::Owner, self::Attendee => true,
            self::Watcher, self::Waitlisted => false,
        };
    }

    /**
     * Whether the holder will actually be in the room. No one still in line will be, which is
     * why waiting does not unmask the location the way taking a seat does.
     */
    public function countsAsPresent(): bool
    {
        return match ($this) {
            self::Owner, self::Attendee, self::Watcher => true,
            self::Waitlisted => false,
        };
    }

    /**
     * The ids of every role that takes up a seat, for the queries that have to ask it in SQL.
     * Derived from {@see occupiesASeat()}, so no query can be left holding a list that has fallen
     * out of step with it.
     *
     * @return list<int>
     */
    public static function seatOccupyingIds(): array
    {
        return array_values(array_map(
            fn (self $role) => $role->value,
            array_filter(self::cases(), fn (self $role) => $role->occupiesASeat()),
        ));
    }
}
