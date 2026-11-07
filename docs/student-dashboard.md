# Student dashboard

The student dashboard organizes the next application action, current workflow stage, recent applications, profile readiness, deadlines, updates, awards, and open scholarships. Detailed form and document checklists expand from the application card and open automatically when the application requires corrections. Existing correction links, award status explanations, and appeal actions are preserved. GWA/GPA remains removed.

## Media

- **Campus video:** [Students walking in a university, Mixkit](https://mixkit.co/free-stock-video/students-walking-in-a-university-4519/), offered under the [Mixkit Stock Video Free License](https://mixkit.co/license/#videoFree). This is illustrative stock footage, not footage of the ADFC campus.
- Video source: `https://assets.mixkit.co/videos/4519/4519-720.mp4`; local file: `public/videos/dashboard/campus-walk.mp4` (6,089,282 bytes).
- Poster source: `https://assets.mixkit.co/videos/4519/4519-thumb-720-0.jpg`; local file: `public/images/dashboard/campus-walk.jpg` (101,991 bytes).
- Scholarship card photos reuse the downloaded Unsplash images documented in `docs/local-assets.json`.

All dashboard media is served locally. The video is decorative, muted, and looped, with a visible play/pause control. Reduced-motion and data-saving preferences prevent automatic video loading. The video pauses when offscreen or when the tab is hidden. The poster remains available if playback fails. No essential content depends on motion or JavaScript.

## Verification

- 30 relevant Laravel tests passed (256 assertions), covering dashboards, authentication, scholarship appeals, and removal of academic average fields.
- Browser preview passed at 1440, 1024, 820, 390, and 360 pixel widths with no page overflow.
- Verified video playback, pause/resume, reduced-motion poster fallback, checklist expansion, mobile navigation, 11 dashboard links, and scholarship photo loading. No JavaScript errors, failed HTTP responses, or external page requests were observed.
- Screenshots and the browser verification report are saved under `storage/app/student-dashboard-preview/`.

To repeat the preview using fictional accounts in an isolated SQLite database:

```powershell
php tools/prepare-professional-preview.php
php -S 127.0.0.1:8017 -t public tools/professional-preview-router.php
# In another terminal:
node tools/preview-student-dashboard.cjs
```

Dashboard presentation is scoped to `public/css/student-dashboard.css`, `public/js/student-dashboard.js`, and `resources/views/dashboards/student.blade.php`. The administrator and staff dashboards keep their existing layouts.
