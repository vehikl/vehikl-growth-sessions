import { test, expect } from '@playwright/test';

test.describe('Authenticated seed', () => {
    test('seed', async ({ page }) => {
        await page.goto('/e2e/login');
        await expect(page).toHaveURL('/');
        await expect(page.getByText('Logout')).toBeVisible();
    });
});
