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
