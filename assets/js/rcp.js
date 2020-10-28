if (jQuery('#gfrcp_is_connected').prop('checked')) {
    jQuery("#rcp-duration-unit, #rcp-duration, #rcp-maximum-renewals-setting, #trial_duration, #trial_duration_unit, #rcp-fee").prop('disabled', true);
    jQuery("#rcp-duration-unit, #rcp-maximum-renewals-setting, #rcp-fee, #trial_duration_unit").after('<p>This setting is now configured in the Gravity Form gateway feed settings of your form.</p>');

    jQuery('#rcp-edit-subscription').on("submit", function () {

        alert("Be sure to update any current active membership fields in Gravity Forms. Updates made here will not update any preexisting membership fields. Only newly created fields");
    });
}