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
- Prefer a readable core over a clever core.
- Make request flow visible by reading files, not hidden behind automatic registration.
- Prioritize clear module boundaries over adding core features.
- Do not grow the initial implementation into a full CMS.

## PHP Style

- Keep request flow readable as procedural PHP.
- Prefer direct `if` / `elseif` request branches or explicit `include` files over hidden dispatch flows.
- Do not use route registration APIs such as `toy_route()` as the default routing model.
- If a module exposes routable handlers, prefer a plain array file that returns allowed method/path to handler mappings, then validate and include explicitly.
- Do not use PHP short tags or short echo tags.
- Use `<?php echo ...; ?>` instead of `<?= ... ?>`.
- Do not render a full HTML layout with heredoc strings such as `echo <<<HTML`.
- Prefer closing PHP and writing normal HTML for view output, using small `<?php echo ...; ?>` blocks only where values are needed.
- Escape output before printing user-controlled or variable values.

## Commit Messages

- Write commit messages in Korean.
- Use the format `type: message`.
- Use common Conventional Commits-style types only:
  - `feat`: user-facing feature or capability addition
  - `fix`: bug fix or behavioral correction
  - `docs`: documentation-only change
  - `chore`: repository maintenance, tooling, or housekeeping
  - `refactor`: internal restructuring without behavior change
  - `test`: test-only change
  - `style`: formatting-only change
  - `perf`: performance improvement
  - `build`: build or dependency change
  - `ci`: CI configuration change
  - `revert`: revert a previous commit
- Do not use project-area prefixes such as `core`, `member`, `admin`, or `install` as the commit type.
- Put the affected area in the Korean message or body when useful.
- Keep the subject concise and describe the actual change.
- Add a Korean body for non-trivial changes, especially when multiple files or behaviors are affected.

Examples:

- `docs: 루트 진입점 배포 기준 정리`
- `feat: 회원 로그인 실패 기록 정책 보완`
- `fix: 설치 상태 확인 조건 수정`
- `chore: 로컬 개발 도구 설정 정리`

## Core Decisions

- Treat `docs/core-decisions.md` as the highest-level decision log when implementation plans appear ambiguous.
- Store token hashes, not token originals.
- Keep SEO value decisions in modules; core only provides output slots and helpers.
- Keep GDPR support split between minimal member/core foundations and optional privacy/admin workflows.
