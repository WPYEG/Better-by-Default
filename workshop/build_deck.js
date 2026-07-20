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
    "Welcome to WPYEG. In this workshop we're building and reviewing a small plugin that defines and activates a dozen sensible defaults for WordPress sites in 2026. Whether you write PHP daily or just manage WordPress sites, you'll leave knowing why each default matters and how to enable (or disable) it."
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
  s.addText("None of these are bugs. They are defaults chosen for maximum compatibility on a 20+ year-old web application — you probably don't need them.", {
    x: 0.6, y: 1.5, w: 11.8, h: 0.9, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });

  const cards = [
    { g: "!", c: CORAL, t: "Usernames leak", d: "REST + author archives list every login name to anonymous visitors." },
    { g: "!", c: CORAL, t: "Legacy XML-RPC wide open", d: "Pingback and system.multicall amplifiers answer by default." },
    { g: "~", c: WHEATD, t: "Dead weight loads", d: "Emoji scripts, version tags, and RSD links on every page." },
    { g: "~", c: WHEATD, t: "Spam surface invites", d: "Comments, pingbacks, and trackbacks open by default." },
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
  s.addNotes("None of these are bugs. They are defaults chosen for maximum compatibility on a 20+ year-old web application. You probably don't need them and can tighten up your own WordPress sites. This is also a good way to learn some important fundamentals about how WordPress works and how to keep it secure, fast, and pretty.");
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
    { t: "}   // that's the whole pattern, repeated ~20 times", k: "c" },
  ], 15);
  s.addNotes("In our demo plugin, a default is an add_filter behind an if ( option ). We have twenty of them.");
})();

/* =================================================================== */
/* 4. PRIMER: hooks & filters (mixed audience)                         */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("Hooks & filters", {
    x: 0.6, y: 0.55, w: 12, h: 0.8, fontFace: HEAD, fontSize: 32, bold: true, color: INK, margin: 0,
  });
  s.addText("WordPress is built to be interrupted at labeled moments (hooks) so you never edit core code.", {
    x: 0.6, y: 1.4, w: 11.8, h: 0.7, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });

  const rows = [
    { g: "A", t: "Action", d: "“When you reach this moment, also DO this.”", ex: "add_action( 'init', fn );" },
    { g: "F", t: "Filter", d: "“Before you use this value, let me CHANGE it first.”", ex: "add_filter( 'xmlrpc_enabled', '__return_false' );" },
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
  s.addNotes("WordPress is built to be interrupted at labeled moments (hooks) so you never edit core code. __return_false is a tiny built-in helper that just hands back false — perfect for switching a feature off.");
})();

/* =================================================================== */
/* 5. ROADMAP                                                          */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("Six categories of defaults", {
    x: 0.6, y: 0.55, w: 12, h: 0.8, fontFace: HEAD, fontSize: 34, bold: true, color: INK, margin: 0,
  });
  const cats = [
    { g: "1", t: "Security", d: "Shrink the attack surface" },
    { g: "2", t: "Content", d: "Close spam channels & info leaks" },
    { g: "3", t: "Admin UX", d: "A calmer, faster dashboard" },
    { g: "4", t: "Login", d: "Sessions & credentials" },
    { g: "5", t: "Branding", d: "Own the login screen" },
    { g: "6", t: "Performance", d: "Trim the page weight" },
  ];
  const cw = 3.83, ch = 2.15, gx = 0.6, gy = 1.7, gapx = 0.5, gapy = 0.5;
  cats.forEach((c, i) => {
    const col = i % 3, row = Math.floor(i / 3);
    const x = gx + col * (cw + gapx), y = gy + row * (ch + gapy);
    s.addShape(p.ShapeType.roundRect, { x, y, w: cw, h: ch, rectRadius: 0.1, fill: { color: WHITE }, line: { color: "DCE6EB", width: 1 }, shadow: { type: "outer", color: "C7D4DB", blur: 5, offset: 2, angle: 90, opacity: 0.5 } });
    dot(s, x + 0.35, y + 0.35, c.g, WHEAT, INK, 0.7);
    s.addText(c.t, { x: x + 0.35, y: y + 1.15, w: cw - 0.7, h: 0.5, fontFace: HEAD, fontSize: 21, bold: true, color: INK, margin: 0 });
    s.addText(c.d, { x: x + 0.35, y: y + 1.6, w: cw - 0.7, h: 0.45, fontFace: BODY, fontSize: 13.5, color: SLATE, margin: 0 });
  });
  footer(s, 5);
  s.addNotes("We'll spend most of our time on security and content, then move quickly through UX, login, branding, and performance, and end up with a plugin that covers them all.");
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

  // option chip
  s.addShape(p.ShapeType.roundRect, { x: 0.6, y: 5.55, w: 5.35, h: 1.05, rectRadius: 0.08, fill: { color: "EAF1F5" }, line: { color: STEEL2, width: 1 } });
  s.addText([
    { text: "OPTION   ", options: { color: STEEL2, bold: true } },
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

codeSlide(7, "SECURITY · 1 of 6",
  "Restrict REST API user discovery",
  "The /wp/v2/users endpoint hands out every author's login name to anyone — half of a brute-force guess, for free. Closing it for logged-out requests only keeps the editor and legit integrations working.",
  "wpyeg_restrict_rest_user_discovery", "yes",
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
  ]).addNotes("The /wp/v2/users endpoint hands out every author's login name to anyone — half of a brute-force guess, for free. Author enumeration is step one of many attack scripts. By closing it for logged-out requests only, the editor and legit integrations will keep working. It's arguably an example of security-by-obscurity, but it also prevents a lot of junk traffic and bots that are up to no good.");

codeSlide(8, "SECURITY · 2 of 6 · opt-in",
  "Lock REST to logged-in users (opt-in)",
  "The sledgehammer version of the slide before: requiring auth for ALL REST calls stops anonymous scraping cold. It breaks anonymous REST — front-end blocks, embeds, search, outside integrations — so it ships off.",
  "wpyeg_disable_rest", "no",
  [
    { t: "add_filter( 'rest_authentication_errors',", k: "" },
    { t: "  function ( $result ) {", k: "" },
    { t: "    if ( ! empty( $result ) ) return $result;", k: "" },
    { t: "    if ( ! is_user_logged_in() ) {", k: "" },
    { t: "      return new WP_Error(", k: "h" },
    { t: "        'rest_not_logged_in', 'Auth required.',", k: "h" },
    { t: "        array( 'status' => 401 ) );", k: "h" },
    { t: "    }", k: "" },
    { t: "    return $result;", k: "" },
    { t: "} );", k: "" },
  ]).addNotes("The sledgehammer version of the slide before. Requiring auth for ALL REST calls stops anonymous scraping cold. It does *not* break the block editor, though — you're logged in there, and the editor authenticates with your cookie plus a REST nonce, so it sails through this filter. What it breaks is ANONYMOUS REST: front-end blocks that fetch data for logged-out visitors, embeds, search, and outside integrations. That's why it ships off. Not every default should default to on; some are opt-in because they trade functionality for safety. Usually it's a better tradeoff to restrict a few REST routes — like the users endpoint we just closed — than to lock ALL of them.");

codeSlide(9, "SECURITY · 3 of 6",
  "Lock XML-RPC down by category",
  "XML-RPC isn't one door — it's an old switchboard, and every method is a phone line. Rather than rip the box off the wall, we unplug lines by category. Four switches, all off by default.",
  "wpyeg_xmlrpc_allow_* + _block_xmlrpc_endpoint", "no (all four)",
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
  "XML-RPC isn't one door — it's an old switchboard, and every method is a phone line. Rather than rip the box off the wall, we unplug lines by category. Four switches, all off by default:\n\n" +
  "1. Pingbacks — drop pingback.ping, a spam and reflection-DDoS relay.\n" +
  "2. Remote publishing — drop the credential-authenticated blogging methods (wp.*, metaWeblog.*, mt.*, blogger.*), the classic brute-force target. This also flips xmlrpc_enabled off and removes the RSD discovery link.\n" +
  "3. system.multicall — the amplifier that batches thousands of login guesses into one request. You can't just unset it: IXR_Server::setCallbacks() re-adds it after the filter runs, so we swap in a replacement server that refuses it.\n" +
  "4. Block the endpoint — the blunt hammer: xmlrpc.php returns 403 for everything.\n\n" +
  "The first three are surgical — they leave third-party methods like Jetpack's jetpack.* connected, so the endpoint stays usable. The fourth nukes the lot, which breaks Jetpack; reach for it only if nothing on the site speaks XML-RPC.\n\n" +
  "[Aside — what's \"IXR\"? The Incutio XML-RPC library. Simon Willison released it in September 2002, one of his first open-source projects, while blogging from the University of Bath; both WordPress *and* Drupal adopted it, and it then sat largely untouched for 15+ years — long enough to pick up a CVE. Willison went on to co-create Django (2003–05 at the Lawrence Journal-World), build Lanyrd (sold to Eventbrite in 2013) and Datasette (2017), and is now one of the most-read writers on LLMs.]"
);

codeSlide(10, "SECURITY · 4 of 6",
  "Keep Application Passwords available",
  "This is an existing default we don't lock down. An Application Password is like a spare key cut for one app: hashed, per-application, revocable on its own — the safer REST credential, and the only one core accepts for REST Basic Auth.",
  "wpyeg_disable_application_passwords", "no (available)",
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

codeSlide(11, "SECURITY · 5 of 6",
  "Require strong passwords",
  "NIST 800-63B and OWASP now say length plus breach screening beats composition rules. So we require 15+ characters and screen every new password against Have I Been Pwned — all enforced server-side.",
  "wpyeg_require_strong_passwords", "yes",
  [
    { t: "// hooked on user_profile_update_errors", k: "c" },
    { t: "if ( strlen( $pw ) < 15 ) {", k: "h" },
    { t: "    $errors->add( 'short', 'Use 15+ characters.' );", k: "" },
    { t: "}", k: "" },
    { t: "", k: "" },
    { t: "// screen against known breaches (HIBP) —", k: "c" },
    { t: "// length + screening, not composition rules", k: "c" },
    { t: "if ( wpyeg_password_is_pwned( $pw ) ) {", k: "h" },
    { t: "    $errors->add( 'pwned', 'Seen in a breach.' );", k: "" },
    { t: "}", k: "" },
  ]).addNotes("The rules changed recently, and most people didn't notice: NIST 800-63B and OWASP now say LENGTH PLUS BREACH SCREENING BEATS COMPOSITION RULES. Forcing upper/lower/number/symbol just herds everyone to Password1! — predictable, not strong. So we require 15+ characters and screen every new password against Have I Been Pwned — by k-anonymity, meaning only the first five characters of the SHA-1 hash ever leave the site; HIBP returns every hash sharing that prefix and the match happens locally, so the password itself is never transmitted. If HIBP is unreachable the check FAILS OPEN rather than locking someone out of a password change. All enforced server-side on save and reset: the JS meter is UX; the server rule is the wall.");

codeSlide(12, "SECURITY · 6 of 6",
  "Remove fingerprints, add headers",
  "One default and one deliberate non-default. Hiding the version is obscurity, not hardening — it buys quieter logs, not a safer site — so it ships off. The three headers are real, low-risk defaults most sites can adopt without breaking anything.",
  "wpyeg_remove_version (no) / wpyeg_security_headers (yes)", "opt-in / on",
  [
    { t: "remove_action( 'wp_head', 'wp_generator' );", k: "h" },
    { t: "", k: "" },
    { t: "add_filter( 'wp_headers', function ( $h ) {", k: "" },
    { t: "  $h['X-Content-Type-Options'] = 'nosniff';", k: "h" },
    { t: "  $h['X-Frame-Options']        = 'SAMEORIGIN';", k: "h" },
    { t: "  $h['Referrer-Policy'] =", k: "h" },
    { t: "        'strict-origin-when-cross-origin';", k: "h" },
    { t: "  return $h;", k: "" },
    { t: "} );", k: "" },
  ]).addNotes(
  "One default and one deliberate non-default — and the difference is the lesson. Hiding the version is obscurity, not hardening: it does not make an out-of-date site any safer, and it does not even hide much, since the version still leaks from asset query strings and feeds. What it genuinely buys is quieter logs. That is worth opting into, not worth shipping on and calling security — so it defaults to off. The headers are the opposite: real, low-risk defaults most sites can adopt without breaking anything:\n\n" +
  "- X-Content-Type-Options: nosniff — the browser must trust the declared Content-Type instead of guessing; kills \"a .txt the browser decides to run as JavaScript\" tricks.\n" +
  "- X-Frame-Options: SAMEORIGIN — only your own site may load the page in an iframe; blocks clickjacking, where your login or admin is hidden under an attacker's page.\n" +
  "- Referrer-Policy: strict-origin-when-cross-origin — sends the full URL within your own site, only the bare domain to other sites, and nothing on an HTTPS→HTTP downgrade; keeps tokens and private paths from leaking in the Referer.\n\n" +
  "A full Content-Security-Policy is a bigger conversation for another time!"
);

/* =================================================================== */
/* SECTION 2 — CONTENT                                                 */
/* =================================================================== */
divider("2", "SECTION TWO", "Content &\nPublic Surfaces", "Close the spam funnels and the thin pages Google (and bots) love to crawl.")
  .addNotes("These reduce channels for spam and clean up the thin, duplicate URLs that bots and search engines get lost in.");

codeSlide(14, "CONTENT · 1 of 3",
  "Disable comments, trackbacks & pingbacks",
  "For many sites, comments are a spam magnet with little upside. Here we close them everywhere, hide existing threads, and drop the admin menu.",
  "wpyeg_disable_comments", "yes",
  [
    { t: "add_filter( 'comments_open', '__return_false', 20 );", k: "h" },
    { t: "add_filter( 'pings_open',    '__return_false', 20 );", k: "h" },
    { t: "add_filter( 'comments_array',", k: "" },
    { t: "            '__return_empty_array', 20 );", k: "" },
    { t: "", k: "" },
    { t: "// + remove_post_type_support() on init", k: "c" },
    { t: "// + remove_menu_page('edit-comments.php')", k: "c" },
    { t: "// + drop the admin-bar comments node", k: "c" },
  ]).addNotes("For many sites, comments are a spam magnet with little upside. Here we close them everywhere, hide existing threads, and drop the admin menu. If you want comments, leave this tuned off — but consider closing pingbacks and trackbacks, which are almost pure spam.");

codeSlide(15, "CONTENT · 2 of 3",
  "Redirect author & attachment pages",
  "Author archives expose the authors' usernames in the URL, and attachment pages are near-empty media wrappers. Both dilute SEO and are targets for trouble. Same hook, two conditions.",
  "wpyeg_disable_author_archives / _redirect_attachment_pages", "yes / yes",
  [
    { t: "add_action( 'template_redirect', function () {", k: "" },
    { t: "  if ( is_author() ) {", k: "h" },
    { t: "    wp_safe_redirect( home_url('/'), 301 );", k: "" },
    { t: "    exit;", k: "" },
    { t: "  }", k: "" },
    { t: "  if ( is_attachment() ) {", k: "h" },
    { t: "    // 301 to parent post, or home", k: "c" },
    { t: "  }", k: "" },
    { t: "} );", k: "" },
  ]).addNotes("Like the REST user routes, author archives expose the authors' usernames in the URL, and attachment pages are near-empty media wrappers. Both dilute SEO and are targets for trouble. template_redirect fires before a template loads — the perfect place to bounce the unwanted requests. Same hook, two conditions.");

codeSlide(16, "CONTENT · 3 of 3",
  "Disable the emoji script",
  "WordPress core injects an emoji-detection script and inline CSS on every page load, plus a DNS-prefetch hint. Modern browsers render emoji natively, so this is pure dead weight.",
  "wpyeg_disable_emojis", "yes",
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

codeSlide(18, "ADMIN UX",
  "Faster search, quieter admin bar",
  "Search the admin post list on a big site and WordPress reads every word of every post — like finding a book by reading the whole library. Title-only search checks just the spines, and it's far faster.",
  "wpyeg_title_only_admin_search / _frontend_admin_bar_behavior", "no / ''",
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

codeSlide(19, "LOGIN & SESSIONS",
  "Right-size the login session",
  "Click “Remember Me” and you stay logged in for 14 days. Cap that extended session, optionally shorten the regular one, or hide the checkbox entirely. One filter covers all three.",
  "wpyeg_remember_me_days / _session_regular_hours", "5 / 0",
  [
    { t: "add_filter( 'auth_cookie_expiration',", k: "" },
    { t: "  function ( $exp, $uid, $remember ) {", k: "" },
    { t: "    if ( $remember ) {", k: "" },
    { t: "      return 5 * DAY_IN_SECONDS;", k: "h" },
    { t: "    }", k: "" },
    { t: "    return 12 * HOUR_IN_SECONDS;", k: "h" },
    { t: "  }, 10, 3 );", k: "" },
    { t: "", k: "" },
    { t: "// DAY_IN_SECONDS: core time constant", k: "c" },
  ]).addNotes("If you click the \"Remember Me\" checkbox when you log in, you stay logged in for 14 days. Cap that extended session length, optionally shorten the regular session, or hide the \"Remember Me\" checkbox entirely. (Good idea for shared machines.) One filter covers all three. WordPress ships handy time constants like DAY_IN_SECONDS, so you never need to do the math.");

/* =================================================================== */
/* SECTION 4 — BRANDING + PERFORMANCE (combined divider)               */
/* =================================================================== */
divider("4", "SECTION FOUR", "Branding &\nPerformance", "Own the login screen, then shave the last bit of weight off every page.")
  .addNotes("The last pair brands the login screen. Then we end with two performance levers to shave some weight off every page.");

codeSlide(21, "BRANDING",
  "Own the login screen",
  "The login page is a site's staff entrance, and by default the welcome mat links to someone else's house — that “W” points out to wordpress.org. Changing it uninvited is intrusive, so the default is to leave it alone.",
  "wpyeg_login_logo_behavior", "keep_default (keep / remove / unlink / replace)",
  [
    { t: "// remove, unlink, or replace — a choice", k: "c" },
    { t: "add_action( 'login_head', $logo_css );", k: "h" },
    { t: "", k: "" },
    { t: "// any change points the link home", k: "c" },
    { t: "// (no separate toggle)", k: "c" },
    { t: "add_filter( 'login_headerurl', 'home_url' );", k: "h" },
    { t: "add_filter( 'login_headertext', fn() =>", k: "" },
    { t: "            get_bloginfo( 'name' ) );", k: "h" },
  ], 12).addNotes("The login page is a WordPress site's staff entrance — the one door you and your clients actually log in through — and by default the welcome mat links to someone else's house! That little WordPress \"W\" on wp-login.php points out to wordpress.org. Changing a site's login screen out of the box is intrusive, though, so the default is to LEAVE IT ALONE. Removing, unlinking, or replacing the logo is an opt-in — and whichever you choose, the link always points home. Swap in a background-image to drop in the site's own logo.");

codeSlide(22, "PERFORMANCE · opt-in",
  "Throttle Heartbeat, defer scripts (opt-in)",
  "The Heartbeat API polls admin-ajax every 15–60s; throttle it to ease up on weak shared hosting. Deferring non-critical scripts keeps them from being render-blocking.",
  "wpyeg_throttle_heartbeat / wpyeg_defer_scripts", "no / no",
  [
    { t: "add_filter( 'heartbeat_settings', fn( $s ) => {", k: "" },
    { t: "  $s['interval'] = 60; return $s;", k: "h" },
    { t: "} );", k: "" },
    { t: "", k: "" },
    { t: "add_filter( 'script_loader_tag',", k: "" },
    { t: "  function ( $tag, $handle ) {", k: "" },
    { t: "    // add ' defer' (skip jquery-core)", k: "c" },
    { t: "    return $tag;", k: "" },
    { t: "}, 10, 2 );", k: "" },
  ]).addNotes("The Heartbeat API polls admin-ajax every 15–60s. Throttle it to ease up on weak shared hosting. Deferring non-critical scripts keeps them from being render-blocking, but this can also break plugins expecting synchronous jQuery.");

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
  footer(s, 23);
  s.addNotes("Some defaults live in wp-config.php, above the plugin layer, because they must load before plugins do. They can't be options — so document them as manual steps in your onboarding checklist and put them in your standard wp-config template.");
})();

/* =================================================================== */
/* 24. THE PLUGIN ARCHITECTURE                                         */
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
/* 25. LIVE DEMO / HANDS-ON                                            */
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
    { n: "4", t: "Toggle & re-check", d: "Flip a switch off, reload, watch the behavior change." },
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
  footer(s, 25);
  s.addNotes("Do this live if there's a sandbox. The /wp-json/wp/v2/users check is the crowd-pleaser — before/after is instantly visible. For the terminal crowd, the WP-CLI one-liner installs and activates from the zip in one shot; swap the local path for a URL if the zip is hosted.");
})();

/* =================================================================== */
/* 26. EXERCISE                                                        */
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

/* =================================================================== */
/* 27. CHEAT SHEET / RECAP                                             */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("The cheat sheet — defaults that ship ON", {
    x: 0.6, y: 0.45, w: 12, h: 0.7, fontFace: HEAD, fontSize: 30, bold: true, color: INK, margin: 0,
  });
  s.addText("Everything on-by-default in one view, mapped to the core hook.", {
    x: 0.6, y: 1.2, w: 12, h: 0.4, fontFace: BODY, fontSize: 14, color: STEEL2, bold: true, margin: 0,
  });
  const rows = [
    ["Restrict REST user discovery", "rest_endpoints", "Security"],
    ["Lock down XML-RPC by category", "xmlrpc_methods / wp_xmlrpc_server_class", "Security"],
    ["Require strong passwords (15+ / breach-screened)", "user_profile_update_errors", "Security"],
    ["Send baseline security headers", "wp_headers", "Security"],
    ["Disable comments & pingbacks", "comments_open / pings_open", "Content"],
    ["Redirect author + attachment pages", "template_redirect", "Content / SEO"],
    ["Disable emoji script", "init (remove_action)", "Performance"],
  ];
  const tblRows = [[
    { text: "Default", options: { bold: true, color: WHITE, fill: { color: STEEL }, fontFace: BODY, fontSize: 13, align: "left", margin: 4 } },
    { text: "Core hook", options: { bold: true, color: WHITE, fill: { color: STEEL }, fontFace: MONO, fontSize: 12, align: "left", margin: 4 } },
    { text: "Category", options: { bold: true, color: WHITE, fill: { color: STEEL }, fontFace: BODY, fontSize: 13, align: "left", margin: 4 } },
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
    x: 0.6, y: 1.65, w: 12.1, colW: [5.0, 4.9, 2.2],
    border: { type: "solid", color: "DCE6EB", pt: 1 }, valign: "middle", rowH: 0.44,
  });
  footer(s, 27);
  s.addNotes("This is your screenshot slide — everything on-by-default in one view, mapped to the core hook. Three deliberate *non*-defaults worth calling out: Application Passwords stay AVAILABLE (the safer REST credential), the login logo is LEFT ALONE unless you opt in, and removing the version fingerprint is OFF, because it is obscurity rather than hardening. All three are choices, not oversights — and the last one is the honest test of the whole talk: if a default doesn't actually make the site safer, don't ship it as security.");
})();

/* =================================================================== */
/* 28. CLOSING                                                         */
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
  s.addNotes("Hand out the zip and the reference doc. Invite everyone to add their own favorite default to the schema and share it back with the group.");
})();

p.writeFile({ fileName: "Better-by-Default.pptx" }).then((f) => console.log("WROTE", f));
