# When two plugins set the same default

Better by Default is one of several plugins that flip WordPress defaults. Sooner
or later somebody installs two of them, or installs this alongside a security
suite that quietly does half the same job.

This is what actually happens, measured rather than guessed — three sibling
plugins activated together on one throwaway install, all three doing
substantially the same work.

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

## The uncomfortable conclusion

If two plugins are competing to set the same defaults, the answer is not to make
them cooperate. It is to run one of them.

Defaults plugins are alternatives, not complements. Two installed together give
you one plugin's behaviour, one plugin's settings screen telling the truth, and
a second screen quietly lying — and no way to tell which is which without
reading `$wp_filter`.
