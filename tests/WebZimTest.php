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
    /** @var PageProcessorChain */
    protected $pageProcessorChain;

    public function setUp()
    {
        $this->app = new WebZim();
        $this->client = new HttpClient('webzim.local');
        $this->pageProcessorChain = new PageProcessorChain();
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
        $this->authenticate();
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
        $this->authenticate();
        $this->assertEquals(302, $this->client->getStatus());
        $this->client->get('/js/ckeditor/editor.js');
        $this->assertEquals(200, $this->client->getStatus());
    }

    protected function authenticate()
    {
        $this->client->setAutherization('admin', 'admin');
        $this->client->get('/index.php?login=1');
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

    public function testCreatePage()
    {
        $this->pageProcessorChain->createPageFile('testing.html', 'base');
        $this->assertEquals(true, file_exists(ROOT_PATH.'/testing.html'));
        unlink(ROOT_PATH.'/testing.html');
    }

    public function testUpdateBlockContents()
    {
        $container = 'zimeditor-1';
        $text  = "<h2>Hello friends</h2><p>This is sample text</p>";

        $this->pageProcessorChain->createPageFile('test.html', 'base');
        $this->pageProcessorChain->updateBlockContents('test.html',$container, $text);
        $contents = file_get_contents(ROOT_PATH.'/test.html');
        $this->assertContains($text, $contents);
        unlink(ROOT_PATH.'/test.html');
    }

    public function testGetConfirmFormForFile()
    {

        $expected = "<html><body><form action='index.php'><p>Do you really want to craete <strong>test.html</strong></p>"
            ."<p>Template: <select name='template'><option>base</option><option>article</option></select></p>"
            ."<p><input type='submit' value='Yes' name='yes'> <input type='submit' value='No' name='no'>"
            ."<input type='hidden' name='filename' value='test.html'><input type='hidden' name='referer' value='index.html'></p></form></body></html>";
        $actual = $this->pageProcessorChain->getConfirmFormForFile('test.html', 'index.html');
        $this->assertEquals($expected, $actual);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCreatePageFileDoesNotCreateOtherThanHTMLFiles()
    {
        $this->pageProcessorChain->createPageFile('test.php', 'base');
        $this->pageProcessorChain->createPageFile('test.js', 'base');
    }

    public function testCreatePageWithFolder()
    {
        $this->pageProcessorChain->createPageFile('people/azamat.html', 'base');
        $actual = file_get_contents(ROOT_PATH.'/people/azamat.html');
        $expected = file_get_contents(ROOT_PATH.'/../templates/base.php');
        unlink(ROOT_PATH.'/people/azamat.html');
        rmdir(ROOT_PATH.'/people');
        $this->assertEquals($expected, $actual);

    }

    public function testCreatePageWithSeveralFolders()
    {

        $this->pageProcessorChain->createPageFile('people/collegues/azamat.html', 'base');
        $actual = file_get_contents(ROOT_PATH.'/people/collegues/azamat.html');
        $expected = file_get_contents(ROOT_PATH.'/../templates/base.php');
        unlink(ROOT_PATH.'/people/collegues/azamat.html');
        rmdir(ROOT_PATH.'/people/collegues');
        $this->assertEquals($expected, $actual);

    }

    public function testUpdateBlockContentsByRequest()
    {
        $this->pageProcessorChain->createPageFile('azamat.html', 'base');
        chmod(ROOT_PATH."/azamat.html", 0777);
        $this->authenticate();
        $this->client->get('/azamat.html');
        $data = array('container'=>"zimeditor-1", 'text'=>"<h2>Testing update of the text using custom http client</h2>");
        $this->client->post('/index.php', $data);
        $this->client->get('/azamat.html');
        $actual = $this->client->getContent();
        $expected = "<h2>Testing update of the text using custom http client</h2>";
        unlink(ROOT_PATH.'/azamat.html');
        $this->assertContains($expected, $actual);

    }

    public function testCreatePageByApprovingConfirmation()
    {
        $this->authenticate();
        $this->client->get('/index.php?filename=azamat.html&yes=1&template=base');
        $this->client->get('/azamat.html');
        unlink(ROOT_PATH.'/azamat.html');
        $this->assertEquals(200, $this->client->getStatus());

    }

    public function testGetImagesListJson()
    {
        $this->authenticate();
        $this->client->get('/index.php?images=1');
        $actual = $this->client->getContent();
        $expected = array(array("image"=>'/files/avatar.png', 'thumb'=>"/index.php?thumb=files/avatar.png", 'dimensions'=>"140x150"),
            array("image"=>'/files/map.png', 'thumb'=>"/index.php?thumb=files/map.png", 'dimensions'=>"682x418"),
            array("image"=>'/files/tracery.png', 'thumb'=>"/index.php?thumb=files/tracery.png",'dimensions'=>"774x768" ));
        $this->assertEquals(json_encode($expected), $actual);
    }

    public function testUploadImageFile()
    {
        $this->authenticate();
        $this->client->post('/index.php', array('upload'=>"@".__DIR__."/smile_icon.png"));

        $this->client->get('/index.php?images=1');
        $actual = $this->client->getContent();
        $expected = array(array("image"=>'/files/avatar.png', 'thumb'=>"/index.php?thumb=files/avatar.png", 'dimensions'=>"140x150"),
            array("image"=>'/files/map.png', 'thumb'=>"/index.php?thumb=files/map.png", 'dimensions'=>"682x418"),
            array("image"=>'/files/tracery.png', 'thumb'=>"/index.php?thumb=files/tracery.png",'dimensions'=>"774x768" ),
            array("image"=>'/files/smile_icon.png', 'thumb'=>"/index.php?thumb=files/smile_icon.png",'dimensions'=>"256x256" ));
        unlink(ROOT_PATH.'/files/smile_icon.png');
        $this->assertEquals(json_encode($expected), $actual);
    }

    public function testDoNotAllowUploadOtherThanImagesFiles()
    {
        $this->authenticate();
        $this->client->post('/index.php', array('upload'=>"@".__DIR__."/upload_test.php"));
        $this->assertEquals(403, $this->client->getStatus());
    }

    public function testUpdatePageMetaInformation()
    {
        $this->authenticate();
        $this->client->get('/index.php?filename=azamat.html&yes=1&template=base');
        $this->client->get('/azamat.html');

        $data = array('document_title'=>"Azamat",
                      'document_description'=>"He is a programmer",
                      'document_keywords'=>"developer, person, sofware",
                      'document_author'=>"Azamat Tokhaev",
                      'document_pub_date'=>"2013-04-12",
                      'document_edit_date'=>"2013-04-15");
        $this->client->post('/index.php?update_meta=1', $data);
        $fileContents = file_get_contents(ROOT_PATH . '/' . 'azamat.html');
        $parser = str_get_html($fileContents);

        $title = $parser->find('title', 0);
        $author = $parser->find('meta[name="author"]', 0);
        $description = $parser->find('meta[name="description"]', 0);
        $keywords = $parser->find('meta[name="keywords"]', 0);
        $pubDate = $parser->find('meta[name="dc.date.created"]', 0);
        $editDate = $parser->find('meta[name="dc.date.modified"]', 0);

        unlink(ROOT_PATH.'/azamat.html');

        $this->assertEquals($data['document_author'], $author->attr['content']);
        $this->assertEquals($data['document_description'], $description->attr['content']);
        $this->assertEquals($data['document_keywords'], $keywords->attr['content']);
        $this->assertEquals($data['document_pub_date'], $pubDate->attr['content']);
        $this->assertEquals($data['document_edit_date'], $editDate->attr['content']);
        $this->assertEquals($data['document_title'], $title->innertext);


    }
}