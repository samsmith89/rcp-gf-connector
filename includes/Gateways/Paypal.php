<?php

namespace GF_RCP\Gateways;

if ( ! class_exists( 'GFForms' ) ) {
die();
}

use GFPaymentAddOn;

class PayPal {

    /**
     * @var
     */
    protected static $_instance;

    public static function get_instance() {
        if ( ! self::$_instance instanceof PayPal ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }


}