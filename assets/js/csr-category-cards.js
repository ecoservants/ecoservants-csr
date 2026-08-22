// CSR Category Card Selection (Issue #7 wiring) + Issue #8 panel state
//
// Category cards are UI-only: checkboxes have no name attribute and are never
// submitted. Checking a card shows that category's existing detail block
// (weight + subcategory fields). Unchecking hides the block and disables its
// inputs so values stay in the DOM (reselect restores them) but are omitted
// from POST. That matches the Issue #8 "preserve visually, exclude from
// submission" decision.
//
// Progressive enhancement: without this script, every detail block stays
// visible and enabled, same as the pre-#8 long form.

(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(function () {
        var form = document.querySelector('.csr-form-container form.csr-form');
        var toggles = document.querySelectorAll('.csr-category-toggle');
        if (toggles.length === 0) {
            return;
        }

        if (form) {
            form.classList.add('js-ready');
        }

        function detailBlockFor(category) {
            return document.querySelector('[data-category-detail="' + category + '"]');
        }

        function setPanelInputsDisabled(detail, disabled) {
            if (!detail) {
                return;
            }
            var controls = detail.querySelectorAll('input, select, textarea');
            controls.forEach(function (el) {
                el.disabled = disabled;
            });
        }

        function syncVisibility(toggle) {
            var category = toggle.getAttribute('data-category');
            var detail = detailBlockFor(category);
            if (!detail) {
                return;
            }

            var selected = toggle.checked;
            detail.hidden = !selected;
            detail.classList.toggle('is-visible', selected);
            setPanelInputsDisabled(detail, !selected);
            toggle.setAttribute('aria-expanded', selected ? 'true' : 'false');

            if (selected) {
                detail.querySelectorAll('fieldset.collapsible').forEach(function (fieldset) {
                    fieldset.classList.add('open');
                });
            }

            var card = toggle.closest('.csr-category-card');
            if (card) {
                card.classList.toggle('csr-category-card-selected', selected);
            }

            if (form) {
                form.dispatchEvent(new CustomEvent('csr-categories-changed'));
            }
        }

        toggles.forEach(function (toggle) {
            syncVisibility(toggle);
            toggle.addEventListener('change', function () {
                syncVisibility(toggle);
            });
        });
    });
})();
