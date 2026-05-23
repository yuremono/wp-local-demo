/**
 * `.Js*` 系に IntersectionObserver で `.show` を付与する。
 * 0413 側の intersectionShow を WordPress 用に縮小移植したもの。
 */

const ATTR_ONCE = "data-io-once";
const ATTR_TOGGLE = "data-io-toggle";

const SELECTOR_ONCE_LEGACY =
	"[class*=Js]:not([class*=JsCh],.JsBgFix),[class*=JsCh]>*";
const SELECTOR_TOGGLE = ".f_main,.JsBgFix";

function isOnceTarget(el: Element): boolean {
	if (!(el instanceof HTMLElement)) return false;
	return true;
}

export type RuntimeDisconnect = { disconnect: () => void };

export function initIntersectionShow(
	root: Document | Element = document,
): RuntimeDisconnect {
	const base = root;

	const observerOnce = new IntersectionObserver(
		(entries) => {
			for (const entry of entries) {
				if (entry.isIntersecting) {
					entry.target.classList.add("show");
				}
			}
		},
		{ rootMargin: "0% 0% -15% 0px", threshold: 0 },
	);

	const observerToggle = new IntersectionObserver(
		(entries) => {
			for (const entry of entries) {
				const target = entry.target;
				if (entry.isIntersecting) {
					target.classList.add("show");
				} else {
					target.classList.remove("show");
				}
			}
		},
		{ rootMargin: "0% 0% -50% 0px" },
	);

	const onceSeen = new Set<Element>();
	const observeOnce = (el: Element) => {
		if (onceSeen.has(el)) return;
		onceSeen.add(el);
		if (!isOnceTarget(el)) return;
		if (el.hasAttribute(ATTR_ONCE)) return;
		el.setAttribute(ATTR_ONCE, "1");
		observerOnce.observe(el);
	};

	base.querySelectorAll(SELECTOR_ONCE_LEGACY).forEach(observeOnce);

	base.querySelectorAll(SELECTOR_TOGGLE).forEach((el) => {
		if (!(el instanceof HTMLElement)) return;
		if (el.hasAttribute(ATTR_TOGGLE)) return;
		el.setAttribute(ATTR_TOGGLE, "1");
		observerToggle.observe(el);
	});

	return {
		disconnect: () => {
			observerOnce.disconnect();
			observerToggle.disconnect();
			base.querySelectorAll(`[${ATTR_ONCE}]`).forEach((el) => {
				el.removeAttribute(ATTR_ONCE);
			});
			base.querySelectorAll(`[${ATTR_TOGGLE}]`).forEach((el) => {
				el.removeAttribute(ATTR_TOGGLE);
			});
		},
	};
}
