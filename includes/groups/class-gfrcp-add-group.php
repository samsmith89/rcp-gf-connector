<?php

class GF_Add_Field_Group extends GF_Field_Text
{

    public $type = 'RCP Field Group';

    /**
     * Adds custom field group
     *
     * @param array $field_groups
     *
     * @return array
     */
    public function add_button($field_groups)
    {

        foreach ($field_groups as $group) {
            if ($group['name'] == 'rcp_field_group') {

                return $field_groups;
            }
        }

        $field_groups[] = array(
            'name' => 'rcp_field_group',
            'label' => __('RCP Fields', 'rcp-gravity-forms'),
            'fields' => array()
        );

        return $field_groups;
    }
}

GF_Fields::register(new GF_Add_Field_Group());