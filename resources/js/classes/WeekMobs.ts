import {IGrowthSession, IWeekGrowthSessions} from '../types';
import {GrowthSession} from './GrowthSession';
import {DateTime} from '../classes/DateTime';

export class WeekMobs {
    weekMobs: IWeekGrowthSessions;
    weekDates: DateTime[] = [];

    constructor(weekMobs: IWeekGrowthSessions) {
        this.weekMobs = weekMobs;
        let weekDateStrings = Object.keys(weekMobs);
        for (let weekDate of weekDateStrings) {
            this.weekMobs[weekDate] = weekMobs[weekDate].map((jsonMob: IGrowthSession) => new GrowthSession(jsonMob));
            this.weekDates.push(DateTime.parseByDate(weekDate))
        }
    }

    get allMobs(): GrowthSession[] {
        let mobs = [];
        for (let weekDate of this.weekDates) {
            mobs.push(...this.weekMobs[weekDate.toDateString()]);
        }
        return mobs as GrowthSession[];
    }

    getSessionByDate(date: DateTime): GrowthSession[] {
        return this.weekMobs[date.toDateString()] as GrowthSession[];
    }

    get isReady(): boolean {
        const numberOfWeekdays = 5;
        return this.weekDates.length >= numberOfWeekdays;
    }

    get firstDay(): DateTime {
        return this.weekDates[0];
    }

    get lastDay(): DateTime {
        return this.weekDates[this.weekDates.length - 1];
    }

    static empty(): WeekMobs {
        return new WeekMobs({[DateTime.today().toDateString()] : []});
    }
}
