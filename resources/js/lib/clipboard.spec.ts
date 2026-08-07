import { copyToClipboard } from '@/lib/clipboard';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

function setClipboard(value: unknown) {
    Object.defineProperty(navigator, 'clipboard', { value, configurable: true });
}

describe('copyToClipboard', () => {
    beforeEach(() => {
        vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
        setClipboard(undefined);
    });

    it('writes through the clipboard API when it is available', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        setClipboard({ writeText });

        expect(await copyToClipboard('https://example.test/board?session=1')).toBe(true);
        expect(writeText).toHaveBeenCalledWith('https://example.test/board?session=1');
    });

    it('falls back to a selection copy where the clipboard API is missing', async () => {
        setClipboard(undefined);
        const execCommand = vi.fn().mockReturnValue(true);
        Object.defineProperty(document, 'execCommand', { value: execCommand, configurable: true });

        expect(await copyToClipboard('some text')).toBe(true);
        expect(execCommand).toHaveBeenCalledWith('copy');
    });

    it('falls back to a selection copy when the clipboard API rejects', async () => {
        setClipboard({ writeText: vi.fn().mockRejectedValue(new Error('denied')) });
        const execCommand = vi.fn().mockReturnValue(true);
        Object.defineProperty(document, 'execCommand', { value: execCommand, configurable: true });

        expect(await copyToClipboard('some text')).toBe(true);
        expect(execCommand).toHaveBeenCalled();
    });

    it('reports failure when neither route copies, leaving no stray field behind', async () => {
        setClipboard(undefined);
        Object.defineProperty(document, 'execCommand', {
            value: vi.fn().mockReturnValue(false),
            configurable: true,
        });

        expect(await copyToClipboard('some text')).toBe(false);
        expect(document.querySelectorAll('textarea')).toHaveLength(0);
    });
});
