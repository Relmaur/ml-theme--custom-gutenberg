import React from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

type EditComponent = React.ComponentType<Record<string, unknown>>;

/** Format settings captured from registerFormatType, keyed by format name. */
let formats: Record<string, { tagName: string; className?: string; attributes?: object; edit: EditComponent }>;

const value = { text: 'Hello', formats: [], replacements: [], start: 0, end: 5 };

function renderFormatButton(name: string, props: Record<string, unknown> = {}) {
    const onChange = vi.fn();
    const Edit = formats[name].edit;
    render(<Edit isActive={false} value={value} onChange={onChange} {...props} />);
    return { onChange };
}

describe('Hero RichText formats', () => {
    beforeEach(async () => {
        // formats.tsx registers on import; import it fresh and collect what it registered.
        vi.resetModules();
        await import('./formats');
        formats = Object.fromEntries(
            window.wp.richText.registerFormatType.mock.calls.map(([name, settings]) => [name, settings]),
        );
    });

    it('registers each format with the markup render.php will output', () => {
        expect(formats['my-theme/highlight']).toMatchObject({ tagName: 'mark', className: 'text-highlight' });
        expect(formats['my-theme/font-weight']).toMatchObject({ tagName: 'span', className: 'text-weight' });
        expect(formats['my-theme/font-accent']).toMatchObject({ tagName: 'span', className: 'text-accent' });
    });

    it('lets font-weight keep its inline style and data attribute', () => {
        expect(formats['my-theme/font-weight'].attributes).toEqual({
            style: 'style',
            'data-weight': 'data-weight',
        });
    });

    it('toggles the highlight format on click', async () => {
        const { onChange } = renderFormatButton('my-theme/highlight');

        await userEvent.click(screen.getByRole('button', { name: 'Highlight' }));

        expect(window.wp.richText.toggleFormat).toHaveBeenCalledWith(value, { type: 'my-theme/highlight' });
        expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ toggled: { type: 'my-theme/highlight' } }));
    });

    it('toggles the accent format on click', async () => {
        const { onChange } = renderFormatButton('my-theme/font-accent');

        await userEvent.click(screen.getByRole('button', { name: 'Accent' }));

        expect(window.wp.richText.toggleFormat).toHaveBeenCalledWith(value, { type: 'my-theme/font-accent' });
        expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ toggled: { type: 'my-theme/font-accent' } }));
    });

    it('reflects the active state on the toolbar button', () => {
        renderFormatButton('my-theme/font-accent', { isActive: true });

        expect(screen.getByRole('button', { name: 'Accent' })).toHaveAttribute('aria-pressed', 'true');
    });

    describe('font weight picker', () => {
        it('is closed until the toolbar button is clicked', async () => {
            renderFormatButton('my-theme/font-weight');
            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();

            await userEvent.click(screen.getByRole('button', { name: 'Font Weight' }));

            expect(screen.getByRole('dialog')).toBeInTheDocument();
        });

        it('closes again when the toolbar button is clicked a second time', async () => {
            renderFormatButton('my-theme/font-weight');
            const toolbarButton = screen.getByRole('button', { name: 'Font Weight' });

            await userEvent.click(toolbarButton);
            await userEvent.click(toolbarButton);

            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        });

        it('applies the chosen weight as an inline style and closes', async () => {
            const { onChange } = renderFormatButton('my-theme/font-weight');

            await userEvent.click(screen.getByRole('button', { name: 'Font Weight' }));
            await userEvent.click(screen.getByRole('button', { name: 'Bold (700)' }));

            const expectedFormat = {
                type: 'my-theme/font-weight',
                attributes: { style: 'font-weight: 700', 'data-weight': '700' },
            };
            expect(window.wp.richText.applyFormat).toHaveBeenCalledWith(value, expectedFormat);
            expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ applied: expectedFormat }));
            expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        });

        it('removes the weight format with "Remove Weight"', async () => {
            const { onChange } = renderFormatButton('my-theme/font-weight');

            await userEvent.click(screen.getByRole('button', { name: 'Font Weight' }));
            await userEvent.click(screen.getByRole('button', { name: 'Remove Weight' }));

            expect(window.wp.richText.removeFormat).toHaveBeenCalledWith(value, 'my-theme/font-weight');
            expect(window.wp.richText.applyFormat).not.toHaveBeenCalled();
            expect(onChange).toHaveBeenCalledWith(expect.objectContaining({ removed: 'my-theme/font-weight' }));
        });
    });
});
