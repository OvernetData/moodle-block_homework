/**
 * Form validation js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* jshint eqeqeq: false */
/* globals M, define */
define(['jquery',
    'theme_bootstrapbase/bootstrap',
    'block_homework/zebra_tooltips'], function ($,$bootstrapjs,$zebrajs) {

    "use strict";

    var validator = function () {
    };

    validator.prototype.validateInputs = function(customValidateFunc,customSubmitFunc){
        $('.ond_validation_error').parents('.Zebra_Tooltip').remove();
        var invalid_count = 0;
        var strs = M.str.block_homework;
        $('input,textarea').filter('.ond_required, .ond_validate').each(function(elementindex){
            var error = "";
            var valid = true;
            var control_visible = true;
            var subgroup = $(this).parent().parent();
            var insubgroup = subgroup.hasClass('ond_controlsubgroup');
            if (insubgroup) {
                control_visible = subgroup.css("display") == 'block';
            }
            if (control_visible) {
                var inputtype = $(this).prop("type");
                var inputvalue = $(this).val();
                
                if (typeof(customValidateFunc) != 'undefined') {
                    error = customValidateFunc($(this));
                    if (error !== '') {
                        valid = false;
                    }
                }
                if (inputtype == 'number') {
                    var inputmin = parseFloat($(this).prop("min"));
                    var inputmax = parseFloat($(this).prop("max"));
                    if ((parseFloat(inputvalue) < inputmin) || (inputvalue > inputmax)) {
                        error = strs.valuerange + " " + inputmin.toString() + " - " +inputmax.toString();
                        valid = false;
                    }
                }

                if ((inputvalue === '') && ($(this).hasClass('ond_required'))) {
                    error = strs.requiredfield;
                    valid = false;
                }
            }
            
            if (!valid) {
                $(this).addClass("ond_required_invalid");
                if (invalid_count === 0) {
                    // Focus on first error.
                    var focused_tab_id = $('.tab-pane.active').prop('id');
                    var error_on_tab_id = $(this).parents('.tab-pane').prop('id');
                    if (focused_tab_id != error_on_tab_id) {
                        // Tab the control is on isn't visible so make it so...
                        $('#' + focused_tab_id).removeClass('active'); // ...make current tab contents invisible...
                        $('#' + focused_tab_id + '-tab').parent().removeClass('active'); // ...turn off active on tab header...
                        // makes the tab active immediately so .focus works and tooltip position is correct.
                        $('#' + error_on_tab_id).addClass('active').removeClass('fade').parent().addClass('active');
                        $('#' + error_on_tab_id + '-tab').parent().addClass('active'); // Turn on active on tab header.
                        $(this).focus();
                        //$('#' + error_on_tab_id + '-tab').tab('show'); // on its own this would be too slow
                    }
                }
                if ($(this).is(":visible")) {
                    error = '<p class="ond_validation_error">' + error + '</p>';
                    var error_popup = new $.Zebra_Tooltips($(this), { background_color: "#faa", color: "black", opacity: 1, content: error });
                    error_popup.show($(this),true);
                }
                invalid_count++;
            } else {
                $(this).attr("placeholder","");
                $(this).removeClass("ond_required_invalid");
            }
        });
        if (invalid_count === 0) {
            if (typeof(customSubmitFunc) == "undefined") {
                $('#ond_form').submit();
            } else {
                customSubmitFunc();
            }
        } else {
            // Fade error message in, leave for a while, fade out...
            $('#ond_validationfailed').fadeIn(1000).delay(5000).fadeOut(1000, function() {
                // ...and remove error popups too.
                $('.ond_validation_error').parents('.Zebra_Tooltip').remove();
            });
            this.showDialog(strs.oops,$('#ond_validationfailed').text(),strs.okbutton);
        }
    };
    
    validator.prototype.showDialog = function(title,content,button) {
        var html = '<div id="dynamicmodal" class="modal fade" tabindex="-1" role="dialog" arial-labelledby="confirm-modal" aria-hidden="true">';
        html += '<div class="modal-dialog">';
        html += '<div class="modal-content">';
        html += '<div class="modal-header"><a class="close" data-dismiss="modal">×</a><h4>' + title + '</h4></div>';
        html += '<div class="modal-body">' + content + '</div>';
        html += '<div class="modal-footer"><span class="ond_material_button_raised" data-dismiss="modal">' + button + '</span></div>';
        html += '</div></div></div>';
        $('body').append(html);
        $("#dynamicmodal").modal('show');
        $('#dynamicmodal').on('hidden.bs.modal', function (e) {
            $(this).remove();
        });
    };
    
    return new validator();
});