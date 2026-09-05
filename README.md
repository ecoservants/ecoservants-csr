# EcoServants® CSR Plugin

The **EcoServants® Community Site Report (CSR) Plugin** is a custom WordPress environmental reporting system developed by the Ecological Servants Project.

CSR allows volunteers, community groups, organizations, cleanup teams, interns, and other participants to document environmental stewardship activity through a guided digital reporting workflow.

The system converts field activity into structured environmental impact data that EcoServants® can use for community reporting, program evaluation, grant reporting, partner engagement, and long-term environmental analysis.

---

## Current Production Release

**Version:** `1.1.8-category-icons`

The GitHub repository was synchronized with the current production CSR plugin on **August 14, 2026**.

The corresponding production baseline is available through the GitHub release:

`v1.1.7-guided-interim`

This release establishes the baseline for continued CSR v1.2 and v2.0 development.

---

# What Is the EcoServants® CSR Plugin?

The EcoServants® CSR Plugin is a custom WordPress plugin designed to make environmental field reporting simple, structured, and useful.

Instead of relying on paper forms, disconnected spreadsheets, or informal cleanup reports, CSR provides a guided reporting process that captures information such as:

* Reporter type
* Individual, team, or organization participation
* Cleanup or restoration activity
* Reporting date
* Location
* Waste categories
* Waste subcategories
* Item counts
* Estimated or recorded weights
* Volunteer participation
* Organization/team information
* Photos
* Notes
* Environmental impact data

Each submitted report is stored as a structured CSR record inside WordPress.

---

# Why It Matters

Environmental stewardship work often happens without consistent documentation.

Volunteers may remove hundreds of pounds of litter, restore habitat, remove invasive species, or organize community cleanup events without that work becoming part of a usable environmental dataset.

CSR helps EcoServants®:

* Document environmental service activity
* Measure pounds of material removed
* Track cleanup and restoration activity
* Record volunteer and organizational participation
* Identify waste patterns
* Build long-term environmental datasets
* Support grant and funder reporting
* Demonstrate community impact
* Provide organizations with measurable environmental outcomes
* Support future dashboards and partner reporting tools

The goal is not simply to collect reports.

The goal is to transform environmental service into **structured, measurable, reusable impact data**.

---

# Current Production Features

## Guided Reporting Workflow

The current CSR system uses a guided, multi-step reporting experience rather than requiring users to complete one large form.

The guided workflow includes:

* Step-by-step navigation
* Back and Next controls
* Progress indicators
* Conditional sections
* Review-before-submit
* Mobile-responsive layouts
* Preservation of entered information while navigating

---

## Reporter Paths

CSR currently supports reporting paths for:

* Individuals
* Teams
* Organizations / companies

Organization and team metadata can be collected with CSR submissions.

The organizational data architecture will be expanded further under the CSR v2.0 roadmap.

---

## Waste Category Selection

### Standardized Category Icon & Visual Asset System (Issue #18)
The CSR plugin features a standardized SVG category icon system providing clear visual indicators for all waste categories across shortcodes, category card selectors, public impact summaries, and WordPress admin meta boxes.

### Asset Conventions & Directory Structure
- **Location**: All raw icon vector files are stored in `assets/icons/`.
- **Naming Convention**: File names use lowercase kebab-case matching category slugs (`unsorted-litter.svg`, `plastic.svg`, `paper.svg`, `food.svg`, `metal.svg`, `glass.svg`, `cigarette.svg`, `textiles.svg`, `medical.svg`, `sanitary.svg`, `fishing.svg`, `styrofoam-hazardous.svg`, `miscellaneous.svg`, `derelict.svg`, `invasive-species.svg`).
- **SVG Specifications**: Icons use clean, scalable vector paths with standard `viewBox="0 0 24 24"`, `width="24"`, `height="24"`, `fill="none"`, `stroke="currentColor"`, `stroke-width="2"`, `stroke-linecap="round"`, and `stroke-linejoin="round"`.

### PHP Helper Functions
- `csr_get_category_icon_svg($category_key, $args = [])`: Returns sanitized inline SVG markup for a given category key (e.g. `'plastic'`, `'invasive_species'`, `'styrofoam_hazardous'`). Supports custom sizing, CSS class assignment, and accessibility attributes (`aria-hidden="true"`, `role="img"`). Inline SVG rendering guarantees seamless rendering inside iframes, widgets, and page builders without cross-origin or relative URL issues.
- `csr_get_all_category_icons()`: Returns an associative array of inline SVG markup strings for all 15 major categories.

### Shortcode & Iframe Asset Enqueuing
- All CSR shortcodes (`[csr_form]`, `[csr_impact_summary]`, `[top_impact]`, `[total_impact]`, `[wall_of_fame]`) invoke `ecoservants_enqueue_scripts()` on render, ensuring styles and scripts load reliably.
- Embedded iframe endpoints (`?csr_form_iframe=1`, `?wall_of_fame_iframe=1`, `?total_impact_iframe=1`, `?top_impact_iframe=1`) include full charset/viewport metadata, versioned CSS stylesheets (`ECOSERVANTS_CSR_VERSION`), and required JavaScript modules (`carousel.js`, `csr-guided-wrapper.js`, `csr-category-cards.js`, `csr-wall-modal.js`).

CSR uses visual category cards to help reporters identify the type of material collected.

Major categories include areas such as:

* Plastic Waste
* Paper Waste
* Metal Waste
* Glass Waste
* Cigarette Litter
* Medical Waste
* Food Waste
* Textile Waste
* Invasive Species
* Other Waste

Existing CSR subcategory data structures are also preserved.

Additional subcategory-selection UX improvements are planned for CSR v1.2.

---

## Draft Saving

The guided reporting interface includes local-device draft persistence.

Reporters can leave or refresh the reporting workflow and recover entered information using the same browser/device.

Future server-side or account-based cross-device draft saving may be developed separately.

---

## Review Before Submission

Before final submission, reporters can review information collected throughout the guided CSR workflow.

The review process allows users to:

* Review submitted information
* Verify environmental activity details
* Review organization/team information where applicable
* Review selected categories and quantities
* Return to earlier steps
* Correct information
* Return to the review screen
* Submit the finalized CSR

Photo-specific review enhancements are being tracked separately.

---

## Photo Reporting

CSR supports:

* Multiple photo uploads
* Photo previews
* Mobile photo selection
* Mobile camera capture where supported
* Attachment of photos to CSR records

Additional validation, error handling, and review-screen improvements are planned under CSR v1.2.

---

## Location Support

CSR includes location-related reporting fields and browser-assisted geolocation functionality where available.

Location information may be used to help EcoServants® understand where environmental stewardship work is occurring and support future geographic analysis.

---

## Environmental Impact Calculations

The plugin includes existing impact calculation and aggregation functionality.

Current and future calculations may support metrics such as:

* Total CSR reports
* Pounds of material removed
* Category-level impact
* Participation totals
* Calendar-year impact
* Restoration activity
* Organization/team impact

This calculation layer will be standardized and expanded through the CSR v2.0 analytics architecture.

---

## Public CSR Impact Summary Shortcode

`[csr_impact_summary]` displays a public summary of aggregate CSR impact on any
WordPress page or post. It reuses the existing calculation functions
(`ecoservants_calculate_totals()` and `ecoservants_get_yearly_totals()`) rather
than duplicating aggregation logic, so its totals stay consistent with
`[total_impact]` and `[top_impact]`.

### Usage

```
[csr_impact_summary]
[csr_impact_summary year="2026"]
[csr_impact_summary year="2026" top="6"]
```

### Attributes

* `year` — optional four-digit calendar year (e.g. `2026`). When set, the summary
  shows impact for that year only and reports its own report count. When omitted,
  the summary shows all-time impact.
* `top` — optional number of top waste categories to display, ranked by weight.
  Defaults to `4`. Set to `0` to hide the Top Categories section.

### What it shows

* Reports submitted
* Total material removed (pounds)
* Participation totals (volunteers)
* Trees planted, bags collected, and invasive species removed, where available
* Top waste categories by weight
* A "no reports yet" message for periods with no data

### Notes

* Only aggregate totals are rendered. No reporter names, emails, locations,
  notes, post IDs, or other private metadata are ever output.
* The all-time view uses the site-wide participation basis (which includes the
  plugin's seeded base volunteer count), so its participation label reads
  "Volunteers Involved". The per-year view uses only that year's reported
  volunteers and is labeled "Volunteers This Year".
* Markup and CSS classes match `[top_impact]`, so the summary inherits the
  existing EcoServants styling with no additional stylesheet.

---

## CSR Exports

CSR already contains CSV/export functionality.

Existing exports are being expanded so the data can be used more effectively for:

* Internal analysis
* Grant reporting
* Funder reporting
* Partner updates
* Organization reporting
* Spreadsheet analysis
* Environmental impact tracking

---

## Administrative Review

CSR submissions are stored as WordPress CSR report records.

EcoServants® administrators can review submitted reports through the WordPress administrative environment.

CSR v1.2 will further improve the management and review experience by organizing report information into clearer sections and surfacing key environmental impact metrics.

---

## Wall of Fame Integration

The current production CSR workflow includes integration with the EcoServants® Wall of Fame experience.

This provides a foundation for future participant recognition and impact-based engagement features.

---

# Data Architecture

CSR reports use the existing WordPress CSR report structure.

The project intentionally preserves historical CSR data and existing field names wherever practical while expanding the reporting experience.

Repository documentation includes:

* `CSR_DATA_DICTIONARY.md`
* `CSR_PROCESS_MAP_FOR_INTERNS.md`
* `PROCESS_MAPPING.md`
* `CSR_ROADMAP.md`
* Guided-flow architecture documentation
* Implementation notes

Developers should review these documents before altering field names, storage conventions, or report-processing logic.

Backward compatibility with existing CSR reports is an important project requirement.

---

# Current Development Roadmap

Development is now divided into two major milestones.

---

## CSR v1.2 — Production Reporting

CSR v1.2 focuses on completing and hardening the core environmental reporting system.

Current areas of development include:

### Waste Subcategory UX

Complete contextual waste-subcategory selection after users select major waste categories.

### Mobile Usability & Responsive QA

Complete production testing and refinement across phones, tablets, and desktop responsive layouts.

### CSR Exports

Complete exports for:

* Analysis
* Grant reporting
* Funder reporting
* Partner reporting
* Organization/team data
* Date-range reporting

### Visual Asset System

Standardize category icons and reusable visual assets.

### Administrative Review

Improve the CSR administrative review and management workflow.

### Validation & Data Integrity

Strengthen:

* Required-field validation
* Numeric validation
* Conditional validation
* Server-side validation
* User-friendly error handling
* Data integrity protections

### Photo Upload & Review UX

Improve:

* File validation
* Upload errors
* Mobile handling
* Review-screen previews
* Photo limits
* Accessibility

### Public Impact Summary

Create a reusable shortcode for displaying selected aggregate CSR impact metrics publicly.

---

# CSR v2.0 — Organizational Impact Platform

CSR v2.0 expands CSR beyond individual environmental reporting into a broader organization and team impact platform.

The milestone includes three major architectural areas.

---

## Organization & Team Reporting Architecture

Standardize organization/team reporting data so reports from the same organization can be reliably grouped and analyzed.

This includes:

* Organization identifiers
* Team information
* Participation data
* Historical compatibility
* Organization-specific reporting
* Analytics-ready metadata

---

## Reusable CSR Impact Analytics Layer

Develop a standardized analytics layer capable of aggregating CSR data by:

* Date
* Month
* Year
* Organization
* Team
* Reporter
* Waste category
* Waste subcategory
* Pounds collected
* Participation
* Restoration activity
* Number of reports

The goal is to avoid rebuilding calculations separately for every dashboard, export, or shortcode.

---

## Organization CSR Impact Dashboard

Build a first-version organization/team dashboard capable of displaying metrics such as:

* Organization name
* Total CSR reports
* Total pounds collected
* Volunteer participation
* Category-level impact
* Restoration activity
* Recent reports
* Date-range impact
* Calendar-year impact

Future enhancements may include:

* Branded impact reports
* Environmental achievement badges
* Organization goals
* Leaderboards
* Partner recognition
* Year-over-year comparisons
* Sponsorship reporting
* Funder reporting tools

---

# Development Principles

CSR development should follow several core principles.

## Preserve Existing Data

Do not unnecessarily rename or replace existing CSR fields.

Historical CSR reports must remain usable whenever practical.

## Reuse Existing Functionality

Existing reporting, calculation, export, and storage logic should be extended rather than duplicated.

## Mobile First

Environmental reporting frequently happens in the field.

Every major workflow should function reliably on modern mobile devices.

## Data Integrity

Server-side validation and sanitization remain authoritative.

Client-side validation should improve usability but must never be the only protection against invalid submissions.

## Privacy

Public-facing features must not expose private reporter information.

Administrative and public reporting functions should remain appropriately separated.

## Accessibility

CSR interfaces should remain understandable and usable without depending exclusively on:

* Color
* Icons
* Animation
* Hover interactions

Text labels and accessible controls should remain available.

## Expandable Architecture

New features should support future development without forcing existing CSR functionality to be rebuilt.

---

# Repository Milestones

Current GitHub development is organized under:

### CSR v1.2 — Production Reporting

Core reporting UX, validation, mobile QA, exports, administration, visual assets, photo workflow, and public impact presentation.

### CSR v2.0 — Organizational Impact Platform

Organization/team architecture, reusable analytics, and organization impact dashboards.

GitHub issues assigned to these milestones should be treated as the current development source of truth.

---

# Release History

## v1.1.7-guided-interim

**Production synchronization baseline — August 14, 2026**

Major functionality represented in this release includes:

* Guided multi-step CSR reporting
* Individual, team, and organization reporting paths
* Category-card interface
* Local draft saving and restoration
* Progress navigation
* Review-before-submit workflow
* Photo previews and mobile capture support
* Organization and event metadata
* Improved submission confirmation
* Wall of Fame integration

This release brought the GitHub repository back into alignment with the current production CSR implementation.

---

# Installation

CSR is developed as a WordPress plugin.

Typical installation:

1. Download or package the plugin files.

2. Upload the plugin folder to:

   `wp-content/plugins/`

3. Activate the EcoServants® CSR plugin from WordPress.

4. Confirm the appropriate CSR pages and shortcodes are configured.

5. Test a complete CSR submission before deploying changes to production.

Developers should avoid deploying development branches directly to the production website without testing.

---

# Development Workflow

Recommended GitHub workflow:

1. Start from the current `main` branch.
2. Review the applicable GitHub issue.
3. Create a feature/fix branch.
4. Make focused changes.
5. Test existing CSR reporting functionality.
6. Submit a pull request.
7. Reference the applicable issue.
8. Review before merging into `main`.

Major production states should be tagged through GitHub releases.

---

# Documentation

Developers and interns should review the project documentation before beginning major CSR work.

Important repository files include:

* `README.md`
* `CSR_ROADMAP.md`
* `CSR_DATA_DICTIONARY.md`
* `CSR_PROCESS_MAP_FOR_INTERNS.md`
* `PROCESS_MAPPING.md`
* `GUIDED_INTERIM_RELEASE.md`
* Guided-flow architecture notes
* Issue-specific implementation notes

The GitHub Issues and Milestones system should be used to determine current unfinished work.

---

# Project Status

CSR is an active EcoServants® environmental technology project.

The system is no longer a prototype or simple digital cleanup form.

Its current development direction is:

**Environmental reporting → structured environmental data → impact analytics → organization reporting → public and partner impact tools**

The long-term goal is to provide EcoServants®, volunteers, community organizations, partners, and funders with reliable tools for documenting and understanding measurable environmental stewardship.

---

# License

This project is proprietary software of the **Ecological Servants Project / EcoServants®**.

It is **not open-source software**.

Use, modification, redistribution, or deployment is governed by the license terms included with the repository.

See:

`license.txt`

for the applicable software license and restrictions.

---

# EcoServants®

**Ecological Servants Project**

Restoring communities. Protecting the planet.
