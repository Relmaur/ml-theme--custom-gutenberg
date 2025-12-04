// Access WordPress globals directly since Vite externals don't export all named exports
const { registerFormatType, toggleFormat, applyFormat, removeFormat } = wp.richText;
const { RichTextToolbarButton } = wp.blockEditor;
const { useState } = wp.element;
const { Button, Popover, Fill } = wp.components;

const HighlightButton = ({ isActive, onChange, value }) => {
    return (
        <>
            {/* Button in the dropdown */}
            <RichTextToolbarButton
                icon="admin-customizer"
                title="Highlight"
                isActive={isActive}
                onClick={() => {
                    onChange(
                        toggleFormat(value, {
                            type: 'my-theme/highlight',
                        })
                    );
                }}
            />
        </>
    );
};

// Register custom formats
registerFormatType('my-theme/highlight', {
    title: 'Highlight',
    tagName: 'mark',
    className: 'text-highlight',
    edit: HighlightButton,
});

// Font Weight Button with Popover
const FontWeightButton = ({ isActive, onChange, value, contentRef }) => {
    const [isOpen, setIsOpen] = useState(false);

    const fontWeights = [
        { label: 'Thin (100)', value: '100' },
        { label: 'Extra Light (200)', value: '200' },
        { label: 'Light (300)', value: '300' },
        { label: 'Regular (400)', value: '400' },
        { label: 'Medium (500)', value: '500' },
        { label: 'Semi Bold (600)', value: '600' },
        { label: 'Bold (700)', value: '700' },
        { label: 'Extra Bold (800)', value: '800' },
        { label: 'Black (900)', value: '900' },
    ];

    const applyFontWeight = (weight) => {
        if (weight) {
            onChange(
                applyFormat(value, {
                    type: 'my-theme/font-weight',
                    attributes: {
                        style: `font-weight: ${weight}`,
                        'data-weight': weight,
                    },
                })
            );
        } else {
            onChange(removeFormat(value, 'my-theme/font-weight'));
        }
        setIsOpen(false);
    };

    return (
        <>
            <RichTextToolbarButton
                icon="editor-bold"
                title="Font Weight"
                isActive={isActive}
                onClick={() => setIsOpen(!isOpen)}
            />
            {isOpen && (
                <Popover
                    position="top"
                    onClose={() => setIsOpen(false)}
                    className="my-theme-font-weight-popover"
                    anchor={contentRef?.current}
                >
                    <div style={{ padding: '12px', minWidth: '180px' }}>
                        <p style={{ marginTop: 0, marginBottom: '8px', fontWeight: 600 }}>
                            Select Font Weight
                        </p>
                        {fontWeights.map((fw) => (
                            <Button
                                key={fw.value}
                                variant="tertiary"
                                onClick={() => applyFontWeight(fw.value)}
                                style={{
                                    display: 'block',
                                    width: '100%',
                                    textAlign: 'left',
                                    fontWeight: fw.value,
                                }}
                            >
                                {fw.label}
                            </Button>
                        ))}
                        <hr style={{ margin: '8px 0' }} />
                        <Button
                            variant="tertiary"
                            isDestructive
                            onClick={() => applyFontWeight(null)}
                            style={{ display: 'block', width: '100%', textAlign: 'left' }}
                        >
                            Remove Weight
                        </Button>
                    </div>
                </Popover>
            )}
        </>
    );
};

registerFormatType('my-theme/font-weight', {
    title: 'Font Weight',
    tagName: 'span',
    className: 'text-weight',
    attributes: {
        style: 'style',
        'data-weight': 'data-weight',
    },
    edit: FontWeightButton,
});

const AccentButton = ({ isActive, onChange, value }) => {
    return (
        <RichTextToolbarButton
            icon="editor-italic"
            title="Accent"
            isActive={isActive}
            onClick={() => {
                onChange(
                    toggleFormat(value, {
                        type: 'my-theme/font-accent',
                    })
                );
            }}
        />
    );
};

registerFormatType('my-theme/font-accent', {
    title: 'Accent',
    tagName: 'span',
    className: 'text-accent',
    edit: AccentButton,
});
