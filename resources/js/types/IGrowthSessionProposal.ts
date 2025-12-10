import { IUser, ITag } from '.';
import { ITimePreference } from './ITimePreference';

export interface IGrowthSessionProposal {
    id: number;
    title: string;
    topic: string;
    status: 'pending' | 'approved' | 'rejected';
    creator: IUser;
    time_preferences: ITimePreference[];
    tags: ITag[];
    created_at: string;
    updated_at: string;
}
