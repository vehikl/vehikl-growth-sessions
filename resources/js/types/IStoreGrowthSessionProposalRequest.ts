import { ITimePreference } from './ITimePreference';

export interface IStoreGrowthSessionProposalRequest {
    title: string;
    topic: string;
    tags?: number[];
    time_preferences: Omit<ITimePreference, 'id' | 'growth_session_proposal_id' | 'created_at' | 'updated_at'>[];
}
