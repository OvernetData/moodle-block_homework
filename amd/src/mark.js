/**
 * Mark assignment page js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* jshint eqeqeq: false */
/* globals M, define */

define(['jquery',
    'block_homework/filterable_exportable_table',
    'block_homework/bootstrap-switch',
    'block_homework/form_validate'], function ($,$fetable,$bootstrapswitchjs,$validatorjs) {

    "use strict";

    var markscreen = function () {

        var strs = M.str.block_homework;
        var achievementpoints = [], behaviourpoints = [];
        var errors = [];
        
        this.start = function (pointslist) {
            achievementpoints = pointslist.achievement;
            behaviourpoints = pointslist.behaviour;
            
            // No point doing all the fancy stuff on the submission page.
            if ($('#ond_form').length === 0) {
                return;
            }

            $('#btncancel').on('click',cancelScreen);
            $('#btnsubmit').click(function() {
                $validatorjs.validateInputs(customValidate,customSubmit);
            });

            // initialise subgroups controlled by switches.
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
                $('input[type=checkbox][data-toggle=toggle]').bootstrapSwitch({
                    handleWidth: 40,
                    labelWidth: 1,
                    onText: strs.on,
                    offText: strs.off});
            }

            $('#achievementtype').on('change',achievementTypeChanged);
            achievementTypeChanged();
            $('#behaviourtype').on('change',behaviourTypeChanged);
            behaviourTypeChanged();
            
            $('.quickgrade').on('change',function(){
                var ctrl = $(this);
                var tr = ctrl.closest('tr');
                tr.children().css("background-color","#e6ffe6");
                var namebits = ctrl.prop("name").split("_");
                var id = namebits[1];
                $('#modified_' + id).val(1);
            });
            
            $fetable.initialise("ond_assign_marking", [0, "asc"],
                M.cfg.wwwroot + '/blocks/homework', 
                {"paging" : false,
                 "scrollY" : false });
        };

        // private functions
        var cancelScreen = function() {
            window.location = M.cfg.wwwroot + "/blocks/homework/view.php?course=" +
                    $('#course').val() + "&mark=1";
        };

        var customValidate = function(control){
            var error = "";
            // Nothing special at the moment...
            return error;
        };
        
        var achievementTypeChanged = function() {
            var ap = $('#achievementpoints');
            if (ap.length) {
                ap.val(achievementpoints[$('#achievementtype').val()]);
            }
        };

        var behaviourTypeChanged = function() {
            var bp = $('#behaviourpoints');
            if (bp.length) {
                bp.val(behaviourpoints[$('#behaviourtype').val()]);
            }
        };
        
        // Because we're using datatables not all the input elements are actually in the DOM due to
        // the pagination; so a custom submit routine is needed to pull out all of the input elements.
        // And due to the time taken to do achievement/behaviour writebacks we use an asynchronous loop
        // to fire off a load of ajax save requests to enable us to put up a progress bar while it's all
        // happening.
        var customSubmit = function(){
            var bulk_achievement = $('#bulkachievement').prop("checked");
            var bulk_behaviour = $('#bulkbehaviour').prop("checked");
            var data = $fetable.getInputData();
            var learners = $('#learners').val().split(',');
            var actions = [];

            for (var i = 0; i < learners.length; i++) {
                if (data.filter('#modified_' + learners[i]).val() == 1) {
                    var grade = data.filter('[name=quickgrade_' + learners[i] + ']').val();
                    if (grade !== '') {
                        var action_m = { 
                            action: "mark",
                            sesskey: $('#sesskey').val(),
                            cmid: $('#id').val(),
                            learnerid: learners[i],
                            name: data.filter('#name_' + learners[i]).val(),
                            grade: grade,
                            feedback: data.filter('#feedback_' + learners[i]).val()
                        };
                        actions.push(action_m);
                    }
                }
                if (bulk_achievement) {
                    if (data.filter('#unsubmitted_' + learners[i]).val() != 1) {
                        var action_a = { 
                            action: "achievement",
                            sesskey: $('#sesskey').val(),
                            cmid: $('#id').val(),
                            learnerid: learners[i],
                            name: data.filter('#name_' + learners[i]).val(),
                            achievementtype: $('#achievementtype').val(),
                            achievementactivity: $('#achievementactivity').val(),
                            achievementcomments: $('#achievementcomments').val(),
                            achievementpoints: $('#achievementpoints').val(),
                            achievementreporter: $('#achievementreporter').val()
                        };
                        actions.push(action_a);
                    }
                }
                if (bulk_behaviour) {
                    if (data.filter('#unsubmitted_' + learners[i]).val() == 1) {
                        var action_b = { 
                            action: "behaviour",
                            sesskey: $('#sesskey').val(),
                            cmid: $('#id').val(),
                            learnerid: learners[i],
                            name: data.filter('#name_' + learners[i]).val(),
                            behaviourtype: $('#behaviourtype').val(),
                            behaviouractivity: $('#behaviouractivity').val(),
                            behaviourstatus: $('#behaviourstatus').val(),
                            behaviourcomments: $('#behaviourcomments').val(),
                            behaviourpoints: $('#behaviourpoints').val(),
                            behaviourreporter: $('#behaviourreporter').val()
                        };
                        actions.push(action_b);
                    }
                }
            }
            
            if (actions.length === 0) {
                $validatorjs.showDialog(strs.nothingdone,strs.nothingdonefull,strs.ok);
            } else {
                var calls_done = 0;
                asyncLoop(actions,10,function(action, index) {
                    if (action.action === "mark") {
                        updateProgressBar(calls_done / actions.length * 100, strs.gradingassignmentfor + ' ' + action.name);
                        $.post('ajax/mark_assignment.php',action)
                            .done(function(data, textStatus, jqXHR){
                                if (!data.success) {
                                    var error = strs.failedtosetmarkfor + ' ' + data.name + '; ' + data.error;
                                    errors.push(error);
                                }
                            })
                            .fail(function(jqXHR, textStatus, errorThrown){
                                var data = queryStringToObject($(this)[0].data);
                                var error = strs.failedtosetmarkfor + data.name + '; ' + errorThrown;
                                errors.push(error);
                            })
                            .always(function(){
                                calls_done += 0.5;
                                updateProgressBar(calls_done / actions.length * 100, strs.savinggrades);
                                if (calls_done >= actions.length) {
                                    finishedSaving(errors);
                                }
                            });
                    } else if (action.action === "achievement") {
                        updateProgressBar(calls_done / actions.length * 100, strs.addingachievementfor + ' ' + action.name);
                        $.post('ajax/write_behaviour.php',action)
                            .done(function(data, textStatus, jqXHR){
                                if (!data.success) {
                                    var error = strs.failedtoaddachievementfor + ' ' + data.name + '; ' + data.error;
                                    errors.push(error);
                                }
                            })
                            .fail(function(jqXHR, textStatus, errorThrown){
                                var data = queryStringToObject($(this)[0].data);
                                var error = strs.failedtoaddachievementfor + ' ' + data.name + '; ' + errorThrown;
                                errors.push(error);
                            })
                            .always(function(){
                                calls_done += 0.5;
                                updateProgressBar(calls_done / actions.length * 100, strs.savingachievement);
                                if (calls_done >= actions.length) {
                                    finishedSaving(errors);
                                }
                            });
                    } else if (action.action === "behaviour") {
                        updateProgressBar(calls_done / actions.length * 100, strs.addingbehaviourfor + ' ' + action.name);
                        $.post('ajax/write_behaviour.php',action)
                            .done(function(data, textStatus, jqXHR){
                                if (!data.success) {
                                    var error = strs.failedtoaddbehaviourfor + ' ' + data.name + '; ' + data.error;
                                    errors.push(error);
                                }
                            })
                            .fail(function(jqXHR, textStatus, errorThrown){
                                var data = queryStringToObject($(this)[0].data);
                                var error = strs.failedtoaddbehaviourfor + ' ' + data.name + '; ' + errorThrown;
                                errors.push(error);
                            })
                            .always(function(){
                                calls_done += 0.5;
                                updateProgressBar(calls_done / actions.length * 100, strs.savingbehaviour);
                                if (calls_done >= actions.length) {
                                    finishedSaving(errors);
                                }
                            });
                    }
                    calls_done += 0.5;
                    updateProgressBar(calls_done / actions.length * 100);
                    if (calls_done >= actions.length) {
                        finishedSaving(errors);
                    }
                });
            }
        };
        
        var finishedSaving = function(errors) {
            var alldone = strs.alldone;
            var buttons = "";
            if (errors.length > 0) {
                buttons = '<div class="ond_centered">' +
                        '<button id="ond_btn_retry" type="button" class="ond_material_button_raised">' +
                            strs.tryagain + '</button>' +
                        '<button id="ond_btn_cancel" type="button" class="ond_material_button_raised">' +
                            strs.cancel + '</button></div>';
                alldone += ", " + errors.length + " error(s)";
                $('#ond_form_progress').after('<div id="ond_errors"><pre>' + errors.join('<br>') + '</pre></div>').after(buttons);
                $('#ond_btn_retry').on("click",function() {
                    location.reload();
                });
                $('#ond_btn_cancel').on("click", cancelScreen);
            } else {
                buttons = '<div class="ond_centered">' +
                        '<button id="ond_btn_ok" type="button" class="ond_material_button_raised">' +
                            strs.ok + '</button></div>';
                $('#ond_form_progress').after(buttons);
                $('#ond_btn_ok').on("click",cancelScreen);
            }
            updateProgressBar(100, alldone);
        };

        var asyncLoop = function(items, delay, callback) {
            var context = null;
            var i = 0;
            var nextIteration = function() {
                if (i === items.length) {
                    return;
                }
                callback.call(context,items[i],i);
                i++;
                setTimeout(nextIteration, delay);
            };
            nextIteration();
        };
        
        var updateProgressBar = function(percent, text) {
            text = text || strs.saving;
            percent = Math.round(percent);
            var bar = $("#ond_form_progress");
            if (bar.css("display") === "none") {
                $('#ond_form').css("display","none");
                bar.css("display","block");
            }
            var width = percent * bar.width() / 100;
            var html = text + '<div id="ond_form_progress_bar_number">' + percent + "%</div>";
            $("#ond_form_progress_bar").css("width", width).animate(100).html(html);
        };
        
        var queryStringToObject = function(str) {
            var keyvalues = str.split("&");
            var result = {};
            for (var i = 0; i < keyvalues.length; i++) {
                var keyvalue = keyvalues[i].split("=");
                result[keyvalue[0]] = decodeURIComponent(keyvalue[1].replace(/\+/g, '%20') || "");
            }
            return JSON.parse(JSON.stringify(result));
        };
    };

    return new markscreen();
});