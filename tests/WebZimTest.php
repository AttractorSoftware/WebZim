<?php
define('ROOT_PATH', dirname(__FILE__).'/../application/web');
require_once(ROOT_PATH.'/../webzim.php');
require_once(ROOT_PATH.'/../vendor/httpclient.php');

class WebZimTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebZim */
    protected $app;
    /** @var HttpClient */
    protected $client;
    public function setUp()
    {
        $this->app = new WebZim();
        $this->client = new HttpClient('webzim.local');

    }

    public function tearDown()
    {
        $this->client = null;
    }

    public function testIndexPageOpens()
    {

        $this->client->get('/');
        $this->assertEquals(200 ,$this->client->getStatus());
    }


    public function testLoginPage()
    {

        $this->client->get('/index.php?login=1');
        $this->assertEquals(401, $this->client->getStatus());
    }

    public function testLoginAction()
    {
        $this->client->get('/');
        $this->client->setAutherization('admin', 'admin');
        $this->client->get('/index.php?login=1');
        $this->assertEquals(302, $this->client->getStatus());
    }

    public function testGetPublicMediaFileWithoutUserCredentials()
    {
        $this->client->get('/js/jquery.js');
        $this->assertNotEmpty($this->client->getContent());
    }

    public function testGetProtectedMediaFileWithoutUserCredentials()
    {
        $this->client->get('/js/ckeditor/editor.js');
        $this->assertEquals(401, $this->client->getStatus());
    }

    public function testGetProtectedMediaFileWithUserCredentials()
    {
        $this->client->setAutherization('admin', 'admin');
        $this->client->get('/index.php?login=1' );
        $this->assertEquals(302, $this->client->getStatus());
        $this->client->setAutherization('admin', 'admin');
        $this->client->get('/js/ckeditor/editor.js');
        $this->assertEquals(200, $this->client->getStatus());
    }

    public function testCreatePage()
    {
        $this->app->createPageFile('testing.html', 'base');
        $this->assertEquals(true, file_exists(ROOT_PATH.'/testing.html'));
        unlink(ROOT_PATH.'/testing.html');
    }


    public function testUpdateBlockContents()
    {
        $container = 'zimeditor-1';
        $text  = "<h2>Hello friends</h2><p>This is sample text</p>";
        $this->app->createPageFile('test.html', 'base');
        $this->app->updateBlockContents('test.html',$container, $text);
        $contents = file_get_contents(ROOT_PATH.'/test.html');
        $this->assertContains($text, $contents);
        unlink(ROOT_PATH.'/test.html');

    }

    public function testGetFileNameFromReferer()
    {
        $referef = "http://webzim.local/";
        $filename = FileManager::getFileNameFromPath($referef);
        $this->assertEquals('index.html', $filename);

        $referef = "http://webzim.local/somefile.html";
        $filename = FileManager::getFileNameFromPath($referef);
        $this->assertEquals('somefile.html', $filename);

        $referef = "http://webzim.local/courts/somefile.html";
        $filename = FileManager::getFileNameFromPath($referef);
        $this->assertEquals('courts/somefile.html', $filename);

    }

    public function testGetConfirmFormForFile()
    {
        $expected = "<html><body><form action='index.php'><p>Do you really want to craete <strong>test.html</strong></p>"
            ."<p>Template: <select name='template'><option>base</option><option>article</option></select></p>"
            ."<p><input type='submit' value='Yes' name='yes'> <input type='submit' value='No' name='no'>"
            ."<input type='hidden' name='filename' value='test.html'><input type='hidden' name='referer' value='index.html'></p></form></body></html>";
        $actual = $this->app->getConfirmFormForFile('test.html', 'index.html');
        $this->assertEquals($expected, $actual);
    }


    /**
     * @expectedException RuntimeException
     */
    public function testCreatePageFileDoesNotCreateOtherThanHTMLFiles()
    {
        $this->app->createPageFile('test.php', 'base');
        $this->app->createPageFile('test.js', 'base');
    }

    public function testCreatePageWithFolders()
    {
        $this->app->createPageFile('people/collegues/azamat.html', 'base');
        $actual = file_get_contents(ROOT_PATH.'/people/collegues/azamat.html');
        $expected = file_get_contents(ROOT_PATH.'/../templates/base.php');
        $this->assertEquals($expected, $actual);
        unlink(ROOT_PATH.'/people/collegues/azamat.html');
        rmdir(ROOT_PATH.'/people/collegues');
    }

    public function testCreatePageWithFolder()
    {
        $this->app->createPageFile('people/azamat.html', 'base');
        $actual = file_get_contents(ROOT_PATH.'/people/azamat.html');
        $expected = file_get_contents(ROOT_PATH.'/../templates/base.php');
        $this->assertEquals($expected, $actual);
        unlink(ROOT_PATH.'/people/azamat.html');
        rmdir(ROOT_PATH.'/people');
    }

    public function testGetImagesListJson()
    {
        $expected = array(array("image"=>'/files/avatar.png', 'thumb'=>"/index.php?thumb=files/avatar.png", 'dimensions'=>"140x150"),
                          array("image"=>'/files/map.png', 'thumb'=>"/index.php?thumb=files/map.png", 'dimensions'=>"682x418"),
                          array("image"=>'/files/tracery.png', 'thumb'=>"/index.php?thumb=files/tracery.png",'dimensions'=>"774x768" ));
        $actual = $this->app->getImageFilesAsJson();
        $this->assertEquals(json_encode($expected), $actual);
    }




}