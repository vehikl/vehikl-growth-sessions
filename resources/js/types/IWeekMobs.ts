import {IGrowthSession} from '.';

export interface IWeekMobs {
    [dateString: string]: IGrowthSession[],
}
