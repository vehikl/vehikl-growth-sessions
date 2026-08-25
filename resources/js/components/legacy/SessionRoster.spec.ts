import { User } from '@/classes/User';
import SessionRoster from '@/components/legacy/SessionRoster.vue';
import { IUser } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

/** The row also carries an avatar, whose initials would otherwise run into the name a text() away. */
const MEMBER_NAME = '[data-testid="session-roster-member"]';

function member(overrides: Partial<IUser> = {}): User {
    return new User({ id: 1, name: 'Ada Lovelace', avatar: '', github_nickname: 'ada', is_vehikl_member: true, ...overrides });
}

function mountRoster(members: User[], props: Record<string, unknown> = {}) {
    return mount(SessionRoster, { props: { heading: 'ATTENDEES (1/4)', members, ...props } });
}

describe('SessionRoster', () => {
    it('renders the heading exactly as the caller wrote it, count and all', () => {
        expect(mountRoster([member()]).text()).toContain('ATTENDEES (1/4)');
    });

    it('links each member to their GitHub profile, in a new tab and without leaking the referrer', () => {
        const link = mountRoster([member({ github_nickname: 'grace' })]).find('a');

        expect(link.attributes('href')).toBe('https://github.com/grace');
        expect(link.attributes('target')).toBe('_blank');
        expect(link.attributes('rel')).toBe('noopener noreferrer');
    });

    /** A guest whose identity is withheld arrives with no nickname — see GuestSafeUser. */
    it('lists a member with no nickname without pointing at a profile that is not theirs', () => {
        const wrapper = mountRoster([member({ name: 'Guest', github_nickname: '' })]);

        expect(wrapper.text()).toContain('Guest');
        expect(wrapper.find('a').exists()).toBe(false);
        expect(wrapper.find('svg').exists()).toBe(false);
    });

    it('lists members in the order it was given them', () => {
        const names = mountRoster([member({ id: 1, name: 'First' }), member({ id: 2, name: 'Second' })]).findAll(MEMBER_NAME);

        expect(names.map((name) => name.text())).toEqual(['First', 'Second']);
    });

    describe('when the order is the point', () => {
        it('marks the roster up as an ordered list, carrying the order in the sequence alone', () => {
            const wrapper = mountRoster([member({ id: 1, name: 'First' }), member({ id: 2, name: 'Second' })], {
                heading: 'WAITLIST (2)',
                ordered: true,
            });

            expect(wrapper.find('ol').exists()).toBe(true);
            expect(wrapper.findAll(MEMBER_NAME).map((name) => name.text())).toEqual(['First', 'Second']);
        });

        it('marks a roster whose order carries no meaning up as a plain list', () => {
            const wrapper = mountRoster([member({ name: 'First' })]);

            expect(wrapper.find('ul').exists()).toBe(true);
            expect(wrapper.find(MEMBER_NAME).text()).toBe('First');
        });
    });
});
