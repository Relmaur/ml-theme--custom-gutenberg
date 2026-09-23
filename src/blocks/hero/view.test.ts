import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * jsdom has no IntersectionObserver, so we use a fake that remembers each
 * instance. Tests call `trigger()` to simulate the browser reporting that an
 * element scrolled into view.
 */
class FakeIntersectionObserver {
    static instances: FakeIntersectionObserver[] = [];
    readonly observed: Element[] = [];
    readonly observe = vi.fn((element: Element) => this.observed.push(element));
    readonly unobserve = vi.fn();
    readonly disconnect = vi.fn();

    constructor(
        private readonly callback: IntersectionObserverCallback,
        readonly options?: IntersectionObserverInit,
    ) {
        FakeIntersectionObserver.instances.push(this);
    }

    trigger(isIntersecting: boolean): void {
        const entries = this.observed.map((target) => ({ target, isIntersecting }) as IntersectionObserverEntry);
        this.callback(entries, this as unknown as IntersectionObserver);
    }
}

async function loadViewScript(): Promise<void> {
    vi.resetModules();
    await import('./view');
    document.dispatchEvent(new Event('DOMContentLoaded'));
}

describe('Hero block — frontend view script', () => {
    beforeEach(() => {
        FakeIntersectionObserver.instances = [];
        vi.stubGlobal('IntersectionObserver', FakeIntersectionObserver);
        document.body.innerHTML = `
            <section class="hero-section" id="first"></section>
            <section class="hero-section" id="second"></section>
        `;
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        document.body.innerHTML = '';
    });

    it('watches every hero on the page once the DOM is ready', async () => {
        await loadViewScript();

        const observed = FakeIntersectionObserver.instances.flatMap((observer) => observer.observed);
        expect(observed.map((element) => element.id)).toEqual(['first', 'second']);
    });

    it('fades a hero in when 10% of it becomes visible', async () => {
        await loadViewScript();
        const [firstObserver] = FakeIntersectionObserver.instances;

        expect(firstObserver.options).toEqual({ threshold: 0.1 });

        firstObserver.trigger(true);

        expect(document.getElementById('first')).toHaveClass('is-visible');
        expect(document.getElementById('second')).not.toHaveClass('is-visible');
    });

    it('stops watching after the animation runs, so it only plays once', async () => {
        await loadViewScript();
        const [firstObserver] = FakeIntersectionObserver.instances;

        firstObserver.trigger(true);

        expect(firstObserver.unobserve).toHaveBeenCalledWith(document.getElementById('first'));
    });

    it('does nothing while the hero is still off screen', async () => {
        await loadViewScript();
        const [firstObserver] = FakeIntersectionObserver.instances;

        firstObserver.trigger(false);

        expect(document.getElementById('first')).not.toHaveClass('is-visible');
        expect(firstObserver.unobserve).not.toHaveBeenCalled();
    });
});
