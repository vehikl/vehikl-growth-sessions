export interface ITag {
    id: number;
    name: string;
}

/** A tag with how many Growth Sessions carry it, over whichever set the page counted. */
export interface ITagUsage extends ITag {
    sessions_count: number;
}
