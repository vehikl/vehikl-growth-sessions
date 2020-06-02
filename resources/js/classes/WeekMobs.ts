import {ISocialMob, IWeekMobs} from '../types';
import {SocialMob} from './SocialMob';
import {DateTime} from '../classes/DateTime';

export class WeekMobs {
    weekMobs: IWeekMobs;
    weekDates: DateTime[] = [];

    constructor(weekMobs: IWeekMobs) {
        this.weekMobs = weekMobs;
        let weekDateStrings = Object.keys(weekMobs);
        for (let weekDate of weekDateStrings) {
            this.weekMobs[weekDate] = weekMobs[weekDate].map((jsonMob: ISocialMob) => new SocialMob(jsonMob));
            this.weekDates.push(DateTime.parseByDate(weekDate))
        }
    }

    get allMobs(): SocialMob[] {
        let mobs = [];
        for (let weekDate of this.weekDates) {
            mobs.push(...this.weekMobs[weekDate.toDateString()]);
        }
        return mobs as SocialMob[];
    }

    getMobByDate(date: DateTime): SocialMob[] {
        return this.weekMobs[date.toDateString()] as SocialMob[];
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
