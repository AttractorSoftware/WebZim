<?php
define('ROOT_PATH', dirname(__FILE__));
require_once('vendor/simplehtml/simple_html_dom.php');
class WebZim
{
    public static $VALID_USERS = array('admin'=>'admin');

    public function run()
    {

        if ($this->doesUserWantToAuthenticate()) {
            $this->authenticateUser();
        }
        if(!$this->isCorrectCredentials())
        {
            exit;
        }
        else if ($this->isEditorJavascriptRequest())
        {
            $this->returnJavascriptReponse();
        }
        if ($this->isUpdateContentAction())
        {
            $referer = $_SERVER["HTTP_REFERER"];
            $path = $this->getFileNameFromPath($referer);
            $this->updateBlockContents($path, $_POST['container'], $_POST['text']);
        }
        if($this->isCreatePageAction())
        {
            $filename = $this->getFileNameFromPath($_SERVER['REQUEST_URI']);
            $this->createPageFile($filename);
            header('Location: '.$filename);
        }
    }

    /**
     * @return bool
     */
    public function isCreatePageAction()
    {
        return strpos($_SERVER['REQUEST_URI'], '.html') !== false;
    }

    /**
     * @return bool
     */
    public function isUpdateContentAction()
    {
        return isset($_POST['container']) && isset($_POST['text']);
    }

    public function createPageFile($filename)
    {
        $template = ROOT_PATH.'/template.php';
        $templateContents = file_get_contents($template);
        $path = ROOT_PATH.'/web/'.$filename;
        file_put_contents($path,  $templateContents);
    }


    public function getEditorJavascript($filename)
    {
        $filePath = ROOT_PATH.'/web/js/ckeditor/'.$filename;
        header('Content-type: text/javascript');
        $contents = @file_get_contents($filePath);
        return $contents;
    }

    protected function authenticateUser() {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');
        echo "You must enter a valid login ID and password to access this resource\n";
        exit;
    }

    public function updateBlockContents($file, $container, $text)
    {
        $fileContents = file_get_contents(ROOT_PATH.'/web/'.$file);
        $parser = str_get_html($fileContents);
        $div = $parser->find('div[name="'.$container.'"]', 0);
        $div->innertext = $text;
        $parser->save(ROOT_PATH.'/web/'.$file);

    }

    public function getFileNameFromPath($referer)
    {
        $parsed = parse_url($referer);
        $path = $parsed['path'];

        if($path == '/')
        {
            return 'index.html';
        }
        $path = explode("/", $path);
        unset($path[0]);
        return(implode('/', $path));
    }

    protected  function returnJavascriptReponse()
    {
        $is_js = @$_GET['js'];
        if ($is_js && !isset($_SERVER['PHP_AUTH_USER'])) {
            exit;
        } else if ($is_js) {
            echo $this->getEditorJavascript($is_js);
            exit;
        }
    }

    /**
     * @return bool
     */
    protected  function isEditorJavascriptRequest()
    {
        return @$_GET['js'] != '';
    }

    /**
     * @return bool
     */
    protected  function doesUserWantToAuthenticate()
    {
        return !isset($_SERVER['PHP_AUTH_USER']) && @$_GET['login'];
    }

    protected  function isCorrectCredentials()
    {
        if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
        {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            if(array_key_exists($username, self::$VALID_USERS))
            {
                if (self::$VALID_USERS[$username] == $password)
                {
                    return true;
                }
            }
        }
        return false;
    }


}