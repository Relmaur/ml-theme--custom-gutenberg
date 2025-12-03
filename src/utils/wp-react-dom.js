// Re-export WordPress's ReactDOM (available globally in the editor)
const ReactDOM = window.ReactDOM || {
    render: () => null,
    createPortal: () => null,
};
export default ReactDOM;
export const { render, createPortal, findDOMNode, unmountComponentAtNode, createRoot, hydrateRoot } = ReactDOM;
