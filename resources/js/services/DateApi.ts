import moment, {Moment} from 'moment';

export class DateApi {
    private moment: Moment;
    private static nowString: string = moment().startOf('day').format('YYYY-MM-DD');

    constructor(date?: string) {
        this.moment = this.momentInstance(date);
    }

    private momentInstance(date?: string) {
        if (! date) {
            return moment().startOf('day');
        }

        let dateString: string = date ? date : DateApi.nowString;
        return moment(dateString, 'YYYY-MM-DD').startOf('day');
    }

    static parse(date: string): DateApi {
        return new DateApi(date);
    }

    static today() {
        return new DateApi(this.nowString);
    }

    static setTestNow(date: string) {
        DateApi.nowString = date;
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

    weekDay(): number {
        return this.moment.weekday();
    }

    addDays(days: number): DateApi {
        return new DateApi(this.moment.add(days, 'days').toString());
    }
}
