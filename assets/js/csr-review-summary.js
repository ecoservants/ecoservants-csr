// CSR Review summary — Issue #8 subcategory slice only
//
// Fills #csr-review-summary with selected major categories, their
// subcategories, counts, and weights. Reads live form controls; does not
// keep a parallel state object. Skips disabled (deselected) panels.
//
// Does not render reporter info, photos, notes, or edit buttons.
// Those belong to closed Issue #9 / open Issue #21.

(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderReview(form) {
        var container = document.getElementById('csr-review-summary');
        if (!container || !form) {
            return;
        }

        var panels = form.querySelectorAll('[data-category-detail]');
        var html = '';
        var any = false;

        panels.forEach(function (panel) {
            var weightInput = panel.querySelector('input[name$="_weight"]');
            if (!weightInput || weightInput.disabled) {
                return;
            }

            any = true;
            var label = panel.getAttribute('data-category-label') || 'Category';
            var weightVal = weightInput.value.trim();

            html += '<section class="csr-review-category">';
            html += '<h4>' + escapeHtml(label) + '</h4>';
            if (weightVal !== '') {
                html += '<p class="csr-review-weight">' + escapeHtml(weightVal) + ' lbs</p>';
            } else {
                html += '<p class="csr-review-weight">No weight entered</p>';
            }

            var rows = [];
            panel.querySelectorAll('input[type="checkbox"][name$="_subcategories[]"]').forEach(function (checkbox) {
                if (checkbox.disabled || !checkbox.checked) {
                    return;
                }
                var countInput = checkbox.parentElement.querySelector('input[type="number"]');
                var countVal = countInput && !countInput.disabled ? countInput.value.trim() : '';
                rows.push({ label: checkbox.value, count: countVal });
            });

            if (rows.length) {
                html += '<ul class="csr-review-subcategories">';
                rows.forEach(function (row) {
                    html += '<li>' + escapeHtml(row.label);
                    if (row.count !== '') {
                        html += ' <span class="csr-review-count">(' + escapeHtml(row.count) + ')</span>';
                    }
                    html += '</li>';
                });
                html += '</ul>';
            } else {
                html += '<p class="csr-review-empty">No subcategories selected</p>';
            }

            html += '</section>';
        });

        if (!any) {
            html = '<p class="csr-review-empty">No waste categories selected.</p>';
        }

        container.innerHTML = html;
    }

    ready(function () {
        var form = document.querySelector('.csr-form-container form.csr-form');
        if (!form) {
            return;
        }

        form.addEventListener('csr-step-shown', function (event) {
            if (event.detail && event.detail.isLast) {
                renderReview(form);
            }
        });

        form.addEventListener('csr-categories-changed', function () {
            var lastStep = form.querySelector('.csr-step[data-step="5"]');
            if (lastStep && lastStep.style.display !== 'none') {
                renderReview(form);
            }
        });
    });
})();
