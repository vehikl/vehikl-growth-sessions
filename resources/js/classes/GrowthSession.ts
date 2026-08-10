import { DateTime } from '@/classes/DateTime';
import { inViewerTimeZone, sessionMoment, viewerDayOffset } from '@/lib/timezone';
import { GrowthSessionApi } from '@/services/GrowthSessionApi';
import { IAnyDesk, IComment, IGrowthSession, ITag, IUser } from '@/types';
import { Moment } from 'moment-timezone';
import { User } from './User';

/** A stored wall-clock time on the viewer's clock, falling back to the raw reading if it doesn't parse. */
function onViewerClock(date: string, time: string, format: string): string {
    const instant = inViewerTimeZone(date, time);

    return instant.isValid() ? instant.format(format) : time;
}

/** The compact UTC stamp a Google Calendar template link expects, e.g. `20200101T200000Z`. */
function toGoogleCalendarStamp(instant: Moment): string {
    return instant.utc().format('YYYYMMDD[T]HHmmss[Z]');
}

export class GrowthSession implements IGrowthSession {
    id!: number;
    title!: string;
    topic!: string;
    location!: string;
    date!: string;
    is_public!: boolean;
    is_unlisted!: boolean;
    start_time!: string;
    end_time!: string;
    owner!: User;
    attendees!: User[];
    watchers!: User[];
    comments!: IComment[];
    attendee_limit!: number | null;
    allow_watchers!: boolean;
    discord_channel_id!: string | null;
    anydesk!: IAnyDesk | null;
    tags!: ITag[];
    share_url?: string;

    constructor(growthSession: IGrowthSession) {
        this.refresh(growthSession);
    }

    get isLimitless(): boolean {
        const PHP_MAX_INT = 9223372036854776000;
        return !this.attendee_limit || this.attendee_limit >= PHP_MAX_INT;
    }

    get googleCalendarDate(): string {
        return (
            toGoogleCalendarStamp(sessionMoment(this.date, this.start_time)) + '/' + toGoogleCalendarStamp(sessionMoment(this.date, this.end_time))
        );
    }

    get startTime(): string {
        return onViewerClock(this.date, this.start_time, 'hh:mm');
    }

    get endTime(): string {
        return onViewerClock(this.date, this.end_time, 'hh:mm a');
    }

    /**
     * The board files a session under its Toronto date, so a viewer far enough east or west reads it
     * as a different day than the column it sits in. This says which day they should actually turn to.
     */
    get viewerDayNote(): string {
        const offset = viewerDayOffset(this.date, this.start_time);

        if (offset === 0) {
            return '';
        }

        return offset > 0 ? 'next day' : 'previous day';
    }

    /** The absolute instant the session starts, for the `datetime` attribute of a `<time>` element. */
    get startsAtIso(): string {
        const start = sessionMoment(this.date, this.start_time);

        return start.isValid() ? start.toISOString() : '';
    }

    /** The session's window as the viewer's own clock reads it, e.g. `03:30 – 05:00 pm`. */
    get timeRange(): string {
        const range = `${this.startTime} – ${this.endTime}`;

        return this.viewerDayNote ? `${range} (${this.viewerDayNote})` : range;
    }

    refresh(growthSession: IGrowthSession) {
        this.id = growthSession.id;
        this.title = growthSession.title;
        this.topic = growthSession.topic;
        this.location = growthSession.location;
        this.date = growthSession.date;
        this.is_public = growthSession.is_public;
        this.is_unlisted = growthSession.is_unlisted ?? false;
        this.allow_watchers = growthSession.allow_watchers;
        this.start_time = growthSession.start_time;
        this.end_time = growthSession.end_time;
        this.owner = new User(growthSession.owner);
        this.attendees = growthSession.attendees?.map((attendee) => new User(attendee)) ?? [];
        this.watchers = growthSession.watchers?.map((attendee) => new User(attendee)) ?? [];
        this.comments = growthSession.comments;
        this.attendee_limit = growthSession.attendee_limit;
        this.discord_channel_id = growthSession.discord_channel_id;
        this.anydesk = growthSession.anydesk;
        this.tags = growthSession.tags;
        this.share_url = growthSession.share_url;
    }

    get hasAlreadyHappened(): boolean {
        const end = sessionMoment(this.date, this.end_time);

        return end.isValid() && end.isBefore(DateTime.today().toISOString());
    }

    get renderedTitle(): string {
        if (this.title) {
            return this.title;
        }

        return `${this.owner.name}'s ${DateTime.parseByDate(this.date).weekDayString()} Growth Session`;
    }

    get calendarUrl(): string {
        const url = new URL('https://www.google.com/calendar/event');
        url.searchParams.append('action', 'TEMPLATE');
        url.searchParams.append('text', this.title);
        url.searchParams.append('dates', this.googleCalendarDate);
        url.searchParams.append('location', this.location);
        url.searchParams.append('details', this.topic);

        return url.toString();
    }

    /**
     * A link anyone can follow to this session: the board, on the week the session lives in,
     * with its detail drawer already open. Built from the current page rather than a hardcoded
     * path so it keeps working wherever the board is mounted, and deliberately carries no other
     * query parameters — the recipient should not inherit the sharer's filters or view.
     */
    get shareUrl(): string {
        const url = new URL(window.location.pathname, window.location.origin);
        url.searchParams.set('date', this.date);
        url.searchParams.set('session', String(this.id));

        return url.toString();
    }

    canWatch(user?: IUser | null): boolean {
        if (!user) {
            return false;
        }

        return this.allow_watchers && !this.isOwner(user) && !this.isAttendeeOrWatcher(user) && !this.hasAlreadyHappened;
    }

    canJoin(user?: IUser | null): boolean {
        if (!user) {
            return false;
        }

        return !this.isOwner(user) && !this.isAttendeeOrWatcher(user) && !this.hasAlreadyHappened && !this.hasReachedAttendeeLimit();
    }

    hasReachedAttendeeLimit(): boolean {
        if (this.isLimitless) {
            return false;
        }

        return this.attendees.length >= this.attendee_limit!;
    }

    async join() {
        try {
            const updated = await GrowthSessionApi.join(this);
            this.refresh(updated);
        } catch {}
    }

    async watch() {
        try {
            const updated = await GrowthSessionApi.watch(this);
            this.refresh(updated);
        } catch {}
    }

    canLeave(user?: IUser | null): boolean {
        if (!user) {
            return false;
        }
        return !this.isOwner(user) && this.isAttendeeOrWatcher(user) && !this.hasAlreadyHappened;
    }

    async leave() {
        this.refresh(await GrowthSessionApi.leave(this));
    }

    async postComment(content: string) {
        this.refresh(await GrowthSessionApi.postComment(this, content));
    }

    canDeleteComment(user: IUser | null | undefined, comment: IComment) {
        return user?.id === comment.user.id;
    }

    async deleteComment(comment: IComment) {
        this.refresh(await GrowthSessionApi.deleteComment(comment));
    }

    canEditOrDelete(user?: IUser | null): boolean {
        if (!user) {
            return false;
        }

        return this.isOwner(user) && !this.hasAlreadyHappened;
    }

    async delete() {
        return await GrowthSessionApi.delete(this);
    }

    isAttendeeOrWatcher(user?: IUser | null): boolean {
        if (!user) {
            return false;
        }
        return (
            this.attendees.filter((attendee) => attendee.id === user?.id).length > 0 ||
            this.watchers.filter((watcher) => watcher.id === user?.id).length > 0
        );
    }

    isOwner(user?: IUser | null): boolean {
        if (!user) {
            return false;
        }
        return this.owner.id === user.id;
    }
}
