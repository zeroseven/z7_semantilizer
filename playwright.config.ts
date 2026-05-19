import { defineConfig, devices } from '@playwright/test';

/**
 * E2E against the local DDEV demo (see README).
 * Run: `ddev start` → `ddev init` → `npm run test:e2e`
 */
const baseURL = process.env.E2E_BASE_URL ?? 'https://z7-semantilizer.ddev.site';

export default defineConfig({
    testDir: './e2e',
    timeout: 60_000,
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? 'github' : 'list',
    use: {
        baseURL,
        trace: 'on-first-retry',
        ignoreHTTPSErrors: true,
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
