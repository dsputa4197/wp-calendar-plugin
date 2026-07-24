# Google Calendar Schedule

A small WordPress plugin that renders an upcoming-events schedule from a
public Google Calendar as a styled, compact agenda widget — recurring
weekly things (classes, meetings, a touring/rotating schedule, office
hours, whatever), not a full month-grid calendar.

## Why not just embed Google Calendar?

Google's own embed/widget code is functional but generic-looking and hard
to restyle to match a theme. This plugin fetches the same public calendar
feed (`.ics` URL) server-side, parses it, and renders it with the site's own
markup and CSS — while still linking out to the calendar so visitors can
subscribe in their own calendar app.

## Features

- Renders from any public Google Calendar `.ics` feed — no Google API key,
  no OAuth.
- Server-side cached (`wp_transient`) so the feed isn't fetched on every
  page load; falls back to the last successful fetch if Google is
  unreachable.
- Compact, month-grouped agenda layout with a native `<details>`/`<summary>`
  "Show more" fold — no JavaScript required.
- De-duplicates the address line when consecutive events share the same
  location (useful when a schedule rotates through the same few venues).
- Configurable accent colors, an optional second-language month label
  (e.g. "Září · September"), a configurable event noun (so "Next Event" can
  become "Next Class", "Next Meeting", etc.), and an optional heading — and
  a `[calendar_schedule]` shortcode that can be dropped anywhere.
- Self-updates via GitHub Releases — see [DEVELOPMENT.md](DEVELOPMENT.md).

## Installation

1. Download the latest release zip from the
   [Releases page](https://github.com/dsputa4197/wp-calendar-plugin/releases),
   or clone/download this repo.
2. In wp-admin: **Plugins → Add New Plugin → Upload Plugin**, choose the
   zip, install, and activate. (Or upload the folder to
   `wp-content/plugins/` via FTP/SFTP and activate from the Plugins list.)
3. Go to **Settings → Calendar Schedule** and paste your calendar's public
   `.ics` URL. In Google Calendar: **Settings and sharing → Integrate
   calendar → "Public address in iCal format"** (the calendar must be set
   to public first, under **Access permissions**).
4. Add `[calendar_schedule]` to any page or post.

## Usage

```
[calendar_schedule]
```

Shortcode attributes (all optional — each falls back to the matching
Settings → Calendar Schedule value):

| Attribute        | Default                  | Description                                                        |
|-------------------|--------------------------|----------------------------------------------------------------------|
| `title`           | Settings → Heading text  | Widget heading. Pass `title=""` to render with no heading at all.  |
| `months`          | Settings → Months to fetch ahead | How many months out to fetch/consider.                     |
| `limit`           | Settings → Max dates to show | Hard cap on events rendered, across all fetched months.            |
| `initial_months`  | Settings → Months shown before "Show more" | Month buckets rendered expanded by default. |
| `ics_url`         | Settings → Google Calendar ICS URL | Show a *different* calendar than the site default — lets one page display two schedules. Must be a `calendar.google.com` URL. |

Example, showing a second calendar further down the same page:

```
[calendar_schedule title="Downtown location" ics_url="https://calendar.google.com/calendar/ical/xyz/public/basic.ics"]
```

## Configuration

All of this lives under **Settings → Calendar Schedule**:

- **Google Calendar ICS URL** — the feed to read from. Required; the widget
  shows a "not configured" notice (visible to admins only) until this is set.
- **Heading text** — the widget's title. Leave blank to render with no heading.
- **Event noun (singular/plural)** — drives the "Next ..." flag and "Show 3
  more ..." toggle. Defaults to "Event"/"Events"; set to whatever the
  calendar actually is ("Class"/"Classes", "Meeting"/"Meetings", ...), or
  blank the singular to hide the "Next ..." flag entirely.
- **Second language for month names** — None / Czech / Spanish / Polish /
  Vietnamese. Renders month dividers like "Září · September" instead of
  just "September".
- **Font** — a fixed list of system/web-safe fonts (no external font files
  to load). Applies to all text in the widget.
- **Text color** — event titles and addresses. A muted secondary tone is
  derived automatically for less prominent text (month labels, addresses).
- **Accent color** / **Heading color** — restyle without touching CSS. The
  pale "wash" background behind the time pill is derived automatically from
  the accent color.
- **Months shown before "Show more"** — the schedule starts collapsed to
  this many month-groups; the rest fold behind a `<details>` toggle.
- **Months to fetch ahead**, **Max dates to show** — bounds on how far
  out and how much gets pulled from the feed at all.
- **Cache duration (hours)** — how long a successful fetch is cached before
  the feed is checked again. A **Refresh now** button forces an immediate
  re-fetch of the default calendar.

Deeper customization (extra colors, a different date/time format, a new
second-language month set) is meant to happen via the filters described in
[DEVELOPMENT.md](DEVELOPMENT.md#hooks) or by overriding the CSS custom
properties on `.wcal-schedule` — not by growing the settings screen further.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
