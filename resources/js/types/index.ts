export interface IUser {
    id: number;
    name: string;
    email: string;
    avatar: string;
}

export interface IComment {
    id: number;
    content: string;
    user: IUser;
}

export interface ISocialMob {
    id: number;
    topic: string;
    location: string;
    date: string;
    start_time: string;
    end_time: string;
    owner: IUser;
    attendees: IUser[];
    comments: IComment[];
}

export interface IWeekMobs {
    [dateString: string]: ISocialMob[],
}

export interface IStoreSocialMobRequest {
    location: string;
    topic: string;
    date: string;
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

export interface IValidationError {
    errors: {
        [field: string] : string[]
    },
    message: string;
}
