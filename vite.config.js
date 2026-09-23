import { defineConfig } from 'vite';
import path from 'path';

/**
 * WordPress externals.
 *
 * WordPress already ships React and every @wordpress/* package as browser
 * globals (window.wp.*, window.React). Bundling our own copies would load React
 * twice and break hooks, so these imports are redirected to the globals instead.
 */
const wpExternals = {
    '@wordpress/blocks': 'wp.blocks',
    '@wordpress/block-editor': 'wp.blockEditor',
    '@wordpress/components': 'wp.components',
    '@wordpress/element': 'wp.element',
    '@wordpress/i18n': 'wp.i18n',
    '@wordpress/data': 'wp.data',
    '@wordpress/compose': 'wp.compose',
    '@wordpress/hooks': 'wp.hooks',
    '@wordpress/rich-text': 'wp.richText',
    react: 'React',
    'react-dom': 'ReactDOM',
};

/**
 * Named exports the virtual modules expose.
 *
 * ES modules need their export names at build time, but the globals only exist
 * at runtime, so we can't discover them. Any name imported from a package above
 * MUST be listed here or it will silently be `undefined` (see AGENTS.md → Gotchas).
 */
const wpExportNames = [
    // @wordpress/blocks
    'registerBlockType',
    'unregisterBlockType',
    'createBlock',
    'getBlockTypes',
    'getBlockType',
    // @wordpress/block-editor
    'useBlockProps',
    'RichText',
    'RichTextToolbarButton',
    'MediaUpload',
    'MediaUploadCheck',
    'InspectorControls',
    'BlockControls',
    'InnerBlocks',
    // @wordpress/components
    'Button',
    'PanelBody',
    'TextControl',
    'SelectControl',
    'ToggleControl',
    'RangeControl',
    'ColorPicker',
    'Placeholder',
    'Popover',
    'Fill',
    // @wordpress/element / react
    'useState',
    'useEffect',
    'useCallback',
    'useMemo',
    'useRef',
    'createElement',
    'Fragment',
    'createRoot',
    'render',
    // @wordpress/i18n
    '__',
    '_x',
    '_n',
    'sprintf',
    // @wordpress/data
    'useSelect',
    'useDispatch',
    'select',
    'dispatch',
    'subscribe',
    // @wordpress/compose
    'compose',
    'withState',
    'withSelect',
    'withDispatch',
    // @wordpress/rich-text
    'registerFormatType',
    'toggleFormat',
    'applyFormat',
    'removeFormat',
];

/**
 * Resolve WordPress/React imports to virtual modules that read from window.
 *
 * One plugin for dev, build AND tests (Vitest runs in "serve" mode), so all
 * three environments resolve these imports the same way.
 */
function wordpressExternals() {
    const virtualPrefix = '\0wp-external:';
    const exportList = wpExportNames.join(', ');

    return {
        name: 'wordpress-externals',
        enforce: 'pre',
        resolveId(id) {
            if (id in wpExternals) {
                return virtualPrefix + id;
            }
            return null;
        },
        load(id) {
            if (!id.startsWith(virtualPrefix)) {
                return null;
            }
            const globalPath = wpExternals[id.slice(virtualPrefix.length)];

            // Read the global when the module is evaluated, not at build time.
            return `
const mod = window.${globalPath} || {};
export default mod;
const { ${exportList} } = mod;
export { ${exportList} };
`;
        },
    };
}

/**
 * Full page reload when a PHP file changes (render.php, templates, classes).
 * Kept disabled by default: enable it by adding phpReload() to `plugins`.
 */
// eslint-disable-next-line @typescript-eslint/no-unused-vars -- opt-in plugin, see above
function phpReload() {
    return {
        name: 'php-reload',
        configureServer(server) {
            server.watcher.add(['**/*.php']);
            server.watcher.on('change', (file) => {
                if (file.endsWith('.php')) {
                    console.log(`\n  PHP file changed: ${path.basename(file)}`);
                    server.ws.send({ type: 'full-reload' });
                }
            });
        },
    };
}

export default defineConfig({
    plugins: [
        // phpReload(),
        wordpressExternals(),
    ],

    /**
     * JSX: the classic runtime (`React.createElement`) is required because
     * WordPress exposes React as a global. The automatic runtime would import
     * 'react/jsx-runtime', which WordPress doesn't provide under that name.
     * Vite 8 compiles TS/TSX with Oxc (it replaced our old esbuild plugin).
     */
    oxc: {
        jsx: {
            runtime: 'classic',
            pragma: 'React.createElement',
            pragmaFrag: 'React.Fragment',
        },
    },

    build: {
        outDir: 'dist',
        manifest: true,
        rolldownOptions: {
            input: {
                // Global assets
                main: path.resolve(import.meta.dirname, 'src/js/main.js'),
                style: path.resolve(import.meta.dirname, 'src/scss/main.scss'),

                // Block: Hero
                'block-hero': path.resolve(import.meta.dirname, 'src/blocks/hero/index.tsx'),
                'block-hero-view': path.resolve(import.meta.dirname, 'src/blocks/hero/view.ts'),
                'block-hero-style': path.resolve(import.meta.dirname, 'src/blocks/hero/style.scss'),
                'block-hero-editor': path.resolve(import.meta.dirname, 'src/blocks/hero/editor.scss'),

                // Add more blocks here...
                // 'block-team': path.resolve(import.meta.dirname, 'src/blocks/team-member/index.tsx'),
                // 'block-team-view': path.resolve(import.meta.dirname, 'src/blocks/team-member/view.ts'),
            },
            output: {
                format: 'es',
                entryFileNames: '[name]-[hash].js',
                chunkFileNames: '[name]-[hash].js',
                assetFileNames: '[name]-[hash][extname]',
            },
        },
    },

    /**
     * Vitest. It reuses this whole config (plugins, JSX settings), so tests
     * compile the code exactly the way the build does.
     */
    test: {
        environment: 'jsdom',
        setupFiles: ['./tests/js/setup.ts'],
        include: ['src/**/*.test.{ts,tsx}', 'tests/js/**/*.test.{ts,tsx}'],
        // Reset call counts on the shared wp.* stubs between tests.
        clearMocks: true,
        coverage: {
            provider: 'v8',
            include: ['src/**/*.{ts,tsx}'],
            exclude: ['src/**/*.test.{ts,tsx}'],
            reporter: ['text', 'html', 'lcov'],
            // Fail `npm run test:coverage` (and CI) if coverage drops below this.
            thresholds: {
                statements: 90,
                branches: 90,
                functions: 90,
                lines: 90,
            },
        },
    },

    server: {
        strictPort: true,
        port: 3000,
        /**
         * The WordPress site (a different origin) loads our dev modules with
         * <script type="module">, which requires CORS. Allow only local dev
         * origins: `cors: true` would let ANY website read our source code
         * from the dev server while it's running.
         */
        cors: {
            origin: /^https?:\/\/(localhost|127\.0\.0\.1|[a-z0-9-]+\.local)(:\d+)?$/,
        },
        hmr: {
            host: 'localhost',
        },
    },
});
