import { IUser } from '.';

export interface ICommentSegment {
    // Sourced from the backend, so this is left as `string` rather than a `'text' | 'image'` union -
    // JSON fixtures/API responses can't carry TS literal types.
    type: string;
    value: string;
}

export interface IComment {
    id: number;
    growth_session_id: number;
    content: string;
    segments: ICommentSegment[];
    time_stamp: string;
    user: IUser;
}
