import {IAnyDesk} from "../types";

export class AnyDesk {
    name: string
    remote_desk_id: string

    constructor(anyDesk: IAnyDesk) {
        this.name = anyDesk.name;
        this.remote_desk_id = anyDesk.remote_desk_id;
    }
}
