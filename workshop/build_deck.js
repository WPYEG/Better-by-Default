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
  s.addText("Sane defaults for every new WordPress site — one toggle at a time.", {
    x: 0.9, y: 4.35, w: 11.2, h: 0.7, fontFace: BODY, fontSize: 21, color: SKY, italic: true, margin: 0,
  });
  s.addText(
    [
      { text: "A hands-on workshop  ", options: { color: SKY } },
      { text: "·  the better-by-default plugin", options: { color: WHEAT } },
    ],
    { x: 0.9, y: 6.25, w: 11.5, h: 0.5, fontFace: MONO, fontSize: 14, margin: 0 }
  );
  s.addNotes(
    "Welcome to WPYEG. Tonight we build one small plugin that flips a menu of sensible defaults on any WordPress site. Whether you write PHP daily or just manage sites, you'll leave knowing WHY each default matters and HOW to toggle it."
  );
})();

/* =================================================================== */
/* 2. THE HOOK / PROBLEM                                               */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("A fresh WordPress install ships wide open", {
    x: 0.6, y: 0.55, w: 12, h: 0.9, fontFace: HEAD, fontSize: 34, bold: true, color: INK, margin: 0,
  });
  s.addText("Out of the box, core leaves a lot of doors ajar and a lot of noise switched on. None of it is a bug — it's just defaults chosen for maximum compatibility, not for your site.", {
    x: 0.6, y: 1.5, w: 11.8, h: 0.9, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });

  const cards = [
    { g: "!", c: CORAL, t: "Usernames leak", d: "REST + author archives happily list every login name to anonymous visitors." },
    { g: "!", c: CORAL, t: "Legacy XML-RPC wide open", d: "pingback and system.multicall amplifiers answer by default." },
    { g: "~", c: WHEATD, t: "Dead weight loads", d: "Emoji scripts, version tags, and RSD links ship on every single page view." },
    { g: "~", c: WHEATD, t: "Spam surface invites", d: "Comments, pingbacks, and trackbacks are open by default on new content." },
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
  s.addNotes("These aren't hypothetical. Every one is a default you can flip. The rest of tonight is just: which doors, and the one line of code that closes each.");
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
  s.addNotes("If you remember nothing else: a default is an add_filter behind an if( option ). Once you see that shape, the entire plugin is just twenty variations of it.");
})();

/* =================================================================== */
/* 4. PRIMER: hooks & filters (mixed audience)                         */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("Two words, gently: hooks & filters", {
    x: 0.6, y: 0.55, w: 12, h: 0.8, fontFace: HEAD, fontSize: 32, bold: true, color: INK, margin: 0,
  });
  s.addText("WordPress is built to be interrupted on purpose. You don't edit core — you hop in at labeled moments.", {
    x: 0.6, y: 1.4, w: 11.8, h: 0.7, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });

  const rows = [
    { g: "A", t: "Action", d: "“When you reach this moment, also DO this.” A doorbell you answer.", ex: "add_action( 'init', fn );" },
    { g: "F", t: "Filter", d: "“Before you use this value, let me CHANGE it first.” A mail slot that edits the letter.", ex: "add_filter( 'xmlrpc_enabled', '__return_false' );" },
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
  s.addNotes("Analogy for non-devs: an action is a doorbell (do something at a moment); a filter is a mail slot (change a value in transit). __return_false is a tiny helper that just hands back false — perfect for switching a feature off.");
})();

/* =================================================================== */
/* 5. ROADMAP                                                          */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("Six categories of default", {
    x: 0.6, y: 0.55, w: 12, h: 0.8, fontFace: HEAD, fontSize: 34, bold: true, color: INK, margin: 0,
  });
  const cats = [
    { g: "1", t: "Security", d: "Shrink the attack surface" },
    { g: "2", t: "Content", d: "Close public spam & leaks" },
    { g: "3", t: "Admin UX", d: "Calmer, faster dashboard" },
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
  s.addNotes("Roadmap. We'll spend most time on security and content, then move fast through UX, login, branding, performance, and end at the plugin that bundles them.");
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
  .addNotes("Security first. The theme: close what you don't use. You can't exploit an endpoint that isn't there.");

codeSlide(7, "SECURITY · 1 of 6",
  "Restrict REST API user discovery",
  "The /wp/v2/users endpoint hands out every author's login name to anyone — half of a brute-force guess, free. We keep REST working for logged-in tools but slam the door on anonymous enumeration.",
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
  ]).addNotes("Author enumeration is step one of most brute-force scripts. This closes it for logged-out requests only, so your editor and integrations keep working.");

codeSlide(8, "SECURITY · 2 of 6",
  "Lock XML-RPC down by category",
  "XML-RPC is an old switchboard — every method is a line. We unplug the lines we don't want: pingbacks (spam/DDoS) and the credential-authenticated blogging APIs (the brute-force target), via a method filter. system.multicall can't be filtered off, so a replacement server refuses it. Jetpack's jetpack.* lines stay untouched.",
  "wpyeg_xmlrpc_allow_pingbacks / _remote_publishing / _multicall", "no (each)",
  [
    { t: "// each category off → remove its methods", k: "c" },
    { t: "add_filter( 'xmlrpc_methods', function ( $m ) {", k: "" },
    { t: "  if ( ! allow( 'pingbacks' ) )", k: "" },
    { t: "    unset( $m['pingback.ping'] );", k: "h" },
    { t: "  if ( ! allow( 'remote_publishing' ) )", k: "" },
    { t: "    // drop wp.* metaWeblog.* mt.* blogger.*", k: "c" },
    { t: "  return $m;", k: "" },
    { t: "} );", k: "" },
    { t: "// multicall: swap in a server that refuses it", k: "c" },
    { t: "add_filter( 'wp_xmlrpc_server_class', $refuse );", k: "h" },
  ]).addNotes("system.multicall is the stubborn line: the xmlrpc_methods filter can't remove it — IXR plugs it right back in — so we swap in a server that refuses the call. And don't *block the endpoint* on a Jetpack site: that breaks its WordPress.com connection. The method toggles leave jetpack.* alone.");

codeSlide(9, "SECURITY · 3 of 6",
  "Keep Application Passwords available",
  "The one we don't lock down. An Application Password is a spare key cut for one app — hashed, per-application, revocable on its own. It's the safer REST credential, and the only one core accepts for REST Basic Auth. So they stay on; prohibit them only if policy forbids non-interactive credentials.",
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
  ], 12.5).addNotes("Switching them off doesn't stop people connecting things — it just pushes them to worse habits, like sharing the login password. So they stay available by default: the safer, revocable REST credential.");

codeSlide(10, "SECURITY · 4 of 6",
  "Require strong passwords",
  "The rules changed: NIST 800-63B and OWASP now say length plus breach screening beats composition rules. Forcing upper/lower/number/symbol just herds everyone to Password1! — predictable, not strong. We require 15+ chars and screen against known breaches, server-side.",
  "wpyeg_require_strong_passwords", "yes",
  [
    { t: "// hooked on user_profile_update_errors", k: "c" },
    { t: "if ( strlen( $pw ) < 15 ) {", k: "h" },
    { t: "    $errors->add( 'short', 'Use 15+ chars.' );", k: "" },
    { t: "}", k: "" },
    { t: "", k: "" },
    { t: "// screen against breaches (HIBP),", k: "c" },
    { t: "// not composition rules", k: "c" },
    { t: "if ( wpyeg_password_is_pwned( $pw ) ) {", k: "h" },
    { t: "    $errors->add( 'pwned', 'Seen in a breach.' );", k: "" },
    { t: "}", k: "" },
  ]).addNotes("Never trust the client — the JS meter is UX, the server rule is the wall. Length + breach screening is the modern NIST/OWASP guidance; composition rules are out.");

codeSlide(11, "SECURITY · 5 of 6",
  "Remove fingerprints, add headers",
  "Two quick wins: stop broadcasting your exact core version to vulnerability scanners, and send three low-risk security headers that most sites can adopt without breaking anything.",
  "wpyeg_remove_version / _security_headers", "yes / yes",
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
  ]).addNotes("Version hiding is obscurity, not security — but it cuts automated noise. The three headers are safe defaults; a full CSP is a bigger conversation.");

codeSlide(12, "SECURITY · 6 of 6 · opt-in",
  "Lock REST to logged-in users",
  "The nuclear option. Requiring auth for ALL REST calls stops anonymous scraping cold — but it also breaks the block editor and many blocks. That's why it ships OFF. Reach for it only on pure brochure sites.",
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
  ]).addNotes("Big red switch. Default off on purpose. Great teaching moment: not every default should default to ON — some are opt-in because they trade functionality for safety.");

/* =================================================================== */
/* SECTION 2 — CONTENT                                                 */
/* =================================================================== */
divider("2", "SECTION TWO", "Content &\nPublic Surfaces", "Close the spam funnels and the thin pages Google (and bots) love to crawl.")
  .addNotes("Content section. These reduce spam surface and clean up thin, duplicate URLs that hurt SEO.");

codeSlide(14, "CONTENT · 1 of 3",
  "Disable comments, trackbacks & pingbacks",
  "For most business sites, comments are a spam magnet with little upside. We close them everywhere, hide existing threads, drop the admin menu, and default new posts to pings-closed.",
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
  ]).addNotes("If the client wants comments, leave this off — but still consider closing pingbacks/trackbacks, which are almost pure spam.");

codeSlide(15, "CONTENT · 2 of 3",
  "Redirect author & attachment pages",
  "Two thin-content leaks, same fix. Author archives expose login slugs; attachment pages are near-empty media wrappers. Both dilute SEO and add surface. Send them home with a 301.",
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
  ]).addNotes("template_redirect fires before a template loads — the perfect place to bounce a request. Same hook, two conditions.");

codeSlide(16, "CONTENT · 3 of 3",
  "Disable the emoji script",
  "Core injects an emoji-detection script and inline CSS on every page load, plus a DNS-prefetch hint. Modern browsers render emoji natively, so this is pure dead weight you can shed.",
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
  ]).addNotes("Small win, but it's on literally every page. Good example of a 'why is this even on?' default.");

/* =================================================================== */
/* SECTION 3 — UX + LOGIN (combined divider)                           */
/* =================================================================== */
divider("3", "SECTION THREE", "Admin UX &\nLogin Sessions", "Small quality-of-life defaults: a calmer dashboard and sensible session policy.")
  .addNotes("Now the quality-of-life defaults. These are more about daily experience and session safety than raw hardening.");

codeSlide(18, "ADMIN UX",
  "Faster search, quieter admin bar",
  "On big sites the admin list-table search reads every word of every post — and crawls. Title-only search checks just the spines, far faster (off by default — it changes editor expectations). And the floating front-end admin bar can be hidden for non-admins.",
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
  ], 11).addNotes("post_search_columns (WP 6.2+) narrows the columns instead of rewriting the SQL clause — core's term parsing and the logged-out password guard stay intact. Scope the filter; don't bulldoze the query. That's the craft here.");

codeSlide(19, "LOGIN & SESSIONS",
  "Right-size the login session",
  "Core's “Remember Me” lasts 14 days — often too long. Cap it (say 5 days), optionally shorten regular logins, or hide the checkbox entirely for shared machines. One filter does all three.",
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
  ]).addNotes("WordPress ships handy time constants — DAY_IN_SECONDS, HOUR_IN_SECONDS — so you never hand-count seconds. Good habit to point out.");

/* =================================================================== */
/* SECTION 4 — BRANDING + PERFORMANCE (combined divider)               */
/* =================================================================== */
divider("4", "SECTION FOUR", "Branding &\nPerformance", "Own the login screen, then shave the last bit of weight off every page.")
  .addNotes("Last pair of sections: a branding touch on the login screen, then two performance levers.");

codeSlide(21, "BRANDING",
  "Own the login screen",
  "The login page is the site's front door — and the default “W” on wp-login.php links out to wordpress.org, a subtle trust leak. But repainting a client's front door uninvited is rude, so the default is to leave it alone; removing, unlinking, or replacing the logo is an opt-in, and any change points the link home.",
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
  ], 12).addNotes("Default is keep_default — leave the login screen untouched. Swap in a background-image to drop in the client's own logo. Tiny detail — clients always notice it.");

codeSlide(22, "PERFORMANCE · opt-in",
  "Throttle Heartbeat, defer scripts",
  "Two opt-in levers. The Heartbeat API polls admin-ajax every 15–60s — throttle it (and drop it on the dashboard home) to ease shared hosting. And defer non-critical front-end scripts so they stop blocking render.",
  "wpyeg_throttle_heartbeat / _defer_scripts", "no / no",
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
  ]).addNotes("Off by default because deferring scripts can break plugins that expect synchronous jQuery. Test before shipping — hence opt-in.");

/* =================================================================== */
/* 23. wp-config things                                                */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("Three things a plugin can't toggle", {
    x: 0.6, y: 0.55, w: 12, h: 0.8, fontFace: HEAD, fontSize: 32, bold: true, color: INK, margin: 0,
  });
  s.addText("Some defaults live in wp-config.php, above the plugin layer. Document these as manual steps in your onboarding checklist.", {
    x: 0.6, y: 1.4, w: 11.8, h: 0.7, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });
  codePanel(s, 0.6, 2.35, 12.1, 2.1, [
    { t: "define( 'DISALLOW_FILE_EDIT', true );  // no in-dashboard code editor", k: "" },
    { t: "define( 'AUTOSAVE_INTERVAL', 120 );    // gentler autosave (seconds)", k: "" },
    { t: "define( 'WP_POST_REVISIONS', 10 );     // cap revision-table bloat", k: "" },
  ], 14);
  const notes = [
    { t: "Kills the theme/plugin editor", d: "A stolen admin login can't rewrite your PHP from the dashboard." },
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
  s.addNotes("These constants can't be options because they must load before plugins do. Put them in your standard wp-config template.");
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
  s.addNotes("The design lesson: a data-driven plugin. Adding a new default = one array entry + one if-block in bootstrap. No new settings-page code. That's the pattern to steal.");
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
    { n: "1", t: "Upload the plugin", d: "Plugins → Add New → Upload Plugin → choose better-by-default.zip → Activate." },
    { n: "2", t: "Open the settings", d: "Settings → Better by Default. Every toggle is grouped by category." },
    { n: "3", t: "Verify a default", d: "Visit /wp-json/wp/v2/users logged out — 401 or empty, not a list of usernames." },
    { n: "4", t: "Toggle & re-check", d: "Flip a switch off, reload, watch the behavior change. That's the filter turning on and off." },
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
    { text: "wp plugin install ./better-by-default.zip --activate", options: { color: CGOLD } },
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
  s.addNotes("Great confidence-builder. It proves the data-driven pattern: they touch two spots and a real feature toggles. If time is short, walk it verbally.");
})();

/* =================================================================== */
/* 27. CHEAT SHEET / RECAP                                             */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("The cheat sheet", {
    x: 0.6, y: 0.45, w: 12, h: 0.7, fontFace: HEAD, fontSize: 30, bold: true, color: INK, margin: 0,
  });
  s.addText("Defaults that ship ON — safe for nearly any site.", {
    x: 0.6, y: 1.2, w: 12, h: 0.4, fontFace: BODY, fontSize: 14, color: STEEL2, bold: true, margin: 0,
  });
  const rows = [
    ["Restrict REST user discovery", "rest_endpoints", "Security"],
    ["Lock down XML-RPC by category", "xmlrpc_methods / wp_xmlrpc_server_class", "Security"],
    ["Require strong passwords (15+ / breach-screened)", "user_profile_update_errors", "Security"],
    ["Remove version + security headers", "wp_generator / wp_headers", "Security"],
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
  s.addNotes("Screenshot slide. Everything ON-by-default in one view, mapped to the core hook so they can find it in the code.");
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
  s.addText("Take the plugin, fork it, teach it. A default is just an opinionated filter behind a toggle.", {
    x: 0.9, y: 3.2, w: 11, h: 0.9, fontFace: BODY, fontSize: 19, color: SKY, italic: true, margin: 0, valign: "top",
  });
  s.addText([
    { text: "Files:  ", options: { color: WHEAT, bold: true } },
    { text: "better-by-default.zip", options: { color: SKY } },
    { text: "   ·   ", options: { color: MUTE } },
    { text: "wordpress-default-settings.md", options: { color: SKY } },
  ], { x: 0.9, y: 4.4, w: 11.5, h: 0.5, fontFace: MONO, fontSize: 14, margin: 0 });
  s.addText([
    { text: "Questions?  ", options: { color: WHITE, bold: true } },
    { text: "License GPL-2.0-or-later — ship it.", options: { color: SKY } },
  ], { x: 0.9, y: 6.15, w: 11.5, h: 0.6, fontFace: BODY, fontSize: 16, margin: 0, valign: "middle" });
  s.addNotes("Wrap: hand out the zip and the reference doc. Invite them to add their own favorite default to the schema and share it back with the group.");
})();

p.writeFile({ fileName: "Better-by-Default.pptx" }).then((f) => console.log("WROTE", f));
