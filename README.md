# AcrossAI Main Menu

A reusable Composer package that registers the shared **AcrossAI** top-level admin menu and its standard submenus inside WP Admin:

- **Dashboard** — the AcrossAI landing page (parent menu)
- **Add-ons** — a fully working add-ons page (free + paid, Freemius checkout, install/activate AJAX); consumer plugins instantiate `\AcrossAI_Addon\AddonsPage` with their Freemius credentials
- **Settings** — a shared WordPress Settings API page (flat or tabbed) that any plugin extends with its own sections, fields, and options

Designed to be installed in **multiple plugins side-by-side**: `automattic/jetpack-autoloader` ensures only the highest-version copy boots, so the menu is registered exactly once regardless of how many plugins ship the package.

## Requirements

- PHP 8.1+
- WordPress 6.0+
- `automattic/jetpack-autoloader: ^5.0` in your plugin's `composer.json`
- `freemius/wordpress-sdk: ^2.0` — pulled in transitively (required by the Add-ons page)

## Installation

```bash
composer require acrossai-co/main-menu
```

Load the autoloader in your plugin (jetpack-autoloader generates `vendor/autoload_packages.php`):

```php
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload_packages.php';
```

## Quick start (consumer plugin)

In your plugin's main file:

```php
add_action( 'plugins_loaded', function () {
    new \AcrossAI_Main_Menu\SettingsPage();
} );
```

That's it. The first plugin (by jetpack-autoloader version resolution) to boot registers, in order under the AcrossAI parent menu:

- `AcrossAI` parent menu (`add_menu_page`, slug `acrossai`) — the Dashboard landing page
- `Add-ons` submenu (slug `acrossai-addons`) — registered when any plugin instantiates `\AcrossAI_Addon\AddonsPage`
- `Settings` submenu (slug `acrossai-settings`, priority **1000** so it lands last)

If 3 plugins all ship this package, you still get **one** menu and **one** of each page. Every other copy becomes a no-op via jetpack-autoloader's version resolution.

The Settings page renders a standard `<form action="options.php">` with `settings_fields()`, `do_settings_sections()`, and `submit_button()`. Feature plugins (Abilities, MCP, Model, etc.) register their own submenu pages against the `acrossai` parent slug from their own codebases — the main-menu package no longer pre-registers navigation slots for them.

## Add-ons page

The Add-ons page (formerly the standalone `acrossai-co/addons-page` package) lives at submenu slug `acrossai-addons` and ships a complete free/paid add-ons UI with Freemius checkout, one-click install, and activate/deactivate AJAX.

Each consumer plugin instantiates `\AcrossAI_Addon\AddonsPage` once with its own Freemius credentials. The first plugin to boot registers the Add-ons submenu under the `acrossai` parent; subsequent plugins still initialize their Freemius product and contribute add-ons to the shared registry but skip re-registering the nav entry.

```php
new \AcrossAI_Addon\AddonsPage(
    __FILE__,
    [
        'fs_product_id' => '12345',      // your Freemius product ID
        'fs_public_key' => 'pk_abc123',  // your Freemius public key
        'fs_slug'       => 'your-plugin', // optional — defaults to 'acrossai-addons'
        'fs_menu'       => [             // optional — override which Freemius auto-submenus surface
            'account' => true,           // Account settings — defaults to true
            'contact' => true,           // Contact Us     — defaults to true
            'support' => true,           // wp.org Support — defaults to true
            'upgrade' => false,          // Upgrade        — defaults to false (Add-ons page owns this UX)
            'pricing' => false,          // Pricing        — defaults to false (Add-ons page owns this UX)
            'addons'  => false,          // Add-ons        — defaults to false (would duplicate the vendor submenu)
        ],
    ]
);
```

The `fs_menu` overrides array is merged over `FreemiusInitializer::DEFAULT_MENU` — omit any key to keep its default. Pass `false` to hide a submenu, `true` to show it. The `slug` key is derived from the parent menu (`$parent_slug` constructor arg) and cannot be overridden here.

Register a free product in your [Freemius dashboard](https://dashboard.freemius.com) (WordPress Plugin, Analytics only, free plan ON) and grab its **Product ID** and **Public Key**. Each plugin gets its own Freemius product so activations and analytics are tracked separately per plugin.

A third positional `$parent_slug` argument is supported for legacy setups that want the page under a different parent menu; omit it to land under `acrossai`.

The package handles:

- Registering the **Add-ons submenu** under the `acrossai` parent
- Rendering the add-ons grid (free + paid)
- Installing free add-ons silently (WordPress.org API or GitHub ZIP)
- Paid add-on checkout via the Freemius JS popup
- Opt-in / "Login & Connect" flow
- Shared opt-in across consumer plugins

The `acrossai-addons-page` text domain and CSS class prefix are preserved from the original package so existing translations and stylesheet overrides continue to work. See [docs/upgrade-notes.md](docs/upgrade-notes.md) and [docs/readme-template.txt](docs/readme-template.txt) for the wordpress.org `readme.txt` blocks (`== Installation ==`, `== External Services ==`, `== Privacy Policy ==`) the Add-ons page requires.

### Known limitations

- **Multisite**: not tested or supported. Works on per-site dashboards but network-activated behaviour is undefined.
- **Uninstall edge case**: if two plugins use the Add-ons page and one is *uninstalled* (not just deactivated) while the other is active, Freemius may clear shared opt-in state. Recovery: the user clicks "Login / Connect" on the remaining plugin's Add-ons page.
- **Non-plugin contexts**: instantiating outside a WordPress plugin (theme, mu-plugin, CLI) throws `\RuntimeException`.

## Adding settings from your plugin

The shared identifier is `acrossai-settings` — it is **both** the page slug (for `add_settings_section` / `add_settings_field`) **and** the option_group (for `register_setting` / `settings_fields`). Use it as the target everywhere.

```php
add_action( 'admin_init', function () {
    // 1. Register each option you want saved.
    register_setting(
        'acrossai-settings',          // option_group — must match the page slug
        'plugin_a_api_key',           // option_name
        [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]
    );

    // 2. Add a section to the shared page.
    add_settings_section(
        'plugin_a_section',
        __( 'Plugin A', 'plugin-a' ),
        function () {
            echo '<p>' . esc_html__( 'Plugin A configuration.', 'plugin-a' ) . '</p>';
        },
        'acrossai-settings'           // page slug
    );

    // 3. Add fields to that section.
    add_settings_field(
        'plugin_a_api_key',
        __( 'API Key', 'plugin-a' ),
        function () {
            printf(
                '<input type="text" name="plugin_a_api_key" value="%s" class="regular-text" />',
                esc_attr( get_option( 'plugin_a_api_key', '' ) )
            );
        },
        'acrossai-settings',          // page slug
        'plugin_a_section'            // section id from step 2
    );
} );
```

That's the entire extension. No JS, no enqueue, no PHP routing — just standard WP hooks. The Settings page will display Plugin A's section automatically.

## Tabs

The Settings page has two rendering modes:

- **Flat** — no plugin hooks the `acrossai_settings_tabs` filter. The page renders a single form (today's behavior); use `'acrossai-settings'` as the page slug for `add_settings_section` / `add_settings_field`.
- **Tabbed** — any plugin registers at least one tab. The page renders a `nav-tab-wrapper` bar; each tab has its own form and Save button. Sections must target a tab's page slug (see below).

### Registering a tab

Hook the `acrossai_settings_tabs` filter and append a tab entry:

```php
add_filter( 'acrossai_settings_tabs', function ( $tabs ) {
    $tabs[] = [
        'slug'     => 'providers',
        'label'    => __( 'Providers', 'plugin-a' ),
        'priority' => 10,
    ];
    return $tabs;
} );
```

Tab entry shape:

| Key | Required | Type | Default | Notes |
|---|---|---|---|---|
| `slug` | yes | string | — | Lowercase `[a-z0-9_-]` (passed through `sanitize_key`). Used in the `?tab=` URL and the per-tab page slug. |
| `label` | yes | string | — | Already-translated label. Rendered with `esc_html`. |
| `priority` | no | int | `10` | Lower = earlier. Ties broken by registration order. |
| `capability` | no | string | `'manage_options'` | Per-tab capability gate. Tabs the user can't satisfy are hidden. |

Duplicate slugs: first registration wins. With `WP_DEBUG` on, subsequent duplicates trigger `_doing_it_wrong()`.

### Adding sections/fields to a tab

Get the shared renderer and call its `tab_page_slug( 'your-tab-slug' )` instance method to obtain the `$page` argument:

```php
add_action( 'admin_init', function () {
    $renderer = \AcrossAI_Main_Menu\SettingsPage::get_settings_renderer();
    if ( ! $renderer ) {
        return; // main-menu package not booted in this request
    }
    $page = $renderer->tab_page_slug( 'providers' );

    register_setting(
        $page,                         // option_group — tab-scoped; each tab has its own whitelist (0.0.13+)
        'plugin_a_api_key',
        [ 'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ]
    );

    add_settings_section(
        'plugin_a_providers',
        __( 'Providers', 'plugin-a' ),
        function () { echo '<p>' . esc_html__( 'Configure providers.', 'plugin-a' ) . '</p>'; },
        $page
    );

    add_settings_field(
        'plugin_a_api_key',
        __( 'API Key', 'plugin-a' ),
        function () {
            printf(
                '<input type="text" name="plugin_a_api_key" value="%s" class="regular-text" />',
                esc_attr( get_option( 'plugin_a_api_key', '' ) )
            );
        },
        $page,
        'plugin_a_providers'
    );
} );
```

Notes:

- **`option_group` is tab-scoped in 0.0.13+.** Each tab's form posts with `option_page = <tab-scoped slug>`, so WordPress walks only that tab's whitelist on save. This prevents the cross-tab option-clobber bug that shared-`acrossai-settings` had in 0.0.12 (saving one tab silently wiped other tabs' options). See "Migrating from 0.0.12" below.
- **One Save button per tab.** Switching tabs without saving discards in-progress changes (standard WP admin pattern).
- **Active tab persistence.** The active tab survives the Save round-trip via `_wp_http_referer` — no extra wiring needed.
- **Backward compatibility.** If no plugin hooks `acrossai_settings_tabs`, the flat-page example earlier in this README keeps working unchanged. Once any plugin registers a tab, sections still attached to the bare `'acrossai-settings'` slug are not rendered — migrate them under a tab.

### Migrating from 0.0.12 (breaking change)

0.0.13 fixes a cross-tab option-clobber bug by making each tab's form use its own tab-scoped `option_page` / `option_group`. If your consumer plugin registered settings against the shared `'acrossai-settings'` in tabbed mode, its Save will silently no-op (WP's `options.php` handler rejects the write because the option is not in the tab-scoped whitelist).

**One-line migration** per `register_setting()` call:

```php
// 0.0.12
register_setting( 'acrossai-settings', 'plugin_a_api_key', [ ... ] );

// 0.0.13+
$page = $renderer->tab_page_slug( 'providers' );
register_setting( $page, 'plugin_a_api_key', [ ... ] );
```

No other code changes are required. `add_settings_section()` / `add_settings_field()` were already using `$page = tab_page_slug(...)` — those stay the same.

### Reusing the tabbed pattern on another page

The Settings page is one instance of a generic pattern. To add a second tabbed admin page (e.g. a "Tools" page) without re-implementing tab rendering, subclass `TabbedPageRenderer` and pin two things — the WP page slug and a short key that becomes the tabs filter name:

```php
use AcrossAI_Main_Menu\TabbedPageRenderer;

final class ToolsPageRenderer extends TabbedPageRenderer {
    protected function get_page_slug(): string { return 'acrossai-tools'; }
    protected function get_tabs_key(): string  { return 'tools'; }
}

// Register the submenu and point it at the renderer:
add_action( 'admin_menu', function () {
    $renderer = new ToolsPageRenderer();
    add_submenu_page(
        \AcrossAI_Main_Menu\SettingsPage::PARENT_SLUG,
        __( 'Tools', 'my-plugin' ),
        __( 'Tools', 'my-plugin' ),
        'manage_options',
        'acrossai-tools',
        [ $renderer, 'render' ]
    );
} );
```

Third-party plugins register tabs on the new page by hooking `acrossai_tools_tabs` — same entry shape (`slug`, `label`, `priority`, `capability`) as `acrossai_settings_tabs`. They register sections against `$renderer->tab_page_slug( 'my-tab' )` exactly as with the Settings page.

The filter name is always `"acrossai_{$key}_tabs"`, so each page gets its own isolated tab list. Rendering, capability gating, active-tab detection, and the per-tab form + Save button are all handled by `TabbedPageRenderer` — subclasses add no rendering code.

### Using the tabs base without a Settings page

The tab plumbing (filter, list, active tab, nav rendering) lives in `\AcrossAI_Main_Menu\Tabs`. `TabbedPageRenderer` extends `Tabs` and layers the Settings-API form + Save button on top. If you want the tab bar but not the Settings-API form — a custom admin screen, a meta box, a dashboard widget, a Tools submenu that renders its own body — extend `Tabs` directly:

```php
use AcrossAI_Main_Menu\Tabs;

final class ReportTabs extends Tabs {
    protected function get_tabs_key(): string { return 'reports'; }
}

// Third-party plugins contribute tabs via `acrossai_reports_tabs`.

// Anywhere in the admin (custom `add_menu_page` callback, meta box, …):
$tabs_ui = new ReportTabs();
$tabs    = $tabs_ui->get_tabs();
if ( ! empty( $tabs ) ) {
    $active = $tabs_ui->get_active_tab( $tabs );
    $tabs_ui->render_tab_nav( $tabs, $active['slug'] );
    // Render the body for $active['slug'] however you like — no form required.
}
```

The default tab-URL builder is `add_query_arg( 'tab', $slug )` against the current request URL, so tab links stay on whatever screen you're rendering. Two extension points cover non-standard contexts:

- **Override `default_tab_url( $tab_slug )`** on your subclass to emit a different URL scheme (e.g. an admin submenu that needs `admin.php?page=…&tab=…`, or a REST-driven screen with a hash fragment).
- **Pass a `$url_for` callable to `render_tab_nav()`** per invocation for one-off tweaks — e.g. `$tabs_ui->render_tab_nav( $tabs, $active['slug'], fn( $slug ) => my_url( $slug ) )`.

For non-URL active-tab sources (block attribute, POST body, session), override `protected function get_requested_slug(): string` to read from your source instead of `$_GET['tab']`.

## How the page composes across plugins

`do_settings_sections( 'acrossai-settings' )` iterates every section registered against that page slug, in registration order. So:

- 0 active plugins registering sections → empty page (just the Save button)
- 1 plugin → its section is shown
- 2+ plugins → sections are stacked top-to-bottom in registration order

All registered options share **one** Save button. A single POST to `options.php` saves every option whitelisted via `register_setting( 'acrossai-settings', ... )` regardless of which plugin registered it.

### Controlling section order

WP renders sections in the order they're registered. If you need deterministic ordering, hook your `admin_init` callback with an explicit priority:

```php
add_action( 'admin_init', 'plugin_a_register_settings', 10 );  // first
add_action( 'admin_init', 'plugin_b_register_settings', 20 );  // second
add_action( 'admin_init', 'plugin_c_register_settings', 30 );  // third
```

## Public PHP API

| Symbol | Purpose |
|---|---|
| `\AcrossAI_Main_Menu\SettingsPage` | Entrypoint. Construct once per request: `new SettingsPage();`. Safe to construct from every consumer plugin — jetpack-autoloader picks one copy to boot. |
| `\AcrossAI_Main_Menu\SettingsPage::PARENT_SLUG` | `'acrossai'` — the parent menu slug. |
| `\AcrossAI_Main_Menu\SettingsPage::SETTINGS_SLUG` | `'acrossai-settings'` — the Settings submenu slug, page slug, and option_group. |
| `\AcrossAI_Main_Menu\SettingsPage::get_settings_renderer()` | Returns the shared `SettingsPageRenderer` instance (or `null` if the main-menu package has not booted yet in this request). Use it to call `->tab_page_slug( 'your-tab' )`. |
| `\AcrossAI_Main_Menu\Tabs` | Abstract base for tab bars — filter dispatch (`acrossai_{key}_tabs`), normalization, capability gating, active-tab resolution, and a `render_tab_nav()` helper. Extend this directly for any UI that needs a tab bar *without* the Settings-API form/Save flow (custom admin screens, meta boxes, dashboard widgets, Tools submenus). |
| `\AcrossAI_Main_Menu\TabbedPageRenderer` | Abstract base for tabbed WP admin pages. Extends `Tabs`. Subclass and implement `get_page_slug()` + `get_tabs_key()` to add a second tabbed page — the filter, rendering, capability gating, and per-tab form/Save button are all handled by the base class. |
| `\AcrossAI_Main_Menu\SettingsPageRenderer` | Concrete subclass of `TabbedPageRenderer` used by the Settings page. Exposes `tab_page_slug( string $tab_slug )` returning e.g. `'acrossai-settings-providers'`. |
| `\AcrossAI_Addon\AddonsPage` | Entrypoint for the Add-ons page. Construct once per consumer plugin with its Freemius credentials: `new AddonsPage( __FILE__, [ 'fs_product_id' => '…', 'fs_public_key' => '…' ] );`. Multiple consumer plugins are supported — first to register wins the nav slot. |
| `\AcrossAI_Addon\MenuRegistrar::SUBMENU_SLUG` | `'acrossai-addons'` — the Add-ons submenu slug. |

## Notes for multi-plugin installs

- **Version pinning matters**: jetpack-autoloader picks the **highest** version of `acrossai-co/main-menu` across all active plugins. Bumping the version in one plugin's `composer.lock` makes that plugin's copy "win".
- **All plugins should agree on the major version** to avoid API drift across vendor copies.
- If you forget to load `vendor/autoload_packages.php`, the class won't be found and the menu silently won't appear.
- Option names must be globally unique across plugins (standard WP rule). Prefix them with your plugin slug (e.g. `plugin_a_api_key`) to avoid collisions.
