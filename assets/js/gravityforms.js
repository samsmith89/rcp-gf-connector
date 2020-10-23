jQuery(".gfrcp-memberships").change(function (e) {
    const gfrcpTitle = jQuery(this).children("option:selected").text();
    const gfrcpVal = jQuery(this).children("option:selected").val();
    const gfrcpPrice = jQuery(this).children("option:selected").attr('data-price');
    if (gfrcpVal !== 'select') {
        jQuery("#field_choices li .gf_insert_field_choice").last().click();
        jQuery("#field_choices li .field-choice-text").last().val(gfrcpTitle);
        jQuery("#field_choices li .field-choice-text").last().attr("value", gfrcpTitle)
        jQuery("#field_choices li .field-choice-value").last().val(gfrcpVal);
        jQuery("#field_choices li .field-choice-value").last().attr("value", gfrcpVal)
        jQuery("#field_choices li .field-choice-price").last().val(gfrcpPrice);
        jQuery("#field_choices li .field-choice-price").last().attr("value", gfrcpPrice);
        jQuery("#field_choices li .field-choice-text").trigger('input');
        jQuery("#field_choices li .field-choice-price").trigger('input');
    }
});
jQuery("#field_choices").on("click", function () {
    jQuery(".gfrcp-membership-field .field-choice-value").prop('disabled', true);
    jQuery(".gfrcp-membership-field .field-choice-price").prop('disabled', true);
});
jQuery("#field_choices").on("keydown", function () {
    jQuery(".gfrcp-membership-field .field-choice-value").prop('disabled', true);
    jQuery(".gfrcp-membership-field .field-choice-price").prop('disabled', true);
});
jQuery(".gfrcp-membership-field .choices_setting").show();
jQuery(".gfrcp-membership-field .gfield_settings_choices_container").addClass('choice_with_price');


