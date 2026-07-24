=== Google Calendar Schedule ===
Contributors: comeniumconsulting
Tags: calendar, google calendar, events, schedule, shortcode
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Renders a compact, styled upcoming-events schedule from a public Google Calendar feed, via a shortcode.

== Description ==

Google Calendar Schedule reads a **public Google Calendar** (an `.ics` feed
URL — no API key, no OAuth) and renders it as a compact, month-grouped
agenda widget that matches your site instead of looking like an embedded
Google widget.

It's built for recurring weekly things — classes, meetings, a schedule that
rotates between a few locations, office hours — rather than a full
month-grid calendar view.

**Features**

* Renders from any public Google Calendar `.ics` feed.
* Server-side cached, with a last-known-good fallback if Google is
  temporarily unreachable.
* Compact, month-grouped layout with a "Show more" fold for anything
  beyond the first month — built with a native `<details>` element, so it
  needs no JavaScript.
* De-duplicates the address line when consecutive events share the same
  location.
* Configurable text color, accent colors, and font (a curated list of
  system/web-safe fonts — nothing extra to load).
* Optional bilingual month labels (e.g. "Září · September") in Czech,
  Spanish, Polish, or Vietnamese.
* A configurable event noun, so "Next Event" can become "Next Class",
  "Next Meeting", etc. — or be hidden entirely.
* An optional heading, and a `[calendar_schedule]` shortcode that can be
  placed anywhere, including a second calendar on the same page via its
  `ics_url` attribute.
* Self-updates from this plugin's GitHub releases.

== Installation ==

1. Upload the plugin through **Plugins → Add New Plugin → Upload Plugin**,
   or upload the folder to `wp-content/plugins/` and activate it from the
   Plugins list.
2. Go to **Settings → Calendar Schedule** and paste your calendar's public
   `.ics` URL. In Google Calendar: **Settings and sharing → Integrate
   calendar → "Public address in iCal format"** (the calendar has to be set
   to public first, under **Access permissions**).
3. Add `[calendar_schedule]` to any page or post.

== Frequently Asked Questions ==

= Does this need a Google API key? =

No. It reads the calendar's public `.ics` feed URL directly — no API key,
no OAuth, no Google Cloud project.

= Can I show more than one calendar? =

Yes — add an `ics_url` attribute to a second shortcode instance, e.g.
`[calendar_schedule ics_url="https://calendar.google.com/.../basic.ics"]`.
Only `calendar.google.com` URLs are accepted there.

= Can I change the colors and font without writing CSS? =

Yes, all under Settings → Calendar Schedule: text color, two accent
colors, and a font picker.

== Screenshots ==

1. ![The calendar widget rendered on a live site — a compact, month-grouped agenda with a date badge, event title, address, and time for each entry.](https://raw.githubusercontent.com/dsputa4197/wp-calendar-plugin/main/assets/images/calendar-view.png)
2. ![The Settings → Calendar Schedule admin page, showing the calendar URL, heading, event noun, language, font, color, and caching options.](https://raw.githubusercontent.com/dsputa4197/wp-calendar-plugin/main/assets/images/calendar-settings.png)

