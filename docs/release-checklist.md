# Tender Library Release Checklist

## First Install

- [ ] Build `tender-library-1.0.0.zip` with `./build-release.sh 1.0.0`.
- [ ] Install the ZIP through `Plugins > Add New > Upload Plugin`.
- [ ] Activate the plugin.
- [ ] Confirm there are no PHP warnings or fatal errors.
- [ ] Confirm required database tables exist.
- [ ] Confirm roles/capabilities are available.
- [ ] Confirm library pages are created or selectable.
- [ ] Visit `Settings > Permalinks` and save once.
- [ ] Confirm book, lending, reservation, profile, search, and event screens still work.

## Dashboard Update Flow

- [ ] Build `tender-library-1.0.1.zip` with `./build-release.sh 1.0.1`.
- [ ] Upload the ZIP to `https://example.com/tender-library/releases/tender-library-1.0.1.zip`.
- [ ] Upload `update.json` advertising version `1.0.1`.
- [ ] Go to `WordPress Admin > Dashboard > Updates`.
- [ ] Confirm the Tender Library update appears.
- [ ] Click `View details` and confirm plugin information/changelog renders.
- [ ] Run the update from the WordPress dashboard.
- [ ] Confirm the installed version updates to `1.0.1`.
- [ ] Confirm plugin data is preserved.
- [ ] Confirm plugin screens still work after update.
- [ ] Confirm version migrations run if the release includes one.

## Failure Checks

- [ ] Temporarily make `update.json` unavailable and confirm the admin area still works.
- [ ] Temporarily publish invalid JSON and confirm the admin area still works.
- [ ] Publish a lower remote version and confirm no update appears.
