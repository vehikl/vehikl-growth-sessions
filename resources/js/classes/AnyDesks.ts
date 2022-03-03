import {IAnyDesk} from "../types";

export class AnyDesks {
    anyDesksList: IAnyDesk[]

    constructor(anyDesksList: IAnyDesk[]) {
        this.anyDesksList = anyDesksList;
    }

    get allAnyDesks(): IAnyDesk[] {
        return this.anyDesksList as IAnyDesk[];
    }
}
