/**
 * A fake `window.wp` for tests.
 *
 * In WordPress, our `@wordpress/*` imports resolve to the browser globals that
 * core loads (see `wordpressExternals` in vite.config.js). Vitest uses the same
 * plugin, so tests need those globals too. These stubs are deliberately tiny:
 * plain HTML that exposes the props our code passes (value, onChange, labels),
 * so tests can check *our* wiring without loading the real block editor.
 *
 * If a block starts importing a new component, add a stub for it here.
 */
import { createRequire } from 'node:module';
import type { ReactNode } from 'react';
import { vi } from 'vitest';

// Real React via require(), for the same reason as in setup.ts: this module is
// imported before window.React exists, so `import React from 'react'` (which
// our externals plugin maps to window.React) would still be empty here.
const require = createRequire(import.meta.url);
const React: typeof import('react') = require('react');

/** Media object the fake MediaUpload "selects". Tests may replace it. */
export const fakeMedia = { id: 42, url: 'https://example.test/hero.jpg' };

interface RichTextStubProps {
    tagName?: string;
    value?: string;
    onChange: (value: string) => void;
    placeholder?: string;
    allowedFormats?: string[];
}

interface TextControlStubProps {
    label: string;
    value?: string;
    onChange: (value: string) => void;
    help?: string;
}

interface MediaUploadStubProps {
    onSelect: (media: typeof fakeMedia) => void;
    render: (args: { open: () => void }) => ReactNode;
}

interface ButtonStubProps {
    children?: ReactNode;
    onClick?: () => void;
    title?: string;
    isActive?: boolean;
}

const blockEditor = {
    useBlockProps: (props: Record<string, unknown> = {}) => props,

    // RichText is a contenteditable in WordPress; an <input> is enough to test
    // that our component reads `value` and forwards edits through `onChange`.
    RichText: ({ tagName = 'div', value, onChange, placeholder, allowedFormats }: RichTextStubProps) => (
        <input
            aria-label={placeholder}
            data-tag-name={tagName}
            data-allowed-formats={allowedFormats?.join(',')}
            value={value ?? ''}
            onChange={(event) => onChange(event.target.value)}
        />
    ),

    InspectorControls: ({ children }: { children: ReactNode }) => (
        <aside data-testid="inspector-controls">{children}</aside>
    ),

    MediaUploadCheck: ({ children }: { children: ReactNode }) => <>{children}</>,

    // The real MediaUpload opens the media library modal; here `open`
    // immediately "selects" fakeMedia.
    MediaUpload: ({ onSelect, render }: MediaUploadStubProps) => <>{render({ open: () => onSelect(fakeMedia) })}</>,

    RichTextToolbarButton: ({ title, onClick, isActive }: ButtonStubProps) => (
        <button type="button" aria-pressed={isActive} onClick={onClick}>
            {title}
        </button>
    ),
};

const components = {
    PanelBody: ({ title, children }: { title: string; children: ReactNode }) => (
        <fieldset>
            <legend>{title}</legend>
            {children}
        </fieldset>
    ),

    TextControl: ({ label, value, onChange, help }: TextControlStubProps) => (
        <label>
            {label}
            <input value={value ?? ''} onChange={(event) => onChange(event.target.value)} />
            {help && <small>{help}</small>}
        </label>
    ),

    Button: ({ children, onClick }: ButtonStubProps) => (
        <button type="button" onClick={onClick}>
            {children}
        </button>
    ),

    Popover: ({ children }: { children: ReactNode }) => <div role="dialog">{children}</div>,
};

/**
 * Rich-text helpers return a tagged copy of the value so tests can assert
 * which format operation was applied, and with what.
 */
const richText = {
    registerFormatType: vi.fn(),
    toggleFormat: vi.fn((value: object, format: object) => ({ ...value, toggled: format })),
    applyFormat: vi.fn((value: object, format: object) => ({ ...value, applied: format })),
    removeFormat: vi.fn((value: object, formatType: string) => ({ ...value, removed: formatType })),
};

const blocks = {
    registerBlockType: vi.fn(),
};

const fakeWp = { blockEditor, components, richText, blocks, element: React };

// Lets tests read `window.wp.blocks.registerBlockType` etc. with proper types.
declare global {
    interface Window {
        wp: typeof fakeWp;
    }
}

/** Install the fakes on window, the way WordPress core would. */
export function installWpGlobals(): void {
    window.wp = fakeWp;
}
