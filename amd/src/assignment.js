/**
 * View assignment page js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* jshint eqeqeq: false */
/* globals M, define */
define(['jquery',
    'theme_bootstrapbase/bootstrap'], function ($,$bootstrapjs) {

    "use strict";

    var assignmentscreen = function () {

        this.start = function () {
            
            // No point doing all the fancy stuff on the submission page.
            if ($('#ond_form').length === 0) {
                return;
            }

            $('#btncancel').on('click',cancelScreen);
            $('#btnsubmit').click(markAsDone);
        };

        // Private functions.
        var cancelScreen = function() {
            window.location = $('#cancelurl').val();
        };

        var markAsDone = function() {
            if ($('#canedit').val() == 1) {
                window.location = M.cfg.wwwroot + "/blocks/homework/set.php?course=" + $('#course').val() + "&edit=" + $('#id').val();
            } else if ($('#nosub').val() == 1) {
                $('#ond_form').submit();
            } else {
                window.location = M.cfg.wwwroot + "/mod/assign/view.php?id=" + $('#id').val() + "&action=editsubmission";
            }
        };
    };

    return new assignmentscreen();
});