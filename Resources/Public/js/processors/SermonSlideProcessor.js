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

/**
 * Update the slide select box
 */
function updateSlideSelect() {
    var ajaxUrl = baseUrl+'ajax/getData/' + $('#data-container').data('key') + '/slides/?sermon='+$('#sermon').val();
    console.log('Calling AJAX: '+ ajaxUrl);
    $.getJSON(ajaxUrl, function (opts) {
        $('#slide').html(opts);
    });
}

/**
 * Update arguments (here: slide select) after a successful cut
 *
 * This function will be called by the cutter js.
 */
function updateArgumentsAfterCut() {
    updateSlideSelect();
}

updateSlideSelect();
