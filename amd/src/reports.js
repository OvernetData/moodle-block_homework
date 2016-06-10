/**
 * Reports page js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* jshint eqeqeq: false */
/* globals M, define, Chart */
define(['jquery',
    'theme_bootstrapbase/bootstrap', 
    'block_homework/filterable_exportable_table',
    'block_homework/Chart', 
    'block_homework/select2'], function ($,$bootstrapjs,$fetable,$chartjs,$select2js) {

    "use strict";

    var reports = function () {

        var chartinstance1 = false;
        var chartinstance2 = false;
        var chartinstance3 = false;
        var chartinstance4 = false;
        var chartinstance5 = false;
        var grouptabledone = false;
        var studenttabledone = false;
        var schooltabledone = false;
        var baseurl = M.cfg.wwwroot + '/blocks/homework/ajax/';
        var strs = M.str.block_homework;
        
        this.start = function (eduLinkPresent) {
            
            $('#user').on('change',refreshStaff).attr("style","width:30%").select2();
            $('#from_staff').on('change',refreshStaff);
            $('#to_staff').on('change',refreshStaff);

            if ($('#grouptableholder').length) {
                $('#group').on('change',refreshGroup).attr("style","width:30%").select2();
                $('#from_group').on('change',refreshGroup);
                $('#to_group').on('change',refreshGroup);
            }

            var lookup_student_url = M.cfg.wwwroot + "/blocks/homework/ajax/lookup_user.php";
            if (eduLinkPresent) {
                lookup_student_url = M.cfg.wwwroot + "/blocks/mis_portal/ajax/homework_lookup_student.php";
            }
            
            $('#student').on('change',refreshStudent).attr("style","width:30%").select2({
                ajax: {
                    url: lookup_student_url,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term, // Search term.
                            page: params.page
                        };
                    },
                    processResults: function (data, params) {
                        // Parse the results into the format expected by Select2
                        // since we are using custom formatting functions we do not need to
                        // alter the remote JSON data, except to indicate that infinite
                        // scrolling can be used.
                        params.page = params.page || 1;

                        return {
                            results: data.data.items,
                            pagination: {
                                more: (params.page * 30) < data.data.total_count
                            }
                        };
                    },
                    cache: true
                },
                escapeMarkup: function (markup) { return markup; }, 
                minimumInputLength: 2,
                templateResult: formatStudentResponse,
                templateSelection: formatStudentSelection
            });
            $('#from_student').on('change',refreshStudent);
            $('#to_student').on('change',refreshStudent);

            $('#from_school').on('change',refreshSchool);
            $('#to_school').on('change',refreshSchool);

            $('#Group_Grades-tab').on('shown.bs.tab', function(e) {
                if (!grouptabledone) {
                    grouptabledone = true;
                    refreshGroup();
                }
            });

            $('#Student_Grades-tab').on('shown.bs.tab', function(e) {
                if (!studenttabledone) {
                    studenttabledone = true;
                    refreshStudent();
                }
            });

            $('#Subjects_and_Staff-tab').on('shown.bs.tab', function(e) {
                if (!schooltabledone) {
                    schooltabledone = true;
                    refreshSchool();
                }
            });

            refreshStaff();
        };

        var clearCanvas = function(id, text) {
            if (typeof(text) == 'undefined') {
                text = strs.loadingdata;
            }
            var canvas = document.getElementById(id);
            var ctx = canvas.getContext('2d');
            ctx.clearRect(0,0,canvas.width,canvas.height);
            ctx.textBaseline = 'middle'; 
            ctx.textAlign = 'center'; 
            ctx.fillStyle = '#888888';
            ctx.font = '30pt Helvetica';
            ctx.fillText(text, canvas.width / 2, canvas.height / 2);
        };
        
        var refreshStaff = function () {
            $('#user').prop('disabled',true);
            $('#from_staff').prop('disabled',true);
            $('#to_staff').prop('disabled',true);
            clearCanvas('mychart1');
            clearCanvas('mychart2');
            clearCanvas('mychart3');
            var url = baseurl + 'reports_staff.php';
            var params = { course: $('#course').val(),
                            sesskey: $('#sesskey').val(),
                            user: $('#user').val(),
                            from: $('#from_staff').val(),
                            to: $('#to_staff').val() };

            $.ajax({
                    method: "POST",
                    url: url,
                    data: params
                    })
                .done(function(data, textStatus, jqXHR) {
                    if (typeof data.error == 'undefined') {
                        var ctx1 = document.getElementById('mychart1').getContext('2d');
                        if (chartinstance1) {
                            chartinstance1.destroy();
                        }
                        chartinstance1 = new Chart(ctx1).Bar(data.chart1);

                        var ctx2 = document.getElementById('mychart2').getContext('2d');
                        if (chartinstance2) {
                            chartinstance2.destroy();
                        }
                        chartinstance2 = new Chart(ctx2).Bar(data.chart2);

                        var ctx3 = document.getElementById('mychart3').getContext('2d');
                        if (chartinstance3) {
                            chartinstance3.destroy();
                        }
                        chartinstance3 = new Chart(ctx3).Pie(data.chart3, {
                            showTooltips: true,
                            onAnimationComplete: function() {
                                this.showTooltip(this.segments, true);
                            },
                            tooltipTemplate: "<%= label %> - <%= value %>"
                        });

                    } else {
                        clearCanvas('mychart1', data.error);
                        clearCanvas('mychart2', data.error);
                        clearCanvas('mychart3', data.error);
                    }
                    $('#user').prop('disabled',false);
                    $('#from_staff').prop('disabled',false);
                    $('#to_staff').prop('disabled',false);
                })
                .error(function(jqXHR, textStatus, errorThrown){
                    clearCanvas('mychart1', errorThrown);
                    clearCanvas('mychart2', errorThrown);
                    clearCanvas('mychart3', errorThrown);
                    $('#user').prop('disabled',false);
                    $('#from_staff').prop('disabled',false);
                    $('#to_staff').prop('disabled',false);
                });
        };

        var refreshGroup = function () {
            var grouptableholder = $('#grouptableholder');
            if (grouptableholder.length) {
                $('#group').prop('disabled',true);
                $('#from_group').prop('disabled',true);
                $('#to_group').prop('disabled',true);
                $('#groupgrades_loaded').css("display","none");
                $('#groupgrades_loading').css("display","block");
                var url = baseurl + 'reports_group.php';
                var params = { course: $('#course').val(),
                                sesskey: $('#sesskey').val(),
                                group: $('#group').val(),
                                from: $('#from_group').val(),
                                to: $('#to_group').val() };

                $.ajax({
                        method: "POST",
                        url: url,
                        data: params
                        })
                    .done(function(data, textStatus, jqXHR) {
                        if (typeof(data.error) == 'undefined') {
                            grouptableholder.html(data.html);
                            $fetable.initialise("groupgrades", null, M.cfg.wwwroot + '/blocks/homework');
                        } else {
                            $('#groupgrades_loading').css("display","none");
                            $('#groupgrades_loaded').html('<h4 class="ond_failure">' + data.error + '</h4>');
                            $('#groupgrades_loaded').css("display","block");
                        }
                        $('#group').prop('disabled',false);
                        $('#from_group').prop('disabled',false);
                        $('#to_group').prop('disabled',false);
                    })
                    .error(function(jqXHR, textStatus, errorThrown){
                        $('#groupgrades_loading').css("display","none");
                        $('#groupgrades_loaded').html('<h4 class="ond_failure">' + errorThrown + '</h4>');
                        $('#groupgrades_loaded').css("display","block");
                        $('#group').prop('disabled',false);
                        $('#from_group').prop('disabled',false);
                        $('#to_group').prop('disabled',false);
                    });
            }
        };

        var refreshStudent = function(){
            var studentid = $('#student').val();
            if (studentid) {
                $('#student').prop('disabled',true);
                $('#from_student').prop('disabled',true);
                $('#to_student').prop('disabled',true);
                $('#studentgrades_loaded').css("display","none");
                $('#studentgrades_loading').css("display","block");
                var url = baseurl + 'reports_student.php';
                var params = { course: $('#course').val(),
                                sesskey: $('#sesskey').val(),
                                student: studentid,
                                from: $('#from_student').val(),
                                to: $('#to_student').val() };

                $.ajax({
                        method: "POST",
                        url: url,
                        data: params
                        })
                    .done(function(data, textStatus, jqXHR) {
                        if (typeof(data.error) == 'undefined') {
                            $('#studenttableholder').html(data.html);
                            $fetable.initialise("studentgrades", null, M.cfg.wwwroot + '/blocks/homework');
                        } else {
                            $('#selectstudentmessage').css("display","none");
                            $('#studentgrades_loading').css("display","none");
                            $('#studentgrades_loaded').html('<h4 class="ond_failure">' + data.error + '</h4>');
                            $('#studentgrades_loaded').css("display","block");
                        }
                        $('#student').prop('disabled',false);
                        $('#from_student').prop('disabled',false);
                        $('#to_student').prop('disabled',false);
                    })
                    .error(function(jqXHR, textStatus, errorThrown){
                        $('#selectstudentmessage').css("display","none");
                        $('#studentgrades_loading').css("display","none");
                        $('#studentgrades_loaded').html('<h4 class="ond_failure">' + errorThrown + '</h4>');
                        $('#studentgrades_loaded').css("display","block");
                        $('#student').prop('disabled',false);
                        $('#from_student').prop('disabled',false);
                        $('#to_student').prop('disabled',false);
                    });
            }
        };
        
        var formatStudentResponse = function(data) {
            if (data.loading) return data.text;
            var markup = "<div class='ond_student_lookup clearfix'>";
            if (data.photourl) {
                markup += "<div class='ond_student_lookup_avatar'><img src='" + data.photourl + "' /></div>";
            }
            markup += "<div class='ond_student_lookup_meta'>" +
                "<div class='ond_student_lookup_name'>" + data.forename + " " + data.surname + "</div>" +
                "<div class='ond_student_lookup_attributes'>";
            if (data.yeargroup) {
                markup += "<div class='ond_student_lookup_year'>" + strs.year + " " + data.yeargroup + "</div>";
            }
            if (data.formgroup) {
                markup += "<div class='ond_student_lookup_form'>" + strs.formgroup + " " + data.formgroup + "</div>";
            }
            if (data.community) {
                markup += "<div class='ond_student_lookup_community'>" + strs.community +" " + data.community + "</div>";
            }
            markup += "</div></div></div>";

            return markup;
        };

        var formatStudentSelection = function(data) {
            return (data.forename + ' ' + data.surname) || data.text;
        };

        var refreshSchool = function () {
            $('#from_school').prop('disabled',true);
            $('#to_school').prop('disabled',true);
            clearCanvas('mychart4');
            clearCanvas('mychart5');
            $('#staffstatistics_loaded').css("display","none");
            $('#staffstatistics_loading').css("display","block");
            var url = baseurl + 'reports_school.php';
            var params = { course: $('#course').val(),
                            sesskey: $('#sesskey').val(),
                            from: $('#from_school').val(),
                            to: $('#to_school').val() };

            $.ajax({
                    method: "POST",
                    url: url,
                    data: params
                    })
                .done(function(data, textStatus, jqXHR) {
                    if (typeof data.error == 'undefined') {
                        var ctx4 = document.getElementById('mychart4').getContext('2d');
                        if (chartinstance4) {
                            chartinstance4.destroy();
                        }
                        chartinstance4 = new Chart(ctx4).Pie(data.chart4, {
                            showTooltips: true,
                            onAnimationComplete: function() {
                                this.showTooltip(this.segments, true);
                            },
                            tooltipTemplate: "<%= label %> - <%= value %>"
                        });
                        var ctx5 = document.getElementById('mychart5').getContext('2d');
                        if (chartinstance5) {
                            chartinstance5.destroy();
                        }
                        chartinstance5 = new Chart(ctx5).Pie(data.chart5, {
                            showTooltips: true,
                            onAnimationComplete: function() {
                                this.showTooltip(this.segments, true);
                            },
                            tooltipTemplate: "<%= label %> - <%= value %>"
                        });
                        
                        $('#staffstatisticstableholder').html(data.html);
                        $fetable.initialise("staffstatistics", null, M.cfg.wwwroot + '/blocks/homework');

                    } else {
                        clearCanvas('mychart4', data.error);
                        clearCanvas('mychart5', data.error);
                        $('#staffstatistics_loading').css("display","none");
                        $('#staffstatistics_loaded').html('<h4 class="ond_failure">' + data.error + '</h4>');
                        $('#staffstatistics_loaded').css("display","block");
                    }
                    $('#from_school').prop('disabled',false);
                    $('#to_school').prop('disabled',false);
                })
                .error(function(jqXHR, textStatus, errorThrown){
                    clearCanvas('mychart4', errorThrown);
                    clearCanvas('mychart5', errorThrown);
                    $('#staffstatistics_loading').css("display","none");
                    $('#staffstatistics_loaded').html('<h4 class="ond_failure">' + errorThrown + '</h4>');
                    $('#staffstatistics_loaded').css("display","block");
                    $('#from_school').prop('disabled',false);
                    $('#to_school').prop('disabled',false);
                });
        };
    };

    return new reports();
});