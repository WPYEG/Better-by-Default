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
├── plugin/sane-defaults/  → the installable plugin (main file, readme.txt, README)
├── docs/                  → wordpress-default-settings.md — the full reference, every
│                            default with its "why" and a code snippet; plus
│                            when-two-plugins-set-the-same-default.md
├── workshop/              → the meetup talk: PowerPoint, iA Presenter markdown,
│                            a PDF handout, and the deck build script
├── tests/                 → plugin-policy.php — the policy suite, and the guard that
│                            keeps the docs and the deck in step with the schema;
│                            class-wp-*.php are the two WordPress stubs it needs
├── bin/                   → build-zip.php — rebuilds dist/ reproducibly
├── dist/                  → sane-defaults.zip — a ready-to-install build
├── composer.json          → the lint/test/build scripts
├── phpcs.xml              → WordPress Coding Standards config
├── LICENSE                → GPL-3.0
└── README.md              → you are here
```

## Quick start

Install the plugin one of three ways:

**Upload** — Plugins → Add New → Upload Plugin → `dist/sane-defaults.zip` → Activate.

**Copy** — drop `plugin/sane-defaults/` into `wp-content/plugins/` and activate.

**WP-CLI**

```bash
wp plugin install ./dist/sane-defaults.zip --activate
```

The documented defaults apply the moment it activates — they live in the schema, not in the
database, so there is nothing to seed and nothing to reset. Then visit
**Settings → Better by Default** to flip switches.

## The defaults

**Which settings ship on, and what each one defaults to, lives in exactly one place:** the
[Quick-Reference Table](docs/wordpress-default-settings.md#quick-reference-table) in the
reference doc. Every row there is checked against `wpyeg_defaults_schema()` by `composer test`,
so it cannot drift. This README used to restate the split in prose, and the prose was wrong for
two settings for two releases — a list nothing checks is a list that goes stale.

The shape of the split, which is the part worth reading here: anything safe on nearly any site
is on, anything that visibly changes how the site behaves is off and opt-in. Login sessions are
the one pair of settings that carry values rather than a yes/no — both in days, matching
WordPress's own (a 2-day regular login, 14 days when remembered), floored so that ticking
"Remember Me" can never *shorten* a session. Three other calls are deliberate exceptions worth
knowing about.

**Application Passwords stay available.** They're the safer, revocable integration credential,
and disabling them pushes people toward a shared login or a third-party auth plugin — harder to
isolate, harder to revoke, and they bypass 2FA the same way. Prohibiting them is opt-in.

**The login screen is left untouched** (`keep_default`). Changing what someone sees at
`wp-login.php` out of the box is intrusive, so removing, unlinking, or replacing the logo is a
choice an administrator makes.

**Removing the version fingerprint is off**, and not because it is risky. It is obscurity
rather than hardening: it trims scanner noise, but it does not make an out-of-date site any
safer, and the version still leaks from asset query strings and feeds. Worth opting into, not
worth presenting as a security default.

Three more live in `wp-config.php`, above the plugin layer, and are documented as manual steps:
`DISALLOW_FILE_EDIT`, `AUTOSAVE_INTERVAL`, and `WP_POST_REVISIONS`.

**Breach screening can be declined.** The Have I Been Pwned lookup is the only thing here that
leaves your server. It is k-anonymous — the password is hashed locally, five characters of that
hash are sent, and neither the password nor its full hash ever goes anywhere — but that answers
"is it safe," not "may I say no." Define `WPYEG_DISABLE_HIBP` in `wp-config.php`, or filter
`wpyeg_disable_hibp` for a per-password decision; the local length, blocklist, and
personal-context rules keep running either way. A default you cannot switch off is not a
default.

Plugin and theme code updates keep using WordPress's individual per-item choices. Better by
Default does not guess release risk from version numbers, and it reports rather than overrides
an explicit `WP_AUTO_UPDATE_CORE`, `AUTOMATIC_UPDATER_DISABLED`, or `DISALLOW_FILE_MODS` policy.
Translation files retain WordPress's existing automatic-update behaviour.

See [`docs/wordpress-default-settings.md`](docs/wordpress-default-settings.md) for the full
reference — every default, the reasoning, and the snippet.

## How the plugin is built

One array — `wpyeg_defaults_schema()` — is the single source of truth. It drives both the
settings screen and the bootstrap that wires each *enabled* policy to its WordPress hook.
Adding a new default is one array entry plus one `if`-block in bootstrap; no new settings-page
code. (The `wpyeg_` option prefix is kept deliberately as the WPYEG org convention.)

### Working on it

```bash
composer install          # lint tooling (require-dev); vendor/ is gitignored
composer test             # tests/plugin-policy.php — no WordPress needed
composer lint             # phpcs against phpcs.xml — warnings fail too (lint:fix autofixes)
composer build            # rewrite dist/sane-defaults.zip from plugin/
composer verify:dist      # check the committed zip matches the source, without rewriting
composer build:deck       # rebuild workshop/Better-by-Default.pptx (needs node)
composer verify:deck      # check the committed deck matches build_deck.js (needs node)
composer verify:pdf       # check the handout matches the deck (needs LibreOffice)
```

`composer test`, `verify:dist`, `verify:deck`, and `lint` run in CI on every push and pull
request — see [`.github/workflows/ci.yml`](.github/workflows/ci.yml). CI runs those composer
scripts verbatim rather than its own variants, so a green local checkout means a green build.
The tests run against PHP 7.4, 8.1, and 8.4, so the `Requires PHP: 7.4` claim is tested rather
than asserted. `verify:pdf` is deliberately excluded, for the reason given below.

`composer test` is the one that matters most, and not only for the policy assertions.
The suite ends with a cross-artifact parity guard: every key in `wpyeg_defaults_schema()`
must appear — with its default and group — in the reference doc, in both workshop scripts,
and in the deck generator; the two workshop scripts must be byte-identical; and the setting
count written out in prose must match the schema. A setting cannot quietly drift out of the
learner-facing material, because the tests fail first.

The guard reaches the **rendered deck** too. `Better-by-Default.pptx` is a zip of XML, so the
suite reads its slide text and speaker notes back out and holds them to the same standard as
the sources. Before that guard existed, `node build_deck.js` sat broken through a commit and
the shipped deck was two corrections behind, with nothing to say so.

Those assertions cover keys, the setting count, and the claims worth policing. They cannot see
a plain rewrite — reword a slide in `build_deck.js`, skip the rebuild, and the suite stays
green while the deck says the old thing. `composer verify:deck` closes that: it runs the
generator into a temporary directory and compares its slide text against the committed deck,
so it catches both the stale rebuild and a generator that no longer runs. It writes nothing.

Rebuilding the deck needs `pptxgenjs`, pinned in `workshop/package.json`:

```bash
cd workshop && npm ci
```

The **PDF handout** is regenerated from the rebuilt deck. It needs LibreOffice, which is not
a dependency of anything else here:

```bash
cd workshop && soffice --headless --convert-to pdf Better-by-Default.pptx
```

On macOS the binary is inside the app bundle and not on `PATH`, so use
`/Applications/LibreOffice.app/Contents/MacOS/soffice` or symlink it.

Do **not** rebuild the handout defensively. The conversion is not byte-reproducible —
LibreOffice numbers PDF objects and tags font subsets differently on every run, so rebuilding
an unchanged deck yields a file that renders identically but differs in tens of thousands of
bytes. Committing that buries the real handout changes it is supposed to show.

To check without rebuilding, `composer verify:pdf` converts into a temporary directory,
compares, and writes nothing:

```bash
composer verify:pdf
```

The handout is only **partly** guarded, and it is worth knowing where the line is. Its text
sits in subsetted font encodings that do not survive naive extraction, so both checks compare
structure rather than pixels. `composer test` asserts one PDF page per slide. `verify:pdf`
adds the normalized byte length, which any real content change moves. Neither is a rendering
comparison: an edit that changed the page while leaving the compressed length untouched would
pass. Rebuild and look at the handout before it goes out.

(`node_modules/` in `workshop/` is gitignored.)

### Spelling

Canadian, throughout — prose, comments, and the strings the plugin puts on screen.
This is a teaching plugin for an Edmonton meetup, so it is written the way its
audience writes: behaviour, colour, favour, centre, defence, licence (the noun).
The `-ize` and `-izer` endings stay as they are; those are Canadian too.

Two things are not prose and never change: **identifiers** — variables, option
keys, hook names, CSS properties, anything WordPress or a browser reads
(`login_logo_behavior`, `background_color`, `text-align: center`) — and **proper
nouns**, including specification titles and other projects' feature names.

Bigger plugins in this family split it differently, keeping source strings in
`en_US` to match WordPress core and shipping Canadian as an `en_CA` catalogue.
That is the right call for anything a stranger might translate. Here it would add
a build step to a file meant to be read start to finish, so it is deliberately
not done.

## Workshop materials

The [`workshop/`](workshop/) folder holds the full talk: `Better-by-Default.pptx`, an iA
Presenter version (`Better-by-Default.ia.md`), a `Better-by-Default.pdf` handout, and
`build_deck.js` (the pptxgenjs generator, in case you want to reskin the slides).

## License

[GPL-3.0-or-later](LICENSE). Fork it, teach with it, ship it.
