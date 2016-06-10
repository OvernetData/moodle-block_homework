/**
 * View assignment listing page js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* jshint eqeqeq: false */
/* globals M, define */
define(['jquery',
    'block_homework/filterable_exportable_table'], function ($,$fetable) {

    "use strict";

    var viewscreen = function () {

        var baseurl = M.cfg.wwwroot + '/blocks/homework/ajax/';
        var strs = M.str.block_homework;
        var on_list_view_tab = true;
        var assignments_table_done = false;
        
        this.start = function () {
            
            $('#user1').on('change',function() {
                $('#displayuser').val($('#user1').val());
                $('#user2').val($('#user1').val());
                refresh();
            });
            $('#user2').on('change',function() {
                $('#displayuser').val($('#user2').val());
                $('#user1').val($('#user2').val());
                refresh();
            });
            
            $('#date').on('change',function() {
               refresh(); 
            });
            
            $('#List_view-tab').on('shown.bs.tab', function(e) {
                on_list_view_tab = true;
                refreshAssignmentsList();
            });

            $('#Timetable_view-tab').on('shown.bs.tab', function(e) {
                on_list_view_tab = false;
            });

            refresh();
        };
        
        var refresh = function () {
            $('#date').prop('disabled',true);
            $('#ond_homework_list_loaded').css("display","none");
            $('#ond_homework_list_loading').css("display","block");
            if ($('#ond_homework_timetable_loaded')) {
                $('#ond_homework_timetable_loaded').css("display","none");
                $('#ond_homework_timetable_loading').css("display","block");
            }
            var url = baseurl + 'view_timetable.php';
            var params = { course: $('#course').val(),
                            sesskey: $('#sesskey').val(),
                            date: $('#date').val(),
                            displayuser: $('#displayuser').val(),
                            user: $('#user').val(),
                            usertype: $('#usertype').val(),
                            marking: $('#marking').val() };

            $.ajax({
                    method: "POST",
                    url: url,
                    data: params
                    })
                .done(function(data, textStatus, jqXHR) {
                    if (typeof(data.error) == 'undefined') {
                        $('#ond_homework_list_loaded').html(data.htmllist);
                        assignments_table_done = false;
                        $('#ond_homework_list_loading').css("display","none");
                        $('#ond_homework_list_loaded').css("display","block");
                        refreshAssignmentsList();
                        
                        $('#ond_homework_timetable_loaded').html(data.htmltimetable);
                        $('#ond_homework_timetable_loading').css("display","none");
                        $('#ond_homework_timetable_loaded').css("display","block");
                        $('#ond_homework_timetable td').mouseenter(function(){
                            $(this).children('.ond_homework_timetable_actions').css('visibility','visible');
                        }).mouseleave(function(){
                            $(this).children('.ond_homework_timetable_actions').css('visibility','hidden');
                        });
                    } else {
                        displayError(data.error);
                    }
                    $('#date').prop('disabled',false);
            })
            .error(function(jqXHR, textStatus, errorThrown){
                displayError(errorThrown);
            });
        };
        
        var displayError = function (error) {
            error = strs.failedtofetchdata + ':<br>' + error;
            $('#ond_homework_list_loading').css("display","none");
            $('#ond_homework_list_loaded').html('<h4 class="ond_failure">' + error + '<h4>');
            $('#ond_homework_list_loaded').css("display","block");
            if ($('#ond_homework_timetable_loaded')) {
                $('#ond_homework_timetable_loaded').html('<h4 class="ond_failure">' + error + '<h4>');
                $('#ond_homework_timetable_loading').css("display","none");
                $('#ond_homework_timetable_loaded').css("display","block");
            }
        };
        
        // Only update the datatable when visible or it messes up the column headers.
        var refreshAssignmentsList = function () {
            if ($('#ond_homework_list') && !assignments_table_done) {
                if (on_list_view_tab) {
                    var sort_column = $('#ond_homework_list th').length - 1; // Sort by last column (due date).
                    $fetable.initialise("ond_homework_list", [sort_column, "desc"], M.cfg.wwwroot + '/blocks/homework');
                    assignments_table_done = true;
                }
            }
        };
    };

    return new viewscreen();
});