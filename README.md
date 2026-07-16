# Better by Default

**A reviewed, testable starting policy for new WordPress sites.**

A small, data-driven teaching plugin that applies a menu of security, content, UX, login,
branding, and performance policies — each one individually toggleable under
**Settings → Better by Default**. Built as the teaching project for the
[WPYEG — Edmonton WordPress Meetup](https://wpyeg.ca/).

The whole thing rests on one idea worth carrying home:

> A "default" is just an opinionated `add_filter()` sitting behind a toggle.

## What's in this repo

```
Better-by-Default/
├── plugin/better-by-default/   → the installable plugin (main file, readme.txt, README)
├── docs/                       → wordpress-default-settings.md — the full reference, every
│                                 default with its "why" and a code snippet
├── workshop/                   → the meetup talk: PowerPoint, iA Presenter markdown,
│                                 a PDF handout, and the deck build script
├── dist/                       → better-by-default.zip — a ready-to-install build
├── LICENSE                     → GPL-3.0
└── README.md                   → you are here
```

## Quick start

Install the plugin one of three ways:

**Upload** — Plugins → Add New → Upload Plugin → `dist/better-by-default.zip` → Activate.

**Copy** — drop `plugin/better-by-default/` into `wp-content/plugins/` and activate.

**WP-CLI**

```bash
wp plugin install ./dist/better-by-default.zip --activate
```

On activation the documented defaults are seeded automatically. Then visit
**Settings → Better by Default** to flip switches.

## The defaults

**On out of the box:** restrict anonymous core REST user routes, disable XML-RPC methods and
discovery hints, enforce a 15-character password floor with a filterable blocklist, and disable
comments, pingbacks, self-pingbacks, public author archives, and emoji compatibility support.
These are starting assumptions, not universal truths; review them for each site's publishing
and integration needs.

**Opt-in, off by default** (they change behavior — turn on deliberately): require auth for all
REST requests, disable Application Passwords, hide the generator tag, redirect legacy
attachment pages, use title-only admin search, alter the front-end admin bar, disable Remember
Me, change session lengths or login branding, and throttle the Heartbeat API.

Deployment-level defaults such as `DISALLOW_FILE_EDIT`, `DISALLOW_FILE_MODS`,
`AUTOSAVE_INTERVAL`, and `WP_POST_REVISIONS` are clearer in `wp-config.php`. A managed
deployment may use `DISALLOW_FILE_MODS`; a site that relies on dashboard updates should not.

See [`docs/wordpress-default-settings.md`](docs/wordpress-default-settings.md) for the full
reference — every default, the reasoning, and the snippet.

## How the plugin is built

One array — `wpyeg_defaults_schema()` — is the single source of truth. It drives both the
settings screen and the bootstrap that wires each *enabled* policy to its WordPress hook.
Adding a new default is one array entry plus one `if`-block in bootstrap; no new settings-page
code. (The `wpyeg_` option prefix is kept deliberately as the WPYEG org convention.)

## Workshop materials

The [`workshop/`](workshop/) folder holds the full talk: `Better-by-Default.pptx`, an iA
Presenter version (`Better-by-Default.ia.md`), a `Better-by-Default.pdf` handout, and
`build_deck.js` (the source used to regenerate the workshop deck).

## Verification

The repository includes PHP syntax checks, WordPress Coding Standards configuration, lightweight
regression tests, and a `wp-env` mapping for a real WordPress smoke test. Run `composer lint`,
`composer test`, and then `npx --yes @wordpress/env start` plus
`npx --yes @wordpress/env run cli wp plugin activate better-by-default` before packaging. The
reference document also links each material claim to WordPress developer documentation, Core
developer notes, or NIST password guidance.

## License

[GPL-3.0-or-later](LICENSE). Fork it, teach with it, ship it.
