import {IUser} from "../types";

export class User implements IUser {
    avatar!: string;
    email!: string;
    id!: number;
    name!: string;
    username!: string;

    constructor(user: IUser) {
        this.avatar = user.avatar;
        this.email = user.email;
        this.id = user.id;
        this.name = user.name;
        this.username = user.username;
    }

    get githubURL() {
        return `https://github.com/${this.username}`;
    }
}
