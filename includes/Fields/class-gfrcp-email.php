<?php

//Look to see if I should be registering this in the main plugin file

namespace GF_RCP\Fields;

if ( ! class_exists( 'GFForms' ) ) {
    die();
}

use GF_Fields;
use GF_Field_Text;

class GF_Field_User_Email extends GF_Field_Text
{

    protected static $_instance;

    public static function get_instance() {
        if ( ! self::$_instance instanceof GF_Field_User_Email ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public $type = 'useremail';

    /**
     * Adds the field button to the specified group.
     *
     * @param array $field_groups
     *
     * @return array
     */
    public function add_button($field_groups)
    {

// Check a button for the type hasn't already been added
        foreach ($field_groups as $group) {
            foreach ($group['fields'] as $button) {
                if (isset($button['data-type']) && $button['data-type'] == $this->type) {
                    return $field_groups;
                }
            }
        }

        $new_button = $this->get_form_editor_button();
        if (!empty($new_button)) {
            foreach ($field_groups as &$group) {
                if ($group['name'] == $new_button['group']) {

// Prepare user email button.
                    $username_button = array(
                        'class' => 'button',
                        'value' => $new_button['text'],
                        'data-type' => $this->type,
                        'onclick' => "StartAddField('{$this->type}');",
                        'onkeypress' => "StartAddField('{$this->type}');",
                    );

// Insert user email.
                    array_splice($group['fields'], 0, 0, array($username_button));

                    break;
                }
            }
        }

        return $field_groups;
    }

    /**
     * Return the field title.
     *
     * @access public
     * @return string
     */
    public function get_form_editor_field_title()
    {
        return esc_attr__('User Email', 'rcp-gravity-forms');
    }

    /**
     * Include the script to set the default label for new fields.
     *
     * @return string
     */
    public function get_form_editor_inline_script_on_page_render()
    {
        return sprintf("function SetDefaultValues_%s(field) {field.label = '%s';}", $this->type, $this->get_form_editor_field_title()) . PHP_EOL;
    }

    /**
     * Assign the field button to the custom group.
     *
     * @return array
     */
    public function get_form_editor_button() {
        return array(
            'group' => 'rcp_field_group',
            'text'  => $this->get_form_editor_field_title(),
        );
    }

}

GF_Fields::register(new GF_Field_User_Email());