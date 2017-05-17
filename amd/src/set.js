/**
 * Set assignment page js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* jshint -W031, eqeqeq: false */
/* globals M, define */
define(['jquery',
    'theme_bootstrapbase/bootstrap',
    'block_homework/jquery.sumoselect',
    'block_homework/bootstrap-switch',
    'block_homework/zebra_tooltips',
    'block_homework/select2',
    'block_homework/datepicker',
    'block_homework/form_validate'], function ($,$bootstrapjs,$sumoselectjs,$bootstrapswitchjs,$zebrajs,$select2js,$dpjs,$validatorjs) {

    "use strict";

    var setscreen = function () {

        var strs = M.str.block_homework;
        
        this.start = function () {
            // No point doing all the fancy stuff on the submission page.
            if ($('#ond_form').length === 0) {
                return;
            }

            $('#btncancel').on('click',cancelScreen);
            $('#btnsubmit').click(function() {
                $validatorjs.validateInputs(customValidate);
            });
            
            if($("#selectcourse").length > 0) {
                $('#btnsubmit').html('Next');
                $('#selectcourse').attr("style","width:30%").select2();
                return;
            }
            
            // Initialise subgroups controlled by switches.
            $('.ond_subgroupcontroller').change(function() {
                var controller = $(this);
                var subgroup_if_on = $("#" + controller.attr("id") + "_subgroup_on");
                var subgroup_if_off = $("#" + controller.attr("id") + "_subgroup_off");
                if ((controller.prop("checked")) || (controller[0].selectedIndex === 0)) {
                    subgroup_if_on.fadeIn();
                    subgroup_if_off.fadeOut();
                } else {
                    subgroup_if_on.fadeOut();
                    subgroup_if_off.fadeIn();
                }
            }).change(); // Makes sure all subgroups shown/hidden initially.

            // Turn checkboxes into nice toggle switches.
            if ($.fn.bootstrapSwitch) {
                $('input[type=checkbox][data-toggle=toggle]').bootstrapSwitch({handleWidth:40,labelWidth:1,onText:strs.on,offText:strs.off});
            }
            
            // Initialise multiselects.
            if ($('#groups').attr("multiple")) {
                $('#groups').SumoSelect({
                    csvDispCount: 5,
                    placeholder: strs.nogrouprestriction,
                    selectAll: true,
                    captionFormatAllSelected: strs.allxselected,
                    search: true
                });
            }
            
            if ($('#users').attr("multiple")) {
                $('#users').SumoSelect({
                    csvDispCount: 5,
                    placeholder: strs.nouserrestriction,
                    selectAll: true,
                    captionFormatAllSelected: strs.allxselected,
                    search: true
                });
            }

            if ($.fn.datepicker) {
                var dateoptions = {
                    autoHide: true,
                    autoPick: true,
                    format: $('#dateformat').val(),
                    date: new Date(Date.parse($('#due').val())),    // new Date(val) behaves oddly, new Date(Date.parse(val)) works!
                    weekStart: 1

                };
                $('#due').datepicker(dateoptions);
                dateoptions.date = new Date(Date.parse($('#available').val()));
                $('#available').datepicker(dateoptions);
            }
            
            new $.Zebra_Tooltips($('.tooltips'), {
                'background_color': 'oldlace',
                'color': 'black',
                'opacity': '1'
            });
        };

        var cancelScreen = function() {
            var url = M.cfg.wwwroot;
            var courseid = $('#course').val();
            if (typeof(courseid) != 'undefined') {
                url += "/course/view.php?id=" + courseid;
            }
            window.location = url;
        };
        
        var customValidate = function(control){
            var strs = M.str.block_homework;
            var error = '';
            if (control.attr("id") == "due") {
                if ($('#due').datepicker('getDate') <= $('#available').datepicker('getDate')) {
                    error = strs.duedateinvalid;
                }
            }
            if ((control.attr("id") == "users") || (control.attr("id") == "groups")) {
                if ($('#reqrestrict').val() == '1') {
                    var usercount = 0;
                    var groupcount = 0;
                    var users = $('#users');
                    var groups = $('#groups');
                    var usersoncourse = false;
                    var groupsoncourse = false;
                    if (typeof(users) != undefined) {
                        if (users.prop("type") == "select-multiple") {
                            usersoncourse = true;
                            var userval = users.val();
                            if (userval != null) {
                                usercount = userval.length;
                            }
                        }
                    }
                    if (typeof(groups) != undefined) {
                        if (users.prop("type") == "select-multiple") {
                            groupsoncourse = true;
                            var groupval = groups.val();
                            if (groupval != null) {
                                groupcount = groupval.length;
                            }
                        }
                    }
                    if ((usercount == 0) && (groupcount == 0) && (usersoncourse || groupsoncourse)) {
                        error = strs.mustrestrict;
                    }
                }
            }
            return error;
        };
    };

    return new setscreen();
});