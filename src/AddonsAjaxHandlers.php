<?php

namespace AcrossAI_Main_Menu;

/**
 * wp_ajax_* handlers for the Add-ons page.
 *
 * All three endpoints:
 *   - verify a shared nonce ('acrossai_addons')
 *   - check the appropriate capability
 *   - resolve the add-on server-side from a validated slug (client-supplied
 *     plugin_file paths are ignored — the installer looks up the file itself)
 */
class AddonsAjaxHandlers {

	const NONCE_ACTION = 'acrossai_addons';

	/** @var AddonsInstaller */
	private $installer;

	/** @var AddonsPageRenderer */
	private $renderer;

	public function __construct( AddonsInstaller $installer, AddonsPageRenderer $renderer ) {
		$this->installer = $installer;
		$this->renderer  = $renderer;
	}

	/** wp_ajax_acrossai_addons_install */
	public function install(): void {
		$this->guard( 'install_plugins' );
		$addon = $this->resolve_addon();

		$result = $this->installer->install( $addon );
		if ( ! $result['success'] ) {
			wp_send_json_error( [ 'message' => $result['message'], 'code' => 'install_failed' ] );
		}

		$plugin_file = $result['plugin_file'];
		if ( '' === $plugin_file ) {
			wp_send_json_success( [
				'message'     => sprintf(
					/* translators: %s: add-on name */
					__( '%s installed but could not be located to activate.', 'acrossai' ),
					$addon['name']
				),
				'activated' => false,
				'state'     => $this->renderer->button_state_for( $addon ),
			] );
		}

		$activate = $this->installer->activate( $plugin_file, $addon['name'] );
		if ( ! $activate['success'] ) {
			wp_send_json_success( [
				'message'   => sprintf(
					/* translators: %s: add-on name */
					__( '%s installed but could not be activated.', 'acrossai' ),
					$addon['name']
				),
				'detail'    => $activate['message'],
				'activated' => false,
				'state'     => $this->renderer->button_state_for( $addon ),
			] );
		}

		wp_send_json_success( [
			'message'   => sprintf(
				/* translators: %s: add-on name */
				__( '%s installed and activated.', 'acrossai' ),
				$addon['name']
			),
			'activated' => true,
			'state'     => $this->renderer->button_state_for( $addon ),
		] );
	}

	/** wp_ajax_acrossai_addons_activate */
	public function activate(): void {
		$this->guard( 'activate_plugins' );
		$addon = $this->resolve_addon();

		$plugin_file = $this->installer->find_plugin_file( $addon );
		if ( null === $plugin_file ) {
			wp_send_json_error( [
				'message' => __( 'Add-on is not installed.', 'acrossai' ),
				'code'    => 'not_installed',
			] );
		}

		$result = $this->installer->activate( $plugin_file, $addon['name'] );
		if ( ! $result['success'] ) {
			wp_send_json_error( [ 'message' => $result['message'], 'code' => 'activate_failed' ] );
		}

		wp_send_json_success( [
			'message' => $result['message'],
			'state'   => $this->renderer->button_state_for( $addon ),
		] );
	}

	/** wp_ajax_acrossai_addons_deactivate */
	public function deactivate(): void {
		$this->guard( 'deactivate_plugins' );
		$addon = $this->resolve_addon();

		$plugin_file = $this->installer->find_plugin_file( $addon );
		if ( null === $plugin_file ) {
			wp_send_json_error( [
				'message' => __( 'Add-on is not installed.', 'acrossai' ),
				'code'    => 'not_installed',
			] );
		}

		$result = $this->installer->deactivate( $plugin_file, $addon['name'] );
		wp_send_json_success( [
			'message' => $result['message'],
			'state'   => $this->renderer->button_state_for( $addon ),
		] );
	}

	// -------------------------------------------------------------------------

	private function guard( string $capability ): void {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error( [
				'message' => __( 'You do not have permission for this action.', 'acrossai' ),
				'code'    => 'forbidden',
			] );
		}
	}

	/** Resolves the add-on from POST slug against the filtered registry, or dies. */
	private function resolve_addon(): array {
		$slug  = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		$addon = $this->renderer->find_addon( $slug );
		if ( null === $addon ) {
			wp_send_json_error( [
				'message' => __( 'Add-on not found.', 'acrossai' ),
				'code'    => 'not_found',
			] );
		}
		return $addon;
	}
}
