updateSlideList();

$('#sermon').change(function () {
    updateSlideList();
});

function updateSlideList() {
    var url = baseUrl + 'ajax/processorAction/' + $('#data-container').data('key') + '/GetSlides';
    console.log(url);
    $.ajax(url, {
        data: {
            sermon: $('#sermon').val()
        },
        success: function (data) {
            $('#slide').html(data);
        }
    });
}

$('#btnCut').unbind('click');
$('#btnCut').click(function () {
    doCut();
    updateSlideList();
});
