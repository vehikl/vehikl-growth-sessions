import moment, {Moment} from 'moment';

export class DateTimeApi {
    private dateTime: string;
    private static nowString: string = (new Date()).toISOString();

    constructor(dateTime?: string) {
        this.dateTime = dateTime || DateTimeApi.nowString;
    }

    static parseByDate(date: string): DateTimeApi {
        return new DateTimeApi(moment(date, 'YYYY-MM-DD').toISOString());
    }

    static parseByTime(time: string): DateTimeApi {
        const is12hrsFormat = /(am|pm)/gi.test(time);
        return new DateTimeApi(moment(time, `hh:mm${is12hrsFormat ? ' a' : ''}`).toISOString(true));
    }

    static today() {
        return new DateTimeApi();
    }

    static setTestNow(date: string) {
        DateTimeApi.nowString = moment(date).toISOString();
    }

    format(formatString: string): string {
        return moment(this.dateTime, 'YYYY-MM-DD').format(formatString);
    }

    toDateString(): string {
        return this.format('YYYY-MM-DD');
    }

    toTimeString12Hours(withAmPm: boolean = true): string {
        return moment(this.dateTime).format(`hh:mm ${withAmPm ? 'a' : ''}`);
    }

    toISOString(): string {
        return moment(this.dateTime).toISOString();
    }

    weekDayNumber(): number {
        return moment(this.dateTime).weekday();
    }

    weekDayString(): string {
        return this.format('dddd');
    }

    addDays(days: number): DateTimeApi {
        this.dateTime = moment(this.dateTime).add(days, 'days').toISOString();
        return this;
    }

    isSameDay(date: DateTimeApi): boolean {
        return moment(this.dateTime).isSame(date.toDateString(), 'days');
    }

    isInAPastDate(): boolean {
        const current: Moment = moment(this.toDateString());
        const today: Moment = moment(DateTimeApi.today().toDateString());
        return current.isBefore(today);
    }
}
