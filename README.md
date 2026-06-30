# AcrossAI Main Menu

A reusable Composer package that registers the shared **AcrossAI** top-level admin menu and a **Settings** submenu inside WP Admin. The Settings page is rendered with the **WordPress Settings API** — consumer plugins extend it by registering their own sections, fields, and options against the shared page slug.

Designed to be installed in **multiple plugins side-by-side**: `automattic/jetpack-autoloader` ensures only the highest-version copy boots, so the menu is registered exactly once regardless of how many plugins ship the package.

## Requirements

- PHP 8.1+
- WordPress 6.0+
- `automattic/jetpack-autoloader: ^5.0` in your plugin's `composer.json`

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
- `Abilities Manager` submenu (slug `acrossai-abilities`, priority **100**)
- `MCP Manager` submenu (slug `acrossai-mcp`, priority **100**)
- `Model Manager` submenu (slug `acrossai-models`, priority **100**)
- `Settings` submenu (slug `acrossai-settings`, priority **1000** so it lands last)

If 3 plugins all ship this package, you still get **one** menu and **one** of each page. Every other copy becomes a no-op via jetpack-autoloader's version resolution.

The three manager pages render a placeholder pointing at the relevant feature plugin until that plugin replaces the content (see "Manager pages" below). The Settings page renders a standard `<form action="options.php">` with `settings_fields()`, `do_settings_sections()`, and `submit_button()`.

## Manager pages

Three submenu pages are pre-registered as navigation slots for the AcrossAI feature plugins:

| Slug | Title | Owner plugin | Render action |
|---|---|---|---|
| `acrossai-abilities` | Abilities Manager | `acrossai-co/acrossai-abilities-manager` | `acrossai_render_abilities_page` |
| `acrossai-mcp` | MCP Manager | `acrossai-co/acrossai-mcp-manager` | `acrossai_render_mcp_page` |
| `acrossai-models` | Model Manager | `acrossai-co/acrossai-model-manager` | `acrossai_render_models_page` |

When a user opens one of these pages, the package fires the matching render action. The owner plugin hooks that action and prints its UI:

```php
add_action( 'acrossai_render_abilities_page', function () {
    echo '<div class="wrap"><h1>' . esc_html__( 'Abilities', 'acrossai-abilities-manager' ) . '</h1>';
    // …render the table, forms, etc.
    echo '</div>';
} );
```

If no callback is attached, the page renders a placeholder explaining that the feature plugin is not active, with **Install from WordPress.org** and **View on GitHub** buttons. This means the AcrossAI menu is consistent across installs regardless of which feature plugins are currently active.

The render action is the entire contract — no further wiring is required. The capability check (`manage_options`) and the page chrome (`.wrap`) are the consumer's responsibility inside the callback.

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

Use `SettingsPage::tab_page_slug( 'your-tab-slug' )` as the `$page` argument:

```php
add_action( 'admin_init', function () {
    $page = \AcrossAI_Main_Menu\SettingsPage::tab_page_slug( 'providers' );

    register_setting(
        'acrossai-settings',           // option_group — always the shared slug, regardless of tab
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

- **`option_group` stays `acrossai-settings` in tabbed mode.** The shared option_group is what makes `register_setting`, the nonce, and the save flow work — regardless of which tab a section lives under. Only the `$page` argument changes per tab.
- **One Save button per tab.** Switching tabs without saving discards in-progress changes (standard WP admin pattern).
- **Active tab persistence.** The active tab survives the Save round-trip via `_wp_http_referer` — no extra wiring needed.
- **Backward compatibility.** If no plugin hooks `acrossai_settings_tabs`, the flat-page example earlier in this README keeps working unchanged. Once any plugin registers a tab, sections still attached to the bare `'acrossai-settings'` slug are not rendered — migrate them under a tab.

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
| `\AcrossAI_Main_Menu\SettingsPage::ABILITIES_SLUG` | `'acrossai-abilities'` — the Abilities Manager submenu slug. |
| `\AcrossAI_Main_Menu\SettingsPage::MCP_SLUG` | `'acrossai-mcp'` — the MCP Manager submenu slug. |
| `\AcrossAI_Main_Menu\SettingsPage::MODELS_SLUG` | `'acrossai-models'` — the Model Manager submenu slug. |
| `\AcrossAI_Main_Menu\SettingsPage::tab_page_slug( string $tab_slug )` | Returns the per-tab page slug (e.g. `'acrossai-settings-providers'`) to pass to `add_settings_section` / `add_settings_field` / `do_settings_sections` in tabbed mode. |

## Notes for multi-plugin installs

- **Version pinning matters**: jetpack-autoloader picks the **highest** version of `acrossai-co/main-menu` across all active plugins. Bumping the version in one plugin's `composer.lock` makes that plugin's copy "win".
- **All plugins should agree on the major version** to avoid API drift across vendor copies.
- If you forget to load `vendor/autoload_packages.php`, the class won't be found and the menu silently won't appear.
- Option names must be globally unique across plugins (standard WP rule). Prefix them with your plugin slug (e.g. `plugin_a_api_key`) to avoid collisions.
