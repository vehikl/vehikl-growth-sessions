import {DateApi} from './DateApi';

describe('DateApi', () => {
   it('can add days', () => {
       const today = '2020-01-02';
       const tomorrow = '2020-01-03';
       const yesterday = '2020-01-01';

       DateApi.setTestNow(today);

       expect(DateApi.parse(today).addDays(0).toString()).toEqual(today);
       expect(DateApi.parse(today).addDays(1).toString()).toEqual(tomorrow);
       expect(DateApi.parse(today).addDays(-1).toString()).toEqual(yesterday);
   });
});
