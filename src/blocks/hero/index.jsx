import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import metadata from './block.json';

/**
 * Block Assets (Optional)
 * 
 * - style.scss   → Loaded on frontend AND editor (visual styles)
 * - editor.scss  → Loaded ONLY in editor (admin UI styles)
 * 
 * Note: view.js is registered separately in PHP for frontend-only execution
 */
import './style.scss';
import './editor.scss';

registerBlockType(metadata.name, {
    ...metadata,
    edit: Edit,
    save: Save,
});
