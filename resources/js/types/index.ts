export interface IUser {
    id: number;
    name: string;
    email: string;
    avatar: string;
}

export interface ISocialMob {
    id: number;
    topic: string;
    location: string;
    start_time: string;
    end_time: string;
    owner: IUser;
    attendees: IUser[];
}

export interface IWeekMobs {
    [dateString: string]: ISocialMob[],
}

export interface IStoreSocialMobRequest {
    location: string;
    topic: string;
    start_time: string;
    end_time?: string;
}

export interface IUpdateSocialMobRequest {
    location?: string;
    topic?: string;
    start_time?: string;
    end_time?: string;
    date?: string;
}
