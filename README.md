# Blocks Test

A WordPress plugin that registers two custom Gutenberg blocks — **Posts Grid** and **Posts Filter** — and seeds a complete demo data on activation. No manual content setup required.

---

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress   | 6.1+    |
| PHP         | 7.4+    |
| Node.js     | 18+     |

---

## Installation & Setup

1. Copy the `blocks-test` folder into `wp-content/plugins/`.
2. Run the build step (required for the editor blocks):
   **cd wp-content/plugins/blocks-test**
   **npm install**
   **npm run build**
3. Activate the plugin via **Plugins → Installed Plugins**.
4. On activation the plugin automatically:
   - Creates 5 categories (prefixed `blocks-test-`) and 8 tags (prefixed `blocks-test-`).
   - Generates 12 featured images locally using PHP's GD library (no outbound HTTP required).
   - Creates 12 demo posts, each assigned to multiple categories/tags.
   - Creates a demo Page titled Blocks Test Demo with both blocks already placed.
5. Visit **Pages → Blocks Test Demo** and click **View Page** to see everything in action.

---

## Blocks

### Blocks Test Posts Grid (`blocks-test/posts-grid`)

A **dynamic**  block that queries posts on every page load.
- Inspector Controls let editors adjust **columns** (2 / 3 / 4) and **posts per page** without touching code.
- Pagination is rendered as part of the block (not a separate inner block).
- On the frontend, page turns and filter changes fetch fresh data from the REST endpoint.

**Attributes:**

| Attribute | Type | Options | Description |
|---|---|-------|-------------|
| `columns` | Dropdown | 2/3/4 |  Grid column count |
| `postsPerPage` | Slider | 2-24  | Posts fetched per page |

**How it works:**
* PHP render_callback runs WP_Query on every page load and outputs the initial HTML with post data as JSON.
* When filters or pagination change, JavaScript fetches 'fresh' data from the REST endpoint (GET /wp-json/blocks-test/v1/posts) and re-renders the cards without a page reload.
* Pagination is implemented as an inner block (blocks-test/posts-pagination) locked inside the grid.

---

### Blocks Test Posts Filter (blocks-test/posts-filter)

block that renders category and tag filter chips. Can be placed anywhere on the same page as the grid — no nesting required

**Filtering logic:**
- **OR within the same filter type** — for example: selecting *Tech* and *Design* shows posts in either category.
- **AND across filter types** — for example: selecting *Tech* category AND *AI* tag shows only posts that are in *Tech* **and** tagged *AI*.

This is implemented in the REST endpoint via a `tax_query`.

---

### Blocks Test Posts Pagination (blocks-test/posts-pagination)
An inner block inside the Posts Grid.
Registered with parent: ['blocks-test/posts-grid'] so it can only be inserted inside the grid

## Architectural Decisions

### 1. Inter-Block Communication: CustomEvent bus

**Chosen approach:** The filter block dispatches a native `CustomEvent` (`bt:filter-change`) on `document`. The grid block listens for it.

// Filter dispatches:
document.dispatchEvent(new CustomEvent('bt:filter-change', {
detail: { categories: [3, 7], tags: [12], page: 1 }
}));

// Grid listens:
document.addEventListener('bt:filter-change', function(e) {
fetchPosts(e.detail);
});

* There is no hierarchical relationship between them in the DOM — they are simply two independent elements on the page.
  The document object is the only thing they share, which makes CustomEvent the natural solution.
---

### 3. Server-side rendering for both blocks

Both blocks return `null` from `save()` and delegate entirely to PHP `render_callback` functions. This means:
- No serialised markup is stored in `post_content` that could become stale.
- Category/tag lists in the filter always reflect live data.
- The editor preview (via `ServerSideRender`) is pixel-accurate to the frontend.

---

### 4. REST endpoint over `admin-ajax.php`

The paginated/filtered data fetching uses a registered REST route (`GET /blocks-test/v1/posts`) rather than `admin-ajax.php`. REST routes are:
- Structured and self-documenting.
- Authenticated with WP nonces via the `X-WP-Nonce` header.
- Cacheable by edge layers when no filters are active.

---

### 5. Demo content seeding

All seeded items are tracked in a single `wp_options` row (`bt_blocks_seeded_ids`). On deactivation, every post, page, term, and attachment is deleted. The seeder is **idempotent** — re-activating the plugin after manual deactivation → reactivation will not create duplicates (it checks the option key first).

**Unique prefix:** All term slugs use the `blocks-test-` prefix (e.g. `blocks-test-tech`, `blocks-test-ai`) to avoid collisions with any existing content.

---

## Known Limitations & Tradeoffs

1. **Outbound HTTP required for image seeding.** If `download_url()` is blocked server-side, posts are created without featured images. The plugin still works; it just won't look as polished on a restricted server.

2. **No block.json manifests.** Block registration is done programmatically in PHP and with a single bundled `editor.js`. A production plugin would use `block.json` + `@wordpress/scripts` for each block, giving automatic script/style dependency inference, i18n support, and tree-shaking. I avoided that build pipeline here to keep the submission self-contained and dependency-free.

3. **Single CustomEvent channel.** All filter blocks on a page broadcast to all grid blocks. For the common single-pair use case this is correct. A multi-pair page would need the `blockId` matching layer described above.

4. **No debounce on filter events.** Clicking chips rapidly fires multiple REST requests. A 150–300ms debounce would reduce unnecessary network traffic; omitted here to keep the JS minimal.

5. **No block transforms or patterns.** A production implementation would register a block pattern that inserts both blocks pre-configured as a pair.

---
## Featured Image Generation via GD

* Featured images are generated locally as PNG files using PHP's GD library — no HTTP request required for it.
---
## Build Step

* The editor script (assets/js/build/editor.js) is compiled from src/editor.js using @wordpress/scripts
This enables JSX syntax with automatic dependency detection via editor.asset.php
* The frontend script (assets/js/frontend.js) requires no build step
---
## File Structure

```
blocks-test/
├── blocks-test.php                     # Plugin bootstrap
├── package.json                        # Build configuration
├── src/
│   └── editor.js                       # JSX source — compiled to assets/js/build/
├── assets/
│   ├── css/
│   │   ├── frontend.css                # Frontend styles
│   │   └── editor.css                  # Editor styles
│   └── js/
│       ├── frontend.js                 # Frontend interactivity logic
│       └── build/
│           ├── editor.js               # Compiled editor script
│           └── editor.asset.php        # Auto dependency
└── includes/
    ├── class-bt-blocks.php             # Block registration + render callbacks
    ├── class-bt-rest.php               # REST endpoint: GET /blocks-test/v1/posts
    ├── class-bt-seeder.php             # Activation seeder + deactivation cleanup
```

---

