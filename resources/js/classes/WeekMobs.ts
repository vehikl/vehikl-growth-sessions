import {ISocialMob, IWeekMobs} from '../types';
import {SocialMob} from './SocialMob';
import {DateTimeApi} from '../services/DateTimeApi';

export class WeekMobs {
    weekMobs: IWeekMobs;
    weekDates: DateTimeApi[] = [];

    constructor(weekMobs: IWeekMobs) {
        this.weekMobs = weekMobs;
        let weekDateStrings = Object.keys(weekMobs);
        for (let weekDate of weekDateStrings) {
            this.weekMobs[weekDate] = weekMobs[weekDate].map((jsonMob: ISocialMob) => new SocialMob(jsonMob));
            this.weekDates.push(DateTimeApi.parseByDate(weekDate))
        }
    }

    get allMobs(): SocialMob[] {
        let mobs = [];
        for (let weekDate of this.weekDates) {
            mobs.push(...this.weekMobs[weekDate.toDateString()]);
        }
        return mobs as SocialMob[];
    }

    getMobByDate(date: DateTimeApi): SocialMob[] {
        return this.weekMobs[date.toDateString()] as SocialMob[];
    }

    get isReady(): boolean {
        const numberOfWeekdays = 5;
        return this.weekDates.length >= numberOfWeekdays;
    }

    get firstDay(): DateTimeApi {
        return this.weekDates[0];
    }

    get lastDay(): DateTimeApi {
        return this.weekDates[this.weekDates.length - 1];
    }

    static empty(): WeekMobs {
        return new WeekMobs({[DateTimeApi.today().toDateString()] : []});
    }
}
