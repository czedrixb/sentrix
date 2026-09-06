import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end tests run against a real Laravel server serving the built SPA.
 *
 * The server is started with its own SQLite database so a run is deterministic
 * and never touches development data. The application code under test is
 * identical either way.
 */
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    timeout: 60_000,
    reporter: [['list'], ['html', { outputFolder: 'tests/e2e/report', open: 'never' }]],

    use: {
        baseURL: 'http://127.0.0.1:8123',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        viewport: { width: 1440, height: 900 },
    },

    projects: [
        { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    ],

    /*
     | The bundle is rebuilt and the database reseeded before the server starts,
     | so a run can never test a stale asset build or inherit rows left behind by
     | the previous run.
     */
    webServer: {
        command: [
            'npm run build',
            'php artisan migrate:fresh --seed --force --env=e2e',
            'php artisan serve --host=127.0.0.1 --port=8123 --env=e2e',
        ].join(' && '),
        url: 'http://127.0.0.1:8123/up',
        reuseExistingServer: false,
        timeout: 180_000,
        stdout: 'pipe',
        stderr: 'pipe',
    },
});
