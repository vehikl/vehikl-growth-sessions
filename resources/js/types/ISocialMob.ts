import {IComment, IUser} from '.';

export interface ISocialMob {
    id: number;
    topic: string;
    location: string;
    date: string;
    start_time: string;
    end_time: string;
    owner: IUser;
    attendees: IUser[];
    comments: IComment[];
}
