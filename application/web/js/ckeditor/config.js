/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {

    // %REMOVE_START%
    // The configuration options below are needed when running CKEditor from source files.
    config.plugins = 'dialogui,dialog,about,a11yhelp,basicstyles,blockquote,clipboard,panel,floatpanel,menu,contextmenu,resize,button,toolbar,elementspath,list,indent,enterkey,entities,popup,filebrowser,floatingspace,listblock,richcombo,format,htmlwriter,horizontalrule,wysiwygarea,image,fakeobjects,link,magicline,maximize,pastetext,pastefromword,sourcearea,specialchar,menubutton,scayt,stylescombo,tab,table,tabletools,undo,wsc,save,xml,ajax';
    config.skin = 'moono';
    // %REMOVE_END%

    // Define changes to default configuration here.
    // For the complete reference:
    // http://docs.ckeditor.com/#!/api/CKEDITOR.config

    // The toolbar groups arrangement, optimized for two toolbar rows.
    config.toolbarGroups = [
        { name: 'clipboard',   groups: ['clipboard', 'undo' ] },
        { name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
        { name: 'links' },
        { name: 'insert' },
        { name: 'tools' },
        { name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
        { name: 'others' },
        '/',
        { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
        { name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align' ] },
        { name: 'styles' },
        { name: 'colors' },
        { name: 'about' }
    ];
    config.saveSubmitURL = 'index.php';



    // Remove some buttons, provided by the standard plugins, which we don't
    // need to have in the Standard(s) toolbar.
    //config.removeButtons = 'Underline,Subscript,Superscript';

    // Se the most common block elements.
    //config.format_tags = 'p;h1;h2;h3;pre';
    config.removeFormatTags = '';
    config.removeFormatAttributes='';
    config.allowedContent = true;
    //config.filebrowserImageUploadUrl = 'index.php';
    //config.filebrowserBrowseUrl = 'index.php';
    config.filebrowserUploadUrl = '/index.php?upload=1';
    config.imageBrowser_listUrl = '/index.php';
    // Update field with id 'txtUrl' in the 'tab1' tab when file is uploaded.



    // Make dialogs simpler.
   // config.removeDialogTabs = 'image:advanced;link:advanced';
};

