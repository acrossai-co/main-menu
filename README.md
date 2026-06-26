# AcrossAI Main Menu

A reusable Composer package that registers the shared **AcrossAI** top-level admin menu and a **Settings** submenu inside WP Admin. The Settings page renders a React mount point with a `@wordpress/components` SlotFill — consumer plugins extend it by registering Fills from their own bundles.

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
    new \AcrossAI_Main_Menu\SettingsPage( __FILE__ );
} );
```

That's it. The first plugin (by jetpack-autoloader version resolution) to boot registers:

- `AcrossAI` parent menu (`add_menu_page`, slug `acrossai`)
- `Settings` submenu (`add_submenu_page`, slug `acrossai-settings`, hooked at admin_menu priority **1000** so it lands last)
- The Settings page renders `<div id="acrossai-settings-root"></div>` and enqueues the React host bundle (`acrossai-settings-host`)

If 3 plugins all ship this package, you still get **one** menu and **one** host bundle — every other copy becomes a no-op.

## Extending the Settings page from another plugin

The Settings page is a React SlotFill host. To add your own panel, ship a JS bundle from your plugin that registers a `<Fill name="AcrossAISettingsTab">`.

### 1. Build a Fill bundle in your plugin

```bash
npm install --save-dev @wordpress/scripts
```

`src/js/settings-fill.js`:

```js
import { registerPlugin } from '@wordpress/plugins';
import { Fill } from '@wordpress/components';

function PluginAPanel() {
    return (
        <div>
            <h2>Plugin A Settings</h2>
            { /* your form, API calls, etc. */ }
        </div>
    );
}

registerPlugin( 'plugin-a-settings', {
    render: () => (
        <Fill
            name="AcrossAISettingsTab"
            tab={ { name: 'plugin-a', title: 'Plugin A', order: 10 } }
        >
            <PluginAPanel />
        </Fill>
    ),
} );
```

`webpack.config.js`:

```js
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
    ...defaultConfig,
    entry: { 'settings-fill': path.resolve( __dirname, 'src/js/settings-fill.js' ) },
    output: {
        ...defaultConfig.output,
        path: path.resolve( __dirname, 'build' ),
        filename: '[name].js',
    },
};
```

```bash
npm run build
```

### 2. Enqueue it on the Settings screen

```php
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    // The Settings screen hook suffix — AcrossAI is the parent menu title.
    if ( 'acrossai_page_acrossai-settings' !== $hook ) {
        return;
    }

    $asset = include plugin_dir_path( __FILE__ ) . 'build/settings-fill.asset.php';

    wp_enqueue_script(
        'plugin-a-settings-fill',
        plugins_url( 'build/settings-fill.js', __FILE__ ),
        array_merge(
            $asset['dependencies'],
            // Depend on the host bundle so it loads first and the slot is mounted.
            [ \AcrossAI_Main_Menu\Assets::HOST_HANDLE ]
        ),
        $asset['version'],
        true
    );
} );
```

That's the whole extension. No PHP routing, no shared state, no order coordination.

## How the page adapts to the number of plugins

The host bundle (`src/assets/js/settings-host.js`) inspects how many Fills register and switches its UI:

| Active Fills | UI |
|---|---|
| 0 | Placeholder text ("No settings panels are installed yet.") |
| 1 | Renders the Fill directly, no tab strip |
| 2+ | `<TabPanel>` sorted by the optional `tab.order` prop (default `100`) |

So 1, 2, or 3 plugins "just work" — no count-aware code anywhere.

## Fill API

Each Fill must target slot name `AcrossAISettingsTab` and pass a `tab` prop:

```js
<Fill
    name="AcrossAISettingsTab"
    tab={ {
        name: 'plugin-a',  // required — unique slug, used as TabPanel tab name
        title: 'Plugin A', // required — visible tab label
        order: 10,         // optional — lower numbers appear first; default 100
    } }
>
    <YourPanelComponent />
</Fill>
```

The host uses `name` for React keys and tab switching, so it **must be unique** across all active plugins. Prefix it with your plugin slug to avoid collisions.

## Public PHP API

| Symbol | Purpose |
|---|---|
| `\AcrossAI_Main_Menu\SettingsPage` | Entrypoint. Construct once per request with `__FILE__`. Safe to construct from every consumer plugin — the singleton effect comes from jetpack-autoloader picking one copy. |
| `\AcrossAI_Main_Menu\Assets::HOST_HANDLE` | The `wp_enqueue_script` handle of the host bundle (`'acrossai-settings-host'`). Use this as a dependency when enqueuing your Fill bundle. |
| `\AcrossAI_Main_Menu\SettingsPage::PARENT_SLUG` | `'acrossai'` — the parent menu slug. |
| `\AcrossAI_Main_Menu\SettingsPage::SETTINGS_SLUG` | `'acrossai-settings'` — the Settings submenu slug. |

## Development

```bash
composer install
npm install
npm run build     # one-shot production build → build/
npm run start     # watch mode for development
```

Output goes to `build/settings-host.js` + `build/settings-host.asset.php`. Both are required at runtime — commit them or have your release pipeline build them.

## Notes for multi-plugin installs

- **Version pinning matters**: jetpack-autoloader picks the **highest** version of `acrossai-co/main-menu` across all active plugins. Bumping the version in one plugin's `composer.lock` makes that plugin's copy "win".
- **All plugins should agree on the major version** to avoid API drift across vendor copies.
- The "winning" copy's `build/settings-host.js` is what gets enqueued — make sure every plugin ships a built `build/` directory.
- If you forget to load `vendor/autoload_packages.php`, the class won't be found and the menu silently won't appear.
