// Re-export WordPress's ReactDOM (available globally in the editor)
const ReactDOM = window.ReactDOM || {
    render: () => null,
    createPortal: () => null,
};
export default ReactDOM;
// Legacy shim, currently unused (see docs/STATE.md). Re-exports deprecated React 17 APIs on purpose.
// eslint-disable-next-line react/no-deprecated
export const { render, createPortal, findDOMNode, unmountComponentAtNode, createRoot, hydrateRoot } = ReactDOM;
