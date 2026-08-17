<?php

namespace App\Enums;

enum NotificationType: string
{
    CASE GS_COMMENT_ADDED = 'gs_comment';
    CASE GS_TIME_CHANGED = 'gs_time';
    CASE GS_DELETED = 'gs_deleted';
    CASE GS_LOCATION_CHANGED = 'gs_location';
    CASE GS_TIME_AND_LOCATION_CHANGED = 'gs_time_location';
}
