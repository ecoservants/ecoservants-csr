// CSR Category Card Selection & Visual Asset System (Issues #7 & #18)
//
// Category cards present standardized, accessible SVG icons for each CSR category.
// Checking a card reveals the corresponding detail block (weight input & subcategory fieldset),
// and unchecking hides it. Values already entered in a hidden block are preserved.

(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    ready(function () {
        var toggles = document.querySelectorAll('.csr-category-toggle');
        if (toggles.length === 0) {
            return;
        }

        function detailBlockFor(category) {
            return document.querySelector('[data-category-detail="' + category + '"]');
        }

        function syncVisibility(toggle) {
            var category = toggle.getAttribute('data-category');
            var detail = detailBlockFor(category);
            if (!detail) {
                return;
            }
            detail.style.display = toggle.checked ? '' : 'none';

            var card = toggle.closest('.csr-category-card');
            if (card) {
                card.classList.toggle('csr-category-card-selected', toggle.checked);
            }
        }

        // Hide every detail block by default, only selected cards reveal theirs.
        toggles.forEach(function (toggle) {
            syncVisibility(toggle);
            toggle.addEventListener('change', function () {
                syncVisibility(toggle);
            });
        });
    });
})();
