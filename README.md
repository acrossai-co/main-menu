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

That's it. The first plugin (by jetpack-autoloader version resolution) to boot registers:

- `AcrossAI` parent menu (`add_menu_page`, slug `acrossai`)
- `Settings` submenu (`add_submenu_page`, slug `acrossai-settings`, hooked at admin_menu priority **1000** so it lands last)
- The Settings page renders a standard `<form action="options.php">` with `settings_fields()`, `do_settings_sections()`, and `submit_button()`

If 3 plugins all ship this package, you still get **one** menu and **one** Settings page. Every other copy becomes a no-op via jetpack-autoloader's version resolution.

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

## Notes for multi-plugin installs

- **Version pinning matters**: jetpack-autoloader picks the **highest** version of `acrossai-co/main-menu` across all active plugins. Bumping the version in one plugin's `composer.lock` makes that plugin's copy "win".
- **All plugins should agree on the major version** to avoid API drift across vendor copies.
- If you forget to load `vendor/autoload_packages.php`, the class won't be found and the menu silently won't appear.
- Option names must be globally unique across plugins (standard WP rule). Prefix them with your plugin slug (e.g. `plugin_a_api_key`) to avoid collisions.
