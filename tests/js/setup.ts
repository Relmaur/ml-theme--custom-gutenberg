/**
 * Vitest setup: runs before every test file.
 */
import { createRequire } from 'node:module';
import { afterEach } from 'vitest';
import { cleanup } from '@testing-library/react';
import '@testing-library/jest-dom/vitest';
import { installWpGlobals } from './wp-globals';

/**
 * Put the REAL React on window, like WordPress does in the editor.
 *
 * `import React from 'react'` would go through our wordpressExternals plugin
 * and return `window.React`, which is exactly what we're trying to set up here.
 * Node's require() skips Vite's plugins and returns the same React instance
 * that @testing-library/react uses, so there's only one React in the test run.
 */
const require = createRequire(import.meta.url);
Object.assign(window, {
    React: require('react'),
    ReactDOM: require('react-dom'),
});

installWpGlobals();

// Unmount anything a test rendered, so tests can't leak DOM into each other.
afterEach(() => {
    cleanup();
});
