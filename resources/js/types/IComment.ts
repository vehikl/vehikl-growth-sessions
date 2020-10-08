import {IUser} from '.';

export interface IComment {
    id: number;
    social_mob_id: number;
    content: string;
    time_stamp: string;
    user: IUser;
}
