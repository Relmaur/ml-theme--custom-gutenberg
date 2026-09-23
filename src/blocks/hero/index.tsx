import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import Edit, { type HeroAttributes } from './edit';
import Save from './save';
import metadata from './block.json' with { type: 'json' };

/**
 * Block Assets
 *
 * Note: style.scss and editor.scss are registered in block.json for API v3 compatibility
 * view.js is registered separately in PHP for frontend-only execution
 */

// Register custom RichText formats
import './formats';

// Pass block.json itself (the pattern WordPress recommends), so name, attributes
// and supports come from one source. JSON imports are typed loosely (e.g.
// `category: string` instead of the category union), hence the cast.
registerBlockType<HeroAttributes>(metadata as BlockConfiguration<HeroAttributes>, {
    edit: Edit,
    save: Save,
});
