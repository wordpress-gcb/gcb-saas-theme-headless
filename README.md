# GCB SaaS Theme (React variant)

Headless WordPress theme. Pairs the [gcb-lite](https://github.com/wordpress-gcb/gutenberg-control-blocks-lite)
plugin with a Next.js component server ([gcb-next-starter](https://github.com/wordpress-gcb/gcb-next-starter))
so a real WordPress install is the editorial backend for a fully React-rendered demo site.

## What this is

A demo theme that shows the **headless** half of GCB's dual-render story:

- WordPress holds the content (CPTs + typed Gutenberg blocks).
- Block definitions live in `blocks/*/block.fields.json` — typed Inspector controls.
- **There is no `render.php`.** Each block defers to the configured component server,
  which renders the matching React component server-side and returns HTML.
- The editor preview, the public WP frontend, and the Vercel demo all consume that same HTML — true 1:1.

The sibling theme [gcb-saas-theme-php](https://github.com/wordpress-gcb/gcb-saas-theme-php)
ships the same blocks with `render.php` files canonical (no Vercel dependency). Pick the variant
that matches your stack.

## Setup

Requires the [gcb-lite](https://github.com/wordpress-gcb/gutenberg-control-blocks-lite) plugin active.

1. Install + activate this theme.
2. In wp-admin go to **Settings → GCB Lite** and set the **Component server URL** to your
   Next.js frontend (e.g. `https://your-frontend.vercel.app`).
3. (Optional) Seed demo content with the script in
   [gcb-next-starter/wordpress/seed-demo.php](https://github.com/wordpress-gcb/gcb-next-starter/blob/examples/wordpress/seed-demo.php):
   ```bash
   wp eval-file wordpress/seed-demo.php
   ```
4. Visit `/` (or whichever page you pinned as front).

## Why no render.php?

gcb-lite's render endpoint dispatches based on whether each block has a `render_callback`:

- **render.php present** → run it locally (the PHP variant).
- **no render.php** → POST the block's attrs to `{component_server}/wordpress/render/[block]`,
  get HTML back (this variant).

Removing the files puts every saas-* block on the second path. Vercel does the rendering work;
WP is a thin proxy that hands back HTML.

## Blocks

Same nine blocks as the PHP variant, identical `block.fields.json` schemas:

- `gcb/saas-banner` — hero with eyebrow, heading, body, primary CTA, social links
- `gcb/saas-projects` — project grid from the `project` CPT
- `gcb/saas-testimonials` — quotes from the `testimonial` CPT
- `gcb/saas-brands` — logo strip from the `brand` CPT
- `gcb/saas-blog` — latest posts grid
- `gcb/saas-cta` — call-to-action card
- `gcb/saas-section-text` — heading + body + CTA, two-column friendly
- `gcb/saas-icon-accordion` — heading + InnerBlocks accordion
- `gcb/saas-icon-accordion-item` — single accordion row (parent: icon-accordion)

## CPTs

This theme registers three custom post types via `gcblite_register_post_fields()`:

| CPT          | Typed fields                                                   |
|--------------|----------------------------------------------------------------|
| `project`    | cover (image), live_url (url), project_category (taxonomy)     |
| `testimonial`| quote, author_name, author_role, author_image, from_label, from_logo |
| `brand`      | logo (image), website (url)                                    |

All exposed in REST as `meta.{key}` (gcb-lite enables `custom-fields` post-type support automatically).

## License

GPL-2.0-or-later — see LICENSE.
