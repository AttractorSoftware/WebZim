$(document).ready(function () {
    var editableBlocks = $('.editable');
    editableBlocks.attr('contenteditable', 'true')
    for (var i = 0; i < editableBlocks.length; i++) {
        CKEDITOR.inline(editableBlocks[i], { "extraPlugins": "imagebrowser",
            "imageBrowser_listUrl": "/index.php?images=1"});
    }
    $(document).click(function(e)
    {
        if (e.ctrlKey && $(e.target).prop('tagName')=="A")
        {
            window.open(e.target.href,'_blank');
        }
    });

    //
    /*var dropTarget = $('.dropTarget'),
        html = $('html'),
        showDrag = false,
        timeout = -1;

    html.bind('dragenter', function () {
        dropTarget.show();
        showDrag = true;
    });
    html.bind('dragover', function(){
        showDrag = true;
        dropTarget.show();
    });
    html.bind('drop', function (e) {
        showDrag = false;
        clearTimeout( timeout );
        timeout = setTimeout( function(){
            if( !showDrag ){ dropTarget.hide(); }
        }, 200 );
    });*/
});





