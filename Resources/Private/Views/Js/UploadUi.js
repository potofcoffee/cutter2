/* 
 * CUTTER
 * Versatile Image Cutter and Processor
 * http://github.com/VolksmissionFreudenstadt/cutter
 *
 * Copyright (c) 2015 Volksmission Freudenstadt, http://www.volksmission-freudenstadt.de
 * Author: Christoph Fischer, chris@toph.de
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function getLegalText() {
    if ($('#provider_user').val() !== '') {
        var legal = $('#provider_site').val() + ' / ' + $('#provider_user').val();
    } else {
        var legal = $('#legal_freetext').val();
    }
    return legal;
}

$(document).ready(function () {

    $("#fileuploader").uploadFile({
        url: CUTTER_baseUrl+'acquisition/receive',
        filename: 'upload',
        downloadStr: 'Hochladen',
        cancelStr: 'Abbrechen',
        abortStr: 'Abbrechen',
        doneStr: 'Fertig',
        dragDropStr: "<span><b>... oder eine Datei hierher ziehen.</b></span>",
        maxFileCount: 1,
        maxFileSize: 100 * 1024 * 1024,
        multiple: false,
        showProgress: true,
        onSubmit: function (files)
        {
            console.log('onSubmit');
            $('#legal').val(getLegalText());
            $("#eventsmessage").html($("#eventsmessage").html() + "<br/>Submitting:" + JSON.stringify(files));
        },
        onSuccess: function (files, data, xhr)
        {
            console.log('onSuccess');
            $("#eventsmessage").html($("#eventsmessage").html() + "<br/>Success for: " + data);
            window.location.href = CUTTER_baseUrl+'acquisition/uploaded';

        },
        afterUploadAll: function ()
        {
            console.log('afterUploadAll');
            $("#eventsmessage").html($("#eventsmessage").html() + "<br/>All files are uploaded");
            window.location.href = CUTTER_baseUrl+'ui/index';
            //$('#fileupload').submit();

        },
        onError: function (files, status, errMsg)
        {
            console.log('onError '+ JSON.stringify(files));
            $("#eventsmessage").html($("#eventsmessage").html() + "<br/>Error for: " + JSON.stringify(files));
        }
    });
    $('#fileupload').hide();
    //$('#origUploadButton').hide();


    $('#crossloadSubmit').click(function () {
        //alert('Hi');
        window.location.href = '?action=import&url=' + encodeURIComponent($('#url').val());
    });

    $('li.providerOption a').click(function () {
        var v = $(this).html();
        $('#btnProviderSite').html(v);
        $('#provider_site').val(v);
    });

    $('#myTab').tab();

});

