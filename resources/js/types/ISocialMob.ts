import {IComment, IUser} from '.';

export interface ISocialMob {
    id: number;
    title: string;
    topic: string;
    location: string;
    date: string;
    start_time: string;
    end_time: string;
    attendee_limit: number | null;
    owner: IUser;
    attendees: IUser[];
    comments: IComment[];
}
