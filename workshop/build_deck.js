const pptxgen = require("pptxgenjs");
const path = require("path");
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
  s.addText("WPYEG  ·  Better by Default", {
    x: 0.55, y: 7.03, w: 8, h: 0.3, fontFace: BODY, fontSize: 9,
    color: WHEATD, bold: true, align: "left", margin: 0,
  });
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
    fontFace: MONO, fontSize: fontSize || 16, align: "left", valign: "top",
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
  s.addText("Explicit, testable starting policies — one toggle at a time.", {
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
  s.addText("A default is an assumption, not a guarantee", {
    x: 0.6, y: 0.55, w: 12, h: 0.9, fontFace: HEAD, fontSize: 34, bold: true, color: INK, margin: 0,
  });
  s.addText("WordPress optimizes for broad compatibility. Our job is to make each project's assumptions explicit, reversible, and testable.", {
    x: 0.6, y: 1.5, w: 11.8, h: 0.9, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });

  const cards = [
    { g: "1", c: STEEL, t: "Name the outcome", d: "Say what the control changes — and what public surface or workflow remains." },
    { g: "2", c: STEEL, t: "Price compatibility", d: "REST, XML-RPC, comments, archives, and sessions can serve legitimate needs." },
    { g: "3", c: WHEATD, t: "Choose the right layer", d: "Some policy belongs in WordPress; headers and endpoint blocking may belong at the edge." },
    { g: "4", c: WHEATD, t: "Verify real behavior", d: "Test status codes, callable methods, cookies, query semantics, and integrations." },
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
  s.addNotes("These aren't hypothetical. Every one is a default you can flip. The rest of the talk is: which doors, and the one line of code that closes each.");
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
  s.addText("A “default” is an explicit,\ntestable hook behind a toggle.", {
    x: 0.8, y: 1.9, w: 11.7, h: 2.4, fontFace: HEAD, fontSize: 44, bold: true, color: WHITE, lineSpacingMultiple: 1.05, margin: 0,
  });
  codePanel(s, 0.8, 4.5, 11.7, 1.9, [
    { t: "if ( wpyeg_defaults_enabled( 'disable_xmlrpc' ) ) {", k: "" },
    { t: "    add_filter( 'xmlrpc_methods', '__return_empty_array' );", k: "h" },
    { t: "}   // delivery is simple; accuracy is the work", k: "c" },
  ], 16);
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
    s.addText(r.ex, { x: 8.65, y: y + 0.5, w: 3.7, h: 0.8, fontFace: MONO, fontSize: 16, color: CGOLD, valign: "middle", margin: 0 });
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
    { g: "1", t: "Security", d: "Reduce unused callable surface" },
    { g: "2", t: "Content", d: "Close unused public surfaces" },
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
  s.addText(title, { x: 0.6, y: 0.85, w: 12.1, h: 0.85, fontFace: HEAD, fontSize: 32, bold: true, color: INK, margin: 0 });

  // left column
  s.addText(why, { x: 0.6, y: 1.95, w: 5.35, h: 3.2, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0, valign: "top", lineSpacingMultiple: 1.12 });

  // option chip
  s.addShape(p.ShapeType.roundRect, { x: 0.6, y: 5.55, w: 5.35, h: 1.05, rectRadius: 0.08, fill: { color: "EAF1F5" }, line: { color: STEEL2, width: 1 } });
  s.addText([
    { text: "OPTION   ", options: { color: STEEL2, bold: true } },
    { text: optKey, options: { color: INK, bold: true } },
    { text: "\nDEFAULT   ", options: { color: STEEL2, bold: true } },
    { text: def, options: { color: def.includes("no") ? WHEATD : STEEL, bold: true } },
  ], { x: 0.8, y: 5.65, w: 5.0, h: 0.85, fontFace: MONO, fontSize: 13, valign: "middle", margin: 0, lineSpacingMultiple: 1.15 });

  // right code
  codePanel(s, 6.25, 1.95, 6.45, 4.65, lines, fs || 16);
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
  "Core exposes public author records and user_nicename slugs — not private user_login values. Removing all core user routes reduces anonymous discovery, but other public author data can remain.",
  "wpyeg_restrict_rest_user_discovery", "yes",
  [
    { t: "foreach ( array_keys( $endpoints ) as $route ) {", k: "" },
    { t: "  if ( preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {", k: "h" },
    { t: "    unset( $endpoints[ $route ] );", k: "h" },
    { t: "  }", k: "" },
    { t: "}", k: "" },
  ], 16).addNotes("Removed routes return the normal REST no-route response, usually 404 — not 401. This is surface reduction, not a promise that author identities are secret.");

codeSlide(8, "SECURITY · 2 of 6",
  "Disable XML-RPC methods",
  "The xmlrpc_enabled filter only disables authenticated methods. Remove the registered method surface and discovery hints to make the setting truthful. The endpoint file itself may still return a fault.",
  "wpyeg_disable_xmlrpc", "yes",
  [
    { t: "add_filter( 'xmlrpc_enabled', '__return_false' );", k: "h" },
    { t: "add_filter(", k: "" },
    { t: "  'xmlrpc_methods',", k: "" },
    { t: "  '__return_empty_array',", k: "h" },
    { t: "  PHP_INT_MAX", k: "" },
    { t: ");", k: "" },
    { t: "remove_action( 'wp_head', 'rsd_link' );", k: "" },
  ], 16).addNotes("Check trusted integrations first. Blocking xmlrpc.php itself belongs at the web server or edge.");

codeSlide(9, "SECURITY · 3 of 6",
  "Application Passwords: explicit policy",
  "Application Passwords are hashed, per-application, revocable integration credentials. Availability is not itself a flaw. Keep them available unless organizational policy forbids them.",
  "wpyeg_disable_application_passwords", "no",
  [
    { t: "add_filter(", k: "" },
    { t: "  'wp_is_application_passwords_available',", k: "h" },
    { t: "  '__return_false'", k: "h" },
    { t: ");", k: "" },
    { t: "", k: "" },
    { t: "// Optional site-policy prohibition.", k: "c" },
    { t: "// Inventory and revoke unused credentials.", k: "c" },
  ], 16).addNotes("Application Passwords are documented core API credentials. Treat availability as policy context, not a failed security check.");

codeSlide(10, "SECURITY · 4 of 6",
  "Require strong passwords",
  "Enforce at least 15 characters and reject common or user-specific values. Avoid arbitrary composition rules. Validate core profile, reset, and REST user flows, then test custom registration, CLI, and SSO separately.",
  "wpyeg_require_strong_passwords", "yes",
  [
    { t: "$length = function_exists( 'mb_strlen' )", k: "" },
    { t: "  ? mb_strlen( $password )", k: "h" },
    { t: "  : strlen( $password );", k: "" },
    { t: "", k: "" },
    { t: "if ( 15 > $length ) {", k: "" },
    { t: "  return new WP_Error(", k: "" },
    { t: "    'password_too_short',", k: "h" },
    { t: "    'Use 15+ characters.' );", k: "" },
    { t: "}", k: "" },
  ], 16).addNotes("NIST recommends length, blocklists, rate limiting, paste/password-manager support, and no forced composition or periodic rotation.");

codeSlide(11, "SECURITY · 5 of 6",
  "Metadata and headers: use the right layer",
  "Removing the generator tag is hygiene, not hardening. The wp_headers filter does not cover every REST/error/redirect, static file, or cached edge response. Configure security headers at the server or CDN.",
  "wpyeg_remove_version", "no",
  [
    { t: "remove_action( 'wp_head', 'wp_generator' );", k: "h" },
    { t: "add_filter( 'the_generator', '__return_empty_string' );", k: "" },
    { t: "", k: "" },
    { t: "// Patch promptly; verify headers at edge.", k: "c" },
  ], 16).addNotes("Prefer CSP frame-ancestors over relying only on X-Frame-Options. Roll out CSP from an inventory and report-only phase.");

codeSlide(12, "SECURITY · 6 of 6 · opt-in",
  "Lock REST to logged-in users",
  "Requiring auth for every REST request can break public blocks, forms, search, oEmbed, and integrations. It ships off. Use it only after an endpoint inventory and compatibility test.",
  "wpyeg_disable_rest", "no",
  [
    { t: "if ( ! empty( $result ) ) {", k: "" },
    { t: "  return $result;", k: "" },
    { t: "}", k: "" },
    { t: "if ( ! is_user_logged_in() ) {", k: "" },
    { t: "  return new WP_Error(", k: "h" },
    { t: "    'rest_not_logged_in',", k: "h" },
    { t: "    'Authentication required.',", k: "h" },
    { t: "    array( 'status' => 401 )", k: "" },
    { t: "  );", k: "" },
    { t: "}", k: "" },
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
    { t: "add_filter( 'pings_open', '__return_false', 20 );", k: "h" },
    { t: "add_filter( 'comments_array', '__return_empty_array', 20 );", k: "" },
    { t: "", k: "" },
    { t: "// + remove_post_type_support() on init", k: "c" },
    { t: "// + remove_menu_page('edit-comments.php')", k: "c" },
    { t: "// + drop the admin-bar comments node", k: "c" },
  ]).addNotes("If the client wants comments, leave this off — but still consider closing pingbacks/trackbacks, which are almost pure spam.");

codeSlide(15, "CONTENT · 2 of 3",
  "Author archives & legacy attachment pages",
  "Disabled author archives should return a real 404 and suppress numeric-author canonical redirects. WordPress 6.4+ already disables attachment pages on new sites; redirects mainly help upgraded sites.",
  "wpyeg_disable_author_archives / _redirect_attachment_pages", "yes / no",
  [
    { t: "if ( is_author() ) {", k: "" },
    { t: "  $wp_query->set_404();", k: "h" },
    { t: "  status_header( 404 );", k: "h" },
    { t: "  nocache_headers();", k: "" },
    { t: "}", k: "" },
    { t: "", k: "" },
    { t: "// Legacy attachment: parent or media file.", k: "c" },
  ], 16).addNotes("Homepage redirects create soft-404 and misleading canonical behavior. Multi-author publications should enable and curate author pages.");

codeSlide(16, "CONTENT · 3 of 3",
  "Disable emoji compatibility support",
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
  "Title-only search changes editor expectations, so it is opt-in. Use core's search-column filter to preserve term parsing, exclusions, password constraints, and SQL composition. Toolbar visibility is UX, not authorization.",
  "wpyeg_title_only_admin_search / _frontend_admin_bar_behavior", "no / ''",
  [
    { t: "add_filter(", k: "" },
    { t: "  'post_search_columns',", k: "" },
    { t: "  function ( $columns, $search, $query ) {", k: "" },
    { t: "    if ( is_admin() && $query->is_search() ) {", k: "" },
    { t: "      return array( 'post_title' );", k: "h" },
    { t: "    }", k: "" },
    { t: "    return $columns;", k: "" },
    { t: "  },", k: "" },
    { t: "  10,", k: "" },
    { t: "  3", k: "" },
    { t: ");", k: "" },
  ], 16).addNotes("manage_options is a capability, not the name of an Administrator role. Hiding the toolbar does not remove wp-admin access.");

codeSlide(19, "LOGIN & SESSIONS",
  "Right-size the login session",
  "WordPress defaults are approximately 14 days remembered and 48 hours regular. Keep those defaults unless policy says otherwise. To disable Remember Me, remove the submitted value server-side; CSS is only presentation.",
  "wpyeg_remember_me_days / _session_regular_hours", "0 / 0",
  [
    { t: "add_action(", k: "" },
    { t: "  'login_init',", k: "" },
    { t: "  static function () {", k: "" },
    { t: "    unset( $_POST['rememberme'] );", k: "h" },
    { t: "    unset( $_REQUEST['rememberme'] );", k: "h" },
    { t: "  }", k: "" },
    { t: ");", k: "" },
    { t: "", k: "" },
    { t: "// Expiration changes affect new cookies only.", k: "c" },
  ], 16).addNotes("Revoke existing sessions separately. Client-side checkbox removal alone is bypassable.");

/* =================================================================== */
/* SECTION 4 — BRANDING + PERFORMANCE (combined divider)               */
/* =================================================================== */
divider("4", "SECTION FOUR", "Branding &\nPerformance", "Own the login screen, then shave the last bit of weight off every page.")
  .addNotes("Last pair of sections: a branding touch on the login screen, then two performance levers.");

codeSlide(21, "BRANDING",
  "Brand the login screen — when useful",
  "The WordPress logo is not a security leak. Keep core behavior by default; remove or replace it only when the project calls for branded authentication UX.",
  "wpyeg_login_logo_behavior / _login_logo_link_home", "default / no",
  [
    { t: "add_filter( 'login_headerurl', 'home_url' );", k: "h" },
    { t: "add_filter(", k: "" },
    { t: "  'login_headertext',", k: "" },
    { t: "  static function () {", k: "" },
    { t: "    return get_bloginfo( 'name' );", k: "h" },
    { t: "  }", k: "" },
    { t: ");", k: "" },
  ], 16).addNotes("This is a preference, not a hardening control. Keep it out of security scorecards.");

codeSlide(22, "PERFORMANCE · opt-in",
  "Throttle Heartbeat; declare script strategy",
  "An opt-in 60-second Heartbeat interval may reduce polling, but do not deregister it: autosave, post locking, sessions, and plugins use it. Declare defer/async per owned script through core's dependency-aware API.",
  "wpyeg_throttle_heartbeat", "no",
  [
    { t: "$settings['interval'] = 60;", k: "h" },
    { t: "", k: "" },
    { t: "wp_enqueue_script(", k: "" },
    { t: "  'example-feature',", k: "" },
    { t: "  $src,", k: "" },
    { t: "  array(),", k: "" },
    { t: "  '1.0.0',", k: "" },
    { t: "  array( 'strategy' => 'defer' )", k: "h" },
    { t: ");", k: "" },
  ], 16).addNotes("WordPress 6.3+ resolves eligible loading strategies through dependency trees. Never inject defer into every script tag.");

/* =================================================================== */
/* 23. wp-config things                                                */
/* =================================================================== */
(() => {
  const s = p.addSlide();
  s.background = { color: CLOUD };
  s.addText("Deployment policy belongs in configuration", {
    x: 0.6, y: 0.55, w: 12, h: 0.8, fontFace: HEAD, fontSize: 32, bold: true, color: INK, margin: 0,
  });
  s.addText("Plugins can define constants early, but wp-config.php or environment configuration makes deployment ownership clearer.", {
    x: 0.6, y: 1.4, w: 11.8, h: 0.7, fontFace: BODY, fontSize: 16, color: SLATE, margin: 0,
  });
  codePanel(s, 0.6, 2.35, 12.1, 2.1, [
    { t: "define( 'DISALLOW_FILE_EDIT', true );  // no in-dashboard code editor", k: "" },
    { t: "define( 'DISALLOW_FILE_MODS', true );  // managed deployments only", k: "h" },
    { t: "define( 'AUTOSAVE_INTERVAL', 120 );    // gentler autosave (seconds)", k: "" },
    { t: "define( 'WP_POST_REVISIONS', 10 );     // cap revision-table bloat", k: "" },
  ], 16);
  const notes = [
    { t: "Separate deployment policy", d: "File editing and update ownership are different decisions." },
    { t: "Protect the update path", d: "DISALLOW_FILE_MODS requires an external update workflow." },
    { t: "Tune editorial storage", d: "Autosave and revisions are workflow/capacity choices." },
  ];
  notes.forEach((c, i) => {
    const x = 0.6 + i * 4.07;
    s.addShape(p.ShapeType.roundRect, { x, y: 4.75, w: 3.85, h: 1.65, rectRadius: 0.09, fill: { color: WHITE }, line: { color: "DCE6EB", width: 1 }, shadow: { type: "outer", color: "C7D4DB", blur: 5, offset: 2, angle: 90, opacity: 0.5 } });
    dot(s, x + 0.28, 5.02, String(i + 1), STEEL, WHITE, 0.5);
    s.addText(c.t, { x: x + 0.95, y: 4.95, w: 2.75, h: 0.65, fontFace: HEAD, fontSize: 15, bold: true, color: INK, margin: 0, valign: "middle" });
    s.addText(c.d, { x: x + 0.28, y: 5.62, w: 3.35, h: 0.7, fontFace: BODY, fontSize: 12.5, color: SLATE, margin: 0, valign: "top" });
  });
  footer(s, 23);
  s.addNotes("Do not enable DISALLOW_FILE_MODS on a site that relies on dashboard updates. These constants are clearer in deployment config, not impossible for plugins to define.");
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
  ], 16);
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
    { n: "3", t: "Verify status codes", d: "Visit /wp-json/wp/v2/users and /?author=1 logged out — both should return 404." },
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
  ], { x: 0.85, y: 6.1, w: 11.6, h: 0.72, fontFace: MONO, fontSize: 16, valign: "middle", margin: 0 });
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
  codePanel(s, 0.8, 3.4, 11.7, 3.0, [
    { t: "// 1) Add a schema entry.", k: "c" },
    { t: "'hide_welcome_panel' => array(", k: "" },
    { t: "  'default' => 'yes',", k: "h" },
    { t: "  'type'    => 'toggle',", k: "" },
    { t: "  'group'   => 'ux',", k: "" },
    { t: "),", k: "" },
    { t: "// 2) Wire the enabled policy.", k: "c" },
    { t: "if ( wpyeg_defaults_enabled( 'hide_welcome_panel' ) ) {", k: "" },
    { t: "  remove_action( 'welcome_panel', 'wp_welcome_panel' );", k: "h" },
    { t: "}", k: "" },
  ], 16);
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
  s.addText("Starting assumptions that ship ON — review each site's boundary.", {
    x: 0.6, y: 1.2, w: 12, h: 0.4, fontFace: BODY, fontSize: 14, color: STEEL2, bold: true, margin: 0,
  });
  const rows = [
    ["Restrict REST user discovery", "rest_endpoints", "Security"],
    ["Disable XML-RPC methods", "xmlrpc_methods", "Security"],
    ["Require password policy", "profile / reset / REST", "Security"],
    ["Disable comments & pingbacks", "comments_open / pings_open", "Content"],
    ["404 public author archives", "canonical + query state", "Content"],
    ["Remove emoji compatibility", "init (remove hooks)", "Performance"],
  ];
  const tblRows = [[
    { text: "Default", options: { bold: true, color: WHITE, fill: { color: STEEL }, fontFace: BODY, fontSize: 16, align: "left", margin: 4 } },
    { text: "Core hook", options: { bold: true, color: WHITE, fill: { color: STEEL }, fontFace: MONO, fontSize: 16, align: "left", margin: 4 } },
    { text: "Category", options: { bold: true, color: WHITE, fill: { color: STEEL }, fontFace: BODY, fontSize: 16, align: "left", margin: 4 } },
  ]];
  rows.forEach((r, i) => {
    const bg = i % 2 ? "EAF1F5" : WHITE;
    tblRows.push([
      { text: r[0], options: { color: INK, fill: { color: bg }, fontFace: BODY, fontSize: 16, align: "left", margin: 4 } },
      { text: r[1], options: { color: STEEL, fill: { color: bg }, fontFace: MONO, fontSize: 16, align: "left", margin: 4 } },
      { text: r[2], options: { color: SLATE, fill: { color: bg }, fontFace: BODY, fontSize: 16, align: "left", margin: 4 } },
    ]);
  });
  s.addTable(tblRows, {
    x: 0.6, y: 1.65, w: 12.1, colW: [5.0, 4.9, 2.2],
    border: { type: "solid", color: "DCE6EB", pt: 1 }, valign: "middle", rowH: 0.65,
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
  s.addText("Take the plugin, fork it, teach it. A default is an explicit, testable hook behind a toggle.", {
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
    { text: "License GPL-3.0-or-later — verify it, then ship it.", options: { color: SKY } },
  ], { x: 0.9, y: 6.15, w: 11.5, h: 0.6, fontFace: BODY, fontSize: 16, margin: 0, valign: "middle" });
  s.addNotes("Wrap: hand out the zip and the reference doc. Invite them to add their own favorite default to the schema and share it back with the group.");
})();

p.writeFile({ fileName: path.join(__dirname, "Better-by-Default.pptx") }).then((f) => console.log("WROTE", f));
