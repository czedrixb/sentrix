/**
 * v-reveal -- fade a section in the first time it scrolls into view.
 *
 * The reference design is a long editorial page, and sections that simply
 * appear at full opacity as you scroll read as abrupt. One shared
 * IntersectionObserver serves every directive on the page rather than one per
 * element, and each element is unobserved once it has been shown: the reveal is
 * a first-impression effect, not something that should re-run on every scroll
 * back up the page.
 *
 * The movement itself lives in resources/css/app.css under [data-reveal]. This
 * file only decides when the state flips, so a visitor who has asked for
 * reduced motion is handled in one place -- the CSS media query -- and the
 * element still ends up visible here either way.
 */

const SHOWN = 'shown';

let observer = null;

/**
 * Created lazily so nothing is constructed during SSR or in a test environment
 * without IntersectionObserver.
 */
function sharedObserver() {
    if (observer) {
        return observer;
    }

    observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) {
                    continue;
                }

                entry.target.dataset.reveal = SHOWN;
                observer.unobserve(entry.target);
            }
        },
        // A little before the element's top edge arrives, so the fade has
        // finished by the time it is properly in frame.
        { rootMargin: '0px 0px -8% 0px', threshold: 0.05 }
    );

    return observer;
}

export const reveal = {
    mounted(el, binding) {
        // v-reveal="false" opts an element out without removing the directive.
        if (binding.value === false || typeof IntersectionObserver === 'undefined') {
            el.dataset.reveal = SHOWN;

            return;
        }

        el.dataset.reveal = '';

        // A delay staggers a row of cards: v-reveal="2" waits two beats.
        if (typeof binding.value === 'number' && binding.value > 0) {
            el.style.transitionDelay = `${binding.value * 90}ms`;
        }

        sharedObserver().observe(el);
    },

    unmounted(el) {
        observer?.unobserve(el);
    },
};

export default reveal;
