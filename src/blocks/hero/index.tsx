import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import Save from './save';
import metadata from './block.json' assert { type: 'json' };

/**
 * Block Assets
 * 
 * Note: style.scss and editor.scss are registered in block.json for API v3 compatibility
 * view.js is registered separately in PHP for frontend-only execution
 */

// Register custom RichText formats
import './formats';

registerBlockType(metadata.name as string, {
    ...metadata,
    edit: Edit,
    save: Save,
} as any);
