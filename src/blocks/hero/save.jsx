import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save({ attributes }) {
    const { title, subtitle, imageUrl } = attributes;
    const blockProps = useBlockProps.save({ className: 'hero-section' });

    return (
        <section {...blockProps}>
            <div className="container">
                <div className="text-col">
                    <RichText.Content tagName="h1" value={title} />
                    {subtitle && <p className="subtitle">{subtitle}</p>}
                </div>
                <div className="img-col">
                    {imageUrl && <img src={imageUrl} alt={title} />}
                </div>
            </div>
        </section>
    );
}


