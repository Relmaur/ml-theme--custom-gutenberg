import { beforeEach, describe, expect, it, vi } from 'vitest';
import metadata from './block.json';

describe('Hero block — registration', () => {
    beforeEach(async () => {
        // index.tsx registers as a side effect of being imported, so re-import
        // it fresh for every test.
        vi.resetModules();
        await import('./index');
    });

    it('registers block.json itself, so name and attributes have one source of truth', () => {
        expect(window.wp.blocks.registerBlockType).toHaveBeenCalledTimes(1);
        expect(window.wp.blocks.registerBlockType).toHaveBeenCalledWith(
            expect.objectContaining({ name: 'my-theme/hero', attributes: metadata.attributes }),
            expect.objectContaining({ edit: expect.any(Function), save: expect.any(Function) }),
        );
    });

    it('is a dynamic block: save() returns null so render.php owns the markup (ADR 0004)', () => {
        const [, settings] = window.wp.blocks.registerBlockType.mock.calls[0];

        expect(settings.save()).toBeNull();
    });

    it('also registers the custom RichText formats', () => {
        const names = window.wp.richText.registerFormatType.mock.calls.map(([name]) => name);

        expect(names).toEqual(['my-theme/highlight', 'my-theme/font-weight', 'my-theme/font-accent']);
    });
});

describe('Hero block — block.json contract', () => {
    it('uses API v3 and the theme block category', () => {
        expect(metadata.apiVersion).toBe(3);
        expect(metadata.category).toBe('theme-blocks');
    });

    it('lives in the my-theme/ namespace, which rigid mode allows (ADR 0005)', () => {
        expect(metadata.name.startsWith('my-theme/')).toBe(true);
    });

    it('renders on the server', () => {
        expect(metadata.render).toBe('file:./render.php');
    });

    it('does not declare scripts or styles (PHP registers them via Vite, ADR 0001)', () => {
        for (const key of ['editorScript', 'script', 'viewScript', 'style', 'editorStyle']) {
            expect(metadata).not.toHaveProperty(key);
        }
    });
});
