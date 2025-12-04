import { useBlockProps, RichText, MediaUpload, MediaUploadCheck, InspectorControls } from '@wordpress/block-editor';
import { Button, PanelBody, TextControl, ColorPicker } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const { title, subtitle, imageUrl, imageId, backgroundColor } = attributes;
    const blockProps = useBlockProps({ className: 'hero-section' });

    // Function to handle image selection
    const onSelectImage = (media) => {
        setAttributes({ imageUrl: media.url, imageId: media.id });
    };

    return (
        <section {...blockProps} style={{ backgroundColor: backgroundColor }}>
            {/* Sidebar Controls */}
            <InspectorControls>
                <PanelBody title="Hero Title & Subtitle" initialOpen={true}>
                    <TextControl
                        label="Title (Plain Text)"
                        value={title}
                        onChange={(val) => setAttributes({ title: val })}
                        help="Enter the main headline."
                    />
                    <TextControl
                        label="Subtitle (Plain Text)"
                        value={subtitle}
                        onChange={(val) => setAttributes({ subtitle: val })}
                        help="Enter the small text above the headline."
                    />
                </PanelBody>
            </InspectorControls>

            <InspectorControls group="styles">
                <PanelBody title="Hero Background" initialOpen={true}>
                    <fieldset className="ml-fieldset">
                        <legend>Background Color</legend>
                        <ColorPicker
                            color={backgroundColor}
                            onChange={(color) => setAttributes({ backgroundColor: color })}
                            enableAlpha
                            defaultValue="#000"
                        />
                    </fieldset>
                </PanelBody>
            </InspectorControls>

            {/* 2. Visual Editor - Mirrors render.php structure */}
            <div className="section-container">
                <div className="text-col">
                    <RichText
                        tagName="h1"
                        value={title}
                        onChange={(val) => setAttributes({ title: val })}
                        placeholder="Enter Hero Title..."
                        allowedFormats={['my-theme/highlight', 'my-theme/font-weight', 'my-theme/font-accent']}
                        // disableLineBreaks
                    />
                    <RichText
                        tagName="p"
                        value={subtitle}
                        onChange={(val) => setAttributes({ subtitle: val })}
                        placeholder="Enter Hero Subtitle..."
                        allowedFormats={['my-theme/highlight', 'my-theme/font-weight', 'my-theme/font-accent']}
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