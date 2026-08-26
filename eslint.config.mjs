import js from '@eslint/js';
import globals from 'globals';

const recommendedRules = {
	...js.configs.recommended.rules,
	// Guarded browser/API reads intentionally initialize variables before try/catch branches.
	'no-useless-assignment': 'off',
	'no-unused-vars': [
		'error',
		{
			argsIgnorePattern: '^_',
			caughtErrors: 'none',
		},
	],
};

export default [
	{
		ignores: [
			'assets/**/*.min.js',
			'build/**',
			'node_modules/**',
			'vendor/**',
		],
	},
	{
		files: [ 'assets/js/**/*.js' ],
		languageOptions: {
			ecmaVersion: 'latest',
			globals: globals.browser,
			sourceType: 'script',
		},
		rules: recommendedRules,
	},
	{
		files: [
			'eslint.config.mjs',
			'playwright.config.mjs',
			'playwright.wp.config.mjs',
			'scripts/**/*.mjs',
			'tests/**/*.test.mjs',
		],
		languageOptions: {
			ecmaVersion: 'latest',
			globals: globals.node,
			sourceType: 'module',
		},
		rules: recommendedRules,
	},
	{
		files: [ 'tests/**/*.browser.spec.mjs' ],
		languageOptions: {
			ecmaVersion: 'latest',
			globals: {
				...globals.browser,
				...globals.node,
			},
			sourceType: 'module',
		},
		rules: recommendedRules,
	},
];
