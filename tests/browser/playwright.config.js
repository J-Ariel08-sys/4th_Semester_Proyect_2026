import { defineConfig } from '@playwright/test';
export default defineConfig({
  testDir: '.', testMatch: '*.spec.js', workers: 1, timeout: 60000,
  use: { baseURL: process.env.SICLIN_E2E_URL || 'http://localhost:8001', headless: true, trace: 'off', screenshot: 'only-on-failure' },
  reporter: [['list']],
});
