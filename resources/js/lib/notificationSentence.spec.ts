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

function notification(type: NotificationType, overrides: Partial<INotification> = {}): INotification {
    return {
        id: 1,
        type,
        read: false,
        growth_session: growthSession(),
        initiator: { id: 7, name: 'Ada', avatar: 'ada.jpg' },
        created_at: '2026-08-17T18:32:00.000000Z',
        ...overrides,
    };
}

describe('notificationSentence', () => {
    it('says who commented and where', () => {
        expect(notificationSentence(notification('gs_comment'))).toBe('Ada commented on Pairing on Vue');
    });

    it('says what the new time is', () => {
        expect(notificationSentence(notification('gs_time'))).toBe('Ada updated the time of Pairing on Vue from 03:30 pm to 05:00 pm');
    });

    it('says what the new location is', () => {
        expect(notificationSentence(notification('gs_location'))).toBe('Ada changed the location of Pairing on Vue to AnyDesk 12');
    });

    it('says both when one edit moved both', () => {
        expect(notificationSentence(notification('gs_time_location'))).toBe(
            'Ada updated the time and location of Pairing on Vue from 03:30 pm to 05:00 pm, now at AnyDesk 12',
        );
    });

    it('names the session a deletion took, from its snapshot', () => {
        const deleted = notification('gs_deleted', { growth_session: growthSession({ id: null }) });

        expect(notificationSentence(deleted)).toBe('Ada deleted Pairing on Vue');
    });

    describe('when the payload is incomplete', () => {
        it('stands in for an initiator it was not given', () => {
            expect(notificationSentence(notification('gs_comment', { initiator: null }))).toBe('Someone commented on Pairing on Vue');
        });

        it('stands in for a session that can no longer describe itself', () => {
            expect(notificationSentence(notification('gs_comment', { growth_session: null }))).toBe('Ada commented on a growth session');
        });

        it('stands in for a session whose snapshot predates the title', () => {
            const untitled = notification('gs_deleted', { growth_session: growthSession({ title: null }) });

            expect(notificationSentence(untitled)).toBe('Ada deleted a growth session');
        });

        // The alternative is "from null to null", which is worse than saying less.
        it('drops the time clause rather than half-reporting a range', () => {
            const halfARange = notification('gs_time', { growth_session: growthSession({ end_time: null }) });

            expect(notificationSentence(halfARange)).toBe('Ada updated the time of Pairing on Vue');
        });

        it('drops the location clause when there is no location', () => {
            const nowhere = notification('gs_location', { growth_session: growthSession({ location: null }) });

            expect(notificationSentence(nowhere)).toBe('Ada changed the location of Pairing on Vue');
        });

        it('keeps whichever half of a combined change survived', () => {
            const timeOnly = notification('gs_time_location', { growth_session: growthSession({ location: null }) });
            const locationOnly = notification('gs_time_location', { growth_session: growthSession({ start_time: null }) });

            expect(notificationSentence(timeOnly)).toBe('Ada updated the time and location of Pairing on Vue from 03:30 pm to 05:00 pm');
            expect(notificationSentence(locationOnly)).toBe('Ada updated the time and location of Pairing on Vue now at AnyDesk 12');
        });

        it('still says something for a type it has not been taught', () => {
            const unknown = notification('gs_something_new' as NotificationType);

            expect(notificationSentence(unknown)).toBe('Ada updated Pairing on Vue');
        });

        it('survives a payload with nothing in it at all', () => {
            const bare = notification('gs_deleted', { initiator: null, growth_session: null });

            expect(notificationSentence(bare)).toBe('Someone deleted a growth session');
        });
    });
});
