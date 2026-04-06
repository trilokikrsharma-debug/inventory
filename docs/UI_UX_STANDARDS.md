# UI/UX Standards

This document defines the baseline UI system for the app. It is intended to stop ad hoc styling drift and keep new pages consistent with the shared CSS already in `assets/css/style.css`.

## Goals

- Keep actions easy to scan.
- Keep forms readable and touch-friendly.
- Keep dark mode and light mode equally usable.
- Keep mobile behavior predictable before page-level customization.
- Prefer shared patterns over inline styles.

## Button Hierarchy

Use only one primary action per screen or form section.

### Primary

Use `btn btn-primary` for the main completion action:

- `Save`
- `Create`
- `Update`
- `Submit`
- `Apply`

Placement:

- Page-level create action: top-right in page header.
- Form submit action: bottom of form or sticky action area.
- Modal confirm action: right-most footer action.

Rules:

- Do not place 2 primary buttons beside each other in the same action group.
- Primary should be visually dominant and reserved for the most important next step.

### Success

Use `btn btn-success` for positive financial or completion flows:

- `Record Payment`
- `Approve`
- `Convert to Sale`
- `Download` when it is the main utility action

Only use when the green meaning is semantically helpful.

### Warning

Use `btn btn-warning` for edit or cautionary-but-not-destructive actions:

- `Edit`
- `Unlock`
- `Suspend`

Avoid using warning for normal navigation.

### Secondary / Outline

Use outline variants for supporting actions:

- `Cancel`
- `Back`
- `Filter`
- `View All`
- `Print`
- `Statement`

Preferred variants:

- `btn-outline-secondary` for neutral support
- `btn-outline-primary` for informative secondary actions
- `btn-outline-success` for export/download helpers
- `btn-outline-info` for inspection or utility actions

### Danger

Use danger only for destructive actions:

- `Delete`
- `Revoke`
- `Reject`
- `Hard Wipe`

Rules:

- Keep destructive actions separated from the primary action.
- Use confirmation for destructive actions.
- Prefer `btn-outline-danger` over solid danger unless the action is final and high-risk.

## Button Placement

### Page Header

Recommended order, right side:

1. Main primary action
2. Secondary utility actions
3. Destructive action last

Examples:

- `Add Customer` primary, `Bulk Import` secondary
- `Edit`, `Download PDF`, `Print`

### Card Header

Only place compact utility actions here:

- filters
- small add buttons
- export buttons
- toggles

Do not put large submit buttons inside card headers.

### Form Footer / Bottom

Preferred order:

1. Primary submit
2. Secondary cancel/back
3. Optional helper action

On mobile:

- full-width stack is acceptable
- primary first

### Table Row Actions

Use icon buttons only for dense row-level actions:

- view
- edit
- print
- delete

Order:

1. view
2. edit
3. print/download
4. destructive last

## Button Sizing

Current shared targets:

- default controls: `42px` min height
- small controls: `36px` min height
- large controls: `48px` min height
- default button min width: `110px`
- large button min width: `148px`

Use:

- `btn-sm` for table rows and dense utility bars
- default `.btn` for page and form actions
- `btn-lg` only for the main form completion button

Avoid:

- tiny icon-only buttons below `36px`
- oversized buttons inside tables

## Typography

Use shared typography, do not manually set random font sizes inline unless absolutely necessary.

Recommended weights:

- headings: `600` to `700`
- labels: `600`
- body: normal
- helper text: muted, smaller

Recommended usage:

- `h4` / `h5` for section headers
- `small` / `.form-text` for guidance
- tabular numeric alignment for totals and amounts

## Spacing

Shared spacing rhythm:

- form label to field: `0.35rem`
- field internal height: from shared control tokens
- button gap in action groups: `0.35rem` to `0.75rem`
- page/card section gap: `1rem`
- table cell padding: keep shared table rules unless a dense reporting screen needs more compact rows

Avoid:

- inline fixed widths for inputs
- inline margin hacks
- crowding 4 to 6 unrelated actions into one row on mobile

## Forms

### Structure

Recommended order:

1. section heading
2. grouped fields
3. helper text
4. submit area

Group related fields in rows:

- identity fields together
- date fields together
- monetary fields together
- notes and long text last

### Validation

Use HTML validation first:

- `required`
- `type="email"`
- `type="number"`
- `min`
- `max`
- `step`
- `maxlength`
- `minlength`

Use visual state classes when server-side validation returns:

- `.is-invalid`
- `.is-valid`
- `.invalid-feedback`
- `.valid-feedback`

Rules:

- every required field should be obvious
- every invalid field should show a readable message near the control
- helper text should not replace error text

### Helper Text

Use `.form-text` for:

- accepted formats
- numeric limits
- business rule hints
- file upload constraints

Do not use helper text for critical warnings that should be alerts.

## Tables

Rules:

- wrap tables in `.table-responsive`
- preserve horizontal scrolling on smaller screens
- keep action columns compact
- right-align amounts and totals
- use badges for status, not raw text labels when possible

For wide financial or ledger views:

- use minimum table widths
- avoid compressing all columns into unreadable mobile stacks unless the page is redesigned fully

## Status and Color Semantics

- primary blue: default system emphasis
- success green: approved, paid, active positive result
- warning yellow: pending, caution, edit-state
- danger red: failed, due, delete, reject
- info cyan: helper, system info, secondary positive utility
- muted text: metadata only

Do not use gray text for important values in dark mode.

## Dark Mode Rules

- never rely on raw `#333`, `#666`, `#888`, `black`, `gray`, `grey` for important text
- prefer shared semantic tokens and helper classes
- avoid `bg-light` with untested dark content unless shared dark override exists
- avoid inline background and text color pairs unless both modes are considered

## Responsive Rules

### Mobile

- action groups may stack full width
- page-header buttons should wrap cleanly
- forms should move from multi-column to single-column when needed
- filters should stack before shrinking below usable width
- row actions may remain horizontal, but primary page actions should usually stack

### Tablet

- prefer two-column forms
- avoid three or four compact controls in a single row unless they remain readable

### Desktop

- keep primary actions top-right or footer-right
- avoid excessive whitespace by grouping related actions

## Patterns To Avoid

- multiple primary buttons in one area
- fixed inline widths like `style="width:130px"` on reusable controls
- inline color styling for neutral text
- black/gray text assumptions in dark mode
- tiny click targets
- placing destructive action before view/edit action
- mixing unrelated actions in the same visual priority

## Review Checklist

Before shipping a page, verify:

1. Is there exactly one primary action per section?
2. Are destructive actions visually separated?
3. Are forms validatable with clear feedback?
4. Are controls touch-friendly on mobile?
5. Is all important text readable in dark mode?
6. Do filters and page-header actions wrap without overlap?
7. Are tables still readable with horizontal scroll?
8. Are totals and statuses visually obvious?

## Current Shared Baseline

The shared CSS now provides reusable baselines for:

- entry pages
- report pages
- detail pages
- platform/admin pages
- list pages

When adding or updating pages, prefer those shared shells and classes first. Add new page-specific CSS only when the shared layer is clearly insufficient.
