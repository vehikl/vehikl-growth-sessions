import {DateTime} from "./DateTime";
import {WeekGrowthSessions} from "./WeekGrowthSessions";

describe('WeekGrowthSessions', () => {
    describe('hasCurrentDate', () => {
        it('returns true if current date is in the growthSessions array', () => {
            const today = '2020-01-02';
            DateTime.setTestNow(today)
            const weekGrowthSessions = new WeekGrowthSessions({
                [today]: []
            })
            expect(weekGrowthSessions.hasCurrentDate).toBeTruthy()
        })

        it('returns false if current date is not in the growthSessions array', () => {
            const today = '2020-01-02';
            const tomorrow = '2020-01-03';
            DateTime.setTestNow(today)
            const weekGrowthSessions = new WeekGrowthSessions({
                [tomorrow]: []
            })
            expect(weekGrowthSessions.hasCurrentDate).toBeFalsy()
        })
    })
})
