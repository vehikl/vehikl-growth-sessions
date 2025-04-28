import {DateTime} from "./DateTime"

describe("DateTime", () => {
    it("can add days", () => {
        const today = "2020-01-02"
        const tomorrow = "2020-01-03"
        const yesterday = "2020-01-01"

        DateTime.setTestNow(today)

        expect(DateTime.parseByDate(today).addDays(0).toDateString()).toEqual(today)
        expect(DateTime.parseByDate(today).addDays(1).toDateString()).toEqual(tomorrow)
        expect(DateTime.parseByDate(today).addDays(-1).toDateString()).toEqual(yesterday)
    })

    describe("isInAPastDate determines if a given date is in a past day (ignores hours/time)", () => {
        it.each`
      timeStandard      | yesterday                     | earlierToday                  | today                     | tomorrow
      ${"24hrs"}        | ${"2020-01-01 23:59:59"}      | ${"2020-01-02 13:00:00"}      | ${"2020-01-02 16:00:00"}  | ${"2020-01-03 00:00:00"}
      ${"ISO string"}   | ${"2020-01-01T23:59:59Z"}     | ${"2020-01-02T13:00:00Z"}     | ${"2020-01-02T16:00:00Z"} | ${"2020-01-03T00:00:00Z"}
      ${"just date"}    | ${"2020-01-01"}               | ${"2020-01-02"}               | ${"2020-01-02"}           | ${"2020-01-03"}
      `("while using the $timeStandard time standard", ({yesterday, earlierToday, today, tomorrow}) => {
            DateTime.setTestNow(today)

            expect(DateTime.parseByDate(yesterday).isInAPastDate(), `Failed to determine ${yesterday} is in a day before ${today}`).toBe(true)

            expect(DateTime.parseByDate(earlierToday).isInAPastDate(), `Failed to determine ${earlierToday} is in the same day as ${today}`).toBe(false)
            expect(DateTime.parseByDate(today).isInAPastDate(), `Failed to determine ${today} is in the same day as ${today}`).toBe(false)
            expect(DateTime.parseByDate(tomorrow).isInAPastDate(), `Failed to determine ${tomorrow} is not in a day before ${today}`).toBe(false)
        })
    })
})

describe("Conversion from 24-hour format to 12-hour format", () => {
    const testData = [
        {input: "15:45", expectedOutput: "03:45 pm"},
        {input: "08:30", expectedOutput: "08:30 am"},
        {input: "00:00", expectedOutput: "12:00 am"},
        {input: "12:00", expectedOutput: "12:00 pm"}
    ]

    test.each(testData)(
        "converts \"%s\"",
        ({input, expectedOutput}) => {
            const result = DateTime.parseByTime(input).toTimeString12Hours()
            expect(result).toBe(expectedOutput)
        }
    )
})
describe("Conversion from 12-hour format to 24-hour format", () => {
    const testData = [
        {input: "03:45 pm", expectedOutput: "15:45"},
        {input: "08:30 am", expectedOutput: "08:30"},
        {input: "12:00 am", expectedOutput: "00:00"},
        {input: "12:00 pm", expectedOutput: "12:00"}
    ]

    test.each(testData)(
        "converts \"%s\"",
        ({input, expectedOutput}) => {
            const result = DateTime.parseByTime(input).toTimeString24Hours()
            expect(result).toBe(expectedOutput)
        }
    )
})
