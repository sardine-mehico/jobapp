# Changelog

## version 1.9.7
- Rewrote GA4 plugin using standard Google gtag bootstrap pattern.
- Fixed encodeURIComponent bug corrupting gtag/js script URL.
- Simplified SPA navigation tracking to single router.afterEach listener.
- GA4 dataLayer queue now initialised before async script loads, ensuring no events are lost.

## version 1.9.6
- Fixed Google Analytics 4 (GA4) not tracking initial direct page loads in SPA mode.
- Added `app:mounted` hook to fire `page_view` on first load regardless of entry URL.
- Confirmed all public paths (e.g. /34fefe, /nojubn, /) are tracked; admin and login routes remain excluded.

## version 1.9.5
- Overhauled native Google Analytics 4 (GA4) configuration routing validation checks.
- Enabled GA4 telemetry universally across all generic public paths (including the root landing index layout).
- Restructured tracking blocks natively using a fallback tracking array to bypass stringent regex filtering boundaries.

## version 1.9.4
- Configured Nuxt Progressive Web App (PWA) manifest using specific 'j icon' artwork assets.
- Explicitly injected metadata link tags to enforce Favicon rendering across browser tabs and bookmark integrations.
- Remodeled Application Details interface and PDF export generation into three distinct modular structural sections (Basics, Checks, and Custom Attributes).
- Removed the standalone 'Quick checks' element.
- Added static information declaration statement strictly to Application PDFs.

## version 1.9.3
- Added Application list dynamic 'is_flagged' column with visual toggle
- Converted global Dashboard and Application list table headers straight to sentence case
- Implemented stripped-string DB checks for spaces in phone number searches
- Styled Knowledgebase rich text links completely with default explicit blue colors and underlines
- Unified Applicant list flagging row-highlight patterns

## version 1.9.2

- Extensive UI layout, padding, and block streamlining for the admin interface
- Consolidated Search and Action buttons into single rows for Jobs/Applications lists
- Replaced the textual "Active" yes/no radio buttons with a unified slider switch
- Restored confirmation logic for Delete buttons on Jobs/Knowledgebase records
- Removed obsolete helper text blurbs and simplified statistics layouts

## version 1.7

ui changes to Jobs list for Applicants

## version 1.6

invalid job links go to main page

## version 1.5

added jobs list on main page.

## version 1.4

Google Analytics 4 implemented

`NUXT_PUBLIC_GA4_ID=G-xxxxxxx` (add your google tag in the ENV and restart the docker image)

## version 1.3

UI theme changed to mobile app like UI
