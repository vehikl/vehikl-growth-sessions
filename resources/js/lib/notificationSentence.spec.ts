import { notificationSentence } from '@/lib/notificationSentence';
import type { INotification, INotificationGrowthSession, NotificationType } from '@/types';

function growthSession(overrides: Partial<INotificationGrowthSession> = {}): INotificationGrowthSession {
    return {
        id: 42,
        title: 'Pairing on Vue',
        location: 'AnyDesk 12',
        date: '2026-08-20',
        start_time: '03:30 pm',
        end_time: '05:00 pm',
        ...overrides,
    };
}

function notification(events: NotificationType[], overrides: Partial<INotification> = {}): INotification {
    return {
        id: 1,
        event_types: events,
        read: false,
        growth_session: growthSession(),
        initiator: { id: 7, name: 'Ada', avatar: 'ada.jpg' },
        created_at: '2026-08-17T18:32:00.000000Z',
        ...overrides,
    };
}

describe('notificationSentence', () => {
    it('says who commented and where', () => {
        expect(notificationSentence(notification(['gs_comment']))).toBe('Ada commented on Pairing on Vue');
    });

    it('names the session a deletion took, from its snapshot', () => {
        const deleted = notification(['gs_deleted'], { growth_session: growthSession({ id: null }) });

        expect(notificationSentence(deleted)).toBe('Ada deleted Pairing on Vue');
    });

    describe('an edit', () => {
        it('says what the new date is', () => {
            expect(notificationSentence(notification(['gs_date']))).toBe('Ada updated the date of Pairing on Vue, now Aug 20');
        });

        it('says what the new time is', () => {
            expect(notificationSentence(notification(['gs_time']))).toBe('Ada updated the time of Pairing on Vue, now 03:30 pm to 05:00 pm');
        });

        it('says what the new location is', () => {
            expect(notificationSentence(notification(['gs_location']))).toBe('Ada updated the location of Pairing on Vue, now at AnyDesk 12');
        });

        it('names two events in one sentence', () => {
            expect(notificationSentence(notification(['gs_time', 'gs_location']))).toBe(
                'Ada updated the time and location of Pairing on Vue, now 03:30 pm to 05:00 pm, at AnyDesk 12',
            );
        });

        it('names three events in one sentence', () => {
            expect(notificationSentence(notification(['gs_date', 'gs_time', 'gs_location']))).toBe(
                'Ada updated the date, time and location of Pairing on Vue, now Aug 20, 03:30 pm to 05:00 pm, at AnyDesk 12',
            );
        });

        // The payload orders the events, but a sentence that reorders itself would read differently
        // for the same edit depending on the writer, so the reading order is fixed here.
        it('reads the events in a fixed order however they arrive', () => {
            expect(notificationSentence(notification(['gs_location', 'gs_date', 'gs_time']))).toBe(
                notificationSentence(notification(['gs_date', 'gs_time', 'gs_location'])),
            );
        });

        it('does not repeat an event the payload sent twice', () => {
            expect(notificationSentence(notification(['gs_time', 'gs_time']))).toBe(
                'Ada updated the time of Pairing on Vue, now 03:30 pm to 05:00 pm',
            );
        });
    });

    describe('when the payload is incomplete', () => {
        it('stands in for an initiator it was not given', () => {
            expect(notificationSentence(notification(['gs_comment'], { initiator: null }))).toBe('Someone commented on Pairing on Vue');
        });

        it('stands in for a session that can no longer describe itself', () => {
            expect(notificationSentence(notification(['gs_comment'], { growth_session: null }))).toBe('Ada commented on a growth session');
        });

        it('stands in for a session whose snapshot predates the title', () => {
            const untitled = notification(['gs_deleted'], { growth_session: growthSession({ title: null }) });

            expect(notificationSentence(untitled)).toBe('Ada deleted a growth session');
        });

        // The alternative is "from null to null", which is worse than saying less.
        it('drops the time clause rather than half-reporting a range', () => {
            const halfARange = notification(['gs_time'], { growth_session: growthSession({ end_time: null }) });

            expect(notificationSentence(halfARange)).toBe('Ada updated the time of Pairing on Vue');
        });

        it('drops the location clause when there is no location', () => {
            const nowhere = notification(['gs_location'], { growth_session: growthSession({ location: null }) });

            expect(notificationSentence(nowhere)).toBe('Ada updated the location of Pairing on Vue');
        });

        it('drops a date the payload could not express as one', () => {
            const undated = notification(['gs_date'], { growth_session: growthSession({ date: 'the day after tomorrow' }) });

            expect(notificationSentence(undated)).toBe('Ada updated the date of Pairing on Vue');
        });

        // Which moved is still worth saying even when what it moved to is missing.
        it('keeps naming every event when only some of the values survived', () => {
            const noLocation = notification(['gs_time', 'gs_location'], { growth_session: growthSession({ location: null }) });

            expect(notificationSentence(noLocation)).toBe('Ada updated the time and location of Pairing on Vue, now 03:30 pm to 05:00 pm');
        });

        it('says the session moved even when none of the new values survived', () => {
            const nothingLeft = notification(['gs_time', 'gs_location'], { growth_session: growthSession({ location: null, end_time: null }) });

            expect(notificationSentence(nothingLeft)).toBe('Ada updated the time and location of Pairing on Vue');
        });

        it('still says something for an event it has not been taught', () => {
            const unknown = notification(['gs_something_new' as NotificationType]);

            expect(notificationSentence(unknown)).toBe('Ada updated Pairing on Vue');
        });

        // A build that has not learned the new event should still report the ones it knows.
        it('reports the events it knows and ignores the ones it does not', () => {
            const partlyKnown = notification(['gs_something_new' as NotificationType, 'gs_location']);

            expect(notificationSentence(partlyKnown)).toBe('Ada updated the location of Pairing on Vue, now at AnyDesk 12');
        });

        it('survives an empty list of events', () => {
            expect(notificationSentence(notification([]))).toBe('Ada updated Pairing on Vue');
        });

        it('survives a payload with nothing in it at all', () => {
            const bare = notification(['gs_deleted'], { initiator: null, growth_session: null });

            expect(notificationSentence(bare)).toBe('Someone deleted a growth session');
        });
    });
});
