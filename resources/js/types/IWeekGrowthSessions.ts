import {IGrowthSession} from '.';

export interface IWeekGrowthSessions {
    [dateString: string]: IGrowthSession[],
}
