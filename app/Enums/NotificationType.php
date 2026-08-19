<?php

namespace App\Enums;

enum NotificationType: string
{
    case GrowthSessionDateChanged = 'growth_session_date_changed';
    case GrowthSessionTimeChanged = 'growth_session_time_changed';
    case GrowthSessionLocationChanged = 'growth_session_location_changed';
    case GrowthSessionDeleted = 'growth_session_deleted';
    case GrowthSessionCommentAdded = 'growth_session_comment_added';
}
