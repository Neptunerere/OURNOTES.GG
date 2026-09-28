/** Apply shared browser-input defaults to static and dynamically rendered forms. */
export function applyInputDefaults(root = document) {
    root.querySelectorAll?.('input').forEach((input) => {
        input.setAttribute('autocomplete', 'off');
        input.setAttribute('autocapitalize', 'off');
        input.setAttribute('spellcheck', 'false');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    applyInputDefaults();

    new MutationObserver((mutations) => {
        mutations.forEach(({ addedNodes }) => addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                applyInputDefaults(node);
                if (node.matches?.('input')) applyInputDefaults(node.parentElement);
            }
        }));
    }).observe(document.body, { childList: true, subtree: true });
});
