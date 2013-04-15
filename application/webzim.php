<?php

require_once('vendor/simplehtml/simple_html_dom.php');
class WebZim
{
    public static $VALID_USERS = array('admin' => 'admin');

    public function run()
    {

        if ($this->didUserJustLogin()) {
            header('Location: index.html');
            exit;
        }

        if ($this->doesUserWantToAuthenticate()) {
            $this->authenticateUser();
            exit;
        }

        if ($this->isMediaRequest()) {
            echo $this->returnMediaResponse();
            exit;
        }
        if (!$this->isCorrectCredentials()) {
            exit;
        }

        if ($this->isUpdateContentAction()) {
            $referer = $_SERVER["HTTP_REFERER"];
            $path = FileManager::getFileNameFromPath($referer);
            $this->updateBlockContents($path, $_POST['container'], $_POST['text']);
            exit;
        }


        if ($this->isCreatePageConfirmed()) {
            $filename = $_REQUEST['filename'];
            $template = $_REQUEST['template'];
            $this->createPageFile($filename, $template);
            header('Location: /' . $filename);
            exit;
        }

        if ($this->isCreatePageNotConfirmed()) {
            $referer = @$_REQUEST['referer'] ? $_REQUEST['referer']: '/index.html';
            header('Location: '.$referer);
            exit;
        }

        if ($this->isCreatePageDialogPage()) {
            $referer = @$_SERVER["HTTP_REFERER"];
            $filename = FileManager::getFileNameFromPath($_SERVER['REQUEST_URI']);
            echo $this->getConfirmFormForFile($filename, $referer);
            exit;
        }

        if(@$_REQUEST['upload'])
        {
            $file = $_FILES['upload'];
            if(move_uploaded_file($file['tmp_name'], ROOT_PATH.'/files/'.$file['name'])){
                $funcNum = $_GET['CKEditorFuncNum'] ;
                echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '/files/".$file['name']."', 'upload success');</script>";
            }
            exit;
        }

        if(@$_GET['images'])
        {
            header('Content-type: application/json');
            echo $this->getImageFilesAsJson();
            exit;
        }

        if(@$_GET['thumb'])
        {
            $maxWith = 80;
            $maxHeight = 80;

            $imageFile = @$_GET['thumb'];
            $image = new SimpleImage();
            $image->load(ROOT_PATH.'/'.$imageFile);
            $image->resizeToHeight($maxHeight);
            $image->resizeToWidth($maxWith);
            $mime_type = $this->getFileMimeType($imageFile);
            header('Content-type: ' . $mime_type);
            $image->output();
            exit;
        }


    }

    public function isCreatePageConfirmed()
    {
        return isset($_REQUEST['filename']) && isset($_REQUEST['yes']);
    }

    public function isCreatePageNotConfirmed()
    {
        return isset($_REQUEST['filename']) && isset($_REQUEST['no']);
    }



    public function didUserJustLogin()
    {
        return FileManager::getFileNameFromPath($_SERVER['REQUEST_URI']) == 'index.php' && $this->isCorrectCredentials() && @$_REQUEST['login'] == 1;
    }

    public function getConfirmFormForFile($filename, $referer="")
    {
        $files = FileManager::getFolderListing(__DIR__.'/templates');
        $select = "<select name='template'>";
        foreach($files as $file)
        {
            $select.="<option>".str_replace(".php",'', $file)."</option>";
        }
        $select .= "</select>";

        return "<html><body><form action='index.php'><p>Do you really want to craete <strong>{$filename}</strong></p>"
            ."<p>Template: {$select}</p>"
            . "<p><input type='submit' value='Yes' name='yes'> <input type='submit' value='No' name='no'>"
            . "<input type='hidden' name='filename' value='{$filename}'><input type='hidden' name='referer' value='{$referer}'></p></form></body></html>";
    }

    public function isCreatePageDialogPage()
    {
        return strpos($_SERVER['REQUEST_URI'], '.html') !== false;
    }

    public function isUpdateContentAction()
    {
        return isset($_POST['container']) && isset($_POST['text']);
    }

    public function createPageFile($filename, $template)
    {
        $this->validateFileExtension($filename);
        FileManager::createFolderIfNotExists($filename);
        FileManager::copyFileContents(__DIR__ .'/templates/'.$template.'.php', ROOT_PATH.'/'.$filename);

    }

    public function updateBlockContents($file, $container, $text)
    {
        $fileContents = file_get_contents(ROOT_PATH . '/' . $file);
        $parser = str_get_html($fileContents);
        $div = $parser->find('div[name="' . $container . '"]', 0);
        $div->innertext = $text;
        $parser->save(ROOT_PATH . '/' . $file);
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

    public function returnMediaFileResponse($filename)
    {
        $filename = strchr($filename, 'js');
        $filePath = ROOT_PATH . '/' . $filename;
        $headers = array();#apache_request_headers();
/*        if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($filePath))) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT', true, 304);
        } else {*/
            $mime_type = $this->getFileMimeType($filePath);
            //header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT', true, 200);
            header('Content-type: ' . $mime_type);
            readfile($filePath);
        //}
        return '';
    }

    public function getFileMimeType($filePath)
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

    protected function isMediaRequest()
    {
        return @$_GET['js'] != '';
    }

    protected function returnMediaResponse()
    {
        $mediaFile = @$_GET['js'];
        if(!$this->isCorrectCredentials())
        {
            if(strpos($mediaFile, 'ckeditor')!==false)
            {
                return "";
            }
        }
        if ($mediaFile){
            return $this->returnMediaFileResponse($mediaFile);
        }
        return "";
    }

    public function getImageFilesAsJson()
    {
        $files = FileManager::getFolderListing(ROOT_PATH.'/files');
        $result = array();
        foreach($files as $file)
        {
            $image_info = getimagesize(ROOT_PATH.'/files/'.$file);
            $result[] = array("image"=>'/files/'.$file, 'thumb'=>"/index.php?thumb=files/".$file, 'dimensions'=>sprintf("%dx%d", $image_info[0], $image_info[1]));
        }
        return json_encode($result);
    }

    /**
     * @return bool
     */
    protected function doesUserWantToAuthenticate()
    {
        if (!$this->isCorrectCredentials() && @$_GET['login'])
            return true;
        else
            return false;
    }

    protected function isCorrectCredentials()
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

    protected function authenticateUser()
    {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        echo "You must enter a valid login ID and password to access this resource\n";
        exit;
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


class MediaFileManager
{

}

class SimpleImage {

    var $image;
    var $image_type;

    function load($filename) {

        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        if( $this->image_type == IMAGETYPE_JPEG ) {

            $this->image = imagecreatefromjpeg($filename);
        } elseif( $this->image_type == IMAGETYPE_GIF ) {

            $this->image = imagecreatefromgif($filename);
        } elseif( $this->image_type == IMAGETYPE_PNG ) {

            $this->image = imagecreatefrompng($filename);
        }
    }
    function save($filename, $image_type=IMAGETYPE_JPEG, $compression=75, $permissions=null) {

        if( $image_type == IMAGETYPE_JPEG ) {
            imagejpeg($this->image,$filename,$compression);
        } elseif( $image_type == IMAGETYPE_GIF ) {

            imagegif($this->image,$filename);
        } elseif( $image_type == IMAGETYPE_PNG ) {

            imagepng($this->image,$filename);
        }
        if( $permissions != null) {

            chmod($filename,$permissions);
        }
    }
    function output($image_type=IMAGETYPE_JPEG) {

        if( $image_type == IMAGETYPE_JPEG ) {
            imagejpeg($this->image);
        } elseif( $image_type == IMAGETYPE_GIF ) {

            imagegif($this->image);
        } elseif( $image_type == IMAGETYPE_PNG ) {

            imagepng($this->image);
        }
    }
    function getWidth() {

        return imagesx($this->image);
    }
    function getHeight() {

        return imagesy($this->image);
    }
    function resizeToHeight($height) {

        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width,$height);
    }

    function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width,$height);
    }

    function scale($scale) {
        $width = $this->getWidth() * $scale/100;
        $height = $this->getheight() * $scale/100;
        $this->resize($width,$height);
    }

    function resize($width,$height) {
        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $new_image;
    }

}