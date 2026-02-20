import { test, expect } from '@playwright/test';

test.describe('Growth Session Creation', () => {
    test.beforeEach(async ({ request }) => {
        await request.post('/e2e/reset');
    });

    test('should create a new growth session', async ({ page }) => {
        await page.goto('/e2e/login');
        await expect(page).toHaveURL('/');
        await expect(page.getByText('Logout')).toBeVisible();

        await page.getByRole('button', { name: 'Add Session' }).first().click();

        await expect(page.getByRole('dialog')).toBeVisible();

        await page.getByRole('textbox', { name: 'Title', exact: true }).fill('E2E Test Growth Session');
        await page.getByRole('textbox', { name: 'Topic' }).fill('This is a test session created by Playwright');
        await page.getByRole('textbox', { name: 'Location' }).fill('https://meet.google.com/test-session');

        await page.getByRole('button', { name: 'Create' }).click();

        await expect(page.getByRole('heading', { name: 'E2E Test Growth Session' })).toBeVisible({ timeout: 10000 });
        await expect(page.getByText('This is a test session created by Playwright')).toBeVisible();
        await expect(page.getByRole('link', { name: 'https://meet.google.com/test-session', exact: true })).toBeVisible();
    });
});
