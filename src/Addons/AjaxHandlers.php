<?php

namespace AcrossAI_Addon;

/**
 * Handles wp_ajax_* requests for free add-on install and activate.
 */
class AjaxHandlers {

	/** @var Installer */
	private $installer;

	/** @var ButtonState */
	private $button_state;

	public function __construct( Installer $installer, ButtonState $button_state ) {
		$this->installer    = $installer;
		$this->button_state = $button_state;
	}

	/** wp_ajax_acrossai_addons_install_free */
	public function install_free(): void {
		check_ajax_referer( 'acrossai_addons_action', 'nonce' );

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to install plugins.', 'acrossai-addons-page' ),
					'code'    => 'forbidden',
				]
			);
		}

		$slug = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';

		$addon = AddonsRegistry::find( $slug );
		if ( null === $addon || 'paid' === $addon['type'] ) {
			wp_send_json_error(
				[
					'message' => __( 'Add-on not found.', 'acrossai-addons-page' ),
					'code'    => 'not_found',
				]
			);
		}

		$install = $this->installer->install_from_source( $addon );

		if ( ! $install['success'] ) {
			wp_send_json_error(
				[
					'message' => sprintf(
						/* translators: %s: add-on name */
						__( 'Could not install %s. Please try again.', 'acrossai-addons-page' ),
						esc_html( $addon['name'] )
					),
					'detail'  => $install['message'],
					'code'    => 'install_failed',
				]
			);
		}

		$plugin_file = $install['plugin_file'];

		// Install succeeded but we couldn't locate the plugin under its
		// expected folder (bad ZIP, registry slug mismatch, missing
		// install_folder key). Report the honest state instead of pretending
		// it activated.
		if ( '' === $plugin_file ) {
			wp_send_json_success(
				[
					'message'     => sprintf(
						/* translators: %s: add-on name */
						__( '%s installed but could not be located to activate.', 'acrossai-addons-page' ),
						esc_html( $addon['name'] )
					),
					'plugin_file' => '',
					'activated'   => false,
					'code'        => 'installed_not_located',
					'state'       => $this->button_state->for_addon( $addon ),
				]
			);
		}

		$activate = $this->installer->activate( $plugin_file, $addon['name'] );

		if ( ! $activate['success'] ) {
			wp_send_json_success(
				[
					'message'     => sprintf(
						/* translators: %s: add-on name */
						__( '%s installed but could not be activated.', 'acrossai-addons-page' ),
						esc_html( $addon['name'] )
					),
					'detail'      => $activate['message'],
					'plugin_file' => $plugin_file,
					'activated'   => false,
					'code'        => 'installed_not_activated',
					'state'       => $this->button_state->for_addon( $addon ),
				]
			);
		}

		wp_send_json_success(
			[
				'message'     => sprintf(
					/* translators: %s: add-on name */
					__( '%s installed and activated.', 'acrossai-addons-page' ),
					esc_html( $addon['name'] )
				),
				'plugin_file' => $plugin_file,
				'activated'   => true,
				'state'       => $this->button_state->for_addon( $addon ),
			]
		);
	}

	/** wp_ajax_acrossai_addons_deactivate */
	public function deactivate(): void {
		check_ajax_referer( 'acrossai_addons_action', 'nonce' );

		if ( ! current_user_can( 'deactivate_plugins' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to deactivate plugins.', 'acrossai-addons-page' ),
					'code'    => 'forbidden',
				]
			);
		}

		$slug  = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';
		$addon = AddonsRegistry::find( $slug );
		if ( null === $addon ) {
			wp_send_json_error(
				[
					'message' => __( 'Add-on not found.', 'acrossai-addons-page' ),
					'code'    => 'not_found',
				]
			);
		}

		// Resolve authoritatively server-side. Any client-supplied plugin_file
		// is ignored — previously this endpoint acted on whatever the POST said.
		$plugin_file = $this->installer->locate_plugin_file( $addon );
		if ( null === $plugin_file ) {
			wp_send_json_error(
				[
					'message' => __( 'Add-on is not installed.', 'acrossai-addons-page' ),
					'code'    => 'not_installed',
				]
			);
		}

		$result = $this->installer->deactivate( $plugin_file, $addon['name'] );

		wp_send_json_success(
			[
				'message' => $result['message'],
				'state'   => $this->button_state->for_addon( $addon ),
			]
		);
	}

	/** wp_ajax_acrossai_addons_activate */
	public function activate(): void {
		check_ajax_referer( 'acrossai_addons_action', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You do not have permission to activate plugins.', 'acrossai-addons-page' ),
					'code'    => 'forbidden',
				]
			);
		}

		$slug  = isset( $_POST['slug'] ) ? sanitize_key( $_POST['slug'] ) : '';
		$addon = AddonsRegistry::find( $slug );
		if ( null === $addon ) {
			wp_send_json_error(
				[
					'message' => __( 'Add-on not found.', 'acrossai-addons-page' ),
					'code'    => 'not_found',
				]
			);
		}

		// Resolve authoritatively server-side. Any client-supplied plugin_file
		// is ignored — previously this endpoint would activate whatever file
		// path the POST body named, provided it had one slash.
		$plugin_file = $this->installer->locate_plugin_file( $addon );
		if ( null === $plugin_file ) {
			wp_send_json_error(
				[
					'message' => __( 'Add-on is not installed.', 'acrossai-addons-page' ),
					'code'    => 'not_installed',
				]
			);
		}

		$result = $this->installer->activate( $plugin_file, $addon['name'] );

		if ( ! $result['success'] ) {
			wp_send_json_error(
				[
					'message' => $result['message'],
					'code'    => 'activate_failed',
				]
			);
		}

		wp_send_json_success(
			[
				'message' => $result['message'],
				'state'   => $this->button_state->for_addon( $addon ),
			]
		);
	}
}
