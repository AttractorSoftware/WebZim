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


## Installation
The web server need to point to the `application/web` folder

When you open the application for the first time you will have login into the system, so that it will be possible to create
`index.html` file
Just open the url `http://yoursitedomain.com/index.php?login=1`, the system will ask you for username and password. After you
successfully login it will ask to create `index.html` file.
The username and password can be set in the `application/webzim.php` file, variable `$VALID_USERS`

## Usage
Public users can only browse through the html files.

The admin can create them and edit contents of the editable areas


