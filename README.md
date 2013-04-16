# WebZim Project
## Description
Simple CMS that uses html files to store its contents.

The main idea of creation such system was inspired by Zim project.

The html files are created based on list of templates defined by the user in the templates folder.
Template files have to contain following lines in the head section of the structure.

    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/ckeditor/ckeditor.js"></script>
    <script type="text/javascript" src="js/ckeditor/editor.js"></script>


Editable areas have be created in the following way

    <div class="hero-unit editable" name="zimeditor-1">
        Some dummy text
    </div>

Where `class` has to be `editable` and it is required to specify the `name` attribute for the editable area.


