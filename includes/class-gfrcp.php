<?php

GFForms::include_feed_addon_framework();;

class GFSimpleAddOn extends GFFeedAddOn
{

    protected $_version = GF_SIMPLE_ADDON_VERSION;
    protected $_min_gravityforms_version = '1.9';
    protected $_slug = 'simpleaddon';
    protected $_path = 'simpleaddon/simpleaddon.php';
    protected $_full_path = __FILE__;
    protected $_title = 'Gravity Forms Simple Add-On';
    protected $_short_title = 'Simple Add-On';

    private static $_instance = null;

    public static function get_instance()
    {
        if (self::$_instance == null) {
            self::$_instance = new GFSimpleAddOn();
        }

        return self::$_instance;
    }

    public function init()
    {
        parent::init();
        add_filter('gform_submit_button', array($this, 'form_submit_button'), 10, 2);
    }

    public function scripts()
    {
        $scripts = array(
            array(
                'handle' => 'my_script_js',
                'src' => $this->get_base_url() . '/js/my_script.js',
                'version' => $this->_version,
                'deps' => array('jquery'),
                'strings' => array(
                    'first' => esc_html__('First Choice', 'rcp-gravity-forms'),
                    'second' => esc_html__('Second Choice', 'rcp-gravity-forms'),
                    'third' => esc_html__('Third Choice', 'rcp-gravity-forms')
                ),
                'enqueue' => array(
                    array(
                        'admin_page' => array('form_settings'),
                        'tab' => 'simpleaddon'
                    )
                )
            ),

        );

        return array_merge(parent::scripts(), $scripts);
    }

    public function styles()
    {
        $styles = array(
            array(
                'handle' => 'my_styles_css',
                'src' => $this->get_base_url() . '/css/my_styles.css',
                'version' => $this->_version,
                'enqueue' => array(
                    array('field_types' => array('poll'))
                )
            )
        );

        return array_merge(parent::styles(), $styles);
    }

    function form_submit_button($button, $form)
    {
        $settings = $this->get_form_settings($form);
        if (isset($settings['enabled']) && true == $settings['enabled']) {
            $text = $this->get_plugin_setting('mytextbox');
            $button = "<div>{$text}</div>" . $button;
        }

        return $button;
    }

    public function plugin_page()
    {
        echo 'This page appears in the Forms menu';
    }

    public function plugin_settings_fields()
    {
        return array(
            array(
                'title' => esc_html__('Simple Add-On Settings', 'rcp-gravity-forms'),
                'fields' => array(
                    array(
                        'name' => 'mytextbox',
                        'tooltip' => esc_html__('This is the tooltip', 'rcp-gravity-forms'),
                        'label' => esc_html__('This is the label', 'rcp-gravity-forms'),
                        'type' => 'text',
                        'class' => 'small',
                        'feedback_callback' => array($this, 'is_valid_setting'),
                    )
                )
            ),
            array(
                array(
                    'title' => 'This is the title for Section 1',
                    'description' => 'This is a description of the purpose of Section 1',
                    'fields' => array(
                        array(
                            'label' => 'My Custom Field',
                            'type' => 'my_nother_custom_field_type',
                            'name' => 'my_custom_field'
                        ),
                    )
                ),
            )
        );
    }

    public function feed_settings_fields() {
        return array(
            array(
                'title'  => esc_html__( 'This is the title for Section 1', 'rcp-gravity-forms' ),
                'fields' => array(
                    array(
                        'name'      => 'RCPMembershipFields',
                        'label'     => esc_html__( 'Map Fields', 'rcp-gravity-forms' ),
                        'type'      => 'field_map',
                        'field_map' => $this->standard_fields_for_feed_mapping(),
                        'tooltip'   => '<h6>' . esc_html__( 'Map Fields', 'sometextdomain' ) . '</h6>' . esc_html__( 'Select which Gravity Form fields pair with their respective third-party service fields.', 'sometextdomain' )
                    )
                )
            )
        );
    }

    public function settings_my_nother_custom_field_type()
    {
        ?>
        <div>
            My custom field contains a bunch of settings:
        </div>
        <?php
        $this->settings_text(
            array(
                'label' => 'Item 1',
                'name' => 'my_custom[1]',
                'default_value' => 'Item 1'
            )
        );
        $this->settings_text(
            array(
                'label' => 'Item 2',
                'name' => 'my_custom[2]',
                'default_value' => 'Item 2'
            )
        );
    }

    public function settings_my_custom_field_type($field, $echo = true)
    {
        echo '<div>' . esc_html__('My custom field contains a few settings:', 'rcp-gravity-forms') . '</div>';

// get the text field settings from the main field and then render the text field
        $text_field = $field['args']['text'];
        $this->settings_text($text_field);

// get the checkbox field settings from the main field and then render the checkbox field
        $checkbox_field = $field['args']['checkbox'];
        $this->settings_checkbox($checkbox_field);
    }

    public function is_valid_setting($value)
    {
        return strlen($value) > 5;
    }

    public function standard_fields_for_feed_mapping() {
        return array(
            array(
                'name'          => 'username',
                'label'         => esc_html__( 'Username', 'rcp-gravity-forms' ),
                'required'      => true,
                'field_type'    => array( 'name', 'username', 'hidden' ),
                'default_value' => $this->get_first_field_by_type( 'name', 3 ),
            ),
            array(
                'name'          => 'useremail',
                'label'         => esc_html__( 'Email', 'rcp-gravity-forms' ),
                'required'      => true,
                'field_type'    => array( 'name', 'useremail', 'hidden' ),
                'default_value' => $this->get_first_field_by_type( 'name', 6 ),
            ),
            array(
                'name'          => 'rcp_password',
                'label'         => esc_html__( 'Password', 'rcp-gravity-forms' ),
                'required'      => false,
                'field_type'    => array( 'rcp_password', 'hidden' ),
                'default_value' => $this->get_first_field_by_type( 'password' ),
            ),
            array(
                'label'   => 'Membership Level',
                'type'    => 'select',
                'name'    => 'membership_level',
                'tooltip' => 'This is the tooltip',
                'choices' => array(
                    array(
                        'label' => 'First Choice',
                        'value' => '1'
                    ),
                    array(
                        'label' => 'Second Choice',
                        'value' => '2'
                    ),
                    array(
                        'label' => 'Third Choice',
                        'value' => '3'
                    )
                )
            ),
        );
    }

    public function process_feed( $feed, $entry, $form ) {
        $username = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_username' ) );
        $email = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_useremail' ) );
        $password = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_rcp_password' ) );
        $membership_level = $this->get_field_value( $form, $entry, rgar( $feed['meta'], 'RCPMembershipFields_membership_level' ) );

        $user_id = wp_create_user($username, $password, $email);

        $customer_id = rcp_add_customer( array(
            'user_id' => $user_id
        ) );

        $level_id = '';
        $levels_db = new RCP_Levels();
        $levels    = $levels_db->get_levels( array( 'status' => 'active' ) );

        foreach ($levels as $level) {
            if ($level->name === $membership_level) {
                return $membership_id = $level->id;
            }
        }

        rcp_add_membership( array(
            'customer_id' => $customer_id,
            'object_id' => $level_id,
            'status' => 'active'
        ) );

    }

}