/**
 * ESLint flat config.
 *
 * Linting finds bugs (unused vars, broken hook rules, unsafe types). Formatting
 * belongs to Prettier, so eslint-config-prettier (last) turns off every rule
 * that would argue with it.
 */
import { defineConfig, globalIgnores } from 'eslint/config';
import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import prettier from 'eslint-config-prettier';
import globals from 'globals';

export default defineConfig([
    globalIgnores(['dist/', 'vendor/', 'coverage/', 'node_modules/']),

    js.configs.recommended,
    tseslint.configs.recommended,

    // Browser code: blocks, global JS, and their tests.
    {
        files: ['src/**/*.{js,ts,tsx}', 'tests/js/**/*.{ts,tsx}'],
        extends: [react.configs.flat.recommended, reactHooks.configs.flat.recommended],
        languageOptions: {
            globals: globals.browser,
        },
        settings: {
            // The React version WordPress core ships (wp-includes/js/dist/vendor/react.js).
            react: { version: '18.3' },
        },
        rules: {
            // Our blocks are typed with TypeScript, not PropTypes.
            'react/prop-types': 'off',
            // We use the classic JSX runtime (React.createElement), so every JSX
            // file DOES need React in scope. Keep the recommended rule on.
            'react/react-in-jsx-scope': 'error',
        },
    },

    // Build tooling runs in Node.
    {
        files: ['*.config.js'],
        languageOptions: {
            globals: globals.node,
        },
    },

    prettier,
]);
