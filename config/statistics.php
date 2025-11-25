<?php

$cacheDurationInHours = 24 * 3;
$cacheDurationInSeconds = 60 * 60 * $cacheDurationInHours;

return [
    'loosen_participation_rules' => [
        'user_ids' => array_filter(explode(',', env('STATISTICS_LOOSEN_PARTICIPATION_RULES_USER_IDS', '')))
    ],
    'max_mob_size' => 10,
    'cache_duration_in_seconds' => $cacheDurationInSeconds,
];
