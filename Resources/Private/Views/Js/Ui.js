/* 
 * CUTTER
 * Versatile Image Cutter and Processor
 * http://github.com/potofcoffee/cutter
 *
 * Copyright (c) Christoph Fischer, https://christoph-fischer.org
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

var cropper;
var origPictureHtml;
var cropRestore;
var currentAspectRatio = 0;
var baseUrl = '{{ baseUrl}}';

function updateCoords(c) {
    $('#x').val(c.x);
    $('#y').val(c.y);
    $('#w').val(c.w);
    $('#h').val(c.h);
}


function loadTemplate(category, set) {
    console.log('{{ baseUrl  }}ajax/load/' + encodeURIComponent(category) + '/' + encodeURIComponent(set));
    $.getJSON('{{ baseUrl  }}ajax/load/' + encodeURIComponent(category) + '/' + encodeURIComponent(set), function (data) {

        // display info
        $('#stepTitle').html(data.title);
        $('#stepIcon span').attr('class', 'glyphicon glyphicon-' + data.icon);
        $('#measurements').html(data.w + ' x ' + data.h);

        // additional options
        console.log('Retrieving option fields: ' + '{{ baseUrl  }}ajax/options/' + encodeURIComponent(category) + '/' + encodeURIComponent(set));
        $.getJSON('{{ baseUrl  }}ajax/options/' + encodeURIComponent(category) + '/' + encodeURIComponent(set), function (opts) {
            console.log(opts);

            var form = '';
            var i;

            if (opts.length > 0) {
                $('#templateDataButton').removeClass('disabled');
                $('#templateDataButton a').first().attr('disabled', false);
                for (i = 0; i < opts.length; i++) {
                    if (opts[i]['label']) {
                        form = form + '<div class="form-group">'
                        form = form + '<label for="' + opts[i]['key'] + '">' + opts[i]['label'] + '</label> '
                            + opts[i]['form'];
                        form = form + '</div>'
                    } else {
                        form = form + opts[i]['form'];
                    }
                }
                $('#arguments').html(form);
                for (i = 0; i < opts.length; i++) {
                    $('#' + opts[i]['key']).addClass('additionalArgument');
                }
            } else {
                $('#templateDataButton').addClass('disabled');
                $('#templateDataButton a').first().prop('disabled', 'disabled');
            }
        });

        // set overlay options
        $('#overlayFontFile').val(data.overlayFontName);
        $('#overlayFontSize').val(data.overlayFontSize);
        $('#overlayAlignment').val(data.overlayAlignment);
        $('#overlayAlignmentOption'+data.overlayAlignment).button('toggle');

        // clear results message
        $('#results').html('');
        $('#results').attr('class', '');


        // store current template key
        $('#data-container').data('category', category).data('set', set);

        // reset cropper
        var newAspectRatio = data.w / data.h;
        if (currentAspectRatio !== newAspectRatio) {
            cropper.release();
            cropper.setOptions({
                aspectRatio: newAspectRatio
            });
            currentAspectRatio = newAspectRatio;
        }

        // reset cut button
        $('#btnCut').addClass('btn-success').removeClass('btn-danger').prop('disabled', false).html('<span class="fa fa-scissors"></span> Zuschneiden');
    });
}

function addUriArguments(uri, ids) {
    for (var i=0; i<ids.length; i++) {
        uri = uri + '&'+ ids[i] + '=' + encodeURIComponent($('#'+ids[i]).val());
    }
    return uri;
}

function doCut() {
    var color = $('#textcolor').val();
    if (color == '')
        color = 'ffffff';
    else
        color = color.substring(1, 7);

    var uri = '{{ baseUrl }}cut/do';
    uri = uri + '?x=' + encodeURIComponent($('#x').val());
    uri = uri + '&y=' + encodeURIComponent($('#y').val());
    uri = uri + '&w=' + encodeURIComponent($('#w').val());
    uri = uri + '&h=' + encodeURIComponent($('#h').val());
    uri = uri + '&legal=' + encodeURIComponent($('#legal').val());
    uri = uri + '&category=' + encodeURIComponent($('#data-container').data('category'));
    uri = uri + '&set=' + encodeURIComponent($('#data-container').data('set'));
    uri = uri + '&color=' + encodeURIComponent(color);

    uri = addUriArguments(uri, ['overlayText', 'overlayFontFile', 'overlayFontSize', 'overlayAlignment', 'overlayColor']);

    $('.additionalArgument').each(function () {
        uri = uri + '&' + $(this).attr('id') + '=' + encodeURIComponent($(this).val());
    });

    $('input.form-control').each(function () {
        if ($(this).attr('name').substr(0, 4) == 'meta') {
            uri = uri + '&' + $(this).attr('name') + '=' + encodeURIComponent($(this).val());
        }
    });
    $('#btnCut').addClass('btn-danger').removeClass('btn-success').prop('disabled', true).html('<span class="fa fa-spinner fa-spin"></span> Zuschneiden');
    console.log('Calling CUT action via AJAX at ' + uri);
    $.getJSON(uri, function (data) {
        console.log('Received result package:');
        console.log(data);
        $('#results').show();
        if (data['result'] == 1) {
            $('#results').attr('class', 'alert alert-success');
            $('#results').html('Das Bild wurde erfolgreich zugeschnitten.');
            // force a download, if necessary
            if (data['forceDownload']) {
                $('#resultsFrame').attr('src', '{{ baseUrl }}ui/download?url=' + data['forceDownload']);
            }
            // fade out results message
            $('#results').fadeOut(5000, function () {
                $('#results').hide();
            });
            // reload template (take care of updated data)
            if (typeof updateArgumentsAfterCut === "function") {
                updateArgumentsAfterCut(data);
            }
            $('#btnCut').addClass('btn-success').removeClass('btn-danger').prop('disabled', false).html('<span class="fa fa-scissors"></span> Zuschneiden');
        } else {
            $('#results').attr('class', 'alert alert-danger');
            $('#results').html('Leider ging beim Zuschneiden etwas schief.');
            $('#btnCut').prop('disabled', false).html('<span class="fa fa-scissors"></span> Zuschneiden');
        }


    });
}


$('document').ready(function () {
    $('.templateSelector').click(function () {
        loadTemplate($(this).data('category'), $(this).data('set'));
    });


    origPictureHtml = $('#cropdiv').html();

    cropper = $.Jcrop($('#cropbox'), {
        aspectRatio: 1 / 1,
        onSelect: updateCoords,
        boxWidth: ($('#imageArea').width())
    });

    $('#customAR').hide();
    $('#measurements').show();
    $('#results').hide();

    $('#btnAbort').click(function () {
        window.location.href = '{{ baseUrl }}acquisition/form';
    });

    $('#btnCut').click(function () {
        doCut();
    });


    $('.colorpick').colorpicker({
        component: '#pickerbutton'
    });

    $('.overlaycolorpick').colorpicker({
        component: '#overlayColorPickerButton'
    });

    $('#btnEyeDropper').click(function () {
        var w = $('#cropbox').width();
        var h = $('#cropbox').height();
        cropRestore = {
            select: cropper.tellSelect(),
            selectScaled: cropper.tellScaled(),
            options: cropper.getOptions()
        };
        $('#cropdiv').html(origPictureHtml);
        $('#cropbox').attr('width', w);
        $('#cropbox').attr('height', h);
        //$('#cropbox').height(h);

        $('#cropbox').dropper({
            clickCallback: function (color) {
                $('div#jquery-dropper-hover-chip').remove();
                $('#textcolor').val('#' + color.rgbhex);
                $('#textcolor').trigger('change');
                // restore cropper
                $('#cropdiv').html(origPictureHtml);
                cropper = $.Jcrop($('#cropbox'), cropRestore.options);
                cropper.setSelect(
                    [
                        cropRestore.select.x,
                        cropRestore.select.y,
                        cropRestore.select.x2,
                        cropRestore.select.y2
                    ]);
            }
        });
    });


    $('#btnEyeDropperOverlay').click(function () {
        var w = $('#cropbox').width();
        var h = $('#cropbox').height();
        cropRestore = {
            select: cropper.tellSelect(),
            selectScaled: cropper.tellScaled(),
            options: cropper.getOptions()
        };
        $('#cropdiv').html(origPictureHtml);
        $('#cropbox').attr('width', w);
        $('#cropbox').attr('height', h);
        //$('#cropbox').height(h);

        $('#cropbox').dropper({
            clickCallback: function (color) {
                $('div#jquery-dropper-hover-chip').remove();
                $('#overlayColor').val('#' + color.rgbhex);
                $('#overlayColor').trigger('change');
                // restore cropper
                $('#cropdiv').html(origPictureHtml);
                cropper = $.Jcrop($('#cropbox'), cropRestore.options);
                cropper.setSelect(
                    [
                        cropRestore.select.x,
                        cropRestore.select.y,
                        cropRestore.select.x2,
                        cropRestore.select.y2
                    ]);
            }
        });
    });



    $('.btnOverlayAlignment').click(function(){
       $('#overlayAlignment').val($(this).find('input').first().val());
    });

});

