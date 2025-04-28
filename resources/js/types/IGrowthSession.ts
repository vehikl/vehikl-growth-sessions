import {IComment, IUser, ITag} from '.';
import {IAnyDesk} from "./IAnyDesk";

export interface IGrowthSession {
    id: number;
    title: string;
    topic: string;
    location: string;
    date: string;
    start_time: string;
    end_time: string;
    is_public: boolean;
    allow_watchers: boolean;
    attendee_limit: number | null;
    discord_channel_id: string | null;
    owner: IUser;
    attendees: IUser[];
    watchers: IUser[];
    comments: IComment[];
    anydesk: IAnyDesk | null;
    tags: ITag[];
}
