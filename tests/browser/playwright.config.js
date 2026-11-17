import { defineConfig } from '@playwright/test';
export default defineConfig({
  testDir: '.', testMatch: process.env.SICLIN_DEMO_TEST ? 'demo.spec.js' : 'siclin.spec.js', workers: 1, timeout: 60000,
  outputDir: process.env.SICLIN_DEMO_TEST ? 'test-results/demo' : 'test-results/e2e',
  use: { baseURL: process.env.SICLIN_E2E_URL || 'http://localhost:8001', headless: true, trace: 'off', screenshot: 'only-on-failure' },
  reporter: [['list']],
});
