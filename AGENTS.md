# AGENTS.md

## Project Naming

This project is named `toycore`.

Use `toy` as the project prefix for database tables and related identifiers that need a shared project namespace.

Examples:

- `toy_sites`
- `toy_site_settings`
- `toy_modules`
- `toy_site_modules`
- `toy_module_settings`
- `toy_member_accounts`

Avoid generic prefixes such as `core_` or module-only prefixes such as `member_` for database table names unless there is a specific compatibility reason.

## Development Direction

- Keep the codebase friendly to procedural PHP development.
- Prefer PHP, vanilla JavaScript, and plain CSS.
- Assume low-cost shared web hosting as a supported deployment environment.
- Treat member authentication as a module, even when it is provided as a default module.

## PHP Style

- Keep request flow readable as procedural PHP.
- Prefer direct `if` / `elseif` request branches or explicit `include` files over hidden dispatch flows.
- Do not use PHP short tags or short echo tags.
- Use `<?php echo ...; ?>` instead of `<?= ... ?>`.
- Do not render a full HTML layout with heredoc strings such as `echo <<<HTML`.
- Prefer closing PHP and writing normal HTML for view output, using small `<?php echo ...; ?>` blocks only where values are needed.
- Escape output before printing user-controlled or variable values.
