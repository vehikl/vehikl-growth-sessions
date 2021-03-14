import {IUser} from '.';

export interface IComment {
    id: number;
    growth_session_id: number;
    content: string;
    time_stamp: string;
    user: IUser;
}
