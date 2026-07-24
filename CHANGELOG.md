# Changelog

## 1.2.1

- Added `readme.txt` (WordPress.org format), which fills out the wp-admin
  "View version details" popup with a fuller description, an FAQ, and two
  screenshots — previously that popup only showed the one-line plugin
  header description.
- Patched the vendored readme parser's HTML allowlist to permit `<img>` —
  it was silently stripping any screenshots out of that popup.

## 1.2.0

- New settings: Font (system/web-safe font list, applies to all widget
  text) and Text color (with an automatically-derived muted tone for
  secondary text). Both follow the same pattern as the existing accent
  color settings.

## 1.1.3

- Set Author to Comenium Consulting (https://www.comenium.info/).

## 1.1.2

- Fixed the update checker: it was configured to require a manually
  uploaded zip on every GitHub Release (`enableReleaseAssets()`), which
  never actually got uploaded — updates would have silently found nothing
  to install. Now uses GitHub's automatic per-tag source archive instead,
  so a plain `git tag` + release is enough.

## 1.1.1

- Renamed the plugin, admin menu, and shortcode to be generic
  (`[calendar_schedule]`, previously `[mass_schedule]` — the old tag still
  works as an alias, nothing embedded on an existing page needs to change).
- Event-noun settings now default to "Event"/"Events" instead of a specific
  example noun.
- Hardened the per-shortcode `ics_url` override to only accept
  `calendar.google.com` URLs, closing a minor SSRF surface (any post author
  could otherwise point the server at an arbitrary URL via a shortcode
  attribute, since shortcode content isn't capability-checked at render time).

## 1.1.0

- Self-updates via GitHub Releases (see [DEVELOPMENT.md](DEVELOPMENT.md#releasing-an-update)).
- New settings: accent color, heading color, second-language month names
  (None/Czech/Spanish/Polish/Vietnamese).
- New `ics_url` shortcode attribute to show a second calendar on the same site.
- The widget heading is now optional — leave it blank to render with no
  title at all.
- New settings for the event noun used in the "Next ..." flag and "Show
  more ..." toggle, since this widget isn't tied to any one kind of event.
- Generalized defaults for public/multi-site use — a fresh install no
  longer pre-fills any site-specific calendar or heading text. Existing
  installs keep their configuration unchanged via an upgrade routine.
- Compact type scale and a native `<details>`/`<summary>` "Show more" fold
  for schedules spanning more than one month — no JavaScript.
- Address line is now de-duplicated when consecutive events share a location.
- Dropped the `prefers-color-scheme: dark` variant — the widget is meant to
  sit inside sites with a single fixed light theme, so a dark card would
  clash rather than match.

## 1.0.0

- Initial version: shortcode-based rendering of a Google Calendar `.ics`
  feed, transient caching with a last-known-good fallback, and a Settings
  page for the calendar URL and display options.
