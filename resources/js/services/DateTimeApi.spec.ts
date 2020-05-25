import {DateTimeApi} from './DateTimeApi';

describe('DateTimeApi', () => {
   it('can add days', () => {
       const today = '2020-01-02';
       const tomorrow = '2020-01-03';
       const yesterday = '2020-01-01';

       DateTimeApi.setTestNow(today);

       expect(DateTimeApi.parse(today).addDays(0).toString()).toEqual(today);
       expect(DateTimeApi.parse(today).addDays(1).toString()).toEqual(tomorrow);
       expect(DateTimeApi.parse(today).addDays(-1).toString()).toEqual(yesterday);
   });

   it('can identify when a day is in the past', () => {
       const today = '2020-01-02';
       const tomorrow = '2020-01-03';
       const yesterday = '2020-01-01';

       DateTimeApi.setTestNow(today);

      expect(DateTimeApi.parse(yesterday).isInThePast()).toBe(true);
      expect(DateTimeApi.parse(today).isInThePast()).toBe(false);
      expect(DateTimeApi.parse(tomorrow).isInThePast()).toBe(false);
   });
});
