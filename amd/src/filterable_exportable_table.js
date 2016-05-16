/**
 * DataTables based script for making tables filterable, exportable etc
 *
 * @package    block_homework
 * @copyright  2016 Overnet Data Ltd. (@link http://www.overnetdata.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery','block_homework/jquery.dataTables'], function ($,$datatablesjs) {

    "use strict";

    // constructor
    var filterable_exportable_table = function () {
        
        // private variables
        var table_name = "";
        var sorting_options = [0, "asc"];
        var ond_block_root = "";
        var table_height = 0;
        var focussed_row = null;
        var strs = M.str.block_homework;

        // private functions
        // purely a visual aid, highlight the row we're in
        var tableRowFocussed = function () {
            var row = this;
            if (focussed_row != row) {
                if (focussed_row) {
                    $(focussed_row).removeClass('focussed');
                }
                focussed_row = row;
                $(focussed_row).addClass('focussed');
            }
        };

        var tableHeight = function () {
            var h = $(window).height();
            return h - 200;
        };

        // use Datatables to paginate table, add searching and exporting
        var makeTableScrollable = function(extraOptions) {
            $('#' + table_name + '_loading').css("display","none");
            if ($('#'+table_name).length == 0) {
                console.log("No table found");
                return;
            }
            table_height = tableHeight();
            $('#' + table_name + '_loaded').css("display","block");
            if (!$.fn.dataTableExt) {
                $('<p>', {
                    class: 'alert centered',
                    style: 'margin: auto',
                    text: 'Failed to initialise DataTables plugin - this screen has lost filtering, exporting and paged display functionality.' 
                }).insertBefore($('#'+table_name));
                $('<p>', {
                    class: 'alert centered',
                    style: 'margin: auto',
                    text: 'This may be caused by a theme loading a second copy of the jQuery library without deconflicting it' 
                }).insertBefore($('#'+table_name));
                return;
            }
            var options = {
                "paging": true,
                "ordering": true,
                "info": true,
                "searching": true,
                "scrollYold": table_height,
                "scrollY": '50vh',
                "scrollX": false,
                "scrollCollapse": true,
                "iDisplayLength": 10,
                "order": [sorting_options],
                "dom": 'lf<t><i<"#' + table_name + '_toolbar">><p>',
                "processing": true
            };
            if (typeof(extraOptions) != "undefined") {
                $.extend(options, extraOptions);
            }
            var table = $("#"+table_name).DataTable(options);
            $("#" + table_name + "_toolbar").html('<div class="dataTables_paginate"><select id="' + table_name + '_exportdata" /><select id="' + table_name + '_exporttype" /><a id="' + table_name + '_exportbutton" class="ond_material_button_raised current" href="javascript:;">' + strs.export + '</a></div>')
                    .css("float","left").css("padding-left","1em");
            var select = $('#' + table_name + '_exportdata');
            addOptionToSelect(select,"_",strs.pleaseselect);
            addOptionToSelect(select,"all",strs.exportall);
            addOptionToSelect(select,"filtered",strs.exportfiltered);
            var select = $('#' + table_name + '_exporttype');
            addOptionToSelect(select,"csv",".csv");
            addOptionToSelect(select,"xls",".xls");
            var tablename = table_name;
            $(window).bind("resize",function(){resizeTable(tablename);});
            table.on('click focusin mouseenter', 'tr', tableRowFocussed);
            var button = $('#' + table_name + '_exportbutton');
            button.on('click', function(){exportTable(tablename);});
        };

        var addOptionToSelect = function (control,value,text) {
            control.attr("name",control.attr("id"));
            control.css("margin-right","1em");
            control.append('<option value="' + value + '">' + text + '</option>');    
        };

        var exportTable = function (tableName) {
            if (typeof(tableName) == "undefined") {
                tableName = table_name;
            }
            var filtered = $('#' + tableName + '_exportdata').val();
            if (filtered != "_"){
                var table = $('#'+tableName).DataTable();
                var exporttype = $('#' + tableName + '_exporttype').val();
                var exportfilename = '';
                if (typeof(table_filename) == 'undefined') {
                    exportfilename = tableName.replace("_table","").replace("table","");
                } else {
                    exportfilename = table_filename;
                }
                var dtrows;
                if (filtered == "filtered") {
                    dtrows = table.rows({filter: 'applied'});
                } else {
                    dtrows = table.rows();
                }
                var row = [];
                var rows = [];
                if (dtrows.data().length > 0) {
                    $.each(table.columns().header(),function(index,th){
                        row.push($(th).html());
                    });
                    rows.push(row); // headings
                    
                    dtrows.every(function(rowindex,tableloop,rowloop) {
                       row = [];
                       $.each(this.data(),function(index,td){
                            var node = $(table.cell(rowindex,index).node());
                            // generally favour the actual <td data-for-export=""> attribute
                            var attr = node.attr('data-for-export');
                            if ((attr != undefined) && (attr !== false)) {
                                row.push(attr);
                            // no data-for-export attribute, cells can be returned as data (sourced on the fly)...
                            } else if (typeof(td) == "object") {
                                if (td.hasOwnProperty("display")) {
                                    row.push(td["display"]);
                                } else {
                                    row.push($(td).html());
                                }
                           } else {
                               // ...or as the <td> content (table built in advance), a string...
                               row.push(td);
                           }
                       });
                       rows.push(row);
                    });
                    
                    rows = JSON.stringify(rows);

                    var params = { 
                        filename: exportfilename,
                        type: exporttype,
                        tabledata: rows
                    };
                    var url = ond_block_root + "/ajax/export_table.php";

                    // http://stackoverflow.com/questions/4545311 - see npdu's post
                    var iframe, iframe_doc, iframe_html;
                    if ((iframe = $('#download_iframe')).length === 0) {
                        iframe = $("<iframe id='download_iframe'" +
                                    " style='display: none' src='about:blank'></iframe>"
                                   ).appendTo("body");
                    }

                    iframe_doc = iframe[0].contentWindow || iframe[0].contentDocument;
                    if (iframe_doc.document) {
                        iframe_doc = iframe_doc.document;
                    }

                    // could add target='_blank' to the form here to hide the console 
                    // notice about "Resource interpreted as Document" but the flash of 
                    // an empty window is pretty ugly...
                    iframe_html = '<html><head></head><body><form method="POST" action="' + url +'">' +
                                  '<input type="hidden" name="filename" value="' + exportfilename  +'"/>' + 
                                  '<input type="hidden" name="type" value="' + exporttype  +'"/>' + 
                                  '<input type="hidden" name="tabledata" value="' + encodeURIComponent(rows) + '"/>' + // encode to hide quotes
                                  "</form>" +
                                  "</body></html>";

                    iframe_doc.open();
                    iframe_doc.write(iframe_html);
                    $(iframe_doc).find('form').submit();
                }
            } else {
                alert(strs.allorfiltered);
            }
        };

        var resizeTable = function (tableName) {
            var newth = tableHeight();
            if (table_height != newth) {
                table_height = newth;
                var table = $("#"+tableName).dataTable();
                table.dataTable().fnSettings().oScroll.sY = table_height;
                table.fnAdjustColumnSizing(true);
            }
        };

        // privileged function
        this.initialise = function (tableName, sortingOptions, ondBlockRoot, extraOptions) {
            if ((typeof(tableName) != 'undefined') && (tableName != '')) {
                table_name = tableName;
                if ((typeof sortingOptions !== 'undefined') && (sortingOptions != null)) {
                    sorting_options = sortingOptions;
                }
                ond_block_root = ondBlockRoot;
                makeTableScrollable(extraOptions);
            }
        };

        // if the table contains input elements, only those visible would be submitted
        // so this gets them all ready for submission
        this.getInputData = function (tableName) {
            if (typeof(tableName) == "undefined") {
                tableName = table_name;
            }
            var table = $('#'+tableName).DataTable();
            return table.$('input, select, textarea');
        };
        
    };

    return new filterable_exportable_table();
});