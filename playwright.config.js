import { defineConfig, devices } from '@playwright/test';
export default defineConfig({
 testDir:'./tests/browser', timeout:45000, expect:{timeout:8000}, retries:0, workers:1,
 reporter:[['list'],['html',{outputFolder:process.env.SIGNALGRID_BROWSER_REPORT,open:'never'}],['json',{outputFile:process.env.SIGNALGRID_BROWSER_JSON}]],
 use:{baseURL:process.env.SIGNALGRID_BASE_URL||'http://127.0.0.1:8000',trace:'retain-on-failure',screenshot:'only-on-failure',video:'retain-on-failure'},
 projects:[{name:'chromium',use:{...devices['Desktop Chrome']}}]
});
