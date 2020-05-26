import {DateTimeApi} from './DateTimeApi';

describe('DateTimeApi', () => {
   it('can add days', () => {
       const today = '2020-01-02';
       const tomorrow = '2020-01-03';
       const yesterday = '2020-01-01';

       DateTimeApi.setTestNow(today);

       expect(DateTimeApi.parse(today).addDays(0).toDateString()).toEqual(today);
       expect(DateTimeApi.parse(today).addDays(1).toDateString()).toEqual(tomorrow);
       expect(DateTimeApi.parse(today).addDays(-1).toDateString()).toEqual(yesterday);
   });

   describe('isInAPastDate determines if a given date is in a past day (ignores hours/time)', () => {
      it.each`
      timeStandard      | yesterday                     | earlierToday                  | today                     | tomorrow
      ${'24hrs'}        | ${'2020-01-01 23:59:59'}      | ${'2020-01-02 13:00:00'}      | ${'2020-01-02 16:00:00'}  | ${'2020-01-03 00:00:00'}
      ${'ISO string'}   | ${'2020-01-01T23:59:59Z'}     | ${'2020-01-02T13:00:00Z'}     | ${'2020-01-02T16:00:00Z'} | ${'2020-01-03T00:00:00Z'}
      ${'just date'}    | ${'2020-01-01'}               | ${'2020-01-02'}               | ${'2020-01-02'}           | ${'2020-01-03'}
      `('while using the $timeStandard time standard', ({yesterday, earlierToday, today, tomorrow}) => {
          DateTimeApi.setTestNow(today);

          expect(DateTimeApi.parse(yesterday).isInAPastDate(), `Failed to determine ${yesterday} is in a day before ${today}`).toBe(true);

          expect(DateTimeApi.parse(earlierToday).isInAPastDate(), `Failed to determine ${earlierToday} is in the same day as ${today}`).toBe(false);
          expect(DateTimeApi.parse(today).isInAPastDate(), `Failed to determine ${today} is in the same day as ${today}`).toBe(false);
          expect(DateTimeApi.parse(tomorrow).isInAPastDate(), `Failed to determine ${tomorrow} is not in a day before ${today}`).toBe(false);
      });
   });
});
