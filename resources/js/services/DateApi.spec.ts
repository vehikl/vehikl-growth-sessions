import {DateTimeApi} from './DateTimeApi';

describe('DateApi', () => {
   it('can add days', () => {
       const today = '2020-01-02';
       const tomorrow = '2020-01-03';
       const yesterday = '2020-01-01';

       DateTimeApi.setTestNow(today);

       expect(DateTimeApi.parse(today).addDays(0).toString()).toEqual(today);
       expect(DateTimeApi.parse(today).addDays(1).toString()).toEqual(tomorrow);
       expect(DateTimeApi.parse(today).addDays(-1).toString()).toEqual(yesterday);
   });
});
