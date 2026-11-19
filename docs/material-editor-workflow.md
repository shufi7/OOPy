# Admin editor: versions, activities, and media

## Three material versions

- `resources/materi/*.php` plus `resources/materi/learning-checks.json` are the trusted, file-backed source.
- `material_contents.draft` is the admin working copy. Saving a draft does not change student pages.
- `material_contents.published` is the last explicitly published snapshot. Student pages use this snapshot as-is until another publish.

The source fingerprint is SHA-256 over the chapter's content after recursively sorting associative keys. List order remains significant. The draft and published fingerprints are recorded separately. Existing rows with a null fingerprint are shown as requiring review; saving them does not silently assign a new baseline. A sync action records the reviewed current fingerprint on the draft, and a later explicit publish copies that draft fingerprint. A legacy publication remains flagged until that new publication is made.

Open **Tinjau sumber** to compare the current draft with the file. Each available source field is a separate choice. Checked fields are copied from the server-side PHP source into the draft; unchecked fields remain as the admin wrote them. A source-only section can be added, but a section missing from the source is only flagged and never removed. Section IDs, quiz data, `live_codes`, checker configuration, and grading fields cannot be edited through this workflow. A source-sync operation updates only the draft. Review its preview and publish separately.

## `Coba pahami` activity data

Legacy activities are stored in `resources/materi/learning-checks.json` keyed by their existing section IDs. The service adds an activity to a source section only when the section has no `learning_check` key. A saved section with `learning_check: null` deliberately disables the activity. An active configuration is stored on the section:

```json
{
  "kind": "Pahami konsep",
  "prompt": "Pertanyaan singkat",
  "code": "opsional, teks biasa",
  "options": [
    {"label": "Pilihan", "correct": true, "feedback": "Umpan balik"},
    {"label": "Pilihan lain", "correct": false, "feedback": "Umpan balik"}
  ],
  "visualization": {
    "caption": "opsional",
    "layout": "compare",
    "items": [{"label": "Konsep", "code": "teks", "detail": "Penjelasan"}]
  }
}
```

The editor requires 2–6 options, exactly one correct answer, feedback for every option, and 2–6 complete visualization items when the visualization is enabled. The browser renderer provides preview, answer feedback, and retry locally; it sends no answer to quiz, grading, or progress endpoints.

## Media library

The picker exposes project assets under `public/images/materi/photos/`, `diagram/`, and `uploads/`. The form stores relative paths only and the server checks each selected path against that library again. Admin uploads accept JPEG, PNG, or WebP up to 5 MB and 6000×6000 pixels. New SVG uploads are rejected; the verified bundled SVG diagrams remain selectable. Removing a media row only detaches it from that section, leaving its file available to other sections and published snapshots.

Photo metadata requires alt text, a short caption, creator/source credit, and an HTTPS source link. License details are stored when supplied. Do not claim a license that has not been verified. Existing photo attributions are documented in [materi-visual-sources.md](materi-visual-sources.md).

## Database rollout

The migration `2026_11_20_000001_add_source_hashes_to_material_contents.php` only adds two nullable SHA-256 columns. It is not run automatically by this editor change. Review and back up the target database, then run the normal deployment migration command against the intended environment. Never use `migrate:fresh`, `db:wipe`, or a local/production database for test runs. The feature tests use the SQLite `:memory:` database configured in `phpunit.xml`.
