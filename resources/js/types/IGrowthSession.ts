import { IComment, ITag, ITextSegment, IUser } from '.';
import { IAnyDesk } from './IAnyDesk';

export interface IGrowthSession {
    id: number;
    title: string;
    topic: string;
    topic_segments: ITextSegment[];
    location: string;
    location_segments: ITextSegment[];
    date: string;
    start_time: string;
    end_time: string;
    is_public: boolean;
    /** Not publicly listed, but reachable by anyone holding its invite link. */
    is_unlisted: boolean;
    allow_watchers: boolean;
    attendee_limit: number | null;
    discord_channel_id: string | null;
    owner: IUser | null;
    attendees: IUser[];
    watchers: IUser[];
    comments: IComment[];
    anydesk: IAnyDesk | null;
    tags: ITag[];
    /** Only sent to viewers who may hand the invitation out: the owner and Vehikl members. */
    share_url?: string;
}
