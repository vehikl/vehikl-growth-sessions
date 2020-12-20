import {IComment, IGrowthSession, IUser} from '../types';
import {DateTime} from '../classes/DateTime';
import {SocialMobApi} from '../services/SocialMobApi';
import {User} from './User';

export class GrowthSession implements IGrowthSession {
    id!: number;
    title!: string;
    topic!: string;
    location!: string;
    date!: string;
    start_time!: string;
    end_time!: string;
    owner!: User;
    attendees!: User[];
    comments!: IComment[];
    attendee_limit!: number | null;

    constructor(growthSession: IGrowthSession) {
        this.refresh(growthSession);
    }

    refresh(mob: IGrowthSession) {
        this.id = mob.id;
        this.title = mob.title;
        this.topic = mob.topic;
        this.location = mob.location;
        this.date = mob.date;
        this.start_time = mob.start_time;
        this.end_time = mob.end_time;
        this.owner = new User(mob.owner);
        this.attendees = mob.attendees.map(attendee => new User(attendee));
        this.comments = mob.comments;
        this.attendee_limit = mob.attendee_limit;
    }

    get isLimitless(): boolean {
        const PHP_MAX_INT = 9223372036854776000;
        return ! this.attendee_limit || this.attendee_limit >= PHP_MAX_INT;
    }

    get startTime(): string {
        return DateTime.parseByTime(this.start_time).toTimeString12Hours(false);
    }

    get endTime(): string {
        return DateTime.parseByTime(this.end_time).toTimeString12Hours();
    }

    get googleCalendarDate(): string {
        return DateTime.parseByDateTime(this.date, this.start_time).toGoogleCalendarStyle() +
            '/' +DateTime.parseByDateTime(this.date, this.end_time).toGoogleCalendarStyle();
    }

    get hasAlreadyHappened(): boolean {
        return DateTime.parseByDate(this.date).isInAPastDate();
    }

    get renderedTitle(): string {
        if (this.title) {
            return this.title;
        }

        return `${this.owner.name}'s ${DateTime.parseByDate(this.date).weekDayString()} Mob`;
    }

    get calendarUrl(): string {
        let url = new URL('http://www.google.com/calendar/event');
        url.searchParams.append('action', 'TEMPLATE');
        url.searchParams.append('text', this.title);
        url.searchParams.append('dates', this.googleCalendarDate);
        url.searchParams.append('location', this.location);
        url.searchParams.append('details', this.topic);

        return url.toString();
    }

    canJoin(user: IUser): boolean {
        if (!user) {
            return false;
        }

        if (this.hasReachedAttendeeLimit()) {
            return false;
        }

        return !this.isOwner(user) && !this.isAttendee(user) && !this.hasAlreadyHappened
    }

    private hasReachedAttendeeLimit() {
        return this.attendee_limit === this.attendees.length;
    }

    async join() {
        try {
            const updated = await SocialMobApi.join(this);
            this.refresh(updated);
            if (window.confirm("Would you like to add it to your calendar?")) {
                window.open(this.calendarUrl, '_blank');
            }
        } catch (e) {

        }


    }

    canLeave(user: IUser): boolean {
        if (!user) {
            return false;
        }
        return !this.isOwner(user) && this.isAttendee(user) && !this.hasAlreadyHappened
    }

    async leave() {
        this.refresh(await SocialMobApi.leave(this));
    }

    async postComment(content: string) {
        this.refresh(await SocialMobApi.postComment(this, content));
    }

    canDeleteComment(user: IUser, comment: IComment) {
        return user?.id === comment.user.id;
    }

    async deleteComment(comment: IComment) {
        this.refresh(await SocialMobApi.deleteComment(comment));
    }

    canEditOrDelete(user: IUser): boolean {
        if (!user) {
            return false;
        }

        return this.isOwner(user) && !this.hasAlreadyHappened;
    }

    async delete() {
        return await SocialMobApi.delete(this);
    }

    isAttendee(user: IUser): boolean {
        if (!user) {
            return false;
        }
        return this.attendees.filter(attendee => attendee.id === user?.id).length > 0;
    }

    isOwner(user: IUser): boolean {
        if (!user) {
            return false;
        }
        return this.owner.id === user.id;
    }
}
