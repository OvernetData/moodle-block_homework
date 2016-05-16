/**
 * Homework block js
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/* jshint -W031, eqeqeq: false */
/* globals define */
define([], function () {

    "use strict";

    var blockscreen = function () {

        this.start = function () {

            // Watch for assignment items being removed on course screen and
            // remove from our block dom structure if so.
            var ond_mo_target = document.querySelector('ul.weeks');
            if (ond_mo_target) {
                var ond_mo_config = { childList: true, subtree: true };
                var ond_mo_observer = new MutationObserver(function(mutations){
                    mutations.forEach(function(mutation){
                        if (mutation.type === 'childList') {
                            for (var i = 0; i < mutation.removedNodes.length; i++ ) {
                                var node = mutation.removedNodes[i];
                                if ((node.className.indexOf('activity') > -1) &&
                                    (node.className.indexOf('modtype_assign') > -1)) {
                                    var id = node.id.replace('module-','');
                                    var blockentry = document.querySelector('#ond_homework_item_' + id);
                                    if (blockentry) {
                                        blockentry.parentElement.removeChild(blockentry);
                                    }
                                }
                            }
                        }
                    });
                });
                ond_mo_observer.observe(ond_mo_target,ond_mo_config);
            }
        };
    };

    return new blockscreen();
});