<?php

namespace App\Enums;

/**
 * One thing that happened to a growth session.
 *
 * These are deliberately single events. A notification carries a list of them, so an edit that
 * moved the date, the time and the location is one notification naming three events - there is no
 * composite case to add, and a new event costs one case here instead of doubling the enum.
 */
enum NotificationType: string
{
    CASE GS_COMMENT_ADDED = 'gs_comment';
    CASE GS_TIME_CHANGED = 'gs_time';
    CASE GS_DELETED = 'gs_deleted';
    CASE GS_LOCATION_CHANGED = 'gs_location';
    CASE GS_DATE_CHANGED = 'gs_date';
}
