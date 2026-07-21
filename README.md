# Better by Default

**Sane defaults for every new WordPress site.**

A small, data-driven plugin that flips a menu of sensible security, UX, SEO, and performance
defaults onto any WordPress install — each one individually toggleable under
**Settings → Better by Default**. Built as the teaching project for the
[WPYEG — Edmonton WordPress Meetup](https://wpyeg.ca/).

Default settings are powerful things. Here they are opinionated filters sitting behind a toggle. Whose opinions do they reflect? Don't let your site be a product of an environment you didn't define. Make your environment a thoughtful product of your own intentions.

## What's in this repo

```
Better-by-Default/
├── plugin/sane-defaults/   → the installable plugin (main file, readme.txt, README)
├── docs/                       → wordpress-default-settings.md — the full reference, every
│                                 default with its "why" and a code snippet
├── workshop/                   → the meetup talk: PowerPoint, iA Presenter markdown,
│                                 a PDF handout, and the deck build script
├── dist/                       → sane-defaults.zip — a ready-to-install build
├── LICENSE                     → GPL-3.0
└── README.md                   → you are here
```

## Quick start

Install the plugin one of three ways:

**Upload** — Plugins → Add New → Upload Plugin → `dist/sane-defaults.zip` → Activate.

**Copy** — drop `plugin/sane-defaults/` into `wp-content/plugins/` and activate.

**WP-CLI**

```bash
wp plugin install ./dist/sane-defaults.zip --activate
```

On activation the documented defaults are seeded automatically. Then visit
**Settings → Better by Default** to flip switches.

## The defaults

**On out of the box** (safe for nearly any site): restrict REST user discovery, lock down
XML-RPC by category (pingbacks / remote publishing / multicall all off), require strong
passwords (length + breach screening, not forced composition), send security headers, disable
comments / pingbacks / self-pingbacks, redirect public author archives and attachment pages,
disable the emoji script, automatically install core maintenance/security releases while
holding major releases for testing.

**Deliberately *not* locked down by default** (opinionated calls, explained in the reference):
Application Passwords stay **available** — they're the safer, revocable integration credential,
and disabling them pushes people to worse alternatives — and the login logo is left
**untouched** (`keep_default`), because changing the login screen out of the box is intrusive.

**Opt-in, off by default** (they change behavior — turn on deliberately): require auth for all
REST, prohibit Application Passwords, remove/replace the login logo, title-only admin search,
hide the front-end admin bar, disable Remember Me, and throttle the Heartbeat API. Removing the
version fingerprint is here too, for a different reason: it is
obscurity rather than hardening — useful for trimming scanner noise, but it does not make an
out-of-date site safer, so it is not presented as a security default.

Three more live in `wp-config.php`, above the plugin layer, and are documented as manual steps:
`DISALLOW_FILE_EDIT`, `AUTOSAVE_INTERVAL`, and `WP_POST_REVISIONS`.

Plugin and theme code updates keep using WordPress's individual per-item choices. Better by
Default does not guess release risk from version numbers, and it reports rather than overrides
an explicit `WP_AUTO_UPDATE_CORE`, `AUTOMATIC_UPDATER_DISABLED`, or `DISALLOW_FILE_MODS` policy.
Translation files retain WordPress's existing automatic-update behavior.

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
`build_deck.js` (the pptxgenjs generator, in case you want to reskin the slides).

## License

[GPL-3.0-or-later](LICENSE). Fork it, teach with it, ship it.
