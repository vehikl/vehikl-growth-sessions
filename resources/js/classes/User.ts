import {IUser} from "../types";

export class User implements IUser {
    avatar!: string;
    id!: number;
    name!: string;
    github_nickname!: string;
    is_vehikl_member!: boolean;

    constructor(user: IUser) {
        this.avatar = user.avatar;
        this.id = user.id;
        this.name = user.name;
        this.github_nickname = user.github_nickname;
        this.is_vehikl_member = user.is_vehikl_member;
    }

    get githubURL() {
        return `https://github.com/${this.github_nickname}`;
    }
}
