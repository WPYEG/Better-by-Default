/*
 * Better by Default — workshop deck generator (pptxgenjs).
 *
 * Build the slides:   node build_deck.js            → Better-by-Default.pptx
 * Render the PDF:     soffice --headless --convert-to pdf Better-by-Default.pptx
 *   (soffice = LibreOffice; the PDF is the pptx design, not the iA Presenter export)
 *
 * The iA Presenter sources (better-by-default.iapresenter/ and Better-by-Default.ia.md)
 * carry the same deck for live presenting; keep all three in sync on content changes.
 */
const pptxgen = require("pptxgenjs");
const p = new pptxgen();
p.layout = "LAYOUT_WIDE"; // 13.33 x 7.5
p.author = "WPYEG";
p.title = "Better by Default";

/* ---------------- Palette: WPYEG prairie / steel blue ---------------- */
const INK    = "0F2733"; // deep steel navy (dark bg)
const INK2   = "16323F";
const STEEL  = "27607A"; // primary steel blue
const STEEL2 = "3E7E9C"; // lighter steel
const SKY    = "D7E5EC"; // pale ice
const WHEAT  = "E0A94B"; // prairie gold accent
const WHEATD = "C88F33";
const CLOUD  = "F5F8FA"; // light content bg
const WHITE  = "FFFFFF";
const SLATE  = "4A5A63"; // muted body on light
const MUTE   = "8A9AA3";
const CODEBG = "0E2836"; // code panel
const CODEFG = "DCEAF1";
const CGREEN = "8FBF9F"; // code comment
const CGOLD  = "E7C070"; // code highlight
const CORAL  = "E07A5F";

const HEAD = "Cambria";
const BODY = "Calibri";
const MONO = "Courier New";

/* ---------------- Helpers ---------------- */
function footer(s, n) {
  s.addText(
    [
      { text: "WPYEG", options: { color: WHEATD, bold: true } },
      { text: "  ·  Better by Default", options: { color: MUTE } },
    ],
    { x: 0.55, y: 7.03, w: 8, h: 0.3, fontFace: BODY, fontSize: 9, align: "left", margin: 0 }
  );
  s.addText(String(n), {
    x: 12.2, y: 7.03, w: 0.6, h: 0.3, fontFace: BODY, fontSize: 9,
    color: MUTE, align: "right", margin: 0,
  });
}

// small filled circle "dot" motif with a glyph
function dot(s, x, y, glyph, fill, txtcolor, size) {
  const d = size || 0.5;
  s.addShape(p.ShapeType.ellipse, { x, y, w: d, h: d, fill: { color: fill } });
  s.addText(glyph, {
    x, y, w: d, h: d, align: "center", valign: "middle",
    fontFace: HEAD, fontSize: d * 26, bold: true, color: txtcolor, margin: 0,
  });
}

// render code lines: array of {t, k} where k: 'c'=comment,'h'=highlight,else default
function codePanel(s, x, y, w, h, lines, fontSize) {
  s.addShape(p.ShapeType.roundRect, {
    x, y, w, h, rectRadius: 0.08, fill: { color: CODEBG },
    line: { color: STEEL, width: 1 },
    shadow: { type: "outer", color: "0A1A22", blur: 6, offset: 3, angle: 90, opacity: 0.4 },
  });
  const runs = [];
  lines.forEach((ln, i) => {
    const last = i === lines.length - 1;
    let color = CODEFG;
    if (ln.k === "c") color = CGREEN;
    else if (ln.k === "h") color = CGOLD;
    runs.push({ text: ln.t === "" ? " " : ln.t, options: { color, breakLine: !last } });
  });
  s.addText(runs, {
    x: x + 0.22, y: y + 0.18, w: w - 0.44, h: h - 0.36,
    fontFace: MONO, fontSize: fontSize || 11.5, align: "left", valign: "top",
    lineSpacingMultiple: 1.08, margin: 0,
  });
}

/* =================================================================== */
/* 1. TITLE                                                            */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: INK };
  // faint prairie horizon band using two low-opacity shapes (not an accent stripe—full-bleed field)
  s.addShape(p.ShapeType.rect, { x: 0, y: 5.9, w: 13.33, h: 1.6, fill: { color: INK2 } });
  dot(s, 0.85, 0.8, "{ }", WHEAT, INK, 0.72);
  s.addText("WPYEG · Edmonton WordPress Meetup", {
    x: 1.75, y: 0.9, w: 9, h: 0.5, fontFace: BODY, fontSize: 15, color: WHEAT, bold: true, margin: 0,
  });
  s.addText("Better by Default", {
    x: 0.85, y: 2.35, w: 11.8, h: 1.5, fontFace: HEAD, fontSize: 68, bold: true,
    color: WHITE, lineSpacingMultiple: 1.02, margin: 0,
  });
  s.addText("Secure defaults for every WordPress site.", {
    x: 0.9, y: 4.35, w: 11.2, h: 0.7, fontFace: BODY, fontSize: 21, color: SKY, italic: true, margin: 0,
  });
  s.addText(
    [
      { text: "a hands-on workshop  ", options: { color: SKY } },
      { text: "·  build the “sane-defaults” plugin", options: { color: WHEAT } },
    ],
    { x: 0.9, y: 6.25, w: 11.5, h: 0.5, fontFace: MONO, fontSize: 14, margin: 0 }
  );
  s.addNotes(
    "Welcome to WPYEG. In this workshop we're building and reviewing a small plugin that defines and activates 32 sensible but little-known and seldom used defaults for WordPress sites in 2026. Whether you write PHP daily or just manage WordPress sites, you'll leave knowing why each default matters and how to enable (or disable) it. This workshop and plugin distils years of experience and new learning from a recent project that I've summed up in this workshop."
  );
})();

/* =================================================================== */
/* 2. THE HOOK / PROBLEM                                               */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("WordPress is open by default; hosts vary in what they close.", {
    x: 0.6, y: 0.55, w: 12, h: 0.9, fontFace: HEAD, fontSize: 34, bold: true, color: INK, margin: 0,
  });
  s.addText("None of these are bugs — or quite the security risks popular opinion alleges. They are defaults chosen for maximum compatibility on a 20+ year-old web application.", {
    x: 0.6, y: 1.5, w: 11.8, h: 0.9, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });

  const cards = [
    { g: "!", c: CORAL, t: "Usernames leak", d: "REST and author archives expose public author slugs that often resemble login names." },
    { g: "!", c: CORAL, t: "XML-RPC exposed", d: "A venerable interop protocol Jetpack still uses. If you don't need it, it just adds attack surface." },
    { g: "~", c: WHEATD, t: "Dead weight loads", d: "Emoji scripts, version tags, and RSD links on every page. Do you need them?" },
    { g: "~", c: WHEATD, t: "Spam surface invites", d: "Comments, pingbacks, and trackbacks are open by default. Legacy cruft, or the heart of the open web?" },
  ];
  const cw = 5.75, ch = 1.85, gx = 0.6, gy = 2.65, gapx = 0.6, gapy = 0.45;
  cards.forEach((c, i) => {
    const col = i % 2, row = Math.floor(i / 2);
    const x = gx + col * (cw + gapx), y = gy + row * (ch + gapy);
    s.addShape(p.ShapeType.roundRect, {
      x, y, w: cw, h: ch, rectRadius: 0.09, fill: { color: WHITE },
      line: { color: "DCE6EB", width: 1 },
      shadow: { type: "outer", color: "C7D4DB", blur: 5, offset: 2, angle: 90, opacity: 0.5 },
    });
    dot(s, x + 0.3, y + 0.35, c.g, c.c, WHITE, 0.55);
    s.addText(c.t, { x: x + 1.05, y: y + 0.3, w: cw - 1.3, h: 0.5, fontFace: HEAD, fontSize: 19, bold: true, color: INK, margin: 0 });
    s.addText(c.d, { x: x + 1.05, y: y + 0.82, w: cw - 1.3, h: 0.9, fontFace: BODY, fontSize: 14, color: SLATE, margin: 0, valign: "top" });
  });
  footer(s, 2);
  s.addNotes("NONE OF THESE ARE BUGS or quite the security risks popular human and AI opinion allege. They are defaults chosen for maximum compatibility on a 20+ year-old web application. You probably don't need them and can tighten up your own WordPress sites unless you're into the IndieWeb and radical open source anarchism, which I highly recommend. Probing the oldest parts of WordPress is a good way to learn some history and important fundamentals about how WordPress works — and how to keep it secure, fast, and pretty.");
})();

/* =================================================================== */
/* 3. THE BIG IDEA                                                     */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: STEEL };
  s.addShape(p.ShapeType.rect, { x: 0, y: 0, w: 13.33, h: 7.5, fill: { color: STEEL } });
  s.addText("The one idea to take home", {
    x: 0.8, y: 1.15, w: 11, h: 0.6, fontFace: BODY, fontSize: 18, color: WHEAT, bold: true, margin: 0,
  });
  s.addText("A “default” is just an\nopinionated filter behind a toggle.", {
    x: 0.8, y: 1.9, w: 11.7, h: 2.4, fontFace: HEAD, fontSize: 44, bold: true, color: WHITE, lineSpacingMultiple: 1.05, margin: 0,
  });
  codePanel(s, 0.8, 4.5, 11.7, 1.9, [
    { t: "if ( wpyeg_defaults_enabled( 'restrict_rest_user_discovery' ) ) {", k: "" },
    { t: "    add_filter( 'rest_endpoints', $hide_users_endpoint );", k: "h" },
    { t: "}   // that's the whole pattern, repeated across the plugin", k: "c" },
  ], 15);
  s.addNotes("In our demo plugin, a default is an add_filter behind an if ( option ). We have 32 settings built around that pattern.");
})();

/* =================================================================== */
/* 4. PRIMER: hooks & filters (mixed audience)                         */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("Hooks", {
    x: 0.6, y: 0.55, w: 12, h: 0.8, fontFace: HEAD, fontSize: 32, bold: true, color: INK, margin: 0,
  });
  s.addText("WordPress is built to be interrupted at labelled moments (hooks) so you never edit core code.", {
    x: 0.6, y: 1.4, w: 11.8, h: 0.7, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });

  const rows = [
    { g: "A", t: "Actions", d: "“When you reach this moment, also DO this.”", ex: "add_action( 'init', 'my_callback' );" },
    { g: "F", t: "Filters", d: "“Before you use this value, let me CHANGE it first.”", ex: "add_filter( 'xmlrpc_enabled', '__return_false' );" },
  ];
  rows.forEach((r, i) => {
    const y = 2.35 + i * 2.05;
    s.addShape(p.ShapeType.roundRect, { x: 0.6, y, w: 12.1, h: 1.8, rectRadius: 0.09, fill: { color: WHITE }, line: { color: "DCE6EB", width: 1 }, shadow: { type: "outer", color: "C7D4DB", blur: 5, offset: 2, angle: 90, opacity: 0.5 } });
    dot(s, 0.95, y + 0.55, r.g, STEEL, WHITE, 0.7);
    s.addText(r.t, { x: 1.9, y: y + 0.22, w: 3, h: 0.5, fontFace: HEAD, fontSize: 22, bold: true, color: STEEL, margin: 0 });
    s.addText(r.d, { x: 1.9, y: y + 0.75, w: 6.4, h: 0.9, fontFace: BODY, fontSize: 14.5, color: SLATE, margin: 0, valign: "top" });
    s.addShape(p.ShapeType.roundRect, { x: 8.5, y: y + 0.5, w: 3.95, h: 0.8, rectRadius: 0.06, fill: { color: CODEBG } });
    s.addText(r.ex, { x: 8.65, y: y + 0.5, w: 3.7, h: 0.8, fontFace: MONO, fontSize: 10.5, color: CGOLD, valign: "middle", margin: 0 });
  });
  footer(s, 4);
  s.addNotes("WordPress is built to be interrupted at labelled moments (hooks) so you never edit core code. __return_false is a tiny built-in helper that just hands back false — perfect for switching a feature off.");
})();

/* =================================================================== */
/* 5. PRECEDENCE                                                       */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  dot(s, 0.6, 0.55, "↓", WHEAT, INK, 0.7);
  s.addText("What wins when settings overlap?", {
    x: 1.45, y: 0.55, w: 11, h: 0.75, fontFace: HEAD, fontSize: 30, bold: true, color: INK, margin: 0, valign: "middle",
  });
  const steps = [
    { n: "1", t: "wp-config.php constants", d: "Load first. When core treats one as authoritative, plugin settings cannot override it." },
    { n: "2", t: "Must-use plugins", d: "Load before normal plugins, so their callbacks register first." },
    { n: "3", t: "Normal plugins", d: "Load in active_plugins order — PMP before BBD on this demo site." },
    { n: "4", t: "Hook priority", d: "Lower runs earlier; higher runs later. Ties keep registration order." },
  ];
  steps.forEach((c, i) => {
    const y = 1.5 + i * 1.14;
    s.addShape(p.ShapeType.roundRect, { x: 0.6, y, w: 12.1, h: 1.0, rectRadius: 0.08, fill: { color: WHITE }, line: { color: "DCE6EB", width: 1 }, shadow: { type: "outer", color: "C7D4DB", blur: 4, offset: 2, angle: 90, opacity: 0.5 } });
    dot(s, 0.9, y + 0.25, c.n, STEEL, WHITE, 0.5);
    s.addText(c.t, { x: 1.6, y: y + 0.08, w: 3.6, h: 0.85, fontFace: HEAD, fontSize: 17, bold: true, color: INK, margin: 0, valign: "middle" });
    s.addText(c.d, { x: 5.2, y: y + 0.08, w: 7.3, h: 0.85, fontFace: BODY, fontSize: 14, color: SLATE, margin: 0, valign: "middle" });
  });
  s.addShape(p.ShapeType.roundRect, { x: 0.6, y: 6.1, w: 12.1, h: 0.72, rectRadius: 0.07, fill: { color: CODEBG }, line: { color: STEEL, width: 1 } });
  s.addText("effective behaviour = hard constants + every callback, in execution order", {
    x: 0.85, y: 6.1, w: 11.6, h: 0.72, fontFace: MONO, fontSize: 13, bold: true, color: CGOLD, valign: "middle", margin: 0,
  });
  footer(s, 5);
  s.addNotes("This is a debugging model, not a universal 'last plugin wins' rule. Constants cannot be redefined; filters pass a value through every callback; actions may accumulate effects. wp-config.php is loaded before plugins. Must-use plugins load before normal plugins. Normal plugins are included in the stored active_plugins order — PMP before BBD on this demo site. Within a hook, lower priorities run earlier, higher priorities later, and equal priorities retain registration order. Sources: https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/ ; https://developer.wordpress.org/plugins/hooks/actions/#priority ; https://developer.wordpress.org/reference/functions/wp_get_active_and_valid_plugins/ ; https://developer.wordpress.org/advanced-administration/wordpress/wp-config/");
})();

/* =================================================================== */
/* 6. ROADMAP                                                          */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("Eight categories of defaults", {
    x: 0.6, y: 0.55, w: 12, h: 0.8, fontFace: HEAD, fontSize: 34, bold: true, color: INK, margin: 0,
  });
  const cats = [
    { g: "1", t: "Security", d: "Make the attack surface smaller" },
    { g: "2", t: "Updates", d: "A deliberate core & translation policy" },
    { g: "3", t: "Content", d: "Close spam channels & info leaks" },
    { g: "4", t: "Admin UX", d: "A calmer, faster, prettier dashboard" },
    { g: "5", t: "Login", d: "Sessions & credentials" },
    { g: "6", t: "Branding", d: "Own your login screen" },
    { g: "7", t: "Performance", d: "Trim the fat" },
    { g: "8", t: "Email", d: "Say so when the site cannot send mail" },
  ];
  // Seven cards: 4 across, 2 down. The last slot stays empty.
  const cw = 2.72, ch = 2.15, gx = 0.6, gy = 1.7, gapx = 0.42, gapy = 0.5;
  cats.forEach((c, i) => {
    const col = i % 4, row = Math.floor(i / 4);
    const x = gx + col * (cw + gapx), y = gy + row * (ch + gapy);
    s.addShape(p.ShapeType.roundRect, { x, y, w: cw, h: ch, rectRadius: 0.1, fill: { color: WHITE }, line: { color: "DCE6EB", width: 1 }, shadow: { type: "outer", color: "C7D4DB", blur: 5, offset: 2, angle: 90, opacity: 0.5 } });
    dot(s, x + 0.35, y + 0.35, c.g, WHEAT, INK, 0.7);
    s.addText(c.t, { x: x + 0.35, y: y + 1.1, w: cw - 0.55, h: 0.45, fontFace: HEAD, fontSize: 19, bold: true, color: INK, margin: 0 });
    s.addText(c.d, { x: x + 0.35, y: y + 1.52, w: cw - 0.55, h: 0.6, fontFace: BODY, fontSize: 12.5, color: SLATE, margin: 0, valign: "top" });
  });
  footer(s, 6);
  s.addNotes("We'll spend most of our time on security and content, then move quickly through updates, UX, login, branding, and performance, and we'll end up with a plugin that covers them all.");
})();

/* =================================================================== */
/* SECTION DIVIDER helper                                              */
/* =================================================================== */
function divider(num, kicker, title, blurb) {
  const s = p.addSlide();
  s.background = { color: INK };
  s.addShape(p.ShapeType.rect, { x: 0, y: 6.1, w: 13.33, h: 1.4, fill: { color: INK2 } });
  dot(s, 0.85, 1.0, num, WHEAT, INK, 0.95);
  s.addText(kicker, { x: 2.05, y: 1.1, w: 9, h: 0.5, fontFace: BODY, fontSize: 16, color: WHEAT, bold: true, margin: 0 });
  s.addText(title, { x: 0.85, y: 2.5, w: 11.6, h: 1.4, fontFace: HEAD, fontSize: 46, bold: true, color: WHITE, margin: 0 });
  s.addText(blurb, { x: 0.9, y: 4.0, w: 11, h: 1.0, fontFace: BODY, fontSize: 19, color: SKY, italic: true, margin: 0, valign: "top" });
  return s;
}

/* ---------- CONTENT CODE SLIDE helper ---------- */
// left: what/why + option chip; right: code panel
function codeSlide(num, kicker, title, why, optKey, def, lines, fs) {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText(kicker, { x: 0.6, y: 0.5, w: 8, h: 0.4, fontFace: BODY, fontSize: 13, color: STEEL2, bold: true, margin: 0 });
  s.addText(title, { x: 0.6, y: 0.85, w: 12.1, h: 0.85, fontFace: HEAD, fontSize: 30, bold: true, color: INK, margin: 0 });

  // left column
  s.addText(why, { x: 0.6, y: 1.95, w: 5.35, h: 3.2, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0, valign: "top", lineSpacingMultiple: 1.12 });

  // setting-key chip
  s.addShape(p.ShapeType.roundRect, { x: 0.6, y: 5.55, w: 5.35, h: 1.05, rectRadius: 0.08, fill: { color: "EAF1F5" }, line: { color: STEEL2, width: 1 } });
  s.addText([
    { text: "KEY      ", options: { color: STEEL2, bold: true } },
    { text: optKey, options: { color: INK, bold: true } },
    { text: "\nDEFAULT   ", options: { color: STEEL2, bold: true } },
    { text: def, options: { color: def.includes("no") ? WHEATD : STEEL, bold: true } },
  ], { x: 0.8, y: 5.65, w: 5.0, h: 0.85, fontFace: MONO, fontSize: 11.5, valign: "middle", margin: 0, lineSpacingMultiple: 1.15 });

  // right code
  codePanel(s, 6.25, 1.95, 6.45, 4.65, lines, fs || 11.5);
  footer(s, num);
  return s;
}

/* =================================================================== */
/* SECTION 1 — SECURITY                                                */
/* =================================================================== */
divider("1", "SECTION ONE", "Security &\nAttack Surface", "Every item here removes something an attacker can poke — usually in one line.")
  .addNotes("Every item in this section removes something an attacker can poke — usually in one line. The theme is simple: disable what you don't use. You can't exploit an endpoint that isn't there.");

codeSlide(8, "SECURITY · 1 of 7",
  "Restrict REST API user discovery",
  "The /wp/v2/users endpoint hands out every author's login name to anyone — half of a brute-force guess, for free. Closing it for logged-out requests only keeps the editor and legit integrations working.",
  "restrict_rest_user_discovery", "yes",
  [
    { t: "add_filter( 'rest_endpoints', function ( $ep ) {", k: "" },
    { t: "    if ( ! is_user_logged_in() ) {", k: "" },
    { t: "        unset( $ep['/wp/v2/users'] );", k: "h" },
    { t: "        unset(", k: "" },
    { t: "          $ep['/wp/v2/users/(?P<id>[\\d]+)']", k: "h" },
    { t: "        );", k: "" },
    { t: "    }", k: "" },
    { t: "    return $ep;", k: "" },
    { t: "} );", k: "" },
  ]).addNotes("The /wp/v2/users endpoint exposes every public author's name, ID, profile link, and slug to anyone. Because an author slug often resembles a login name, that gives attack scripts a useful credential hint for free. By closing the user-list and numeric user routes for logged-out requests only, the editor and legitimate integrations keep working while anonymous enumeration attempts receive an ordinary 404. It's partly security by obscurity — not a substitute for strong passwords, MFA, or rate limiting — but it also rejects junk requests from bots that are up to no good. Why spend even a few extra electrons helping them? Author archives take the separate path we'll see later: a 301 to the homepage. If probes persist, a properly configured host can count those request patterns and ban the source IP with Fail2Ban or a similar tool such as CrowdSec, SSHGuard, or Defensia.");

codeSlide(9, "SECURITY · 2 of 7 · opt-in",
  "Lock REST to logged-in users (opt-in)",
  "The sledgehammer version of the slide before: requiring auth for ALL REST calls stops anonymous scraping cold. It breaks anonymous REST — front-end blocks, embeds, search, outside integrations — so it ships off.",
  "disable_rest", "no",
  [
    { t: "add_filter( 'rest_authentication_errors',", k: "" },
    { t: "  function ( $result ) {", k: "" },
    { t: "    // Only an ERROR short-circuits. A cookie with no", k: "c" },
    { t: "    // nonce resolves to user 0 and returns true.", k: "c" },
    { t: "    if ( is_wp_error( $result ) ) return $result;", k: "h" },
    { t: "    // Leave oEmbed open, or every site embedding", k: "c" },
    { t: "    // yours silently degrades to a bare link.", k: "c" },
    { t: "    if ( route_is_public() ) return $result;", k: "h" },
    { t: "    if ( ! is_user_logged_in() ) {", k: "" },
    { t: "      return new WP_Error(", k: "" },
    { t: "        'rest_not_logged_in', 'Auth required.',", k: "" },
    { t: "        array( 'status' => 401 ) );", k: "" },
    { t: "    }", k: "" },
    { t: "    return $result;", k: "" },
    { t: "} );", k: "" },
  ]).addNotes("This is the sledgehammer version of the slide before. Requiring auth for ALL REST calls stops anonymous scraping cold. It does *not* break the block editor — you're logged in there, and the editor authenticates with your cookie plus a REST nonce, so it sails through. That's why it ships off: not every default should default to on.\n\nTwo details on this slide are the whole lesson, and both are things the obvious implementation gets wrong.\n\nFirst: only a WP_Error may short-circuit. The tempting line is `if ( ! empty( $result ) ) return $result;` — and core hands this filter `true` from rest_cookie_check_errors() AFTER calling wp_set_current_user( 0 ), when a cookie arrives with no X-WP-Nonce. Treat that `true` as \"already authenticated\" and you have written a gate that waves through the exact request it exists to stop, dispatching as user 0. This deck taught the `! empty()` version until we tested it.\n\nSecond: oEmbed. It is served over REST, so closing REST closes it too — and the damage lands somewhere you never look. Every site that has embedded one of your posts silently degrades to a bare link, with nothing on your site to show it happened. We measured four popular plugins that close REST outright; not one of them allowlists oembed/1.0. So we allowlist it, filterable with wpyeg_public_rest_routes.\n\nAnd the carve-out is only safe because of what sits behind it: oEmbed returns author_name and an author_url carrying the account nicename, so opening that route would hand an anonymous caller the usernames the users-endpoint removal just refused them. Closing REST therefore strips those fields too. A hole you open on purpose still has to be closed on the other side.");

codeSlide(10, "SECURITY · 3 of 7",
  "Lock XML-RPC down by category",
  "XML-RPC is legitimate but aging — an extra surface, not a backdoor. We unplug unused method families while preserving the endpoint for integrations that need it.",
  "xmlrpc allow keys + block_xmlrpc_endpoint", "no (all four)",
  [
    { t: "// each category off → remove its methods", k: "c" },
    { t: "add_filter( 'xmlrpc_methods', function ( $m ) {", k: "" },
    { t: "  if ( ! allow( 'pingbacks' ) )", k: "" },
    { t: "    unset( $m['pingback.ping'] );", k: "h" },
    { t: "  if ( ! allow( 'remote_publishing' ) )", k: "" },
    { t: "    // drop wp.* metaWeblog.* mt.* blogger.*", k: "c" },
    { t: "  return $m;", k: "" },
    { t: "} );", k: "" },
    { t: "", k: "" },
    { t: "// multicall can't be filtered off (IXR re-adds it)", k: "c" },
    { t: "// → swap in a server that refuses it", k: "c" },
    { t: "add_filter( 'wp_xmlrpc_server_class', $refuse );", k: "h" },
  ], 11).addNotes(
  "XML-RPC is a legitimate but aging API. (Mad love to Dave Winer!) It's not a backdoor or an emergency. It is an old switchboard where every method is a phone line. Rather than rip out a connection that Jetpack or a publishing client may need, we unplug unused lines by category. Four switches, all off by default:\n\n" +
  "1. Pingbacks — drop pingback.ping, the clearest live nuisance and reflection-DDoS surface. A valid call performs database work, waits a second, and fetches the claimed source URL. Keep it if you're a crusty punk who loves the IndieWeb and everything before Facebook turned everything to shit, ca. 2005.\n" +
  "2. Remote publishing — drop the credential-authenticated blogging methods (wp.*, metaWeblog.*, mt.*, blogger.*), another password-guessing entrance when legacy clients are not needed. This also flips xmlrpc_enabled off and removes the RSD discovery link.\n" +
  "3. system.multicall — refuse a general batching wrapper with little established modern use. WordPress 4.4 prevented it from being used as a password-guessing multiplier, so the old 'thousands of guesses' story is obsolete. (To this day, people say XML-RPC is some kind of open, free credential verification oracle — NOT TRUE.) Multicall can still batch other work, including pingbacks, but it does not enable pingback abuse.\n" +
  "4. Block the endpoint — the blunt hammer: xmlrpc.php returns 403 for everything. Prefer doing this at the CDN, WAF, or web server so the request never consumes PHP.\n\n" +
  "The first three are surgical and leave third-party registrations such as Jetpack's jetpack.* in place. That is not a compatibility guarantee: keep the endpoint reachable, leave Remote Publishing enabled until testing proves it unnecessary, and test the Jetpack connection and features after method changes. Block the endpoint only when nothing on the site speaks XML-RPC.\n\n" +
  "[Aside — what's \"IXR\"? The Incutio XML-RPC library. Simon Willison released it in September 2002, one of his first open-source projects, while blogging from the University of Bath; both WordPress *and* Drupal adopted it, and it then sat largely untouched for 15+ years — long enough to pick up a CVE. Willison went on to co-create Django (2003–05 at the Lawrence Journal-World), build Lanyrd (sold to Eventbrite in 2013) and Datasette (2017), and is now one of the most-read writers on LLMs.]"
);

codeSlide(11, "SECURITY · 4 of 7",
  "Keep Application Passwords available",
  "This is an existing default we don't lock down. An Application Password is like a spare key cut for one app: hashed, per-application, revocable on its own — the safer REST credential, and the only one core accepts for REST Basic Auth.",
  "disable_application_passwords", "no (available)",
  [
    { t: "// available by default —", k: "c" },
    { t: "// prohibit only if opted in", k: "c" },
    { t: "if ( wpyeg_defaults_enabled(", k: "" },
    { t: "       'disable_application_passwords' ) ) {", k: "" },
    { t: "  add_filter(", k: "" },
    { t: "    'wp_is_application_passwords_available',", k: "h" },
    { t: "    '__return_false'", k: "h" },
    { t: "  );", k: "" },
    { t: "}", k: "" },
  ], 12.5).addNotes("This is an existing default we *don't* lock down. An Application Password is like a spare key cut for one app: each app gets its own hashed key, so you can revoke one without touching the others or changing the account password. That makes it the safer REST credential and the only one core accepts for REST Basic Auth. So they are good — they just don't have a toggle in WordPress core settings. You might need to prohibit application passwords on a site that forbids non-interactive credentials, but switching them off doesn't stop people connecting things, it just pushes them to worse habits, like sharing an account.");

codeSlide(12, "SECURITY · 5 of 7",
  "Screen breaches without sending the password",
  "NIST SP 800-63B-4 § 3.1.1.2: use 15+ characters, a blocklist, and no composition rules. BBD hashes the candidate locally, sends HIBP only the first 5 SHA-1 characters, and matches the remaining 35 locally. The password and full hash never leave WordPress; unavailable or invalid HIBP data fails open.",
  "require_strong_passwords", "yes",
  [
    { t: "$hash   = strtoupper( sha1( $pw ) );", k: "" },
    { t: "$prefix = substr( $hash, 0, 5 );", k: "h" },
    { t: "$suffix = substr( $hash, 5 );", k: "" },
    { t: "", k: "" },
    { t: "// HIBP receives $prefix and returns", k: "c" },
    { t: "// matching suffixes plus breach counts.", k: "c" },
    { t: "// BBD compares $suffix locally.", k: "c" },
    { t: "if ( response_contains( $suffix ) ) {", k: "h" },
    { t: "    reject( 'Seen in a breach.' );", k: "" },
    { t: "}", k: "" },
    { t: "// invalid or 128 KiB => fail open", k: "c" },
  ]).addNotes("NIST SP 800-63B-4 § 3.1.1.2 calls for at least 15 characters for single-factor passwords, no composition rules, and a blocklist of commonly used, expected, or compromised passwords. BBD first applies its length rule, a small local blocklist, and checks for the username or email name. It then screens the candidate against the Have I Been Pwned Pwned Passwords range API.\n\nThe privacy trick is k-anonymity. BBD computes the candidate's SHA-1 hash locally, sends HIBP only the first five hexadecimal characters, and receives roughly 800–1,000 suffixes that share that prefix. BBD compares the remaining 35 characters locally. The password and its full hash never leave WordPress. SHA-1 is only HIBP's lookup format here; WordPress still owns password storage and uses its normal password hashing.\n\nBBD also sends Add-Padding: true, so response size does not disclose how many real matches exist; synthetic rows have a count of zero and are ignored. WordPress caps the response at 128 KiB with limit_response_size. Because a response reaching that cap may be truncated, capped, empty, malformed, failed, and non-200 responses are treated as unavailable and fail open. Only structurally valid prefix responses are cached for 12 hours; the local length, blocklist, and personal-context checks still apply. The same server-side validator covers profile changes, password resets, and REST user-password requests.\n\nAND YOU CAN SWITCH IT OFF — WPYEG_DISABLE_HIBP in wp-config.php, or the wpyeg_disable_hibp filter for a per-password decision. This is the only thing the whole plugin does that leaves your server, and everything on this slide is an argument that it is safe to do: hashed locally, five characters sent, padded response, nothing recoverable. All true, and none of it is an answer to \"may I decline.\" Someone under a data-protection regime, on an air-gapped network, or just unwilling to make an outbound call on every password change does not owe anyone a justification. Which is the whole talk, pointed back at us: a default you cannot turn off is not a default, it is a requirement wearing a default's clothes. Every other setting here has a toggle. This one is the test of whether we meant it.\n\nSources: HIBP API v3, Pwned Passwords — Searching by hash range using k-anonymity and Introducing padding: https://haveibeenpwned.com/API/v3#SearchingPwnedPasswordsByRange ; NIST SP 800-63B-4 § 3.1.1.2, Password Verifiers: https://pages.nist.gov/800-63-4/sp800-63b/authenticators/#passwordver");

codeSlide(13, "SECURITY · 6 of 7",
  "Remove fingerprints, add headers",
  "Two headers with no real downside ship on; framing is its own setting, because it is the only one that can break a working site. Hiding the version is obscurity, not hardening, so it ships off.",
  "security_headers / frame_options", "yes / SAMEORIGIN",
  [
    { t: "remove_action( 'wp_head', 'wp_generator' );", k: "h" },
    { t: "", k: "" },
    { t: "add_filter( 'wp_headers', function ( $h ) {", k: "" },
    { t: "  // compare, do not yield - see the notes", k: "c" },
    { t: "  $key = find_key( $h, 'X-Frame-Options' );", k: "" },
    { t: "  if ( stronger( $want, $h[ $key ] ) )", k: "h" },
    { t: "    $h[ $key ] = $want;", k: "h" },
    { t: "", k: "" },
    { t: "  // one effective value, so correct it", k: "c" },
    { t: "  $h[ ctk( $h ) ] = 'nosniff';", k: "h" },
    { t: "  return $h;", k: "" },
    { t: "} );", k: "" },
  ]).addNotes(
  "One default and one deliberate non-default — and the difference is the lesson. Hiding the version is obscurity, not hardening: it does not make an out-of-date site any safer, and it does not even hide much, since the version still leaks from asset query strings and feeds. What it genuinely buys is quieter logs. That is worth opting into, not worth shipping on and calling security — so it defaults to off. The headers are the opposite: real, low-risk defaults most sites can adopt without breaking anything:\n\n" +
  "- X-Content-Type-Options: nosniff — the browser must trust the declared Content-Type instead of guessing; kills \"a .txt the browser decides to run as JavaScript\" tricks.\n" +
  "- Referrer-Policy: strict-origin-when-cross-origin — sends the full URL within your own site, only the bare domain to other sites, and nothing on an HTTPS→HTTP downgrade; keeps tokens and private paths from leaking in the Referer.\n\n" +
  "X-Frame-Options is deliberately a SEPARATE setting (frame_options, default SAMEORIGIN), and that split is the point worth teaching. It is the only one of the three that can break a working site: blocking cross-origin framing also blocks legitimate embedding — a client intranet, a partner site, a preview tool — and it fails as a silent blank frame, which is miserable to debug. Bundled with the other two, a site that needs to be embeddable would have to give up nosniff as well.\n\n" +
  "Note HOW they are applied, too, because this changed in 1.1.1. The original rule was \"set only if unset\", which sounds polite and is wrong: whatever arrived first won, so a host's permissive X-Frame-Options silently beat a deliberately configured DENY. The values are compared now, and the configured one replaces what is there only when it is strictly stronger — an unrecognised value, a deprecated ALLOW-FROM say, is left alone rather than guessed at. X-Content-Type-Options has exactly one effective value, so an existing header saying anything else is not a policy to respect, it is a header doing nothing; it gets corrected in place. And names are matched case-insensitively, because HTTP says header names are and PHP array keys say they are not — that mismatch used to make another plugin's x-content-type-options invisible here and add a second, conflicting line. Referrer-Policy still defers to whatever is already set: its tokens have no single strictness axis, so there is nothing to compare.\n\n" +
  "Be honest about the limit though — PHP only sees headers set in PHP, so one added by nginx or a CDN is invisible here. Check the response, not just the code. A full Content-Security-Policy is a bigger conversation for another time!"
);

codeSlide(14, "SECURITY · 7 of 7",
  "The filter that calls itself",
  "Editors hold unfiltered_html on a single-site install — enough to save a raw <script> into a post. Taking it back is easy. Doing it without blowing the stack is the lesson.",
  "limit_unfiltered_html_to_admins", "yes",
  [
    { t: "add_filter( 'user_has_cap', function (", k: "" },
    { t: "    $allcaps, $caps, $args, $user ) {", k: "" },
    { t: "", k: "" },
    { t: "  if ( empty( $allcaps['unfiltered_html'] ) ) {", k: "" },
    { t: "    return $allcaps;", k: "" },
    { t: "  }", k: "" },
    { t: "", k: "" },
    { t: "  $roles = isset( $user->roles )", k: "" },
    { t: "    ? (array) $user->roles : array();", k: "" },
    { t: "", k: "" },
    { t: "  // Read what you were handed. Never ask.", k: "c" },
    { t: "  if ( in_array( 'administrator', $roles, true )", k: "h" },
    { t: "    || ! empty( $allcaps['manage_options'] ) ) {", k: "h" },
    { t: "    return $allcaps;", k: "" },
    { t: "  }", k: "" },
    { t: "", k: "" },
    { t: "  $allcaps['unfiltered_html'] = false;", k: "h" },
    { t: "  return $allcaps;", k: "" },
    { t: "}, PHP_INT_MAX - 1, 4 );", k: "" },
  ], 10.5).addNotes(
  "Editors hold unfiltered_html on a single-site install. That is enough to save a raw <script> into a post — not a vulnerability, a CAPABILITY, and one most sites never consciously granted. This takes it back to administrators, plus Super Admins on multisite.\n\n" +
  "THE LESSON HERE IS THE TRAP, NOT THE POLICY. user_has_cap fires on every capability check there is. So a filter hooked to it that ASKS a capability question calls itself, and calls itself again, until the stack blows. current_user_can( 'manage_options' ) inside this callback is infinite recursion. So is is_super_admin() on single site, which is the one that catches people — it calls has_cap( 'delete_users' ), straight back in here. On multisite it reads the network list instead and is safe, which is exactly why the real code guards it with is_multisite().\n\n" +
  "The fix is not cleverness, it is discipline: DECIDE FROM WHAT YOU WERE HANDED. $user->roles is already on the object. $allcaps['manage_options'] is already resolved — it was computed before your filter ran. Read those. Never ask.\n\n" +
  "One more detail worth stealing: the priority is PHP_INT_MAX - 1, so this has close to the final say over other user_has_cap filters — a plugin that grants the capability back later in the chain would otherwise quietly win. Not PHP_INT_MAX itself, which leaves a slot for something that genuinely must run last.\n\n" +
  "Put this beside the comment-feed 404 and you have the pattern's two failure modes. There, a filter that looked complete and was not. Here, a filter that can destroy itself by asking an innocent question. Both of them pass every test you would think to write."
);

/* =================================================================== */
/* SECTION 2 — CONTENT                                                 */
/* =================================================================== */
divider("2", "SECTION TWO", "Content &\nPublic Surfaces", "Close the spam funnels and the thin pages Google (and bots) love to crawl.")
  .addNotes("These reduce channels for spam and clean up the thin, duplicate URLs that bots and search engines get lost in.");

codeSlide(16, "CONTENT · 1 of 4",
  "Disable comments, trackbacks & pingbacks",
  "For many sites, comments are a spam magnet with little upside. Here we close them everywhere, hide existing threads, and drop the admin menu.",
  "disable_comments / disable_pingbacks / disable_self_pingbacks", "yes each",
  [
    { t: "add_filter( 'comments_open', '__return_false', 20 );", k: "h" },
    { t: "add_filter( 'pings_open',    '__return_false', 20 );", k: "h" },
    { t: "add_filter( 'comments_array',", k: "" },
    { t: "            '__return_empty_array', 20 );", k: "" },
    { t: "add_filter( 'get_comments_number', '__return_zero', 20 );", k: "" },
    { t: "add_filter( 'comments_pre_query',", k: "h" },
    { t: "            $empty_comment_queries, 10, 2 );", k: "h" },
    { t: "add_filter( 'render_block',", k: "h" },
    { t: "            $suppress_comment_blocks, 10, 2 );", k: "h" },
    { t: "", k: "" },
    { t: "// + remove_post_type_support() on init", k: "c" },
    { t: "// + remove_menu_page('edit-comments.php')", k: "c" },
    { t: "// + drop the admin-bar comments node", k: "c" },
  ]).addNotes("For many sites, comments are a spam magnet with little upside. Here we close them everywhere, hide existing threads, and drop the admin menu. If you want comments, leave this tuned off — but consider closing pingbacks and trackbacks, which are almost pure spam.\n\n" +
  "Closing comments is four jobs, not one: the template, the data, the editor, and the page. comments_open and comments_array answer the theme's comment template. comments_pre_query answers everything else — /wp/v2/comments most of all, which otherwise serves every comment the site has ever had. allowed_block_types_all takes the comment blocks out of the inserter, but the inserter only decides what an editor can add NEXT, and a block theme has already put those blocks in its post template. That markup needs render_block to return an empty string, or every post prints a \"Comments\" heading over an empty wrapper and the site reads as broken rather than as one that deliberately has no comments. get_comments_number is the same gap one layer down: wp_count_comments() answers zero once the query filter is in place, but the theme's heading reads the post's cached comment_count and cheerfully prints \"1 Comment\" above a thread that renders nothing.\n\n" +
  "Returning an empty string rather than unregistering the block types is what keeps this reversible. The blocks stay registered, the theme's markup stays as its author wrote it, and turning the setting off brings the whole thing back with nothing to undo.");

codeSlide(17, "CONTENT · 2 of 4",
  "A clean 404 needs redirect_canonical gone",
  "Dropping the feed link stops the feed being advertised, not served. Answering it takes a 404 — and the 404 takes one more removal that no filter-level test will ever catch.",
  "disable_comments", "yes",
  [
    { t: "add_action( 'template_redirect',", k: "" },
    { t: "            $block_comment_feeds, 9 );", k: "" },
    { t: "", k: "" },
    { t: "function block_comment_feeds() {", k: "" },
    { t: "  if ( ! is_comment_feed() ) { return; }", k: "h" },
    { t: "", k: "" },
    { t: "  $wp_query->set_404();", k: "" },
    { t: "  remove_action( 'template_redirect',", k: "h" },
    { t: "                 'redirect_canonical' );", k: "h" },
    { t: "", k: "" },
    { t: "  status_header( 404 );", k: "" },
    { t: "  nocache_headers();", k: "" },
    { t: "}", k: "" },
  ]).addNotes("Dropping the <link rel=\"alternate\"> stops the feed being advertised; it does not stop it being served. /comments/feed/ and <post>/feed/ keep answering 200 to anyone who types the URL, and a crawler that saw one once keeps asking. With comment queries already emptied, they answer 200 with nothing — a live, crawlable endpoint whose only purpose is to say nothing. That is the worst of both.\n\n" +
  "set_404() re-runs init_query_flags(), which clears is_feed() along with everything else, so the template loader stops routing to do_feed() and renders the theme's 404 instead. That is why is_comment_feed() has to be tested FIRST: a moment later there is nothing left to test.\n\n" +
  "And redirect_canonical has to go with it — this is the part worth remembering. It does not bail on a 404. It calls redirect_guess_404_permalink(), and against the query we have just emptied it answers /post-name/feed/ with a 301 to /post-name/feed/feed/. Leaving it hooked turns a clean 404 into a redirect to a URL that has never existed, which is worse than the bug we set out to fix.\n\n" +
  "EVERY FILTER-LEVEL TEST STILL PASSES. ONLY A REAL REQUEST CATCHES IT. That is the honest limit of the pattern this whole talk is built on: a filter behind a toggle is a claim about one hook, and what a visitor actually gets is the sum of all of them. The recursion trap in limit_unfiltered_html_to_admins is the same lesson from the other direction — a user_has_cap filter that asks a capability question calls itself, so it has to decide from $user->roles and the already-resolved $allcaps and never ask. Test the request, not just the hook.");

codeSlide(18, "CONTENT · 3 of 4",
  "Redirect author & attachment pages",
  "Author archives expose the authors' usernames in the URL, and attachment pages are near-empty media wrappers. Both dilute SEO and are targets for trouble. Same hook, two conditions.",
  "disable_author_archives / redirect_attachment_pages", "yes / yes",
  [
    { t: "add_action( 'template_redirect', function () {", k: "" },
    { t: "  if ( is_author() ) {", k: "h" },
    { t: "    wp_safe_redirect( home_url('/'), 301 );", k: "" },
    { t: "    exit;", k: "" },
    { t: "  }", k: "" },
    { t: "  if ( is_attachment() ) {", k: "h" },
    { t: "    // parent post, else the FILE - never home", k: "c" },
    { t: "  }", k: "" },
    { t: "} );", k: "" },
  ]).addNotes("Like the REST user routes, author archives expose the authors' usernames in the URL, and attachment pages are near-empty media wrappers. template_redirect fires before a template loads - the perfect place to bounce the unwanted requests. Same hook, two conditions.\n\nTwo details on the attachment half, because the obvious version is subtly wrong. Unattached media has no parent - and that is most of the Media Library - so a naive else-home_url() points every one of those at your homepage, which search engines read as a soft 404. Fall back to the FILE instead, which is what core does. And skip the redirect entirely when the theme ships attachment.php or image.php: that theme built those pages deliberately (the photography case), and quietly bouncing past it deletes someone's feature.\n\nCore moved here too: WordPress 6.4 added wp_attachment_pages_enabled, off for new installs. So this default is not adding the redirect so much as choosing a better destination than the bare file.");

codeSlide(19, "CONTENT · 4 of 4",
  "Disable the emoji script",
  "WordPress core injects an emoji-detection script and inline CSS on every page load, plus a DNS-prefetch hint. Modern browsers render emoji natively, so this is pure dead weight.",
  "disable_emojis", "yes",
  [
    { t: "add_action( 'init', function () {", k: "" },
    { t: "  remove_action( 'wp_head',", k: "h" },
    { t: "    'print_emoji_detection_script', 7 );", k: "h" },
    { t: "  remove_action( 'wp_print_styles',", k: "h" },
    { t: "    'print_emoji_styles' );", k: "h" },
    { t: "  // ...admin + feed + mail variants too", k: "c" },
    { t: "  add_filter( 'emoji_svg_url',", k: "" },
    { t: "              '__return_false' );", k: "" },
    { t: "} );", k: "" },
  ]).addNotes("WordPress core injects an emoji-detection script and inline CSS on every page load, plus a DNS-prefetch hint. Modern browsers render emoji natively, so this is pure dead weight. Small win, but it's on literally every page — a good example of a \"why is this even on?\" default that's not included in core settings.");

/* =================================================================== */
/* SECTION 3 — UX + LOGIN (combined divider)                           */
/* =================================================================== */
divider("3", "SECTION THREE", "Admin UX &\nLogin Sessions", "Small quality-of-life defaults: a calmer dashboard and sensible session policy.")
  .addNotes("Now the quality-of-life defaults. These are more about your daily user experience and session safety than raw hardening.");

codeSlide(21, "ADMIN UX",
  "Faster search, quieter admin bar",
  "Search the admin post list on a big site and WordPress reads every word of every post — like finding a book by reading the whole library. Title-only search checks just the spines, and it's far faster.",
  "title_only_admin_search / frontend_admin_bar_behavior", "no / ''",
  [
    { t: "// title-only admin search — narrow the COLUMNS", k: "c" },
    { t: "add_filter( 'post_search_columns',", k: "" },
    { t: "  function ( $cols, $s, $q ) {", k: "" },
    { t: "    if ( is_admin() && $q->is_main_query() )", k: "" },
    { t: "        return array( 'post_title' );", k: "h" },
    { t: "    return $cols;   // front-end untouched", k: "" },
    { t: "  }, 10, 3 );", k: "" },
    { t: "// hide bar for non-admins", k: "c" },
    { t: "add_filter( 'show_admin_bar', fn( $s ) =>", k: "" },
    { t: "  current_user_can('manage_options') ? $s : false );", k: "h" },
  ], 11).addNotes("Search the admin post list on a big site and WordPress reads every word of every post — like finding a book by reading the whole library. Title-only search checks just the spines, and it's far faster. The craft is in the *how*: post_search_columns (WP 6.2+) narrows the columns instead of rewriting the whole SQL clause, so core's term parsing and the logged-out password guard stay intact. Scope the filter; don't bulldoze the query.");

codeSlide(22, "LOGIN & SESSIONS",
  "Right-size the login session",
  "A normal login lasts 2 days; “Remember Me” extends it to 14. Both are in days, and the remembered one can never be shorter. Now look at the registration: this callback throws away the value it was handed, so two plugins setting it cannot both win — and at core's own 2 / 14 we have nothing to say, so we stay out of it.",
  "disable_remember_me / session_regular_days / remember_me_days", "no / 2 / 14",
  [
    { t: "function session_length( $exp, $uid, $remember ) {", k: "" },
    { t: "  return $remember", k: "" },
    { t: "    ? 14 * DAY_IN_SECONDS", k: "h" },
    { t: "    : 2 * DAY_IN_SECONDS;", k: "h" },
    { t: "}", k: "" },
    { t: "", k: "" },
    { t: "// only when we differ from core's 2 / 14,", k: "c" },
    { t: "// and late enough to be the last word", k: "c" },
    { t: "if ( policy_is_custom() )", k: "h" },
    { t: "  add_filter( 'auth_cookie_expiration',", k: "" },
    { t: "    'session_length', 50, 3 );", k: "h" },
  ]).addNotes("A normal login lasts 2 days; ticking \"Remember Me\" extends it to 14. Both lengths are in days, and the remembered one can never be shorter than the regular one. Shorten either, or hide the \"Remember Me\" checkbox entirely so every login uses the regular length. (Good idea for shared machines.) DAY_IN_SECONDS is one of core's time constants, so you never do the math. — Now the two lines that are not about session length, because they are the most portable thing in this deck. This callback ignores the $exp it was handed and returns its own number. A filter that ADDS TO its input composes: several plugins can each contribute a header to wp_headers and all of them survive. A filter that REPLACES its input does not. WordPress runs all of them and keeps whichever answered last, the others do nothing at all, and every losing plugin's settings screen goes on displaying a number the site is not using. No error, nothing logged, nothing on Site Health. Nothing in the API tells you which kind you are writing — both are add_filter(). The difference is entirely in what your callback does with the argument it was given, and it decides whether your plugin can coexist with another one or silently fights it. You cannot make a number filter additive. What you can do is decline the fights you have no stake in: our defaults ARE WordPress's own 2 and 14, so on a site that has not changed them we would be registering only to assert the answer core already gives. Hence the if. And the callback has a name rather than being anonymous, because $wp_filter can only report a callback that has one — an anonymous one shows up as 'closure' in exactly the diagnostic you would run to find out who won. Then there is the 50, which is the half everyone forgets. Declining a fight worth nothing only helps if you win the one worth something, and on a replacing filter the LAST callback to run is the one that counts. Register at the default 10 and you are not being polite, you are queueing up behind every plugin that never thought about priority at all — so a session length somebody set on purpose gets decided by load order. This plugin shipped at 10 in every release before 1.2.2 and lost to both its siblings on any site running two of them. Fifty is late enough to be the last word and low enough that a site wanting the final say can still take it deliberately. The question to ask of any filter callback you write: if a second plugin did exactly this, would both still work? If not, you are setting policy, and only one plugin on the site can win — so decide both whether to enter and when.");

/* =================================================================== */
/* SECTION 4 — BRANDING + PERFORMANCE (combined divider)               */
/* =================================================================== */
divider("4", "SECTION FOUR", "Branding, Performance\n& Email", "Brand the login screen, throttle one polling API, and end on the only default that changes nothing at all.")
  .addNotes("We brand the login screen, throttle one polling API, and end on the only default in the plugin that changes nothing at all.");

codeSlide(24, "BRANDING",
  "Own the login screen",
  "The default WordPress logo sends users to wordpress.org. Removing, unlinking, or replacing it keeps the login screen organizationally consistent and prevents an unexpected external destination. BBD leaves it unchanged unless you opt in.",
  "login_logo_behavior", "keep_default (keep / remove / unlink / replace)",
  [
    { t: "// remove, unlink, or replace — a choice", k: "c" },
    { t: "add_action( 'login_head', $logo_css );", k: "h" },
    { t: "", k: "" },
    { t: "// any change points the link home", k: "c" },
    { t: "// (no separate toggle)", k: "c" },
    { t: "add_filter( 'login_headerurl', 'home_url' );", k: "h" },
    { t: "add_filter( 'login_headertext', fn() =>", k: "" },
    { t: "            get_bloginfo( 'name' ) );", k: "h" },
  ], 12).addNotes("The login page is a WordPress site's staff entrance, and the default WordPress \"W\" on wp-login.php links to wordpress.org. Removing, unlinking, or replacing it keeps the login screen organizationally consistent and prevents the logo from sending users to an unexpected external site. Changing a site's login screen out of the box is intrusive, though, so the default is to LEAVE IT ALONE. Any opt-in change points the link home. Swap in a background-image to use the site's own logo.");

codeSlide(25, "PERFORMANCE · opt-in",
  "Throttle Heartbeat — and a default we deleted",
  "Throttle Heartbeat to ease up on weak shared hosting. The more interesting half is the toggle that used to be here: WordPress 6.3 gave scripts a per-script loading strategy, so our blanket defer filter had to go.",
  "throttle_heartbeat", "no (opt-in)",
  [
    { t: "add_filter( 'heartbeat_settings', function ( $s ) {", k: "" },
    { t: "  $s['interval'] = 60;", k: "h" },
    { t: "  return $s;", k: "" },
    { t: "} );", k: "" },
    { t: "", k: "" },
    { t: "// Deferring is NOT a setting here. Since WP 6.3:", k: "c" },
    { t: "wp_enqueue_script( 'front', $src, array(), '1.0',", k: "" },
    { t: "  array( 'strategy' => 'defer' ) );", k: "h" },
  ]).addNotes("The Heartbeat API polls admin-ajax every 15-60s. Throttle it to ease up on weak shared hosting.\n\nThe more interesting half of this slide is the toggle that USED to be here. We shipped a \"defer front-end scripts\" default that hooked script_loader_tag and string-replaced ' src=' with ' defer src=' on every handle. It had to skip jQuery core, and it still broke anything expecting a particular execution order — because a blanket filter cannot know which scripts are safe to defer.\n\nWordPress 6.3 added a per-script loading strategy, so core now answers this precisely, at the point of enqueue, where the person who wrote the script decides. Keeping our version would have meant teaching a workaround for a problem the platform already solved. Deleting a default is a legitimate result.");

codeSlide(26, "EMAIL",
  "Say so when the site cannot send mail",
  "WordPress sends from wordpress@yourdomain unless something changes it. On a domain that cannot send, password resets fail silently — wp_mail() returns false and nothing surfaces it. This warns, and that is all it does.",
  "mail_deliverability_notice", "yes",
  [
    { t: "// The From address the site will ACTUALLY use.", k: "c" },
    { t: "$from = apply_filters( 'wp_mail_from',", k: "" },
    { t: "          'wordpress@' . $host );", k: "h" },
    { t: "", k: "" },
    { t: "// A shape check, not a delivery test.", k: "c" },
    { t: "$risky = ! is_email( $from )", k: "" },
    { t: "  || in_array( $domain, $never_resolves )", k: "h" },
    { t: "  || preg_match( '/\\.(local|test|invalid)$/', $d );", k: "h" },
    { t: "", k: "" },
    { t: "// Warn. Never block, never rewrite.", k: "c" },
    { t: "add_action( 'admin_notices', $render_notice );", k: "" },
  ], 11).addNotes("WordPress sends mail from wordpress@yourdomain unless something changes it. On a domain that cannot actually send \u2014 a staging host, a .local address, a domain with no mail records \u2014 password resets and order receipts FAIL SILENTLY. wp_mail() returns false and nothing surfaces it. Nobody finds out until a customer says they never got the email.\n\nSo this default warns, and that is all it does. Be precise about how little it claims: it is a SHAPE CHECK, NOT A DELIVERY TEST. Proving mail works needs SPF and DMARC lookups and a real send \u2014 far more than a settings screen should do on page load. These are the cases knowable for free: not a valid address, example.com, localhost, or a reserved TLD that never resolves publicly (.local, .test, .invalid, .example \u2014 RFC 2606 and RFC 6762).\n\nTwo details make it honest. It reads the EFFECTIVE From address through wp_mail_from, so whatever your SMTP plugin actually sets is what gets judged, not the theoretical default it replaced. And it stays quiet when wp_get_environment_type() says local, because a local site is MEANT to have an undeliverable address \u2014 warning there is exactly how a notice trains people to dismiss notices. Staging is not local, and staging does warn, which is usually where this catches something real.\n\nThe lesson is the one thing here that is not about email. Every other default in this plugin changes what WordPress does. THIS ONE CHANGES NOTHING. It has no effect on a single request; it only tells an administrator something true that WordPress had been keeping to itself. Deciding not to intervene is a design decision as real as any filter, and often the better one \u2014 a plugin that silently rewrote your From address to something deliverable would be a considerably worse plugin. When you find a silent failure, the first question is not how do I fix this for them, but who needs to know, and would they rather I told them or guessed on their behalf?");

/* =================================================================== */
/* 23. wp-config things                                                */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("Three things a plugin can't toggle", {
    x: 0.6, y: 0.55, w: 12, h: 0.8, fontFace: HEAD, fontSize: 32, bold: true, color: INK, margin: 0,
  });
  s.addText("Some defaults live in wp-config.php, above the plugin layer, because they must load before plugins do. Document them as manual steps in your onboarding checklist.", {
    x: 0.6, y: 1.4, w: 11.8, h: 0.7, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });
  codePanel(s, 0.6, 2.35, 12.1, 2.1, [
    { t: "define( 'DISALLOW_FILE_EDIT', true );  // no in-dashboard code editor", k: "" },
    { t: "define( 'AUTOSAVE_INTERVAL', 120 );    // gentler autosave (seconds)", k: "" },
    { t: "define( 'WP_POST_REVISIONS', 10 );     // cap revision-table bloat", k: "" },
  ], 14);
  const notes = [
    { t: "Kills the theme/plugin editor", d: "A stolen admin login can't rewrite your PHP." },
    { t: "Writes to the DB less often", d: "Fewer autosave revisions during long edits." },
    { t: "Keeps revisions in check", d: "Ten per post instead of unbounded growth." },
  ];
  notes.forEach((c, i) => {
    const x = 0.6 + i * 4.07;
    s.addShape(p.ShapeType.roundRect, { x, y: 4.75, w: 3.85, h: 1.65, rectRadius: 0.09, fill: { color: WHITE }, line: { color: "DCE6EB", width: 1 }, shadow: { type: "outer", color: "C7D4DB", blur: 5, offset: 2, angle: 90, opacity: 0.5 } });
    dot(s, x + 0.28, 5.02, String(i + 1), STEEL, WHITE, 0.5);
    s.addText(c.t, { x: x + 0.95, y: 4.95, w: 2.75, h: 0.65, fontFace: HEAD, fontSize: 15, bold: true, color: INK, margin: 0, valign: "middle" });
    s.addText(c.d, { x: x + 0.28, y: 5.62, w: 3.35, h: 0.7, fontFace: BODY, fontSize: 12.5, color: SLATE, margin: 0, valign: "top" });
  });
  footer(s, 24);
  s.addNotes("Some defaults live in wp-config.php, above the plugin layer, because they must load before plugins do. They can't be options — so document them as manual steps in your onboarding checklist and put them in your standard wp-config template.");
})();

/* =================================================================== */
/* 25. THE PLUGIN ARCHITECTURE                                         */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: INK };
  s.addText("How the plugin is built", {
    x: 0.7, y: 0.6, w: 11, h: 0.8, fontFace: HEAD, fontSize: 32, bold: true, color: WHITE, margin: 0,
  });
  s.addText("One array is the whole map. Read it and you understand the plugin.", {
    x: 0.7, y: 1.45, w: 11.5, h: 0.6, fontFace: BODY, fontSize: 16, color: SKY, italic: true, margin: 0,
  });
  const steps = [
    { g: "1", t: "schema()", d: "One array: every setting, its default, type & group. The single source of truth." },
    { g: "2", t: "settings page", d: "Loops the schema to render toggles under Settings → Better by Default." },
    { g: "3", t: "bootstrap()", d: "For each ENABLED key, wires its add_filter / add_action to the right hook." },
  ];
  steps.forEach((c, i) => {
    const x = 0.7 + i * 4.1;
    s.addShape(p.ShapeType.roundRect, { x, y: 2.35, w: 3.75, h: 2.55, rectRadius: 0.1, fill: { color: INK2 }, line: { color: STEEL, width: 1 } });
    dot(s, x + 0.3, 2.62, c.g, WHEAT, INK, 0.62);
    s.addText(c.t, { x: x + 1.1, y: 2.68, w: 2.5, h: 0.55, fontFace: MONO, fontSize: 16, bold: true, color: WHEAT, margin: 0, valign: "middle" });
    s.addText(c.d, { x: x + 0.32, y: 3.45, w: 3.1, h: 1.3, fontFace: BODY, fontSize: 14, color: SKY, margin: 0, valign: "top", lineSpacingMultiple: 1.1 });
    if (i < 2) s.addText("➜", { x: x + 3.72, y: 2.35, w: 0.5, h: 2.55, fontFace: BODY, fontSize: 24, color: WHEAT, align: "center", valign: "middle", margin: 0 });
  });
  codePanel(s, 0.7, 5.15, 11.95, 1.35, [
    { t: "$stored = get_option('wpyeg_better_by_default');   // read once", k: "c" },
    { t: "foreach ( wpyeg_defaults_schema() as $key => $field ) { /* render + wire */ }", k: "" },
  ], 12.5);
  s.addNotes("The design lesson is a data-driven plugin. Adding a new default equals one array entry plus one if-block in bootstrap — no new settings-page code. That's the pattern to steal for your own projects.");
})();

/* =================================================================== */
/* 26. LIVE DEMO / HANDS-ON                                            */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  dot(s, 0.6, 0.55, "▶", WHEAT, INK, 0.7);
  s.addText("Hands-on: install & flip switches", {
    x: 1.45, y: 0.55, w: 11, h: 0.75, fontFace: HEAD, fontSize: 30, bold: true, color: INK, margin: 0, valign: "middle",
  });
  const steps = [
    { n: "1", t: "Upload the plugin", d: "Plugins → Add New → Upload Plugin → choose sane-defaults.zip → Activate." },
    { n: "2", t: "Open the settings", d: "Settings → Better by Default; every toggle grouped by category." },
    { n: "3", t: "Verify a default", d: "Visit /wp-json/wp/v2/users logged out → 401 or empty, not a list of usernames." },
    { n: "4", t: "Toggle & re-check", d: "Flip a switch off, reload, watch the behaviour change." },
  ];
  steps.forEach((c, i) => {
    const y = 1.5 + i * 1.14;
    s.addShape(p.ShapeType.roundRect, { x: 0.6, y, w: 12.1, h: 1.0, rectRadius: 0.08, fill: { color: WHITE }, line: { color: "DCE6EB", width: 1 }, shadow: { type: "outer", color: "C7D4DB", blur: 4, offset: 2, angle: 90, opacity: 0.5 } });
    dot(s, 0.9, y + 0.25, c.n, STEEL, WHITE, 0.5);
    s.addText(c.t, { x: 1.6, y: y + 0.08, w: 3.6, h: 0.85, fontFace: HEAD, fontSize: 17, bold: true, color: INK, margin: 0, valign: "middle" });
    s.addText(c.d, { x: 5.2, y: y + 0.08, w: 7.3, h: 0.85, fontFace: BODY, fontSize: 14, color: SLATE, margin: 0, valign: "middle" });
  });
  // WP-CLI one-liner callout
  s.addShape(p.ShapeType.roundRect, { x: 0.6, y: 6.1, w: 12.1, h: 0.72, rectRadius: 0.07, fill: { color: CODEBG }, line: { color: STEEL, width: 1 } });
  s.addText([
    { text: "prefer the terminal?   ", options: { color: WHEAT, bold: true } },
    { text: "wp plugin install ./sane-defaults.zip --activate", options: { color: CGOLD } },
  ], { x: 0.85, y: 6.1, w: 11.6, h: 0.72, fontFace: MONO, fontSize: 13, valign: "middle", margin: 0 });
  footer(s, 26);
  s.addNotes("Do this live if there's a sandbox. The /wp-json/wp/v2/users check is the crowd-pleaser — before/after is instantly visible. For the terminal crowd, the WP-CLI one-liner installs and activates from the zip in one shot; swap the local path for a URL if the zip is hosted.");
})();

/* =================================================================== */
/* 27. EXERCISE                                                        */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: STEEL };
  s.addText("Your turn", { x: 0.8, y: 0.9, w: 11, h: 0.7, fontFace: BODY, fontSize: 18, color: WHEAT, bold: true, margin: 0 });
  s.addText("Add one new default to the plugin", {
    x: 0.8, y: 1.5, w: 11.6, h: 1.0, fontFace: HEAD, fontSize: 36, bold: true, color: WHITE, margin: 0,
  });
  s.addText("Goal: disable the WordPress dashboard “Welcome” panel. Two small edits — no new settings-page code.", {
    x: 0.8, y: 2.7, w: 11.5, h: 0.7, fontFace: BODY, fontSize: 17, color: SKY, italic: true, margin: 0, valign: "top",
  });
  codePanel(s, 0.8, 3.55, 11.7, 2.7, [
    { t: "// 1) add a schema entry in wpyeg_defaults_schema()", k: "c" },
    { t: "'hide_welcome_panel' => array(", k: "" },
    { t: "    'default' => 'yes', 'type' => 'toggle', 'group' => 'ux',", k: "h" },
    { t: "    'label' => 'Hide dashboard welcome panel',", k: "" },
    { t: "),", k: "" },
    { t: "", k: "" },
    { t: "// 2) wire it inside wpyeg_defaults_bootstrap()", k: "c" },
    { t: "if ( wpyeg_defaults_enabled( 'hide_welcome_panel' ) ) {", k: "" },
    { t: "    remove_action( 'welcome_panel', 'wp_welcome_panel' );", k: "h" },
    { t: "}", k: "" },
  ], 13);
  s.addNotes("A great confidence-builder: it proves the data-driven pattern. Touch two spots and a real feature toggles. If time is short, walk it through verbally instead of live.");
})();

/* ---------- CANONICAL SCHEMA MAP helper ---------- */
function schemaMapSlide(num, title, subtitle, rows, notes) {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText(title, {
    x: 0.6, y: 0.45, w: 12, h: 0.7, fontFace: HEAD, fontSize: 30, bold: true, color: INK, margin: 0,
  });
  s.addText(subtitle, {
    x: 0.6, y: 1.2, w: 12, h: 0.4, fontFace: BODY, fontSize: 14, color: STEEL2, bold: true, margin: 0,
  });
  const tblRows = [[
    { text: "Setting key or owner", options: { bold: true, color: WHITE, fill: { color: STEEL }, fontFace: BODY, fontSize: 13, align: "left", margin: 4 } },
    { text: "Default", options: { bold: true, color: WHITE, fill: { color: STEEL }, fontFace: MONO, fontSize: 12, align: "left", margin: 4 } },
    { text: "Core hook / authority", options: { bold: true, color: WHITE, fill: { color: STEEL }, fontFace: BODY, fontSize: 13, align: "left", margin: 4 } },
  ]];
  rows.forEach((r, i) => {
    const bg = i % 2 ? "EAF1F5" : WHITE;
    tblRows.push([
      { text: r[0], options: { color: INK, fill: { color: bg }, fontFace: BODY, fontSize: 12.5, align: "left", margin: 4 } },
      { text: r[1], options: { color: STEEL, fill: { color: bg }, fontFace: MONO, fontSize: 11, align: "left", margin: 4 } },
      { text: r[2], options: { color: SLATE, fill: { color: bg }, fontFace: BODY, fontSize: 12, align: "left", margin: 4 } },
    ]);
  });
  s.addTable(tblRows, {
    x: 0.6, y: 1.65, w: 12.1, colW: [4.75, 1.75, 5.6],
    border: { type: "solid", color: "DCE6EB", pt: 1 }, valign: "middle", rowH: 0.44,
  });
  footer(s, num);
  s.addNotes(notes);
}

/* =================================================================== */
/* 28–31. CANONICAL SCHEMA MAP                                         */
/* =================================================================== */
schemaMapSlide(28, "Schema map — security surfaces and credentials",
  "Exact keys and defaults from wpyeg_defaults_schema().",
  [
    ["restrict_rest_user_discovery", "yes", "rest_endpoints"],
    ["disable_rest", "no", "rest_authentication_errors"],
    ["xmlrpc_allow_pingbacks", "no", "xmlrpc_methods / headers"],
    ["xmlrpc_allow_remote_publishing", "no", "xmlrpc_methods / discovery"],
    ["xmlrpc_allow_multicall", "no", "wp_xmlrpc_server_class"],
    ["block_xmlrpc_endpoint", "no", "template_redirect"],
    ["disable_application_passwords", "no", "wp_is_application_passwords_available"],
    ["require_strong_passwords", "yes", "server-side password validation"],
  ],
  "These are the exact unprefixed keys stored inside the single wpyeg_better_by_default option. An allow-setting at no can still mean a protective behaviour is active: the three XML-RPC categories are unavailable by default, while the all-or-nothing endpoint block remains opt-in. Application Passwords remain available; strong-password validation is active."
);

schemaMapSlide(29, "Schema map — security policy and updates",
  "Plugin defaults and the policies intentionally left to other layers.",
  [
    ["remove_version", "no", "wp_head / the_generator"],
    ["security_headers", "yes", "wp_headers"],
    ["frame_options", "SAMEORIGIN", "wp_headers"],
    ["disable_ai_connectors", "yes", "wp_supports_ai / Connectors screen"],
    ["core_update_policy", "minor", "automatic core-update filters"],
    ["Translation files", "inherit", "WordPress / host / fleet tooling"],
    ["Plugin and theme code", "per-item", "WordPress per-item choices"],
    ["WP_AUTO_UPDATE_CORE", "operator", "wp-config.php wins"],
  ],
  "AI connectors are disabled through the WordPress 7.0 core gate and the Connectors screen is closed. Baseline headers and SAMEORIGIN ship separately because framing can break legitimate embeds. BBD governs core release classes unless a constant wins, while language files and plugin/theme code remain with WordPress, the host, or fleet tooling."
);

schemaMapSlide(30, "Schema map — content and everyday UX",
  "The schema group is authoritative; emoji removal is a Content setting.",
  [
    ["disable_comments", "yes", "comments, UI, post-type support"],
    ["disable_pingbacks", "yes", "default ping options"],
    ["disable_self_pingbacks", "yes", "pre_ping"],
    ["disable_author_archives", "yes", "template_redirect"],
    ["redirect_attachment_pages", "yes", "template_redirect"],
    ["disable_emojis", "yes", "init removes emoji assets"],
    ["limit_unfiltered_html_to_admins", "yes", "user_has_cap drops the cap for non-admins"],
    ["disable_post_passwords", "no", "CSS hides the editor's password option"],
    ["force_classic_editor", "no", "four editor gates answered false"],
    ["lowercase_upload_filenames", "yes", "sanitize_file_name at priority 20"],
    ["media_sizes_panel", "yes", "read-only meta box on attachments"],
    ["title_only_admin_search", "no", "post_search_columns"],
    ["frontend_admin_bar_behavior", "''", "show_admin_bar"],
  ],
  "The three comment and pingback settings are separate because a site may keep comments while closing new-post pings and suppressing self-pingbacks. Title-only search and front-end admin-bar changes remain opt-in."
);

schemaMapSlide(31, "Schema map — login, branding, and performance",
  "Schema keys live in one option array; constants remain above plugins.",
  [
    ["disable_remember_me", "no", "login UI / cookie expiration"],
    ["session_regular_days", "2", "auth_cookie_expiration"],
    ["remember_me_days", "14", "auth_cookie_expiration"],
    ["login_logo_behavior", "keep_default", "login header presentation"],
    ["mail_deliverability_notice", "yes", "admin_notices when the From address looks undeliverable"],
    ["throttle_heartbeat", "no", "Heartbeat settings / enqueue"],
    ["wpyeg_better_by_default", "array", "the only wp_options row"],
    ["DISALLOW_FILE_EDIT", "manual", "wp-config.php"],
    ["revisions / autosave", "manual", "wp-config.php constants"],
  ],
  "The visible names on earlier slides are schema keys, not separate WordPress options. A regular login lasts 2 days and a remembered one 14 - WordPress's own values, prefilled rather than sentinels, with a one-day floor on each and the remembered length clamped so it can never be shorter than the regular one. The login logo and Heartbeat remain opt-in, and the three configuration constants stay above the plugin layer."
);

/* =================================================================== */
/* 32. CLOSING                                                         */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: INK };
  s.addShape(p.ShapeType.rect, { x: 0, y: 5.7, w: 13.33, h: 1.8, fill: { color: INK2 } });
  dot(s, 0.85, 0.85, "{ }", WHEAT, INK, 0.7);
  s.addText("Thanks, WPYEG!", {
    x: 0.85, y: 1.9, w: 11.6, h: 1.2, fontFace: HEAD, fontSize: 50, bold: true, color: WHITE, margin: 0,
  });
  s.addText("Set your defaults wisely.", {
    x: 0.9, y: 3.2, w: 11, h: 0.9, fontFace: BODY, fontSize: 19, color: SKY, italic: true, margin: 0, valign: "top",
  });
  s.addText([
    { text: "Files:  ", options: { color: WHEAT, bold: true } },
    { text: "sane-defaults.zip", options: { color: SKY } },
    { text: "   ·   ", options: { color: MUTE } },
    { text: "wordpress-default-settings.md", options: { color: SKY } },
  ], { x: 0.9, y: 4.4, w: 11.5, h: 0.5, fontFace: MONO, fontSize: 14, margin: 0 });
  s.addText([
    { text: "Questions?  ", options: { color: WHITE, bold: true } },
    { text: "License GPL-3.0-or-later", options: { color: SKY } },
  ], { x: 0.9, y: 6.15, w: 11.5, h: 0.6, fontFace: BODY, fontSize: 16, margin: 0, valign: "middle" });
  s.addNotes("Hand out the zip and the reference doc. Invite everyone to add their own favourite default to the schema and share it back with the group.");
})();

p.writeFile({ fileName: "Better-by-Default.pptx" }).then((f) => console.log("WROTE", f));
