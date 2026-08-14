import AnimatedCounter from '@/components/AnimatedCounter.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, test } from 'vitest';

function mountCounter(value: string) {
    return mount(AnimatedCounter, { props: { value } });
}

describe('AnimatedCounter', () => {
    test('reads as its finished value, so the number is never announced mid-climb', () => {
        expect(mountCounter('193h 30m').text()).toBe('193h 30m');
    });

    test('keeps the animated layer out of the accessibility tree and out of the text', () => {
        const wrapper = mountCounter('193h 30m');

        expect(wrapper.get('[data-testid="counter-layers"]').attributes('aria-hidden')).toBe('true');
        expect(wrapper.get('[data-testid="counter-animation"]').text()).toBe('');
    });

    test('gives each run of digits its own target to count up to', () => {
        const runs = mountCounter('11,229').findAll('[data-testid="counter-digits"]');

        expect(runs).toHaveLength(2);
        expect(runs[0].attributes('style')).toContain('--gs-num-to: 11');
        expect(runs[1].attributes('style')).toContain('--gs-num-to: 229');
    });

    test('counts in plain digits, so a run climbs 99 to 100 rather than sitting at 099', () => {
        const runs = mountCounter('11,229').findAll('[data-testid="counter-digits"]');

        expect(runs.some((run) => run.classes().some((name) => name.startsWith('gs-counter-digits--')))).toBe(false);
    });

    test('reserves the width of the finished value, so gaining a digit cannot shift the tile', () => {
        const ghost = mountCounter('11,229').get('[data-testid="counter-ghost"]');

        expect(ghost.attributes('style')).toContain('--gs-num-full: "11,229"');
        expect(ghost.text()).toBe('');
    });

    test('hands the text between the digits to CSS as well, so no visible character sits in the DOM twice', () => {
        const literals = mountCounter('193h 30m').findAll('[data-testid="counter-literal"]');

        expect(literals).toHaveLength(2);
        expect(literals[0].attributes('style')).toContain('--gs-num-text: "h "');
        expect(literals[1].attributes('style')).toContain('--gs-num-text: "m"');
    });

    test('quotes the text between the digits, so it cannot break out of the declaration', () => {
        const literal = mountCounter('1 "of" 2').findAll('[data-testid="counter-literal"]')[0];

        expect(literal.attributes('style')).toContain('--gs-num-text: " \\"of\\" "');
    });

    test('counts a value with no separators as a single run', () => {
        const wrapper = mountCounter('42');

        expect(wrapper.findAll('[data-testid="counter-digits"]')).toHaveLength(1);
        expect(wrapper.findAll('[data-testid="counter-literal"]')).toHaveLength(0);
    });

    test('replays the count when the value changes, rather than leaving a finished animation in place', async () => {
        const wrapper = mountCounter('3');
        const before = wrapper.get('[data-testid="counter-animation"]').element;

        await wrapper.setProps({ value: '7' });

        expect(wrapper.get('[data-testid="counter-animation"]').element).not.toBe(before);
        expect(wrapper.text()).toBe('7');
    });
});
