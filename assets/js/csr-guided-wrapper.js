// Guided CSR Wrapper (Issue #6)
//
// Wraps the existing CSR form into a step-by-step flow without changing any
// field names and without touching form-handler.php. Steps are hidden with
// CSS display:none rather than removed from the DOM, so field values are
// preserved automatically when navigating backward, no extra state storage
// needed for that part.
//
// Out of scope, tracked as separate issues:
//   #7  category card selection UI (step 2 is still the original checkbox/fieldset layout)
//   #8  subcategory picker UI (same as above)
//   #9  full review screen with editable sections (step 5 here is a bare final step)
//   #10 mobile-specific layout polish
//   #11 richer progress indicator styling
//   #12 save-and-continue draft persistence across page reloads
//   #13 individual vs team/organization path (intentionally not included here,
//       see IMPLEMENTATION_COMPARISON.md's recommendation to keep the initial
//       wrapper limited to existing csr_* fields only)
//   #20 full validation rules beyond native HTML required/type checks

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
        if (!form) {
            return;
        }

        var steps = Array.prototype.slice.call(form.querySelectorAll('.csr-step'));
        if (steps.length === 0) {
            // No step wrappers found, fail safe and leave the form as a normal
            // long form rather than breaking submission.
            return;
        }

        var currentIndex = 0;

        var progress = document.createElement('div');
        progress.className = 'csr-step-progress';
        progress.setAttribute('aria-live', 'polite');
        form.insertBefore(progress, steps[0]);

        var nav = document.createElement('div');
        nav.className = 'csr-step-nav';

        var backBtn = document.createElement('button');
        backBtn.type = 'button';
        backBtn.className = 'csr-step-back';
        backBtn.textContent = 'Back';

        var nextBtn = document.createElement('button');
        nextBtn.type = 'button';
        nextBtn.className = 'csr-step-next';
        nextBtn.textContent = 'Next';

        nav.appendChild(backBtn);
        nav.appendChild(nextBtn);
        steps[steps.length - 1].insertAdjacentElement('afterend', nav);

        var submitButton = form.querySelector('button[type="submit"][name="csr_submit"]');

        function stepTitle(step) {
            return step.getAttribute('data-step-title') || '';
        }

        function updateProgress() {
            var title = stepTitle(steps[currentIndex]);
            var label = 'Step ' + (currentIndex + 1) + ' of ' + steps.length;
            if (title) {
                label += ': ' + title;
            }
            progress.textContent = label;
        }

        function validateStep(step) {
            var invalid = step.querySelector(':invalid');
            if (invalid) {
                invalid.reportValidity();
                return false;
            }
            return true;
        }

        function showStep(index) {
            steps.forEach(function (step, i) {
                step.style.display = (i === index) ? '' : 'none';
            });

            backBtn.style.display = (index === 0) ? 'none' : '';

            var isLastStep = (index === steps.length - 1);
            nextBtn.style.display = isLastStep ? 'none' : '';
            if (submitButton) {
                submitButton.style.display = isLastStep ? '' : 'none';
            }

            updateProgress();

            form.dispatchEvent(new CustomEvent('csr-step-shown', {
                detail: { index: index, isLast: isLastStep }
            }));
        }

        backBtn.addEventListener('click', function () {
            if (currentIndex > 0) {
                currentIndex -= 1;
                showStep(currentIndex);
            }
        });

        nextBtn.addEventListener('click', function () {
            if (!validateStep(steps[currentIndex])) {
                return;
            }
            if (currentIndex < steps.length - 1) {
                currentIndex += 1;
                showStep(currentIndex);
            }
        });

        showStep(currentIndex);
    });
})();
