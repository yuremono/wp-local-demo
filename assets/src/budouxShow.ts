import { loadDefaultJapaneseParser } from "budoux";

const ATTR = "data-budoux-show";
const SELECTOR = ".BudouxShow";
const PHRASE_CLASS = "BudouxShowPhrase";
const ZWSP = "\u200B";

type BudouxParser = ReturnType<typeof loadDefaultJapaneseParser>;

export interface InitBudouxShowOptions {
	observerOptions?: IntersectionObserverInit;
}

export type RuntimeDisconnect = { disconnect: () => void };

interface BudouxShowState {
	element: HTMLElement;
	phrases: HTMLElement[];
}

function normalizeText(text: string) {
	return text
		.split(/\r?\n/)
		.map((line) => line.trim())
		.filter(Boolean)
		.join("");
}

function delayStyle(index: number) {
	return `transition-delay:calc(var(--first-delay) + ${index} * var(--phrase-delay))`;
}

function wrapTextNode(
	textNode: Text,
	parser: BudouxParser,
	index: { count: number },
) {
	const text = normalizeText(textNode.nodeValue ?? "");
	if (!text) {
		textNode.remove();
		return;
	}

	const phrases = parser.parse(text).filter(Boolean);
	if (phrases.length === 0) return;

	const ownerDocument = textNode.ownerDocument;
	const fragment = ownerDocument.createDocumentFragment();
	phrases.forEach((phrase, phraseIndex) => {
		const span = ownerDocument.createElement("span");
		span.className = PHRASE_CLASS;
		span.setAttribute("style", delayStyle(index.count));
		span.textContent = phrase;
		fragment.append(span);
		index.count += 1;

		if (phraseIndex < phrases.length - 1) {
			fragment.append(ownerDocument.createTextNode(ZWSP));
		}
	});

	textNode.replaceWith(fragment);
}

function wrapElement(
	element: HTMLElement,
	parser: BudouxParser,
	index: { count: number },
) {
	Array.from(element.childNodes).forEach((node) => {
		if (node instanceof Text) {
			wrapTextNode(node, parser, index);
			return;
		}

		if (node instanceof HTMLBRElement) return;

		if (node instanceof HTMLElement) {
			wrapElement(node, parser, index);
		}
	});
}

function prepareElement(element: HTMLElement, parser: BudouxParser) {
	if (!element.hasAttribute(ATTR)) {
		element.setAttribute(ATTR, "1");
		wrapElement(element, parser, { count: 0 });
	}

	return Array.from(element.querySelectorAll(`.${PHRASE_CLASS}`)).filter(
		(phrase): phrase is HTMLElement => phrase instanceof HTMLElement,
	);
}

function getTargets(base: Document | Element) {
	const targets = base instanceof HTMLElement && base.matches(SELECTOR)
		? [base]
		: [];

	targets.push(
		...Array.from(base.querySelectorAll(SELECTOR)).filter(
			(element): element is HTMLElement => element instanceof HTMLElement,
		),
	);

	return targets;
}

function reveal(state: BudouxShowState) {
	if (state.element.classList.contains("show")) return;
	state.element.classList.add("show");
}

export function initBudouxShow(
	root: Document | Element = document,
	options: InitBudouxShowOptions = {},
): RuntimeDisconnect {
	const base = root;
	const parser = loadDefaultJapaneseParser();
	const states = getTargets(base)
		.map((element) => ({
			element,
			phrases: prepareElement(element, parser),
		}))
		.filter((state) => state.phrases.length > 0);

	if (states.length === 0) {
		return {
			disconnect: () => {
				/* noop */
			},
		};
	}

	const observer = new IntersectionObserver(
		(entries) => {
			for (const entry of entries) {
				if (!entry.isIntersecting) continue;
				const state = states.find(({ element }) => element === entry.target);
				if (!state) continue;
				reveal(state);
				observer.unobserve(entry.target);
			}
		},
		options.observerOptions ?? { rootMargin: "0% 0% -15% 0px", threshold: 0 },
	);

	states.forEach((state) => {
		observer.observe(state.element);
	});

	return {
		disconnect: () => {
			observer.disconnect();
		},
	};
}
