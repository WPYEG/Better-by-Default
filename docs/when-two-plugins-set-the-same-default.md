# When two plugins set the same default

Better by Default is one of several plugins that flip WordPress defaults. Sooner
or later somebody installs two of them, or installs this alongside a security
suite that quietly does half the same job.

This is what actually happens, measured rather than guessed — three sibling
plugins activated together on one throwaway install, all three doing
substantially the same work.

The short version: nothing breaks. No fatal errors, no clobbered settings, no
warnings in a log. One plugin's setting silently stops applying, its settings
screen goes on displaying the value it thinks it set, and the site uses somebody
else's. This page shows which kinds of setting behave that way and which are safe
to share, how to check any site by hand in about a minute, and what two attempts
at automating that check got wrong.

## What did not break

Worth saying first, because it is the failure everyone expects and it did not
happen.

**No fatal errors.** Each plugin prefixes its functions (`wpyeg_defaults_*`,
and the others likewise), so nothing was declared twice.

**No settings clobbered.** Each stores its own option, so nobody overwrote
anybody's saved choices.

**Security headers came out right.** All three set `X-Content-Type-Options` and
`Referrer-Policy`, and all three *add to* the header array they were handed
rather than replacing it — so every contribution survived and the order they ran
in did not matter. Three plugins, one correct result.

That is the composition property, and it is separate from what each plugin does
about a header somebody else already set. The two siblings write only when the
key is absent. Better by Default does not: it corrects `X-Content-Type-Options`
to `nosniff` whatever it said before, because that header has exactly one
effective value and an existing anything-else is not a policy to defer to; it
replaces `X-Frame-Options` only when its configured value is strictly stronger;
and it defers on `Referrer-Policy`, whose tokens cannot be ranked. Different
policies, same additive shape — which is why they still composed.

That last one is the important observation, and the rest of this page is about
why.

## What did break, silently

All three set the login session length, through the same filter:

```php
add_filter( 'auth_cookie_expiration', function ( $expiration, $user_id, $remember ) {
    return 2 * DAY_IN_SECONDS;   // note: $expiration is thrown away
}, 50, 3 );
```

Read that callback carefully. It **ignores the value it was handed** and returns
its own. So when three plugins do it, WordPress runs all three and keeps
whichever answered last. The other two did nothing at all.

There is no error. Nothing is logged. Nothing appears on the Site Health screen.
The two losing plugins go on displaying their own session length on their own
settings screens — a number the site is not using.

On the test install all three happened to agree on two days, so everything
looked fine. **That is the dangerous case**, not the obvious one: the collision
is invisible precisely until somebody changes a value, at which point a setting
silently stops working and the screen still says otherwise.

## The rule this teaches

Compare the two filters:

| | `wp_headers` | `auth_cookie_expiration` |
|---|---|---|
| Callback receives | an array of headers | a number of seconds |
| Callback returns | that array, plus its own key | its own number |
| Two plugins together | both contributions survive | last one wins, others discarded |

**A filter that adds to its input composes. A filter that replaces its input
does not.**

Nothing in the WordPress API tells you which kind you are writing. Both are
`add_filter()`. Both look identical at the call site. The difference is entirely
in what your callback does with the argument it is given, and it decides whether
your plugin can coexist with another one or silently fights it.

When you write a filter callback, ask: *if a second plugin did exactly this,
would both still work?* If the answer is no, you are setting policy, and only
one plugin on the site can win.

## Checking a real site

You do not need a plugin to find this. WordPress keeps every registered callback
in the global `$wp_filter`, so you can ask directly:

```bash
wp eval '
global $wp_filter;
$hook = "auth_cookie_expiration";
foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
    foreach ( $callbacks as $cb ) {
        $f = $cb["function"];
        if ( is_array( $f ) ) {
            $name = ( is_object( $f[0] ) ? get_class( $f[0] ) : $f[0] ) . "::" . $f[1];
        } elseif ( $f instanceof Closure ) {
            $name = "closure";
        } else {
            $name = $f;
        }
        printf( "%-6s %s\n", $priority, $name );
    }
}'
```

On the three-plugin test install that prints:

```
10     closure
50     PixelManagedPlatform\Security\Settings::filter_auth_cookie_expiration
50     keel_defaults_session_length
```

Three entries on a filter that only keeps one answer. More than one line, on a
filter of the replacing kind, means somebody is losing.

Note that the two named callbacks sit at priority 50 and the anonymous one at
10. Priority decides the order they run in, and on a replacing filter the
**last** one to run is the one that counts — so registering earlier does not
help you, it guarantees you lose.

That output is from the original measurement and no longer reproduces: all three
plugins register at 50 now, the third having been moved there because of what
this page found. Kept as it was recorded, because the lesson is in the numbers
being different, and a page that quietly edits its own evidence to match today
is worth less than one that shows what it saw.

A line reading `__return_false` is the awkward case, and it is not rare. That is
a *core* function, so it names nothing about who registered it — several plugins
can appear on one hook as several identical lines. Priority is the only thing
distinguishing them, and reflection will not help: the file is `wp-includes`.

If a line says only `closure`, the plugin registered an anonymous function and
there is no name to print. `ReflectionFunction` will give you the file, which is
usually enough to identify it:

```bash
wp eval '
global $wp_filter;
foreach ( $wp_filter["auth_cookie_expiration"]->callbacks as $prio => $cbs ) {
    foreach ( $cbs as $cb ) {
        if ( $cb["function"] instanceof Closure ) {
            $r = new ReflectionFunction( $cb["function"] );
            printf( "closure@%s defined in %s\n", $prio, $r->getFileName() );
        }
    }
}'
```

## What this plugin changed after measuring it

That `closure` at priority 10 was Better by Default, and writing this page is
what got it fixed. Two things were wrong with it, and neither was the session
length itself.

**It registered when it had nothing to say.** Both of its length defaults are
WordPress's own values, 2 days and 14. On a site that had never changed them it
was contesting the filter in order to assert the answer core already gives —
losing to two plugins that meant something by it, or worse, beating one that
did. It now registers only when those settings differ from their defaults, or
when Remember Me is switched off, which is a policy whatever the numbers say.

**It was anonymous.** The callback is a named function now, so the diagnostic
above prints `wpyeg_defaults_auth_cookie_expiration` and nobody has to run the
reflection version to find out whose it is.

**It registered at priority 10, which guaranteed losing the fights it did
enter.** This one took a second pass to see, and it arrived from outside: a
sibling plugin's source carried the argument, in a comment explaining why *it*
used 50 — that a policy clamp has to be the last word, because at 10 anything
registering at a default priority lands after it and quietly wins. That
described this plugin exactly. Abstaining from a fight worth nothing achieves
nothing if you also lose the one worth something, and for four releases a
deliberately set session length here was decided by load order. It registers at
50 now, matching both siblings — late enough to be the last word, low enough
that a site wanting the final say can still take it on purpose.

Worth noting how that was found, because it is the argument for reading the
other implementations rather than only your own: the first two problems were
visible in this plugin's own code, and the third was only visible next to
somebody else's.

None of the three makes a replacing filter compose — nothing can. They make this
plugin abstain from the fights it has no stake in, identifiable in the ones it
does, and able to win those.

Useful hooks to try: `auth_cookie_expiration`, `login_headerurl`,
`rest_authentication_errors`. Contrast them with `wp_headers` or
`user_has_cap`, where several entries are perfectly healthy.

## Why this plugin does not check for you

Two of the sibling plugins ship a Site Health check that reports this
automatically. Better by Default deliberately does not.

It registers no Site Health tests at all, and adding one would mean introducing
a whole admin surface to a plugin whose value is being small enough to read in
an afternoon. The lesson is worth more here than the feature: once you can see
the difference between an additive filter and a replacing one, you can check any
site by hand in a minute, on any hook, including hooks nobody has thought to
write a checker for.

If you want the automated version, the design worth copying is:

- **Detect by hook, not by plugin name.** A list of known rival plugins only
  ever knows yesterday's plugins.
- **Attribute callbacks by reflection** — resolve the callback to the file it
  lives in, and map that into the plugins directory. That names a plugin nobody
  has heard of exactly like a familiar one.
- **Only report the replacing kind.** Flagging `wp_headers` would flag every
  well-behaved plugin on the site, and a check that cries wolf gets ignored.
- **Do not tell the user which plugin to keep.** That is a judgement about the
  site, and a plugin that answered it would be arguing for its own retention.
- **Only report a hook you are registered on yourself.** Every default in a
  plugin like this is switchable, and a setting that is off leaves you off the
  hook — at which point another plugin holding it is not a collision, it is the
  only plugin doing the job. Reporting it anyway sends somebody to deactivate
  the plugin providing the behaviour.
- **Measure the outcome, not the culprit, where you can.** Attribution answers
  "who is overriding me", which WordPress did not record. Whether your setting
  is taking effect is a different question, and the site answers it for free
  every time it runs the filter — observe at `PHP_INT_MAX` and compare against
  what your setting asks for. It sees the plugins reflection cannot, because it
  does not care who they are. See the third attempt below.
- **Know what reflection cannot see, and resist filling the gap.** A plugin that
  turns something off by registering one of WordPress's own callbacks leaves
  nothing to attribute: `__return_false` resolves to `wp-includes` and is
  indistinguishable from core doing it. That is the ordinary way the
  disable-something category is written — Classic Editor in its default
  configuration registers
  `add_filter( 'use_block_editor_for_post_type', '__return_false', 100 )` — so a
  reflection-only check is blind to a good part of the field it is looking at. A
  clear result means nothing attributable was found, not that nothing is
  contesting the hook, and it is worth saying so wherever the result is shown.

  A sibling plugin has made three attempts on that gap, and the two it withdrew
  are the more useful half of the story. There are two obvious moves once you
  accept that reflection cannot see these plugins, and the field has now tried
  each; both came back out. The third one works, and it works by giving up on
  the premise the other two share.

  **The first was to read the source.** Require two independent things
  before naming a plugin unproven: an untraceable callback on the hook at
  runtime, and an active plugin whose *source* declares a filter on that same
  hook. It reads as conservative and is not. The runtime half is satisfied
  before any third party is involved — by the checking plugin's own
  `__return_false` registrations, and by core's, since `comments_open` always
  carries `_close_comments_for_old_post`. That leaves one weak signal doing the
  work of two, and a source mention is very weak: a plugin that declares the
  filter in a mode the site is not using matches exactly like one that is
  fighting you. Measured, two multi-feature plugins were named across five hooks
  while registering nothing on any of them. Excluding your own callbacks does
  not rescue it, because core's are indistinguishable from a third party's use
  of the same core function, and nothing recovers who called `add_filter`.
  Naming a plugin that is doing nothing, beside advice to deactivate it, is
  worse than admitting the blind spot.

  **The second was to run the filter and measure what came out.** This is the
  one to be careful about, because it is the rigorous-sounding choice: it needs
  no attribution at all, it observes the site's real behaviour instead of
  inferring from a registry, and it answers the question exactly. Clone the hook,
  keep your callback and one rival, call `apply_filters()`, compare the result
  against your own value, and you know whether that plugin actually changes the
  outcome.

  It shipped and was withdrawn a day later. Running a filter runs somebody
  else's code, and a filter callback is only pure by convention:

  - Side effects persist. A `try/finally` restores the hook registry and nothing
    else — database writes, mail, HTTP requests, globals and object state all
    survive the rollback.
  - `exit()` in a foreign callback ends the request, and nothing catches it —
    not `Throwable`, and not the `finally` that restores the hook registry, which
    does not run either. Only `register_shutdown_function` fires. So the registry
    is left in its swapped state and an admin page white-screens with no error
    attributable to anyone. (`wp_redirect()` alone returns rather than
    terminating; it is the conventional `wp_redirect(); exit;` that does this,
    and by `admin_notices` the headers have gone out anyway.)
  - The arguments are invented. Passing `null` where a `WP_Post` is contracted
    throws before the callback finishes, but may not throw before it has done
    something; passing a real user or post ID is worse, because then the foreign
    code operates on a real entity during an unrelated request.
  - The answer is not the site's answer. You-plus-one-rival is not the callback
    stack the site actually runs, so two rivals that cancel or compound each
    other are measured as neither.

  A check that reports collisions must not cause them, and no amount of care
  inside the harness makes it transactional — WordPress has no boundary to roll
  a filter call back across.

  Both failures are the same failure underneath: they try to recover information
  WordPress did not record. Nothing stores which plugin called `add_filter()`,
  and everything downstream of that absence is a guess or a gamble.

  **The third attempt stopped trying to.** Ask what the filter produced instead
  of who produced it. Register on the hook at `PHP_INT_MAX`, and when WordPress
  runs it *of its own accord*, compare the value the chain settled on against
  the value your own setting asks for. If they disagree, something on this site
  is deciding that setting and it is not you. Nothing is invoked, no arguments
  are invented, and no callback runs that was not already going to run — the
  whole distinction from the second attempt is that observing a filter WordPress
  is already running is not the same as calling one.

  It does not name anybody, and it does not need to. "Your setting is not taking
  effect" is the half an operator can act on; the remedy — look at the active
  plugins — is the same whoever it turns out to be. Measured against Disable
  XML-RPC, which registers `add_filter( 'xmlrpc_enabled', '__return_false' )`
  and is invisible to attribution by construction, the divergence is detected.

  Two things about it are easy to get wrong, and both were:

  - **The observer cannot be gated to admin requests.** `xmlrpc_enabled` never
    fires on an admin page load; it fires in `xmlrpc.php`. The hook fires where
    it fires, so the observation has to be recorded there and read back later on
    the screen that reports it. A check that only looks while somebody is on the
    settings screen sees nothing.
  - **A finding with nobody to name must not be graded as passing.** The status
    was computed from the attributable overlaps alone, so a site whose only
    finding was an untraceable override got a green badge over a paragraph
    saying a setting was not taking effect. Those two conditions coincide rather
    than being rare together: an override nobody can name is precisely the case
    with no overlap to report.

  Alongside it, the reporting that made the rest honest: three states rather
  than two. Report the hooks you can prove, stay silent where you can prove
  nothing, and add a third category — informational, not actionable — for hooks
  where something is present that you cannot judge. It says less, and everything
  it says is true.

## The uncomfortable conclusion

If two plugins are competing to set the same defaults, the answer is not to make
them cooperate. It is to run one of them.

Defaults plugins are alternatives, not complements. Two installed together give
you one plugin's behaviour, one plugin's settings screen telling the truth, and
a second screen quietly lying — and no way to tell which is which without
reading `$wp_filter`.
