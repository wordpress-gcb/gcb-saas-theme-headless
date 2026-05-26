<?php
/**
 * GCB SaaS Theme — theme bootstrap.
 *
 * Registers the three content types the Saas SaaS demo lives on
 * (project, testimonial, brand) and attaches typed gcb-lite fields to
 * each. Each CPT is REST-exposed so the headless React frontend can
 * query them at /wp/v2/{slug}.
 *
 * No layout / template work happens here — the theme intentionally
 * defers all rendering to the React frontend. WP's only job in this
 * setup is to author content and expose it via REST.
 *
 * @package GCB_SaaS_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme-level dependency check.
 *
 * Everything below — CPT registration, the asset enqueue, even the
 * saas-* blocks themselves (via gcb-lite's BlockLoader which scans
 * this theme's blocks/ dir) — assumes the gcb-lite plugin is active.
 * Without it, the theme would white-screen the site the next time any
 * saas-* render.php runs.
 *
 * If gcb-lite is missing, register a single admin notice telling the
 * admin to install / activate it, then bail out so we don't register
 * CPTs, enqueue scripts, or do anything else that depends on the plugin.
 *
 * The render.php files in blocks/ also each guard individually, so even
 * if the plugin is deactivated mid-flight the worst case is empty
 * blocks, not fatals. Belt + braces.
 */
if (!function_exists('gcblite_register_post_fields')) {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error"><p><strong>GCB SaaS Theme theme</strong> requires the <strong>GCB Lite</strong> plugin to be active. CPTs, fields, and the front-end block rendering all depend on it.</p></div>';
    });
    return;
}

add_action('after_setup_theme', static function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    // theme.json already handles editor styles + colour palette + typography
    // so we don't enqueue an editor stylesheet here. Anything that needs
    // a CSS variable can reference --wp--preset--color--{slug} directly.
});

/**
 * No asset enqueues here.
 *
 * The React variant defers all rendering to the gcb-next-starter Next.js
 * frontend. For each saas-* block, gcb-lite calls /wordpress/render/[block]
 * on the component-server URL (Settings → GCB Lite → Component server URL)
 * and gets back fully-styled HTML — Vercel does the Bootstrap + theme CSS
 * + React component work server-side, then ships the output as HTML to WP.
 *
 * That means we don't need:
 *  - A theme.css bundle (Vercel's HTML already has Bootstrap + theme
 *    styles inlined; gcb-lite's EditorAssets::enqueue_component_server_styles
 *    pulls the same CSS into the editor iframe + frontend automatically).
 *  - A theme.js bundle (no client-side React hydration — Vercel returns
 *    server-rendered HTML; interactive behaviour like accordion open/close
 *    is handled by tiny inline scripts the React component emits).
 *
 * If you want a self-contained WP-only frontend with no dependency on
 * Vercel, use gcb-saas-theme-php instead.
 */

/**
 * Project — a portfolio / case-study item.
 *
 * Field set mirrors the original Saas JSON fixture at
 * src/data/project/projectData.json so the React frontend's existing
 * components can consume it without changes:
 *   { id, image, title, category, excerpt, body[] }
 *
 * - title:    WP post title (no custom field needed)
 * - image:    'cover' image field
 * - category: a real WP taxonomy ('project_category') so it inherits the
 *             full taxonomy admin UI and REST surface. Authors get
 *             tag-style chip entry rather than a free-text field.
 * - excerpt:  WP native excerpt (post supports 'excerpt')
 * - body:     WP native editor (longer prose) — the original JSON
 *             splits this into paragraphs; the React frontend can do
 *             the same split server-side if it cares about per-paragraph
 *             control.
 */
add_action('init', static function () {
    register_post_type('project', [
        'label'        => __('Projects', 'gcb-saas-theme'),
        'public'       => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-portfolio',
        'supports'     => ['title', 'editor', 'excerpt', 'thumbnail'],
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'projects'],
    ]);

    register_taxonomy('project_category', 'project', [
        'label'        => __('Project Categories', 'gcb-saas-theme'),
        'public'       => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'rewrite'      => ['slug' => 'project-category'],
    ]);

    if (function_exists('gcblite_register_post_fields')) {
        gcblite_register_post_fields('project', [
            'has_body' => true, // we kept editor support for the long body
            'controls' => [
                ['type'  => 'image',
                 'attributeKey' => 'cover',
                 'label' => __('Cover image', 'gcb-saas-theme'),
                 'validation' => ['required' => true]],
                ['type'  => 'url',
                 'attributeKey' => 'live_url',
                 'label' => __('Live URL', 'gcb-saas-theme'),
                 'helpText' => __('Optional — link to the live project.', 'gcb-saas-theme')],
            ],
        ]);
    }
});

/**
 * Testimonial — a single customer quote.
 *
 * Mirrors src/data/testimonial/TestimonialData.json:
 *   { fromtext, from, description, authorimg, authorname, authordesig }
 *
 * Quote (description) is required; everything else is optional but the
 * React frontend has fallbacks for missing fields.
 */
add_action('init', static function () {
    register_post_type('testimonial', [
        'label'        => __('Testimonials', 'gcb-saas-theme'),
        'public'       => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-format-quote',
        'supports'     => ['title'], // 'editor' stripped by gcb-lite (fields-only)
    ]);

    if (function_exists('gcblite_register_post_fields')) {
        gcblite_register_post_fields('testimonial', [
            'controls' => [
                ['type'  => 'textarea',
                 'attributeKey' => 'quote',
                 'label' => __('Quote', 'gcb-saas-theme'),
                 'placeholder' => __('“ Donec metus lorem… ”', 'gcb-saas-theme'),
                 'validation' => ['required' => true, 'minLength' => 10]],
                ['type'  => 'text',
                 'attributeKey' => 'author_name',
                 'label' => __('Author name', 'gcb-saas-theme'),
                 'validation' => ['required' => true]],
                ['type'  => 'text',
                 'attributeKey' => 'author_role',
                 'label' => __('Author role', 'gcb-saas-theme')],
                ['type'  => 'image',
                 'attributeKey' => 'author_image',
                 'label' => __('Author headshot', 'gcb-saas-theme')],
                ['type'  => 'text',
                 'attributeKey' => 'from_label',
                 'label' => __('Source label', 'gcb-saas-theme'),
                 'placeholder' => __('e.g. Google, Yelp', 'gcb-saas-theme')],
                ['type'  => 'image',
                 'attributeKey' => 'from_logo',
                 'label' => __('Source logo', 'gcb-saas-theme')],
            ],
        ]);
    }
});

/**
 * Brand — a logo strip entry.
 *
 * Title = brand name. Logo = the only field we need.
 */
add_action('init', static function () {
    register_post_type('brand', [
        'label'        => __('Brands', 'gcb-saas-theme'),
        'public'       => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-awards',
        'supports'     => ['title'],
    ]);

    if (function_exists('gcblite_register_post_fields')) {
        gcblite_register_post_fields('brand', [
            'controls' => [
                ['type'  => 'image',
                 'attributeKey' => 'logo',
                 'label' => __('Logo', 'gcb-saas-theme'),
                 'validation' => ['required' => true]],
                ['type'  => 'url',
                 'attributeKey' => 'website',
                 'label' => __('Website', 'gcb-saas-theme')],
            ],
        ]);
    }
});

/**
 * Light heads-up if the gcb-lite plugin isn't active. We could harder-
 * gate by die()ing in functions.php, but that locks out admins who are
 * mid-setup. An admin notice is enough nudge.
 */
add_action('admin_notices', static function () {
    if (function_exists('gcblite_register_post_fields')) {
        return;
    }
    echo '<div class="notice notice-warning"><p>'
        . esc_html__(
            'GCB SaaS Theme needs the GCB Lite plugin to register CPT fields. Install + activate it from the Plugins screen.',
            'gcb-saas-theme'
        )
        . '</p></div>';
});
