export interface IUser {
    id: number;
    name: string;
    email: string;
    avatar: string;
}

export interface ISocialMob {
    id: number;
    topic: string;
    start_time: string;
    owner: IUser;
    users: IUser[];
}
