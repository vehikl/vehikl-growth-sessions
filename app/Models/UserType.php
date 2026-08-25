<?php

namespace App\Models;

class UserType
{
    const OWNER_ID = 1;
    const ATTENDEE_ID = 2;
    const WATCHER_ID = 3;
    const WAITLISTED_ID = 4;
    const OWNER = 'owner';
    const ATTENDEE = 'attendee';
    const WATCHER = 'watcher';
    const WAITLISTED = 'waitlisted';

    const LOOKUP = [
        self::OWNER_ID => self::OWNER,
        self::ATTENDEE_ID => self::ATTENDEE,
        self::WATCHER_ID => self::WATCHER,
        self::WAITLISTED_ID => self::WAITLISTED,
    ];

    public static function getTypeById(int $id): ?string
    {
        return self::LOOKUP[$id] ?? null;
    }
}
