var dirtyCategories = 0;

function loadCategoryForm(target, category, idx) {
    console.log(baseUrl + 'settings/categoryForm/' + encodeURIComponent(category));
    dirtyCategories++;
    $.ajax({
        url: baseUrl + 'settings/categoryForm/' + encodeURIComponent(category),
        type: 'GET',
        data: {'idx': idx},
        success: function (data) {
            $(target).replaceWith(data);
            dirtyCategories--;
            if (dirtyCategories == 0) {
                $('.ajax-get-set').each(function () {
                    loadSetForm(this, $(this).data('category'), $(this).data('category-idx'), $(this).data('set'), $(this).data('idx'));
                });
            }
        }
    });
}

function loadSetForm(target, category, catIdx, set, setIdx) {
    console.log(baseUrl + 'settings/setForm/' + encodeURIComponent(category) + '/' + encodeURIComponent(set) + '/' + encodeURIComponent(catIdx) + '/' + encodeURIComponent(setIdx));
    $.ajax({
        url: baseUrl + 'settings/setForm/' + encodeURIComponent(category) + '/' + encodeURIComponent(set) + '/' + encodeURIComponent(catIdx) + '/' + encodeURIComponent(setIdx),
        type: 'GET',
        data: {
            'category': category,
            'set': set,
            'catIdx': catIdx,
            'setIdx': setIdx
        },
        success: function (data) {
            $(target).replaceWith(data);
        }
    });

}

$(document).ready(function () {
    /**
     * Load all category forms
     */
    $('.ajax-get-category').each(function () {
        loadCategoryForm(this, $(this).data('category'), $(this).data('idx'));
    });


    /**
     * Update width on input
     */
    $(document).on('change click keyup keydown', '.set-width', function () {
        $(this).closest('.panel').find('.info-set-width').html($(this).val());
    });
    /**
     * Update height on input
     */
    $(document).on('change click keyup keydown', '.set-height', function () {
        $(this).closest('.panel').find('.info-set-height').html($(this).val());
    });
    /**
     * Update title on input
     */
    $(document).on('change click keyup keydown', '.set-title', function () {
        $(this).closest('.panel').find('.info-set-title').html($(this).val());
    });
    /**
     * Delete set
     */
    $(document).on('click', '.btn-delete-panel', function () {
        $(this).closest('.panel').remove();
    });
    /**
     * Add category
     */
    $('.btn-add-category').click(function (event) {
        event.preventDefault();
        var categoryTitle = prompt('Titel der neuen Gruppe:', '');
        if ((categoryTitle !== null) && (categoryTitle !== '')) {
            var catIdx = $('#accordion .panel:last-child').data('category-idx');
            catIdx++;
            $('#accordion').append('<div class="panel panel-heading" id="category' + catIdx + '" data-category-idx="' + catIdx + '"><span class="fa fa-spinner fa-spin"></span> Neue Gruppe "' + categoryTitle + '" wird erstellt...</div>');
            loadCategoryForm($('#category' + catIdx), categoryTitle, catIdx);
        }
    });
    /**
     * Add set
     */
    $(document).on('click', '.btn-add-set', function (event) {
        var referenceItem = $(this).parent().find('.panel').last();
        var setIdx = referenceItem.data('set-idx') + 1;
        var catIdx = referenceItem.data('category-idx');
        var id = 'set' + catIdx + '_' + setIdx;
        event.preventDefault();
        referenceItem.after('<div class="panel panel-heading" id="' + id + '" data-category-idx="' + catIdx + '" data-set-idx="' + setIdx + '"><span class="fa fa-spinner fa-spin"></span> Neue Vorlage wird erstellt...</div>')
        loadSetForm($('#' + id), referenceItem.data('category'), catIdx, setIdx, setIdx);
        $('#collapse' + catIdx + '_' + setIdx).collapse('show');
    });
    /**
     * Add fieldset
     */
    $(document).on('click', '.btn-add-fieldset', function (event) {
        event.preventDefault();
        var title = prompt('Schlüsselwert für den neuen Abschnitt:', '');
        var provider = $(this).parent().find('fieldset').last().data('provider');
        $(this).parent().find('hr').before('<fieldset data-fieldset="'+title+'" data-provider="'+provider+'"><legend>'+title+'<span class="pull-right"><button class="btn btn-default btn-sm btn-add-field" title="Schlüssel hinzufügen"><span class="fa fa-plus"></span></button><button class="btn btn-danger btn-sm btn-remove-fieldset" title="Abschnitt löschen"><span class="fa fa-trash"></span></button></span></legend></fieldset>');
    });
    /**
     * Delete fieldset
     */
    $(document).on('click', '.btn-remove-fieldset', function (event) {
        event.preventDefault();
        $(this).closest('fieldset').remove();
    });
    /**
     * Add field
     */
    $(document).on('click', '.btn-add-field', function (event) {
        event.preventDefault();
        var title = prompt('Schlüsselwert für das neue Eingabefeld:', '');
        var fieldset = $(this).closest('fieldset')
        var fieldsetName = fieldset.data('fieldset');
        var provider = fieldset.data('provider');
        var key = 'config['+provider+']['+fieldsetName+']['+title+']';
        var fieldType = (title == 'password') ? 'password' : 'text';
        fieldset.append('<div class="form-group"><label for="'+key+'">'+title+'</label><div class="input-group"><input class="form-control" type="'+fieldType+'" name="'+key+'" /><span class="input-group-btn"><button class="btn btn-default btn-remove-field" type="button"><span class="fa fa-trash" style="line-height: inherit !important;" title="Schlüssel löschen"></span></button></span></div></div>');
    });
    /**
     * Delete field
     */
    $(document).on('click', '.btn-remove-field', function(){
        $(this).closest('.form-group').remove();
    });


});