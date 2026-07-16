# Better by Default

**Sane defaults for every new WordPress site.**

A small, data-driven plugin that flips a menu of sensible security, UX, SEO, and performance
defaults onto any WordPress install — each one individually toggleable under
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

**On out of the box** (safe for nearly any site): restrict REST user discovery, disable
XML-RPC, disable Application Passwords, require strong passwords, remove the version
fingerprint + send security headers, disable comments / pingbacks / self-pingbacks, redirect
public author archives and attachment pages, disable the emoji script, and own the login logo.

**Opt-in, off by default** (they change behavior — turn on deliberately): require auth for all
REST, title-only admin search, hide the front-end admin bar, disable Remember Me, throttle the
Heartbeat API, and defer front-end scripts.

Three more live in `wp-config.php`, above the plugin layer, and are documented as manual steps:
`DISALLOW_FILE_EDIT`, `AUTOSAVE_INTERVAL`, and `WP_POST_REVISIONS`.

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
