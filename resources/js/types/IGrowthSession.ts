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
    /** Everyone waiting for a seat, front of the queue first. */
    waitlist: IUser[];
    /** Where the viewer stands in that queue, counting from 1, or null if they are not in it. */
    waitlist_position: number | null;
    comments: IComment[];
    anydesk: IAnyDesk | null;
    tags: ITag[];
    series_name: string | null;
    /** Only sent to viewers who may hand the invitation out: the owner and Vehikl members. */
    share_url?: string;
}
