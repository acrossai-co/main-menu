<?php

namespace AcrossAI_Addon;

/**
 * Computes the correct button label, action, and state for each add-on
 * given the current user's install / opt-in / license status.
 */
class ButtonState {

	/** @var FreemiusBridge */
	private $fs_bridge;

	public function __construct( FreemiusBridge $fs_bridge ) {
		$this->fs_bridge = $fs_bridge;
	}

	/**
	 * @return array{label:string, action:string, enabled:bool, css_class:string, tooltip:string}
	 */
	public function for_addon( array $addon ): array {
		if ( 'free' === $addon['type'] ) {
			return $this->state_for_free( $addon );
		}
		return $this->state_for_paid( $addon );
	}

	// -------------------------------------------------------------------------

	private function state_for_free( array $addon ): array {
		$plugin_file = PluginFileLocator::for_addon( $addon );

		if ( null === $plugin_file ) {
			return [
				'label'     => __( 'Install', 'acrossai-addons-page' ),
				'action'    => 'install',
				'enabled'   => true,
				'css_class' => 'button-primary',
				'tooltip'   => '',
			];
		}

		if ( is_plugin_active( $plugin_file ) ) {
			return [
				'label'       => __( 'Deactivate', 'acrossai-addons-page' ),
				'action'      => 'deactivate',
				'enabled'     => true,
				'css_class'   => 'button-secondary acrossai-addons-page__btn--active',
				'tooltip'     => '',
				'plugin_file' => $plugin_file,
			];
		}

		return [
			'label'       => __( 'Activate', 'acrossai-addons-page' ),
			'action'      => 'activate',
			'enabled'     => true,
			'css_class'   => 'button-secondary',
			'tooltip'     => '',
			'plugin_file' => $plugin_file,
		];
	}

	private function state_for_paid( array $addon ): array {
		$plugin_file   = PluginFileLocator::for_addon( $addon );
		$is_registered = $this->fs_bridge->is_registered();
		$price_label   = $addon['price_label'] ?? '$0';

		// Active.
		if ( $plugin_file && is_plugin_active( $plugin_file ) ) {
			return [
				'label'       => __( 'Deactivate', 'acrossai-addons-page' ),
				'action'      => 'deactivate',
				'enabled'     => true,
				'css_class'   => 'button-secondary acrossai-addons-page__btn--active',
				'tooltip'     => '',
				'plugin_file' => $plugin_file,
			];
		}

		// Installed but inactive.
		if ( $plugin_file ) {
			return [
				'label'       => __( 'Activate', 'acrossai-addons-page' ),
				'action'      => 'activate',
				'enabled'     => true,
				'css_class'   => 'button-secondary',
				'tooltip'     => '',
				'plugin_file' => $plugin_file,
			];
		}

		// Not installed + opted in + owns license → show Install.
		if ( $is_registered && isset( $addon['fs_product_id'] ) && $this->fs_bridge->is_owned( $addon['fs_product_id'] ) ) {
			return [
				'label'     => __( 'Install', 'acrossai-addons-page' ),
				'action'    => 'install_licensed',
				'enabled'   => true,
				'css_class' => 'button-primary',
				'tooltip'   => '',
			];
		}

		// Not installed + no license (opted in or not) → Buy.
		/* translators: %s: price e.g. $49/year */
		$label = sprintf( __( 'Buy %s', 'acrossai-addons-page' ), $price_label );
		return [
			'label'     => $label,
			'action'    => 'buy',
			'enabled'   => true,
			'css_class' => 'button-primary',
			'tooltip'   => '',
		];
	}

}
