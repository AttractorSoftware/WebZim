<?php
require_once('../application/webzim.php');
class WebZimTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebZim */
    protected $app;
    public function setUp()
    {
        $this->app = new WebZim();
    }

    public function testIndexPage()
    {
        $this->app->createPageFile('index.html');
        $this->assertEquals(true, file_exists(ROOT_PATH.'/web/index.html'));
    }


    public function testUpdateBlockContents()
    {
        $container = 'zimeditor-1';
        $text  = "<h2>Hello friends</h2><p>This is sample text</p>";
        $this->app->createPageFile('test.html');
        $this->app->updateBlockContents('test.html',$container, $text);
        $contents = file_get_contents(ROOT_PATH.'/web/test.html');
        $this->assertContains($text, $contents);
        unlink(ROOT_PATH.'/web/test.html');

    }

    public function testGetFileNameFromReferer()
    {
        $referef = "http://webzim.local/";
        $filename = $this->app->getFileNameFromPath($referef);
        $this->assertEquals('index.html', $filename);

        $referef = "http://webzim.local/somefile.html";
        $filename = $this->app->getFileNameFromPath($referef);
        $this->assertEquals('somefile.html', $filename);

        $referef = "http://webzim.local/courts/somefile.html";
        $filename = $this->app->getFileNameFromPath($referef);
        $this->assertEquals('courts/somefile.html', $filename);

    }

}