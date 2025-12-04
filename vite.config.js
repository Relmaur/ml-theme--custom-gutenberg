import { defineConfig } from 'vite';
import path from 'path';

// WordPress externals - these are provided globally by WordPress
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
    'react': 'React',
    'react-dom': 'ReactDOM',
};

// Plugin to handle WordPress externals in dev mode (virtual modules)
function wordpressExternalsDev() {
    const virtualPrefix = '\0wp-external:';
    
    return {
        name: 'wordpress-externals-dev',
        enforce: 'pre',
        apply: 'serve', // Only apply in dev mode
        resolveId(id) {
            if (id in wpExternals) {
                return virtualPrefix + id;
            }
        },
        load(id) {
            if (id.startsWith(virtualPrefix)) {
                const actualId = id.slice(virtualPrefix.length);
                if (actualId in wpExternals) {
                    const globalVar = wpExternals[actualId];
                    return `
const mod = window.${globalVar} || {};
export default mod;
const { 
    registerBlockType, unregisterBlockType, createBlock, getBlockTypes, getBlockType,
    useBlockProps, RichText, MediaUpload, MediaUploadCheck, InspectorControls, BlockControls, InnerBlocks,
    Button, PanelBody, TextControl, SelectControl, ToggleControl, RangeControl, ColorPicker, Placeholder,
    useState, useEffect, useCallback, useMemo, useRef, createElement, Fragment, createRoot, render,
    __, _x, _n, sprintf,
    useSelect, useDispatch, select, dispatch, subscribe,
    compose, withState, withSelect, withDispatch
} = mod;
export { 
    registerBlockType, unregisterBlockType, createBlock, getBlockTypes, getBlockType,
    useBlockProps, RichText, MediaUpload, MediaUploadCheck, InspectorControls, BlockControls, InnerBlocks,
    Button, PanelBody, TextControl, SelectControl, ToggleControl, RangeControl, ColorPicker, Placeholder,
    useState, useEffect, useCallback, useMemo, useRef, createElement, Fragment, createRoot, render,
    __, _x, _n, sprintf,
    useSelect, useDispatch, select, dispatch, subscribe,
    compose, withState, withSelect, withDispatch
};
                    `;
                }
            }
        },
    };
}

// Plugin to transform external imports to global variable access in production build
function wordpressExternalsBuild() {
    const virtualPrefix = '\0wp-build-external:';
    
    return {
        name: 'wordpress-externals-build',
        enforce: 'pre',
        apply: 'build', // Only apply in build mode
        resolveId(id) {
            if (id in wpExternals) {
                return virtualPrefix + id;
            }
        },
        load(id) {
            if (id.startsWith(virtualPrefix)) {
                const actualId = id.slice(virtualPrefix.length);
                if (actualId in wpExternals) {
                    const globalVar = wpExternals[actualId];
                    // Return a simple module that exports from the global
                    return `
const mod = window.${globalVar} || {};
export default mod;
const { 
    registerBlockType, unregisterBlockType, createBlock, getBlockTypes, getBlockType,
    useBlockProps, RichText, MediaUpload, MediaUploadCheck, InspectorControls, BlockControls, InnerBlocks,
    Button, PanelBody, TextControl, SelectControl, ToggleControl, RangeControl, ColorPicker, Placeholder,
    useState, useEffect, useCallback, useMemo, useRef, createElement, Fragment, createRoot, render,
    __, _x, _n, sprintf,
    useSelect, useDispatch, select, dispatch, subscribe,
    compose, withState, withSelect, withDispatch
} = mod;
export { 
    registerBlockType, unregisterBlockType, createBlock, getBlockTypes, getBlockType,
    useBlockProps, RichText, MediaUpload, MediaUploadCheck, InspectorControls, BlockControls, InnerBlocks,
    Button, PanelBody, TextControl, SelectControl, ToggleControl, RangeControl, ColorPicker, Placeholder,
    useState, useEffect, useCallback, useMemo, useRef, createElement, Fragment, createRoot, render,
    __, _x, _n, sprintf,
    useSelect, useDispatch, select, dispatch, subscribe,
    compose, withState, withSelect, withDispatch
};
                    `;
                }
            }
        },
    };
}

// Simple JSX transform plugin using esbuild
function jsxTransform() {
    return {
        name: 'jsx-transform',
        enforce: 'pre',
        async transform(code, id) {
            if (id.endsWith('.jsx') || id.endsWith('.tsx')) {
                const esbuild = await import('esbuild');
                const result = await esbuild.transform(code, {
                    loader: id.endsWith('.tsx') ? 'tsx' : 'jsx',
                    jsx: 'transform',
                    jsxFactory: 'React.createElement',
                    jsxFragment: 'React.Fragment',
                    sourcemap: true,
                    sourcefile: id,
                });
                return {
                    code: result.code,
                    map: result.map || null,
                };
            }
        },
    };
}

export default defineConfig({
    plugins: [
        wordpressExternalsDev(),
        wordpressExternalsBuild(),
        jsxTransform(),
    ],
    build: {
        outDir: 'dist',
        manifest: true,
        rollupOptions: {
            input: {
                // Global assets
                main: path.resolve(__dirname, 'src/js/main.js'),
                style: path.resolve(__dirname, 'src/scss/main.scss'),
                
                // Block: Hero
                'block-hero': path.resolve(__dirname, 'src/blocks/hero/index.jsx'),
                'block-hero-view': path.resolve(__dirname, 'src/blocks/hero/view.js'),
                
                // Add more blocks here...
                // 'block-team': path.resolve(__dirname, 'src/blocks/team-member/index.jsx'),
                // 'block-team-view': path.resolve(__dirname, 'src/blocks/team-member/view.js'),
            },
            output: {
                format: 'es',
                entryFileNames: '[name]-[hash].js',
                chunkFileNames: '[name]-[hash].js',
                assetFileNames: '[name]-[hash][extname]',
            },
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                api: 'modern-compiler',
            },
        },
    },
    server: {
        cors: true,
        strictPort: true,
        port: 3000,
        hmr: {
            host: 'localhost',
        },
    },
});