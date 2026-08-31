# CLAUDE.md

Guidance for Claude Code (or any AI assistant) working in this repository.

## What this project is

**Destiny Sphere** («Сфера Судьбы») — a Russian browser MMO strategy game
(Flash client + PHP backend), live since 2003. This repo is being used to
archive and document the legacy codebase ahead of a planned remaster.
Full context: [docs/README.md](docs/README.md), [README.md](README.md).

## Repository layout

- `old-sphere/` — the entire legacy codebase, as extracted from the live
  hosting. Treat it as a **frozen historical artifact** being documented,
  not actively developed — don't refactor or "clean up" code in here unless
  explicitly asked. If a future remaster starts, it will most likely live
  at the repo root, alongside (not inside) `old-sphere/`.
- `docs/` — documentation of the *current* state of `old-sphere/`, written
  during a first documentation pass. Read [docs/README.md](docs/README.md)
  first — it's an index into per-subsystem docs (game engine, multi-world
  architecture, admin panel, billing, portal CMS, utils, frontend).
- `REDACTED_CREDENTIALS.txt` (repo root, gitignored, local only) — maps
  every `REDACTED_DB_PASSWORD` / `REDACTED_API_SECRET` placeholder in
  `old-sphere/` back to the real value that was there before this repo was
  published. If you need a real credential to actually run/connect to
  something, look there — never invent a plausible-looking value.

## Critical: secrets in `old-sphere/`

The original codebase had dozens of hardcoded DB passwords and payment API
secrets scattered across many files (found in several separate sweeps, not
all with the same naming pattern — some were `'password' => '...'`, others
`$pass = '...'`, others `$secretkey = '...'` or `define('...ApiSecretKey...', '...')`).
All known instances have been replaced with `REDACTED_DB_PASSWORD` /
`REDACTED_API_SECRET` / `REDACTED_DB_HOST` / `REDACTED_DB_USER`.

**This was NOT a single mechanical pass — treat it as incomplete.** If you
read a file in `old-sphere/` and find what looks like a real credential
that isn't already a `REDACTED_*` placeholder (a password-shaped string
next to `host`/`user`/`dbname`, a signing secret, an API key), stop and
redact it the same way before doing anything else with that file — don't
just document it and move on. Update `REDACTED_CREDENTIALS.txt` with what
you found and why.

## Known incident: do not reintroduce

Two directories, `fr/` and `securite/`, were found during documentation to
be an unrelated real-time phishing proxy impersonating Desjardins and CIBC
online banking (dated ~2010-2011, evidence of a past hosting compromise —
nothing to do with the game). They were deleted and purged from git history
entirely (`git filter-branch` + force-push). Full details:
[docs/portal-cms.md §3.4](docs/portal-cms.md). If you ever come across
similar unexplained content elsewhere in `old-sphere/` (files/paths that
don't fit the game's structure, foreign-bank branding, credential-harvesting
patterns), stop and flag it — don't just document it as a curiosity.

## Working with the legacy code

- Most `old-sphere/` PHP files are **Windows-1251** encoded (Russian
  comments show as mojibake if read as UTF-8/binary). Use `iconv -f CP1251
  -t UTF-8` when you need to read Cyrillic comments accurately.
- No autoloader in the modern sense — classes load via a hand-rolled
  `import()`/`__autoload()` (`ds/include/Import.inc.php`) that resolves
  `PREFIX_ClassName` to `ds/classes/PREFIX/PREFIX_ClassName.class.php`.
  Details: [docs/game-engine.md](docs/game-engine.md).
- One codebase serves three parallel site instances (Classic/Unlimited/
  Pulsar), switched via `posix_getuid()` of the web server process in
  `ds/config/settings.php`, each with its own list of game-world databases.
  Details: [docs/multi-world-architecture.md](docs/multi-world-architecture.md).
- Game worlds run on **PostgreSQL** despite `mysql`-looking config array
  keys; the portal/locking layer and the site CMS (`sys/`) genuinely use
  **MySQL**. Don't assume one DB engine for the whole codebase.
- Uses deprecated `mysql_*` (not `mysqli`/PDO) in the portal layer, and
  `pg_*` directly (no ORM/query builder) in the game engine.

## Style for further documentation work

- Write documentation in Russian, matching the existing `docs/` files and
  the project owner's language.
- Cite concrete `file.php:line` references wherever possible — these docs
  are meant to be navigable, not just descriptive.
- Mark inferences explicitly ("предположительно" / "не установлено") rather
  than presenting a guess as fact — the existing docs are careful about
  this and new ones should match that standard.
