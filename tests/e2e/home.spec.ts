import { test, expect } from '@playwright/test';

test('home page shows login button', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByText('Login with Github')).toBeVisible();
});
