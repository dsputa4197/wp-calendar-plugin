# Developer notes

## Architecture

```
wp-calendar-plugin.php        Bootstrap: constants, activation/upgrade,
                               asset enqueue, update-checker init.
includes/
  class-wcal-ics-parser.php   Stateless .ics -> array[] parser. No WP
                               dependencies except wp_timezone().
  class-wcal-feed.php         wp_remote_get + transient cache + a
                               last-known-good fallback stored in wp_options.
  class-wcal-shortcode.php    [mass_schedule]: resolves atts, filters/sorts
                               events, groups by month, applies filters.
  class-wcal-admin.php        Settings → Mass Schedule (Settings API).
templates/schedule.php        The only file that outputs HTML. Renders the
                               month/timeline markup via a closure so the
                               same code runs both above and inside the
                               <details> fold.
assets/css/schedule.css       Everything scoped under .wcal-schedule, using
                               CSS custom properties for the two accent
                               colors (overridden inline per-site from the
                               admin color pickers via wp_add_inline_style).
vendor/plugin-update-checker/ Vendored copy of YahnisElsts/plugin-update-checker
                               (MIT). Not autoloaded via Composer on purpose —
                               required directly so this works on plain
                               shared hosting with no build step.
```

### Why no RRULE support in the ICS parser

Google Calendar can export events either as one `VEVENT` per occurrence, or
as a single `VEVENT` + `RRULE` for a true recurring series. This plugin only
handles the former. That's a deliberate scope cut, not an oversight: a
schedule that rotates between several locations (the motivating use case)
can't be expressed as a single RRULE anyway — it's naturally one event per
occurrence. If you need RRULE expansion, it'd belong in
`WCAL_ICS_Parser::parse()`; nothing else in the codebase assumes
one-event-per-occurrence, so it's an additive change.

### Caching model

`WCAL_Feed::get_events( $ics_url = '' )`:
- Empty `$ics_url` → resolves to the site-wide default
  (`get_option('wcal_ics_url')`) and uses the plain, backward-compatible
  cache keys (`WCAL_TRANSIENT_KEY`, `WCAL_FALLBACK_OPTION`).
- A non-empty `$ics_url` (from a shortcode's `ics_url` attribute) gets its
  own cache keys, suffixed with `md5($ics_url)`, so multiple calendars on
  one site cache independently without colliding.
- On a failed fetch (network error, non-200, empty body, or a parse that
  produced zero events — treated as a transient hiccup rather than a truly
  empty calendar), it serves the last successful parse from the fallback
  `wp_option` instead of showing nothing.

## Hooks

Two filters exist for customization that doesn't belong in the settings UI:

```php
// Customize an event's displayed title.
add_filter( 'wcal_event_summary', function ( $summary, $event ) {
    return $summary;
}, 10, 2 );

// Customize a month divider's label (e.g. to add a third language, or
// switch to a fully different format).
add_filter( 'wcal_month_label', function ( $label, DateTime $dt ) {
    return $label;
}, 10, 2 );
```

To add another language to the built-in "second language" dropdown, add an
entry to `WCAL_Shortcode::month_translations()` and to the `$language_labels`
array in `WCAL_Admin::render_page()`.

## Testing

There's no WordPress test harness in this repo (no PHPUnit/wp-env setup) —
it's a small plugin and the highest-value testing is against the real feed
shape, not a database. The approach used while developing this:

1. A standalone PHP script (not committed) that stubs the handful of WP
   functions actually used (`get_option`, `wp_remote_get`, `esc_html`,
   etc.) and requires the plugin's classes directly, so
   `WCAL_ICS_Parser::parse()` and `WCAL_Shortcode::render()` can run against
   a real downloaded `.ics` fixture outside of WordPress entirely.
2. `php -l` on every changed file before committing.

If you're touching the parser or the month-grouping/dedup logic in
`class-wcal-shortcode.php`, that stub-and-run approach is the fastest way to
verify against real calendar data — a full `wp-env`/PHPUnit setup would be
overkill for a plugin this size, but is a reasonable thing to add if it
grows.

## Releasing an update

Updates ship via [GitHub Releases](https://github.com/YahnisElsts/plugin-update-checker#github-integration),
checked by the vendored `plugin-update-checker` library. Sites running this
plugin will see a normal wp-admin "Update available" notice once a release
is published — no separate update server needed.

1. Bump `Version:` in the `wp-calendar-plugin.php` header (and `WCAL_VERSION`
   just below it — keep them in sync).
2. Note the change in [CHANGELOG.md](CHANGELOG.md).
3. Commit, then tag and push:
   ```bash
   git tag vX.Y.Z
   git push origin main --tags
   ```
4. Publish a GitHub Release from that tag (`gh release create vX.Y.Z --notes-from-tag`
   or via the GitHub UI). Don't mark it as a pre-release — the update
   checker ignores those.

Sites check for updates periodically in the background (standard WP
behavior via `wp_update_plugins`), or immediately if an admin visits the
Plugins page.

## Adding a new setting

Every option is registered in `WCAL_Admin::register_settings()` with an
explicit `sanitize_callback` (the Settings API silently drops unsanitized
input otherwise) and rendered as a row in `WCAL_Admin::render_page()`. If
the setting needs to survive an *existing* site's upgrade with a
non-default value (the way `wcal_month_language` defaults to `'cs'` only on
sites that were already configured pre-1.1.0), add that logic to
`wcal_maybe_upgrade()` in the main plugin file rather than to the
`register_setting()` default — the registered default only applies when
`get_option()` finds no row at all, which is indistinguishable between "new
site" and "old site that predates this option" without the version check
`wcal_maybe_upgrade()` does.
