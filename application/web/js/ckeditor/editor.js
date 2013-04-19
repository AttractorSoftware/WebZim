
$(document).ready(function () {
    MetaDataToolBar.init();

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

    $('.carousel').each(function(){
        $(this).carousel({
            interval: false
        });
    });

    $('.editable').click(function() {
        $(this).attr('contenteditable', 'true');
    });
});




var MetaDataToolBar = {
    init: function()
    {
        var container = this._createContainer();
        var form = this.buildForm();
        container.append(form);
        $('body').append(container);
    },

    buildForm: function()
    {
        var $form = this._createForm();
        for(var i = 0; i<this.formElements.length; i++)
        {
            var current = this.formElements[i];
            var $label = this._createLabelForInput(current.name, current.title);
            var $element = this._createTextInputElement(current.name, current.title, current.cssclass);
            var $metaTag = $(current.selector);
            if(current.attribute)
            {
                $element.val($metaTag.attr(current.attribute));
            }
            else
            {
                $element.val($metaTag.text());
            }

            //$element.val($('meta[name="'+current.name+'"]').attr('content'));
            $form.append($label);
            $form.append($element);
        }
        $form.append(this._createSaveButton());
        return $form;
    },

    _createContainer: function()
    {
        var $metaDataToolbar = $(document.createElement('DIV'));
        $metaDataToolbar .attr('id', 'metaDataToolbar');
        return $metaDataToolbar;
    },


    _createForm: function()
    {
        var $form = $(document.createElement('FORM'));
        $form .addClass('form-inline');
        $form.attr('method', 'post');
        $form.on('submit', function(){
            var data = $form.serialize();
            $.post('index.php?update_meta=1',data,
                function(response){
                    alert("changes saved");
                });
            return false;
        });
        return $form;
    },
    _createTextInputElement: function (name, title, cssclass)
    {

        var $textInput = $(document.createElement('INPUT'));
        $textInput.attr('name', name);
        $textInput.attr('id', name);
        $textInput.attr('type', 'text');
        $textInput.addClass(cssclass);
        return $textInput;
    },
    _createLabelForInput: function(name, title)
    {
        var $label = $(document.createElement('LABEL'));
        $label.attr('for', name);
        $label.text(title);
        return $label;
    },

    _createSaveButton: function()
    {
        var $button = $(document.createElement('BUTTON'));
        $button.addClass('btn btn-info');
        $button.attr('type', 'submit');
        $button.text('Save');
        return $button;
    },

    formElements: [
        {
            name: 'document_title',
            title: 'Title:',
            cssclass: 'input-medium',
            selector: 'title',
            attribute: false
        },
        {
            name: 'document_description',
            title: 'Descr:',
            cssclass: 'input-medium',
            selector: 'meta[name="description"]',
            attribute: 'content'

        },
        {
            name: 'document_keywords',
            title: 'Keywords:',
            cssclass: 'input-medium',
            selector: 'meta[name="keywords"]',
            attribute: 'content'
        },
        {
            name: 'document_author',
            title: 'Author:',
            cssclass: 'input-small',
            selector: 'meta[name="author"]',
            attribute: 'content'
        },
        {
            name: 'document_pub_date',
            title: 'Pub:',
            cssclass: 'input-small',
            selector: 'meta[name="dc.date.created"]',
            attribute: 'content'
        },
        {
            name: 'document_edit_date',
            title: 'Edit:',
            cssclass: 'input-small',
            selector: 'meta[name="dc.date.modified"]',
            attribute: 'content'
        }

    ]

}





