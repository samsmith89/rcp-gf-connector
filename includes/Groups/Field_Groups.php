<?php

namespace GF_RCP\Groups;

use GF_Fields;
use GF_Field_Text;

class Field_Groups extends GF_Field_Text
{
	protected static $_instance;

	public static function get_instance() {
		if ( ! self::$_instance instanceof Field_Groups ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

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

GF_Fields::register(new Field_Groups());