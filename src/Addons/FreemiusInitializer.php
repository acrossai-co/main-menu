<?php

namespace AcrossAI_Addon;

/**
 * Loads the Freemius SDK and creates a per-plugin FS instance.
 *
 * Each consumer plugin passes its own Freemius product credentials so that
 * activations, opt-ins, and analytics are tracked separately per plugin in
 * the Freemius dashboard.
 */
class FreemiusInitializer {

	/**
	 * Freemius product ID for the AcrossAI umbrella. All paid child add-ons
	 * (Claude Connectors, BuddyBoss Abilities, and any future paid add-ons)
	 * register themselves under this parent via the Freemius `parent` block,
	 * and rely on `do_action( 'acrossai_loaded' )` / `\acrossai_main_menu()` (see
	 * ../parent-addon-signal.php) as the "umbrella is ready" signal.
	 *
	 * @since 0.0.19
	 */
	public const UMBRELLA_PRODUCT_ID = '34418';

	/** @var object[] Memoized FS instances keyed by product ID. */
	private static $instances = [];

	/**
	 * True once the first consumer has claimed the shared contact/support
	 * submenus under the AcrossAI parent. All subsequent consumers get
	 * contact=false, support=false so we render one Contact Us + one Support
	 * Forum entry under the parent instead of N of each.
	 *
	 * @var bool
	 */
	private static $shared_menus_claimed = false;

	/**
	 * account-slug => product display name, captured at init() time so the
	 * once-hooked admin_menu pass can rename colliding account entries to
	 * "<Product Name> Account" without needing to reach back into the FS
	 * instances.
	 *
	 * @var array<string, string>
	 */
	private static $account_labels = [];

	/** @var bool Guard so the rename admin_menu action is registered exactly once. */
	private static $rename_hook_registered = false;

	/**
	 * Default Freemius `menu` config values (excluding the required `slug`).
	 *
	 * Consumers override individual keys by passing $menu_overrides to init().
	 * `slug` is derived from the caller's $menu_slug argument and cannot be
	 * overridden via $menu_overrides — pass a different $menu_slug instead.
	 *
	 * Rationale for defaults:
	 * - account defaults to `true` so every product has its own Freemius
	 *   Account entry (activation / opt-in / license). The rename pass in
	 *   maybe_rename_account_entries() disambiguates the labels so we don't
	 *   render N identically-named "Account" rows.
	 * - contact / support default to `true` here so the first consumer to
	 *   init() gets them; init() flips them to `false` for every consumer
	 *   after that (see $shared_menus_claimed) so the parent menu shows a
	 *   single Contact Us and a single Support Forum entry.
	 * - upgrade / pricing default to `false` because the bundled Add-ons
	 *   page owns the upgrade + pricing UX for AcrossAI products.
	 * - addons defaults to `false` because a second Freemius-added Add-ons
	 *   row would duplicate the vendor's already-registered Add-ons submenu
	 *   (MenuRegistrar::register()).
	 */
	private const DEFAULT_MENU = array(
		'account' => true,
		'contact' => true,
		'support' => true,
		'upgrade' => false,
		'pricing' => false,
		'addons'  => false,
	);

	/**
	 * Load the SDK (if not already loaded) and return the FS instance for the given product.
	 *
	 * @param string $consumer_main_file Absolute path to the consumer plugin's main file.
	 * @param string $menu_slug          Consumer's parent admin menu slug (becomes `menu.slug`; cannot be overridden).
	 * @param string $product_id         Freemius product ID (numeric string).
	 * @param string $public_key         Freemius product public key (pk_...).
	 * @param string $slug               Freemius product slug.
	 * @param array  $menu_overrides     Optional per-consumer overrides for the Freemius `menu` config.
	 *                                    Accepted keys: `account`, `contact`, `support`, `upgrade`,
	 *                                    `pricing`, `addons` (all boolean). Any key omitted from this
	 *                                    array keeps its DEFAULT_MENU value. Unknown keys pass through
	 *                                    verbatim so future Freemius menu config extensions can be used
	 *                                    without a package bump. `slug` is stripped — pass $menu_slug.
	 * @param bool   $has_addons         Passed to fs_dynamic_init() as `has_addons`. Required to be
	 *                                    `true` for Freemius to render its Add-ons submenu at all
	 *                                    (Freemius SDK gates the row on `if ( $this->has_addons() )`).
	 *                                    Defaults to `false` for backwards compatibility with
	 *                                    consumers that don't expose an Add-ons UX. Umbrella-style
	 *                                    consumer plugins (Freemius product hosts all AcrossAI
	 *                                    add-ons) MUST pass `true`.
	 *
	 * @return object Freemius instance.
	 */
	public static function init(
		string $consumer_main_file,
		string $menu_slug,
		string $product_id,
		string $public_key,
		string $slug,
		array $menu_overrides = array(),
		bool $has_addons = false
	): object {
		if ( isset( self::$instances[ $product_id ] ) ) {
			return self::$instances[ $product_id ];
		}

		self::load_sdk();

		if ( ! function_exists( 'fs_dynamic_init' ) ) {
			throw new \RuntimeException(
				'AddonsPage: Freemius SDK loaded but fs_dynamic_init() is unavailable. ' .
				'Ensure vendor/freemius/wordpress-sdk/start.php is accessible.'
			);
		}

		// Strip `slug` so consumers can't override the parent-menu slug via this array
		// (they'd have to pass a different $menu_slug to actually change the parent menu).
		unset( $menu_overrides['slug'] );

		$menu_config = array_merge(
			self::DEFAULT_MENU,
			$menu_overrides,
			array( 'slug' => $menu_slug )
		);

		// First consumer to init() keeps contact + support; every subsequent
		// consumer gets them force-off so we don't stack N Contact Us and N
		// Support Forum entries under the shared parent menu. Consumers can
		// still opt themselves out entirely by passing false in $menu_overrides.
		if ( self::$shared_menus_claimed ) {
			$menu_config['contact'] = false;
			$menu_config['support'] = false;
		} else {
			self::$shared_menus_claimed = true;
		}

		self::$instances[ $product_id ] = fs_dynamic_init(
			array(
				'id'             => $product_id,
				'slug'           => $slug,
				'type'           => 'plugin',
				'public_key'     => $public_key,
				'is_premium'     => false,
				'has_addons'     => $has_addons,
				'has_paid_plans' => false,
				'menu'           => $menu_config,
				'navigation'     => 'menu',
				'file'           => $consumer_main_file,
			)
		);

		// First time the umbrella product is registered, load the global
		// `\acrossai_main_menu()` helper and fire `acrossai_loaded` so paid
		// child add-ons (Claude Connectors, BuddyBoss Abilities, …) can
		// safely call `fs_dynamic_init` with `parent` pointing at 34418.
		// See ../parent-addon-signal.php for the helper.
		if ( self::UMBRELLA_PRODUCT_ID === $product_id ) {
			if ( ! function_exists( 'acrossai_main_menu' ) ) {
				require_once __DIR__ . '/../parent-addon-signal.php';
			}
			/**
			 * Fires once, after the AcrossAI umbrella Freemius product
			 * (34418) is registered with the SDK. Paid child add-ons use
			 * this as their init signal.
			 *
			 * @since 0.0.19
			 */
			do_action( 'acrossai_loaded' );
		}

		// Track this product's Account label so the once-hooked rename pass can
		// disambiguate multiple "Account" submenu entries under the parent.
		// The Freemius SDK adds Account entries at slug "{menu_slug}-account".
		if ( ! empty( $menu_config['account'] ) ) {
			self::$account_labels[ $menu_slug . '-account' ] = self::product_label( self::$instances[ $product_id ], $slug );
		}

		if ( ! self::$rename_hook_registered ) {
			self::$rename_hook_registered = true;
			add_action( 'admin_menu', [ self::class, 'maybe_rename_account_entries' ], 999 );
		}

		return self::$instances[ $product_id ];
	}

	/**
	 * Returns the umbrella product (34418) Freemius instance, or null if
	 * it has not been registered yet. Backing method for the global
	 * `\acrossai_main_menu()` helper in ../parent-addon-signal.php.
	 *
	 * @return object|null
	 * @since  0.0.19
	 */
	public static function umbrella_instance(): ?object {
		return self::$instances[ self::UMBRELLA_PRODUCT_ID ] ?? null;
	}

	/**
	 * admin_menu (priority 999) callback: renames every account entry we've seen
	 * this request from the generic "Account" label to "<Product Name> Account",
	 * so N consumer plugins under one parent don't render N identically-labelled
	 * rows. Runs once per request regardless of consumer count.
	 *
	 * @internal Exposed as public only because add_action() needs a callable.
	 */
	public static function maybe_rename_account_entries(): void {
		global $submenu;
		if ( empty( self::$account_labels ) || empty( $submenu ) || ! is_array( $submenu ) ) {
			return;
		}

		foreach ( $submenu as $parent_slug => &$items ) {
			foreach ( $items as &$item ) {
				// $item = [ 0 => menu_title, 1 => capability, 2 => menu_slug, ... ]
				if ( ! isset( $item[2] ) ) {
					continue;
				}
				$account_slug = $item[2];
				if ( ! isset( self::$account_labels[ $account_slug ] ) ) {
					continue;
				}
				$product_name = self::$account_labels[ $account_slug ];
				/* translators: %s: product display name */
				$item[0] = sprintf( __( '%s Account', 'acrossai' ), $product_name );
			}
			unset( $item );
		}
		unset( $items );
	}

	/**
	 * Best-effort human-readable product name for a Freemius instance.
	 * Falls back to the slug when the SDK surface isn't available.
	 */
	private static function product_label( object $fs, string $slug ): string {
		try {
			if ( method_exists( $fs, 'get_plugin_name' ) ) {
				$name = (string) $fs->get_plugin_name();
				if ( '' !== $name ) {
					return $name;
				}
			}
		} catch ( \Exception $e ) {
			// Fall through to slug.
		}
		return $slug;
	}

	/**
	 * Require the Freemius SDK start.php.
	 * Guards against double-loading — FS itself also guards internally.
	 */
	private static function load_sdk(): void {
		if ( function_exists( 'fs_dynamic_init' ) ) {
			return;
		}

		// When installed via Composer the SDK lives at:
		// {consumer}/vendor/freemius/wordpress-sdk/start.php
		// This file lives at: {consumer}/vendor/acrossai-co/main-menu/src/Addons/FreemiusInitializer.php
		// Walk up: Addons -> src -> main-menu -> acrossai-co -> vendor, then into freemius/wordpress-sdk.
		$sdk_path = dirname( __DIR__, 4 ) . '/freemius/wordpress-sdk/start.php';

		if ( ! file_exists( $sdk_path ) ) {
			throw new \RuntimeException(
				"AddonsPage: Freemius SDK not found at expected path: {$sdk_path}\n" .
				'Run `composer install` in your plugin directory to install dependencies.'
			);
		}

		require_once $sdk_path;
	}
}
