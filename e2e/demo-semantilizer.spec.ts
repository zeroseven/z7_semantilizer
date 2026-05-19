import { test, expect, type Page, type Response } from '@playwright/test';

const DEMO_PATH = '/semantilizer-demo';

async function gotoDemo(page: Page): Promise<Response | null> {
    try {
        return await page.goto(DEMO_PATH, { waitUntil: 'domcontentloaded', timeout: 15_000 });
    } catch {
        test.skip(true, 'Site unreachable (start DDEV: ddev start && npm run typo3:build)');
        return null;
    }
}

function skipIfDemoUnreachable(response: Response | null): void {
    test.skip(
        response === null || !response.ok(),
        `Demo page not reachable (HTTP ${response?.status() ?? 'n/a'}) — run: npm run typo3:build`,
    );
}

test.describe('Semantilizer demo', () => {
    test('demo page renders semantic headline hierarchy', async ({ page }) => {
        const response = await gotoDemo(page);
        skipIfDemoUnreachable(response);

        const main = page.locator('main, [role="main"], body').first();

        await expect(main.getByRole('heading', { name: /Chapter: Product overview/i })).toBeVisible({
            timeout: 15_000,
        });
        await expect(main.getByRole('heading', { name: /Section: Features/i })).toBeVisible();
        await expect(main.getByRole('heading', { name: /Subsection: Details/i })).toBeVisible();
    });

    test('headlines use h1, h2 and h3 elements in document order', async ({ page }) => {
        const response = await gotoDemo(page);
        skipIfDemoUnreachable(response);

        const headings = page.locator('h1, h2, h3');
        await expect(headings).toHaveCount(3, { timeout: 15_000 });

        await expect(headings.nth(0)).toHaveText(/Chapter: Product overview/);
        await expect(headings.nth(1)).toHaveText(/Section: Features/);
        await expect(headings.nth(2)).toHaveText(/Subsection: Details/);
    });

    test('semantilizer partial adds ce__header class to headlines', async ({ page }) => {
        const response = await gotoDemo(page);
        skipIfDemoUnreachable(response);

        const styledHeaders = page.locator('.ce__header');
        await expect(styledHeaders).toHaveCount(3, { timeout: 15_000 });
    });

    test('page contains body text from all demo content elements', async ({ page }) => {
        const response = await gotoDemo(page);
        skipIfDemoUnreachable(response);

        await expect(page.getByText('Top-level semantic headline')).toBeVisible({ timeout: 15_000 });
        await expect(page.getByText('Second-level headline')).toBeVisible();
        await expect(page.getByText('Third-level headline')).toBeVisible();
    });
});
