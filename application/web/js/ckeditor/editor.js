$(document).ready(function () {
    var editableBlocks = $('.editable');
    editableBlocks.attr('contenteditable', 'true')
    for (var i = 0; i < editableBlocks.length; i++) {
        CKEDITOR.inline(editableBlocks[i]);
    }
});





