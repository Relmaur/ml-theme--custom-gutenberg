import React from 'react';
import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Edit, { type HeroAttributes } from './edit';
import { fakeMedia } from '../../../tests/js/wp-globals';

const defaultAttributes: HeroAttributes = {
    title: 'Welcome',
    subtitle: 'Subtitle text',
    imageUrl: '',
    imageId: 0,
};

function renderEdit(attributes: Partial<HeroAttributes> = {}) {
    const setAttributes = vi.fn();
    const { container } = render(
        <Edit attributes={{ ...defaultAttributes, ...attributes }} setAttributes={setAttributes} />,
    );
    // The preview <img> has alt="" (decorative), so it has no "img" role to query by.
    const previewImage = () => container.querySelector('.image-upload-area img');
    return { setAttributes, previewImage };
}

describe('Hero block — Edit', () => {
    it('shows the current title and subtitle in the canvas', () => {
        renderEdit();

        expect(screen.getByLabelText('Enter Hero Title...')).toHaveValue('Welcome');
        expect(screen.getByLabelText('Enter Hero Subtitle...')).toHaveValue('Subtitle text');
    });

    it('renders the title as an h1 and the subtitle as a paragraph (mirrors render.php)', () => {
        renderEdit();

        expect(screen.getByLabelText('Enter Hero Title...')).toHaveAttribute('data-tag-name', 'h1');
        expect(screen.getByLabelText('Enter Hero Subtitle...')).toHaveAttribute('data-tag-name', 'p');
    });

    it('only allows the theme text formats in RichText (rigid editing)', () => {
        renderEdit();

        for (const label of ['Enter Hero Title...', 'Enter Hero Subtitle...']) {
            expect(screen.getByLabelText(label)).toHaveAttribute(
                'data-allowed-formats',
                'my-theme/font-weight,my-theme/font-accent',
            );
        }
    });

    it('saves canvas edits to the title attribute', async () => {
        const { setAttributes } = renderEdit({ title: '' });

        await userEvent.type(screen.getByLabelText('Enter Hero Title...'), 'H');

        expect(setAttributes).toHaveBeenLastCalledWith({ title: 'H' });
    });

    it('saves canvas edits to the subtitle attribute', async () => {
        const { setAttributes } = renderEdit({ subtitle: '' });

        await userEvent.type(screen.getByLabelText('Enter Hero Subtitle...'), 'S');

        expect(setAttributes).toHaveBeenLastCalledWith({ subtitle: 'S' });
    });

    it('saves sidebar edits to the title attribute', async () => {
        const { setAttributes } = renderEdit({ title: '' });

        await userEvent.type(screen.getByLabelText(/Title \(Plain Text\)/), 'T');

        expect(setAttributes).toHaveBeenLastCalledWith({ title: 'T' });
    });

    it('saves sidebar edits to the subtitle attribute', async () => {
        const { setAttributes } = renderEdit({ subtitle: '' });

        await userEvent.type(screen.getByLabelText(/Subtitle \(Plain Text\)/), 'S');

        expect(setAttributes).toHaveBeenLastCalledWith({ subtitle: 'S' });
    });

    it('shows an upload placeholder when there is no image', () => {
        const { previewImage } = renderEdit({ imageUrl: '' });

        expect(screen.getByText('Click to Upload Image')).toBeInTheDocument();
        expect(previewImage()).not.toBeInTheDocument();
    });

    it('stores both the URL and the attachment ID when an image is selected', async () => {
        const { setAttributes } = renderEdit();

        await userEvent.click(screen.getByText('Click to Upload Image'));

        expect(setAttributes).toHaveBeenCalledWith({ imageUrl: fakeMedia.url, imageId: fakeMedia.id });
    });

    it('previews the selected image', () => {
        const { previewImage } = renderEdit({ imageUrl: 'https://example.test/current.jpg' });

        expect(previewImage()).toHaveAttribute('src', 'https://example.test/current.jpg');
    });
});
