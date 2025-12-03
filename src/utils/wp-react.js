// Re-export WordPress's React (available globally in the editor)
const React = window.React || window.wp?.element?.React || {
    createElement: () => null,
    Fragment: 'div',
};
export default React;
export const { createElement, Fragment, useState, useEffect, useCallback, useMemo, useRef, useContext, createContext, Component, PureComponent, memo, forwardRef, lazy, Suspense, Children, cloneElement, createRef, isValidElement } = React;
