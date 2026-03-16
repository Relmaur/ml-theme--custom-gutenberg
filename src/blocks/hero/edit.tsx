import React from 'react';
import { useBlockProps, RichText, MediaUpload, MediaUploadCheck, InspectorControls } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, ColorPicker } from '@wordpress/components';

/**
 * Interfaces
 */
export interface HeroAttributes {
    title: string,
    subtitle: string,
    imageUrl: string,
    imageId: number
};

// Props
interface EditProps {
    attributes: HeroAttributes,
    setAttributes: (attributes: Partial<HeroAttributes>) => void
}

export default function Edit({ attributes, setAttributes }: EditProps): JSX.Element {

    const { title, subtitle, imageUrl, imageId } = attributes;

    const blockProps = useBlockProps({ className: 'hero-section' });

    // Function to handle image selection
    const onSelectImage = (media: { url: string; id: number }) => {
        setAttributes({ imageUrl: media.url, imageId: media.id });
    };

    return (
        <section {...blockProps}>
            {/* Sidebar Controls */}
            <InspectorControls>
                <PanelBody title="Hero Title & Subtitle" initialOpen={true}>
                    <TextControl
                        label="Title (Plain Text)"
                        value={title}
                        onChange={(val: string) => setAttributes({ title: val })}
                        help="Enter the main headline."
                    />
                    <TextControl
                        label="Subtitle (Plain Text)"
                        value={subtitle}
                        onChange={(val: string) => setAttributes({ subtitle: val })}
                        help="Enter the small text above the headline."
                    />
                </PanelBody>
            </InspectorControls>

            {/* 2. Visual Editor - Mirrors render.php structure */}
            <div className="section-container">
                <div className="text-col">
                    <RichText
                        tagName="h1"
                        value={title}
                        onChange={(val: string) => setAttributes({ title: val })}
                        placeholder="Enter Hero Title..."
                        allowedFormats={['my-theme/font-weight', 'my-theme/font-accent']}
                    // disableLineBreaks
                    />
                    <RichText
                        tagName="p"
                        value={subtitle}
                        onChange={(val: string) => setAttributes({ subtitle: val })}
                        placeholder="Enter Hero Subtitle..."
                        allowedFormats={['my-theme/font-weight', 'my-theme/font-accent']}
                    // disableLineBreaks
                    />
                    {/* <p className="subtitle">{subtitle || 'Add subtitle in sidebar...'}</p> */}
                </div>

                <div className="img-col">
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={onSelectImage}
                            allowedTypes={['image']}
                            value={imageId}
                            render={({ open }) => (
                                <div className="image-upload-area" onClick={open}>
                                    {imageUrl ? (
                                        <img src={imageUrl} alt="" />
                                    ) : (
                                        <span className="upload-placeholder">Click to Upload Image</span>
                                    )}
                                </div>
                            )}
                        />
                    </MediaUploadCheck>
                </div>
            </div>
        </section>
    );
}