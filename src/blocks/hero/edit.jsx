import { useBlockProps, RichText, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

export default function Edit({ attributes, setAttributes }) {
    const { title, subtitle, imageUrl, imageId } = attributes;
    const blockProps = useBlockProps({ className: 'hero-section' });

    // Function to handle image selection
    const onSelectImage = (media) => {
        setAttributes({ imageUrl: media.url, imageId: media.id });
    };

    return (
        <div {...blockProps}>
            {/* 1. Sidebar Controls (The "Carbon Fields" Settings area) */}
            <InspectorControls>
                <PanelBody title="Hero Settings">
                    <TextControl
                        label="Subtitle (Plain Text)"
                        value={subtitle}
                        onChange={(val) => setAttributes({ subtitle: val })}
                        help="Enter the small text above the headline."
                    />
                </PanelBody>
            </InspectorControls>

            {/* 2. Visual Editor (Restricted) */}
            <div className="hero-inner">
                <div className="hero-content">
                    {/* RESTRICTION: Only H1 allowed, no bold/italic controls */}
                    <RichText
                        tagName="h1"
                        value={title}
                        onChange={(val) => setAttributes({ title: val })}
                        placeholder="Enter Hero Title..."
                        allowedFormats={[]} // Disable bold, italic, links
                        disableLineBreaks // Force single line
                    />
                    <p className="hero-subtitle-preview">{subtitle || 'Add subtitle in sidebar...'}</p>
                </div>

                <div className="hero-image">
                    <MediaUploadCheck>
                        <MediaUpload
                            onSelect={onSelectImage}
                            allowedTypes={['image']}
                            value={imageId}
                            render={({ open }) => (
                                <div onClick={open} style={{ cursor: 'pointer', background: '#f0f0f0', minHeight: '200px' }}>
                                    {imageUrl ? <img src={imageUrl} alt="" /> : 'Click to Upload Image'}
                                </div>
                            )}
                        />
                    </MediaUploadCheck>
                </div>
            </div>
        </div>
    );
}