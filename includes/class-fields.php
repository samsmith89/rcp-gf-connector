<?php


class GFRCP_Fields {

    protected static $_instance;
    public $choices;

    public static function get_instance() {
        if ( ! self::$_instance instanceof GFRCP_Fields ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

//    protected function __construct()
//    {
//        add_filter( 'gform_field_choice_markup_pre_render', [$this, 'yep'], 10, 4 );
//    }
//
//
//
//    public function yep( $choice_markup, $choice, $field, $value )
//    {
//        if ('membership' === $field->type) {
//            $choices = [];
//
//            $levels_db = new RCP_Levels();
//            $levels    = $levels_db->get_levels( array( 'status' => 'active' ) );
//
//            foreach ($levels as $level) {
//                $choices[] = [
//                    'text'  => $level->name,
//                    'value' => $level->id
//                ];
//            }
//
//            $field->choices = $choices;
//        }
//        return $choice_markup;
//    }

}

GFRCP_Fields::get_instance();


