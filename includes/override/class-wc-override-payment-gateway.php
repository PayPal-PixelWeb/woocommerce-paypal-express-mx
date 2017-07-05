<?php

if ( ! class_exists('WC_Payment_Gateway_Paypal') ) :
class WC_Payment_Gateway_Paypal extends WC_Payment_Gateway {
    /**
     * Generate Text HTML.
     *
     * @param  mixed $key
     * @param  mixed $data
     * @since  1.0.0
     * @return string
     */
    public function generate_html_html( $key, $data ) {
        $field_key = $this->get_field_key( $key );
        $defaults  = array(
            'title'             => '',
            'type'              => 'html',
            'description'       => ''
        );

        $data = wp_parse_args( $data, $defaults );

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
            </th>
            <td class="forminp">
                <?php echo $data['description']; ?>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }
    
    /**
     * Select image from Media.
     *
     * @param  mixed $key
     * @param  mixed $data
     * @since  1.0.0
     * @return string
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
                <?php echo $this->get_tooltip_html( $data ); ?>
                <label for="<?php echo esc_attr( $field_key ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
                    <input class="input-text regular-input <?php echo esc_attr( $data['class'] ); ?>" type="hidden" id="media-<?php echo esc_attr( $field_key ); ?>" name="<?php echo esc_attr( $field_key ); ?>" id="<?php echo esc_attr( $field_key ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" value="<?php echo esc_attr( $this->get_option( $key ) ); ?>" placeholder="<?php echo esc_attr( $data['placeholder'] ); ?>" <?php disabled( $data['disabled'], true ); ?> <?php echo $this->get_custom_attribute_html( $data ); ?> />
                    <input type='button' class="button-primary pp_media_manager" value="<?php esc_attr_e( 'Select a image', 'woocommerce-paypal-express-mx' ); ?>" data-dest-id="media-<?php echo esc_attr( $field_key ); ?>" data-max-width="<?php echo $data['max-width']; ?>" />
                    <div id="media-<?php echo esc_attr( $field_key ); ?>-preview"><?php echo $data['preview']; ?></div>
					<?php echo $this->get_description_html( $data ); ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }
    
}
endif;