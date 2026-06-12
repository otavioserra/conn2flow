import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.C2F_E2E_BASE_URL || 'http://127.0.0.1';

export default defineConfig({
  testDir: './tests/E2E',
  outputDir: './tests/E2E/test-results',
  reporter: [
    ['list'],
    ['html', { outputFolder: './tests/E2E/playwright-report', open: 'never' }]
  ],
  timeout: 30_000,
  use: {
    baseURL,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure'
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] }
    }
  ],
  webServer: process.env.C2F_E2E_WEB_SERVER_COMMAND
    ? {
        command: process.env.C2F_E2E_WEB_SERVER_COMMAND,
        url: baseURL,
        reuseExistingServer: true,
        timeout: 120_000
      }
    : undefined
});
