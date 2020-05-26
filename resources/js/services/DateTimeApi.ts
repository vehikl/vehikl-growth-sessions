import moment, {Moment} from 'moment';

export class DateTimeApi {
    private moment: Moment;
    private static nowString: string = moment().toISOString();

    constructor(date?: string) {
        this.moment = moment(date || DateTimeApi.nowString);
    }

    static parse(date: string): DateTimeApi {
        return new DateTimeApi(date);
    }

    static today() {
        return new DateTimeApi(this.nowString);
    }

    static setTestNow(date: string) {
        DateTimeApi.nowString = date;
    }

    format(formatString: string): string {
        return this.moment.format(formatString);
    }

    toDateString(): string {
        return this.format('YYYY-MM-DD');
    }

    toTimeString12Hours(): string {
        return this.moment.format('hh:mm a');
    }

    toISOString(): string {
        return this.moment.toISOString();
    }

    weekDayNumber(): number {
        return this.moment.weekday();
    }

    weekDayString(): string {
        return this.moment.format('dddd');
    }

    addDays(days: number): DateTimeApi {
        return new DateTimeApi(this.moment.add(days, 'days').toISOString());
    }

    isSame(date: DateTimeApi): boolean {
        return this.moment.isSame(date.toDateString(), 'days');
    }

    isInAPastDate(): boolean {
        return this.moment.diff(DateTimeApi.today().toISOString(), 'days') < 0;
    }
}
