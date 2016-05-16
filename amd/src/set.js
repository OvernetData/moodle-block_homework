/**
 * Set assignment page js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* jshint -W031, eqeqeq: false */
/* globals M, define, ondEduLinkActivities, ondEduLinkDefaultGroup */
define(['jquery',
    'theme_bootstrapbase/bootstrap',
    'block_homework/jquery.sumoselect',
    'block_homework/bootstrap-switch',
    'block_homework/zebra_tooltips',
    'block_homework/select2',
    'block_homework/form_validate'], function ($,$bootstrapjs,$sumoselectjs,$bootstrapswitchjs,$zebrajs,$select2js,$validatorjs) {

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
            
            $('#activity').on('change',activityChanged);

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
                    placeholder: strs.sethomeworkforall,
                    selectAll: true
                });
            }
            
            new $.Zebra_Tooltips($('.tooltips'), {
                'background_color': 'oldlace',
                'color': 'black',
                'opacity': '1'
            });

            activityChanged();
        };

        var cancelScreen = function() {
            var url = M.cfg.wwwroot;
            var courseid = $('#course').val();
            if (typeof(courseid) != 'undefined') {
                url += "/course/view.php?id=" + courseid;
            }
            window.location = url;
        };
        
        var activityChanged = function () {
            var activity_id = $('#activity').val();
            var name = "";
            var description = "";
            var gradingscaleid = 0;
            if (activity_id !== "0") {
                // Use or clone existing activity (use.xx or clone.xx).
                var clone = activity_id.slice(1) == 'c';
                activity_id = activity_id.slice(activity_id.indexOf('.')+1);
                console.log(activity_id);
                name = ondEduLinkActivities[activity_id].name;
                if (clone) {
                    name = strs.copyof_ + name;
                }
                var avail = ondEduLinkActivities[activity_id].availability;
                var allconditionsaregroups = true;
                var groups = [];
                if (avail != null) {
                    for (var i = 0; i < avail.c.length; i++) {
                        if (avail.c[i].type != "group") {
                            allconditionsaregroups = false;
                            break;
                        } else {
                            groups.push(avail.c[i].id);
                        }
                    }
                }
                if (allconditionsaregroups) {
                    if ($('#groups').attr("type") != "hidden") {
                        $('#groups').val(groups);
                        $('#groups')[0].sumo.reload();
                    }
                }
                description = ondEduLinkActivities[activity_id].description;
                gradingscaleid = ondEduLinkActivities[activity_id].grade;
            } else {
                if (typeof(ondEduLinkDefaultGroup) != 'undefined') {
                    if ($('#groups').attr("type") != "hidden") {
                        var groups = [ondEduLinkDefaultGroup];
                        $('#groups').val(groups);
                        $('#groups')[0].sumo.reload();
                    }
                }
            }
            $('#name').val(name);
            $('#description').val(description);
            $('#gradingscale').val(gradingscaleid);
        };

        var customValidate = function(control){
            var strs = M.str.block_homework;
            var error = '';
            if (control.attr("id") == "due") {
                if (control.val() <= $('#available').val()) {
                    error = strs.duedateinvalid;
                }
            }
            return error;
        };
    };

    return new setscreen();
});