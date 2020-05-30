import moment, {Moment} from 'moment';

export class DateTimeApi {
    private dateTime: string;
    private static nowString: string = (new Date()).toISOString();

    constructor(dateTime?: string) {
        this.dateTime = dateTime || DateTimeApi.nowString;
    }

    static parse(date: string): DateTimeApi {
        return new DateTimeApi(date);
    }

    static today() {
        return new DateTimeApi();
    }

    static setTestNow(date: string) {
        DateTimeApi.nowString = date;
    }

    format(formatString: string): string {
        return moment(this.dateTime).format(formatString);
    }

    toDateString(): string {
        return this.format('YYYY-MM-DD');
    }

    toTimeString12Hours(withAmPm: boolean = true): string {
        return moment(this.dateTime).format(`h:mm ${withAmPm ? 'A' : ''}`);
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

    isSame(date: DateTimeApi): boolean {
        return moment(this.dateTime).isSame(date.toDateString(), 'days');
    }

    isInAPastDate(): boolean {
        const current: Moment = moment(this.toDateString());
        const today: Moment = moment(DateTimeApi.today().toDateString());
        return current.isBefore(today);
    }
}
