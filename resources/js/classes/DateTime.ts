import moment, {Moment} from "moment"

export class DateTime {
    private dateTime: string;
    private static nowString: string = (new Date()).toLocaleString('en-CA', {timeZone: 'America/Toronto'});

    constructor(dateTime?: string) {
        this.dateTime = dateTime || DateTime.nowString;
    }

    static parseByDate(date: string): DateTime {
        return new DateTime(moment(date, 'YYYY-MM-DD').toISOString(true));
    }

    static parseByTime(time: string): DateTime {
        const is12hrsFormat = /(am|pm)/gi.test(time);
        return new DateTime(moment(time, `hh:mm${is12hrsFormat ? ' a' : ''}`).toISOString(true));
    }

    static parseByDateTime(date: string, time: string): DateTime {
        return new DateTime(moment(`${date} ${time}`, 'YYYY-MM-DD hh:mm a').toISOString(true));
    }

    static today() {
        return new DateTime();
    }

    static setTestNow(date: string) {
        DateTime.nowString = moment(date).toISOString();
    }

    format(formatString: string): string {
        return moment(this.dateTime, "YYYY-MM-DD").format(formatString)
    }

    toDateString(): string {
        return this.format("YYYY-MM-DD")
    }

    toTimeString12Hours(withAmPm: boolean = true): string {
        return moment(this.dateTime).format(`hh:mm ${withAmPm ? "a" : ""}`)
    }

    toTimeString24Hours(): string {
        return moment(this.dateTime).format(`HH:mm`)
    }

    toGoogleCalendarStyle(): string {
        return this.toISOString().replace(/[\-:]/g, "").replace(".000", "")
    }

    toISOString(): string {
        return moment(this.dateTime).toISOString()
    }

    weekDayNumber(): number {
        return moment(this.dateTime).weekday();
    }

    weekDayString(): string {
        return this.format('dddd');
    }

    addDays(days: number): DateTime {
        this.dateTime = moment(new Date(this.dateTime.split(',')[0])).add(days, 'days').toISOString(true);
        return this;
    }

    isSameDay(date: DateTime): boolean {
        return moment(this.dateTime).isSame(date.toDateString(), 'days');
    }

    isToday(): boolean {
        return this.isSameDay(DateTime.today());
    }

    isInAPastDate(): boolean {
        const current: Moment = moment(this.toDateString());
        const today: Moment = moment(DateTime.today().toDateString());
        return current.isBefore(today);
    }

    isEvenDate() {
        return this.weekDayNumber() % 2 === 0
    }
}
