<?php

require_once('vendor/simplehtml/simple_html_dom.php');


class WebZim
{
    public static $VALID_USERS = array('admin' => 'admin');

    public function run()
    {
        $authenticationChain = new AuthenticationChain();
        $authenticationChain->handle();

        $staticContentChain = new StaticContentChain();
        $staticContentChain->handle();

        $pageProcessorChain = new PageProcessorChain();
        $pageProcessorChain->handle();

        $mediaChain = new MediaChain();
        $mediaChain->handle();
    }

    public static function isCorrectCredentials()
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            if (array_key_exists($username, self::$VALID_USERS)) {
                if (self::$VALID_USERS[$username] == $password) {
                    return true;
                }
            }
        }
        return false;
    }


}

abstract class ActionHandler
{
    public function handle()
    {
        $this->handleRequest();
    }

    abstract protected function handleRequest();
}

class AuthenticationChain extends ActionHandler
{
    protected function handleRequest()
    {
        if ($this->didUserJustLogin()) {
            header('Location: index.html');
            exit;
        }

        if ($this->isUserWantToAuthenticate()) {
            $this->authenticateUser();
            exit;
        }
    }

    protected function isUserWantToAuthenticate()
    {
        if (!WebZim::isCorrectCredentials() && @$_GET['login'])
            return true;
        else
            return false;
    }

    protected function didUserJustLogin()
    {
        return FileManager::getFileNameFromPath($_SERVER['REQUEST_URI']) == 'index.php' && WebZim::isCorrectCredentials() && @$_REQUEST['login'] == 1;
    }

    protected function authenticateUser()
    {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        echo "You must enter a valid login ID and password to access this resource\n";
        exit;
    }

}

class StaticContentChain extends ActionHandler
{
    protected function handleRequest()
    {
        if ($this->isMediaRequest()) {
            $this->returnMediaResponse();
            exit;
        }
    }

    protected function isMediaRequest()
    {
        return @$_GET['js'] != '';
    }

    protected function returnMediaResponse()
    {
        $mediaFile = @$_GET['js'];
        if (!WebZim::isCorrectCredentials()) {
            if (strpos($mediaFile, 'ckeditor') !== false) {
                header('HTTP/1.0 401 Unauthorized');
                exit;
            }
        }
        if ($mediaFile) {
            $this->returnMediaFileResponse($mediaFile);
        }
    }

    public function returnMediaFileResponse($filename)
    {
        $filename = strchr($filename, 'js');
        $filePath = ROOT_PATH . '/' . $filename;

        $mime_type = self::getFileMimeType($filePath);
        header('Content-type: ' . $mime_type);
        readfile($filePath);
    }

    public static function getFileMimeType($filePath)
    {
        $extension = strrchr($filePath, '.');
        $mime_type = '';
        switch ($extension) {
            case ".js":
                $mime_type = 'text/javascript';
                break;
            case ".css":
                $mime_type = 'text/css';
                break;
            case ".png":
                $mime_type = 'image/png';
                break;
            case '.jpg':
                $mime_type = 'image/jpeg';
                break;
            case '.gif':
                $mime_type = 'image/gif';
                break;
        }
        return $mime_type;
    }
}

class PageProcessorChain extends ActionHandler
{

    protected function handleRequest()
    {
        if (!WebZim::isCorrectCredentials()) {
            return;
        }

        if ($this->isUpdateContentAction()) {
            $referer = $_SERVER["HTTP_REFERER"];
            $path = FileManager::getFileNameFromPath($referer);
            $this->updateBlockContents($path, $_POST['container'], $_POST['text']);
            return;
        }


        if ($this->isCreatePageConfirmed()) {
            $filename = $_REQUEST['filename'];
            $template = $_REQUEST['template'];
            $this->createPageFile($filename, $template);
            header('Location: /' . $filename);
            return;
        }

        if ($this->isCreatePageNotConfirmed()) {
            $referer = @$_REQUEST['referer'] ? $_REQUEST['referer'] : '/index.html';
            header('Location: ' . $referer);
            return;
        }

        if ($this->isCreatePageDialogPage()) {
            $referer = @$_SERVER["HTTP_REFERER"];
            $filename = FileManager::getFileNameFromPath($_SERVER['REQUEST_URI']);
            echo $this->getConfirmFormForFile($filename, $referer);
            return;
        }
    }


    protected function isUpdateContentAction()
    {
        return isset($_POST['container']) && isset($_POST['text']);
    }

    protected function isCreatePageDialogPage()
    {
        return strpos($_SERVER['REQUEST_URI'], '.html') !== false;
    }

    protected function isCreatePageConfirmed()
    {
        return isset($_REQUEST['filename']) && isset($_REQUEST['yes']);
    }

    protected function isCreatePageNotConfirmed()
    {
        return isset($_REQUEST['filename']) && isset($_REQUEST['no']);
    }

    public function updateBlockContents($file, $container, $text)
    {
        $fileContents = file_get_contents(ROOT_PATH . '/' . $file);
        $parser = str_get_html($fileContents);
        $div = $parser->find('div[name="' . $container . '"]', 0);
        $div->innertext = $text;
        $parser->save(ROOT_PATH . '/' . $file);
    }


    public function createPageFile($filename, $template)
    {
        $this->validateFileExtension($filename);
        FileManager::createFolderIfNotExists($filename);
        FileManager::copyFileContents(__DIR__ . '/templates/' . $template . '.php', ROOT_PATH . '/' . $filename);

    }

    /**
     * @param $filename
     * @throws RuntimeException
     */
    public function validateFileExtension($filename)
    {
        if (strchr($filename, '.') != '.html') {
            throw new RuntimeException("Invalid file extension to create");
        }
    }

    public function getConfirmFormForFile($filename, $referer = "")
    {
        return $this->getCreateConfirmPage($filename, $referer);
    }

    /**
     * @param $filename
     * @param $referer
     * @return string
     */
    public function getCreateConfirmPage($filename, $referer)
    {
        $files = FileManager::getFolderListing(__DIR__ . '/templates');
        $select = "<select name='template'>";
        foreach ($files as $file) {
            $select .= "<option>" . str_replace(".php", '', $file) . "</option>";
        }
        $select .= "</select>";

        return "<html><body><form action='index.php'><p>Do you really want to craete <strong>{$filename}</strong></p>"
            . "<p>Template: {$select}</p>"
            . "<p><input type='submit' value='Yes' name='yes'> <input type='submit' value='No' name='no'>"
            . "<input type='hidden' name='filename' value='{$filename}'><input type='hidden' name='referer' value='{$referer}'></p></form></body></html>";
    }


}

class MediaChain extends ActionHandler
{
    protected function handleRequest()
    {

        if ($this->isFileUpload()) {
            $this->uploadFile();
            return;
        }

        if ($this->isListImagesForPreview()) {
            header('Content-type: application/json');
            echo $this->getImageFilesAsJson();
            return;
        }

        if ($this->isThumnailRequest()) {
            $this->getImageThumbnail();
            return;
        }
    }

    protected function isThumnailRequest()
    {
        return @$_GET['thumb'];
    }

    protected function isListImagesForPreview()
    {
        return @$_GET['images'];
    }

    protected function isFileUpload()
    {
        return @$_FILES['upload'];
    }

    protected function getImageThumbnail()
    {
        $maxWith = 80;
        $maxHeight = 80;
        $imageFile = @$_GET['thumb'];
        $image = new SimpleImage();
        $image->load(ROOT_PATH . '/' . $imageFile);
        $image->resizeToHeight($maxHeight);
        $image->resizeToWidth($maxWith);
        $mime_type = StaticContentChain::getFileMimeType($imageFile);
        header('Content-type: ' . $mime_type);
        $image->output();
    }

    protected function uploadFile()
    {
        $file = $_FILES['upload'];
        if (move_uploaded_file($file['tmp_name'], ROOT_PATH . '/files/' . $file['name'])) {
            $funcNum = $_GET['CKEditorFuncNum'];
            echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '/files/" . $file['name'] . "', 'upload success');</script>";
        }
    }


    protected function getImageFilesAsJson()
    {
        $files = FileManager::getFolderListing(ROOT_PATH . '/files');
        $result = array();
        foreach ($files as $file) {
            $image_info = getimagesize(ROOT_PATH . '/files/' . $file);
            $result[] = array("image" => '/files/' . $file, 'thumb' => "/index.php?thumb=files/" . $file, 'dimensions' => sprintf("%dx%d", $image_info[0], $image_info[1]));
        }
        return json_encode($result);
    }
}


class FileManager
{
    public static function getFileNameFromPath($rawPath)
    {
        $parsed = parse_url($rawPath);
        $path = $parsed['path'];

        if ($path == '/') {
            return 'index.html';
        }
        $path = explode("/", $path);
        unset($path[0]);
        return (implode('/', $path));
    }

    public static function createFolderIfNotExists($filePath)
    {
        if (strpos($filePath, '/') !== false) {
            if (!is_dir(dirname(ROOT_PATH . '/' . $filePath))) {
                mkdir(dirname(ROOT_PATH . '/' . $filePath), 0755, true);
            }
        }
    }

    public static function copyFileContents($sourceFile, $destinationFile)
    {
        $sourceContents = file_get_contents($sourceFile);
        file_put_contents($destinationFile, $sourceContents);
    }

    public static function getFolderListing($folderPath)
    {
        $filesList = array();
        if ($handle = opendir($folderPath)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $filesList[] = $entry;
                }
            }
            closedir($handle);
        }
        return $filesList;
    }
}


class SimpleImage
{
    var $image;
    var $image_type;

    function load($filename)
    {
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        if ($this->image_type == IMAGETYPE_JPEG) {

            $this->image = imagecreatefromjpeg($filename);
        } elseif ($this->image_type == IMAGETYPE_GIF) {

            $this->image = imagecreatefromgif($filename);
        } elseif ($this->image_type == IMAGETYPE_PNG) {

            $this->image = imagecreatefrompng($filename);
        }
    }

    function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null)
    {
        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->image, $filename, $compression);
        } elseif ($image_type == IMAGETYPE_GIF) {

            imagegif($this->image, $filename);
        } elseif ($image_type == IMAGETYPE_PNG) {

            imagepng($this->image, $filename);
        }
        if ($permissions != null) {

            chmod($filename, $permissions);
        }
    }

    function output($image_type = IMAGETYPE_JPEG)
    {
        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->image);
        } elseif ($image_type == IMAGETYPE_GIF) {

            imagegif($this->image);
        } elseif ($image_type == IMAGETYPE_PNG) {

            imagepng($this->image);
        }
    }

    function getWidth()
    {
        return imagesx($this->image);
    }

    function getHeight()
    {
        return imagesy($this->image);
    }

    function resizeToHeight($height)
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
    }

    function resizeToWidth($width)
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width, $height);
    }

    function scale($scale)
    {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;
        $this->resize($width, $height);
    }

    function resize($width, $height)
    {
        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $new_image;
    }

}