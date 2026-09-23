import React, { useState } from 'react';
import { registerFormatType, toggleFormat, applyFormat, removeFormat } from '@wordpress/rich-text';
import type { RichTextValue } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';
import { Button, Popover } from '@wordpress/components';

/**
 * Props WordPress passes to a format's `edit` component.
 * (@wordpress/rich-text types `edit` as a bare Function, so we define them.)
 */
interface FormatEditProps {
    isActive: boolean;
    value: RichTextValue;
    onChange: (value: RichTextValue) => void;
    contentRef?: React.RefObject<HTMLElement>;
}

const HighlightButton = ({ isActive, onChange, value }: FormatEditProps) => {
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
                        }),
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
const FontWeightButton = ({ isActive, onChange, value, contentRef }: FormatEditProps) => {
    const [isOpen, setIsOpen] = useState(false);
    // The element the popover points at. Read from the ref in the click handler:
    // reading `ref.current` during render won't re-render when the ref changes.
    const [anchor, setAnchor] = useState<HTMLElement | null>(null);

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

    // `null` removes the weight format from the selection.
    const applyFontWeight = (weight: string | null) => {
        if (weight) {
            onChange(
                applyFormat(value, {
                    type: 'my-theme/font-weight',
                    attributes: {
                        style: `font-weight: ${weight}`,
                        'data-weight': weight,
                    },
                }),
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
                onClick={() => {
                    setAnchor(contentRef?.current ?? null);
                    setIsOpen(!isOpen);
                }}
            />
            {isOpen && (
                <Popover
                    placement="top-start"
                    onClose={() => setIsOpen(false)}
                    className="my-theme-font-weight-popover"
                    anchor={anchor}
                >
                    <div style={{ padding: '12px', minWidth: '180px' }}>
                        <p style={{ marginTop: 0, marginBottom: '8px', fontWeight: 600 }}>Select Font Weight</p>
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

// WordPress supports `attributes` on formats at runtime, but its WPFormat type
// omits it. Typing the settings separately (instead of an inline object) lets
// us declare the extra field without an `as any` cast.
const fontWeightFormat: Parameters<typeof registerFormatType>[1] & {
    attributes: Record<string, string>;
} = {
    title: 'Font Weight',
    tagName: 'span',
    className: 'text-weight',
    attributes: {
        style: 'style',
        'data-weight': 'data-weight',
    },
    edit: FontWeightButton,
};

registerFormatType('my-theme/font-weight', fontWeightFormat);

const AccentButton = ({ isActive, onChange, value }: FormatEditProps) => {
    return (
        <RichTextToolbarButton
            icon="editor-italic"
            title="Accent"
            isActive={isActive}
            onClick={() => {
                onChange(
                    toggleFormat(value, {
                        type: 'my-theme/font-accent',
                    }),
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
