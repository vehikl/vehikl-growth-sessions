import {ISocialMob, IUser} from '../types';
import {DateTimeApi} from '../services/DateTimeApi';
import {SocialMobApi} from '../services/SocialMobApi';

export class SocialMob implements ISocialMob {
    id!: number;
    topic!: string;
    location!: string;
    date!: string;
    start_time!: string;
    end_time!: string;
    owner!: IUser;
    attendees!: IUser[];

    constructor(mob: ISocialMob) {
        this.refresh(mob);
    }

    refresh(mob: ISocialMob) {
        this.id = mob.id;
        this.topic = mob.topic;
        this.location = mob.location;
        this.date = mob.date;
        this.start_time = mob.start_time;
        this.end_time = mob.end_time;
        this.owner = mob.owner;
        this.attendees = mob.attendees;
    }

    get startTime(): string {
        return DateTimeApi.parseByTime(this.start_time).toTimeString12Hours(false);
    }

    get endTime(): string {
        return DateTimeApi.parseByTime(this.end_time).toTimeString12Hours();
    }

    get hasAlreadyHappened(): boolean {
        return DateTimeApi.parseByDate(this.date).isInAPastDate();
    }

    get isLocationAnUrl(): boolean {
        try {
            new URL(this.location);
            return true;
        } catch {
            return false;
        }
    }

    canJoin(user: IUser): boolean {
        if (!user) {
            return false;
        }
        return !this.isOwner(user) && !this.isAttendee(user) && !this.hasAlreadyHappened
    }

    async join() {
        this.refresh(await SocialMobApi.join(this));
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
