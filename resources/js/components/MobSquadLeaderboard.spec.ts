import MobSquadLeaderboard from '@/components/MobSquadLeaderboard.vue';
import type { IMobSquadMember } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

function mountLeaderboard(members: IMobSquadMember[]) {
    return mount(MobSquadLeaderboard, {
        props: { members },
        slots: { empty: '<p class="empty">Nobody yet.</p>' },
    });
}

const alex: IMobSquadMember = { id: 1, name: 'Alex Barry', avatar: 'https://example.test/alex.png', sessions_together_count: 7 };
const gavin: IMobSquadMember = { id: 2, name: 'Gavin Abeele', avatar: null, sessions_together_count: 1 };

describe('MobSquadLeaderboard', () => {
    it('renders one row per member, in the order given', () => {
        const rows = mountLeaderboard([alex, gavin]).findAll('[data-testid="mob-squad-member"]');

        expect(rows).toHaveLength(2);
        expect(rows[0].text()).toContain('Alex Barry');
        expect(rows[1].text()).toContain('Gavin Abeele');
    });

    it('ranks the rows by their position, so the leader reads as first', () => {
        const rows = mountLeaderboard([alex, gavin]).findAll('[data-testid="mob-squad-rank"]');

        expect(rows.map((rank) => rank.text())).toEqual(['1', '2']);
    });

    it('counts the sessions each member shared, pluralised', () => {
        const rows = mountLeaderboard([alex, gavin]).findAll('[data-testid="mob-squad-member"]');

        expect(rows[0].text()).toContain('7 mobs');
        expect(rows[1].text()).toContain('1 mob');
    });

    it('shows a member’s own avatar when they have one', () => {
        const avatar = mountLeaderboard([alex]).find('[data-testid="mob-squad-avatar"]');

        expect(avatar.find('img').attributes('src')).toBe('https://example.test/alex.png');
        expect(avatar.find('img').attributes('alt')).toBe('Alex Barry');
    });

    it('falls back to a member’s initials when they have no avatar', () => {
        const avatar = mountLeaderboard([gavin]).find('[data-testid="mob-squad-avatar"]');

        expect(avatar.find('img').exists()).toBe(false);
        expect(avatar.text()).toBe('GA');
    });

    it('falls back to initials for an avatar that is an empty string rather than absent', () => {
        const avatar = mountLeaderboard([{ ...alex, avatar: '' }]).find('[data-testid="mob-squad-avatar"]');

        expect(avatar.find('img').exists()).toBe(false);
        expect(avatar.text()).toBe('AB');
    });

    it('renders the empty slot instead of any rows when nobody has been mobbed with', () => {
        const wrapper = mountLeaderboard([]);

        expect(wrapper.findAll('[data-testid="mob-squad-member"]')).toHaveLength(0);
        expect(wrapper.find('.empty').text()).toBe('Nobody yet.');
    });
});
