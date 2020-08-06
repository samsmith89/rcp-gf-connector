<?php

class GF_Field_Membership extends GF_Field_Select
{

    public $type = 'membership';

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

// Prepare Membership button.
                    $membership_button = array(
                        'class' => 'button',
                        'value' => $new_button['text'],
                        'data-type' => $this->type,
                        'onclick' => "StartAddField('{$this->type}');",
                        'onkeypress' => "StartAddField('{$this->type}');",
                        // Get the active RCP memberhsips and echo them into choices
                    );

// Insert membership button.
                    array_splice($group['fields'], 0, 0, array($membership_button));

                    break;
                }
            }
        }

        return $field_groups;
    }

    public function get_form_editor_field_settings() {
        return array(
            'conditional_logic_field_setting',
            'prepopulate_field_setting',
            'error_message_setting',
            'enable_enhanced_ui_setting',
            'label_setting',
            'label_placement_setting',
            'admin_label_setting',
            'size_setting',
            'choices_setting',
            'rules_setting',
//            'default_value_setting',
            'placeholder_setting',
            'visibility_setting',
            'duplicate_setting',
            'description_setting',
            'css_class_setting',
        );
    }

    public function get_choices( $value ) {
        $field = $this;

        $choices = [];

        $levels_db = new RCP_Levels();
        $levels    = $levels_db->get_levels( array( 'status' => 'active' ) );

        foreach ($levels as $level) {
            $choices[] = [
                'text'  => $level->name,
                'value' => $level->id
            ];
        }

        $field->choices = $choices;
        return GFCommon::get_select_choices( $field, $value );
    }

    /**
     * Return the field title.
     *
     * @access public
     * @return string
     */
    public function get_form_editor_field_title()
    {
        return esc_attr__('Membership', 'rcp-gravity-forms');
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

GF_Fields::register(new GF_Field_Membership());