import { DateTime } from '@/classes/DateTime';
import { IGrowthSession, IWeekGrowthSessions } from '@/types';
import { GrowthSession } from './GrowthSession';

export class WeekGrowthSessions {
    weekGrowthSessions: IWeekGrowthSessions;
    weekDates: DateTime[] = [];

    constructor(weekGrowthSessions: IWeekGrowthSessions) {
        this.weekGrowthSessions = weekGrowthSessions;
        const weekDateStrings = Object.keys(weekGrowthSessions);
        for (const weekDate of weekDateStrings) {
            this.weekGrowthSessions[weekDate] = weekGrowthSessions[weekDate].map((growthSession: IGrowthSession) => new GrowthSession(growthSession));
            this.weekDates.push(DateTime.parseByDate(weekDate));
        }
    }

    get allGrowthSessions(): GrowthSession[] {
        const growthSessions = [];
        for (const weekDate of this.weekDates) {
            growthSessions.push(...this.weekGrowthSessions[weekDate.toDateString()]);
        }
        return growthSessions as GrowthSession[];
    }

    getSessionByDate(date: DateTime): GrowthSession[] {
        return (this.weekGrowthSessions[date.toDateString()] as GrowthSession[]) ?? [];
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

    get hasCurrentDate(): boolean {
        return !!this.weekDates.find((date) => date.isToday());
    }

    static empty(): WeekGrowthSessions {
        return new WeekGrowthSessions({ [DateTime.today().toDateString()]: [] });
    }
}
