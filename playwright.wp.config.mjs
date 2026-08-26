import { defineConfig, devices } from '@playwright/test';

export default defineConfig( {
	testDir: './tests',
	testMatch: '**/wp71-editor.browser.spec.mjs',
	fullyParallel: false,
	forbidOnly: Boolean( process.env.CI ),
	retries: process.env.CI ? 1 : 0,
	workers: 1,
	reporter: process.env.CI ? 'github' : 'line',
	outputDir: './build/playwright-wp-results',
	use: {
		trace: 'retain-on-failure',
	},
	projects: [
		{
			name: 'authenticated-wordpress-chromium',
			use: {
				...devices[ 'Desktop Chrome' ],
			},
		},
	],
} );
