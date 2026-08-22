# Issue #8: Complete CSR waste subcategory selection UX

**Status:** Implemented in working tree (contributor owns commit/PR)
**Depends on:** #6 guided wrapper, #7 category-card wiring (restored in this change)
**Files changed:** `form-template.php`, `assets/js/csr-category-cards.js`, `assets/js/csr-review-summary.js` (new), `assets/js/csr-guided-wrapper.js`, `assets/css/style.css`, `ecoservants-csr.php`, `form-handler.php` (`isset()` guards only)

---

## What this does

Restores the Issue #7 category-card grid (it existed in markup hooks but was not populated after the v1.1.7 production sync) and completes the contextual subcategory experience:

- Selecting a major-category card reveals only that category's existing weight and subcategory fields.
- Multiple cards and multiple subcategories can be selected.
- Entered values survive Back/Next because steps and panels stay in the DOM.
- Deselecting a card **keeps values in the inputs** but **disables** those inputs so they are omitted from POST.
- Step 5 `#csr-review-summary` lists selected categories, subcategories, counts, and weights only.

No `csr_*` input names, meta keys, or stored formats were renamed.

---

## Why form-handler.php changed at all

Deselected panels disable their weight inputs, so those keys are missing from `$_POST`. Fourteen weight reads used `floatval($_POST['...'])` without `isset()`. They now use the same `isset()` pattern Issue #4 used for `csr_notes`. Absent weight → `0`, same as a blank submitted field.

---

## What was intentionally left out of scope

- Full editable review (closed #9): no edit buttons, reporter blocks, or notes.
- Photo review (#21).
- Category icon system (#18); emoji remain placeholders.
- Export column expansion (#16).
- Site-wide mobile QA (#10) and full validation (#20).
- Draft persistence (#12).
- Submit-button selector cleanup in the guided wrapper (not a user-facing bug; the button lives inside step 5).

---

## How to test

1. Load `[csr_form]`. Step 2 should show 14 cards and no detail panels until a card is checked.
2. Check Plastic, enter a weight and two subcategory counts. Uncheck Plastic: panel hides; recheck: values are still there.
3. Submit with Plastic selected and Paper previously filled then deselected. wp-admin report should store Plastic data and `0`/empty for Paper.
4. Step 5 should list only selected categories and their measurements. Change a count, go Back, return: review updates.
5. Narrow the viewport (~375px): cards reflow, subcategory rows remain tappable.
6. Keyboard: Tab to a card checkbox, Space toggles it, focus ring is visible.
7. Disable JavaScript: the long form still shows every category and still submits.

---

## Manual verification still required in WordPress

This change cannot fully exercise wp-admin storage, CSV export, or historical records from static checks. After activating the updated plugin locally:

- Submit one-category, multi-category, Unsorted Litter-only, and Styrofoam & Hazardous reports.
- Confirm the admin meta box, single CSV, yearly CSV, and `[total_impact]` still read the same meta keys.
- Open a report created before this change and confirm it still displays.
