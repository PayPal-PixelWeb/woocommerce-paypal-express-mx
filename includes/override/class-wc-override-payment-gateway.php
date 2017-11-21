<?php
/**
 * Override of WC_Payment_Gateway for WooCommerce Plugin.
 *
 * @package   WooCommerce -> Paypal Express Checkout MX
 * @author    Kijam Lopez <info@kijam.com>
 * @license   Apache-2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Payment_Gateway_Paypal' ) ) :
	/**
	 * Override of WC_Payment_Gateway for WooCommerce Plugin.
	 *
	 * @since 1.0.0
	 */
	class WC_Payment_Gateway_Paypal extends WC_Payment_Gateway {
		/**
		 * Generate Text HTML.
		 *
		 * @param  string $key Input name.
		 * @param  array  $data List of parameters.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function generate_html_html( $key, $data ) {
			$field_key = $this->get_field_key( $key );
			$defaults  = array(
				'title'             => '',
				'type'              => 'html',
				'description'       => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			?>
			<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<?php echo wp_kses_post( $data['description'] ); ?>
			</td>
		</tr>
		<?php

		return ob_get_clean();
		}

		/**
		 * Select image from Media.
		 *
		 * @param  string $key Input name.
		 * @param  array  $data List of parameters.
		 *
		 * @return string
		 *
		 * @since 1.0.0
		 */
		public function generate_media_html( $key, $data ) {
			$field_key = $this->get_field_key( $key );
			$defaults  = array(
				'title'             => '',
				'disabled'          => false,
				'class'             => '',
				'css'               => '',
				'placeholder'       => '',
				'preview'           => '',
				'max-width'         => '',
				'type'              => 'media',
				'desc_tip'          => false,
				'description'       => '',
				'custom_attributes' => array(),
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			?>
			<tr valign="top">
			<th scope="row" class="titledesc">
				<?php echo wp_kses_post( $this->get_tooltip_html( $data ) ); ?>
				<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo esc_html( $data['title'] ); ?></span></legend>
					<input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="hidden" id="media-<?php echo esc_attr( $field_key ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo wp_kses_post( $this->get_custom_attribute_html( $data ) ); ?> />
					<input type='button' class="button-primary pp_media_manager" value="<?php esc_attr_e( 'Select a image', 'woocommerce-paypal-express-mx' ); ?>" data-dest-id="media-<?php echo esc_attr( $field_key ); ?>" data-max-width="<?php echo esc_attr( $data['max-width'] ); ?>" />
					<div id="media-<?php echo esc_attr( $field_key ); ?>-preview"><?php echo wp_kses_post( $data['preview'] ); ?></div>
					<?php echo wp_kses_post( $this->get_description_html( $data ) ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
		}

	}
endif;
