import moment, {Moment} from 'moment';

export class DateTimeApi {
    private moment: Moment;
    private static nowString: string = moment().format('YYYY-MM-DD');

    constructor(date?: string) {
        this.moment = this.momentInstance(date);
    }

    private momentInstance(date?: string) {
        if (! date) {
            return moment();
        }

        let dateString: string = date ? date : DateTimeApi.nowString;
        return moment(dateString, 'YYYY-MM-DD');
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

    toString(): string {
        return this.format('YYYY-MM-DD');
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
        return new DateTimeApi(this.moment.add(days, 'days').format('YYYY-MM-DD'));
    }

    isSame(date: DateTimeApi): boolean {
        return this.moment.isSame(date.toString(), 'days');
    }

    isInThePast(): boolean {
        return this.moment.diff(DateTimeApi.today().toISOString(), 'days') < 0;
    }
}
