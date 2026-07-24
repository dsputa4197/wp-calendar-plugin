# Changelog

## 1.1.0

- Self-updates via GitHub Releases (see [DEVELOPMENT.md](DEVELOPMENT.md#releasing-an-update)).
- New settings: accent color, heading color, second-language month names
  (None/Czech/Spanish/Polish/Vietnamese).
- New `ics_url` shortcode attribute to show a second calendar on the same site.
- The widget heading is now optional — leave it blank to render with no
  title at all.
- New settings for the event noun ("Mass"/"Masses" by default) used in the
  "Next ..." flag and "Show more ..." toggle, since this calendar isn't
  always for Masses.
- Generalized defaults for public/multi-site use — a fresh install no
  longer pre-fills a specific parish's calendar or Czech heading text.
  Existing installs keep their configuration unchanged.
- Compact type scale and a native `<details>`/`<summary>` "Show more" fold
  for schedules spanning more than one month — no JavaScript.
- Address line is now de-duplicated when consecutive events share a location.
- Dropped the `prefers-color-scheme: dark` variant — the widget is meant to
  sit inside sites with a single fixed light theme, so a dark card would
  clash rather than match.

## 1.0.0

- Initial release: `[mass_schedule]` shortcode, ICS parsing, transient
  caching with a last-known-good fallback, Settings → Mass Schedule.
