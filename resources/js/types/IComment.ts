import { ITextSegment, IUser } from '.';

export interface IComment {
    id: number;
    growth_session_id: number;
    content: string;
    segments: ITextSegment[];
    time_stamp: string;
    user: IUser;
}
