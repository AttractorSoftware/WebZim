<?php

require_once('vendor/simplehtml/simple_html_dom.php');
class WebZim
{
    public static $VALID_USERS = array('admin' => 'admin');

    public function run()
    {

        if ($this->didUserJustLogin()) {
            header('Location: index.html');
        }

        if ($this->doesUserWantToAuthenticate()) {
            $this->authenticateUser();
        }

        if ($this->isMediaRequest()) {
            echo $this->returnMediaResponse();
        }
        if (!$this->isCorrectCredentials()) {
            exit;
        }

        if ($this->isUpdateContentAction()) {
            $referer = $_SERVER["HTTP_REFERER"];
            $path = FileManager::getFileNameFromPath($referer);
            $this->updateBlockContents($path, $_POST['container'], $_POST['text']);
        }

        if ($this->isCreatePageDialogPage()) {
            $referer = @$_SERVER["HTTP_REFERER"];
            $filename = FileManager::getFileNameFromPath($_SERVER['REQUEST_URI']);
            echo $this->getConfirmFormForFile($filename, $referer);
        }

        if ($this->isCreatePageConfirmed()) {
            $filename = $_REQUEST['filename'];
            $this->createPageFile($filename);
            header('Location: /' . $filename);
        } else if ($this->isCreatePageNotConfirmed()) {
            $referer = @$_REQUEST['referer'] ? $_REQUEST['referer']: '/index.html';
            header('Location: '.$referer);
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
        return FileManager::getFileNameFromPath($_SERVER['REQUEST_URI']) == 'index.php' && $this->isCorrectCredentials();
    }

    public function getConfirmFormForFile($filename, $referer="")
    {
        return "<html><body><form action='index.php'><p>Do you really want to craete <strong>{$filename}</strong></p>"
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

    public function createPageFile($filename)
    {
        $this->validateFileExtension($filename);
        FileManager::createFolderIfNotExists($filename);
        FileManager::copyFileContents(ROOT_PATH .'/../template.php', ROOT_PATH.'/'.$filename);

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


    public function getEditorJavascript($filename)
    {
        $filename = strchr($filename, 'js');
        $filePath = ROOT_PATH . '/' . $filename;
        $headers = apache_request_headers();
        if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($filePath))) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT', true, 304);
        } else {
            $contents = @file_get_contents($filePath);
            $mime_type = $this->getFileMimeType($filePath);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($filePath)) . ' GMT', true, 200);
            header('Content-type: ' . $mime_type);
            return $contents;
        }
        return '';
    }

    /**
     * @param $filePath
     * @return string
     */
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
            return $this->getEditorJavascript($mediaFile);

        }
        return "";
    }

    /**
     * @return bool
     */
    protected function isMediaRequest()
    {
        return @$_GET['js'] != '';
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
}


class MediaFileManager
{

}