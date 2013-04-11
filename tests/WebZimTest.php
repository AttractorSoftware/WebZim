<?php
require_once('../application/webzim.php');
define('ROOT_PATH', dirname(__FILE__).'/../application/web');
class WebZimTest extends \PHPUnit_Framework_TestCase
{
    /** @var WebZim */
    protected $app;
    public function setUp()
    {
        $this->app = new WebZim();
    }

    public function testCreatePage()
    {
        $this->app->createPageFile('testing.html');
        $this->assertEquals(true, file_exists(ROOT_PATH.'/testing.html'));
        unlink(ROOT_PATH.'/testing.html');
    }


    public function testUpdateBlockContents()
    {
        $container = 'zimeditor-1';
        $text  = "<h2>Hello friends</h2><p>This is sample text</p>";
        $this->app->createPageFile('test.html');
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
        $this->app->createPageFile('test.php');
        $this->app->createPageFile('test.js');
    }

    public function testCreatePageWithFolders()
    {
        $this->app->createPageFile('people/collegues/azamat.html');
        $actual = file_get_contents(ROOT_PATH.'/people/collegues/azamat.html');
        $expected = file_get_contents(ROOT_PATH.'/../template.php');
        $this->assertEquals($expected, $actual);
        unlink(ROOT_PATH.'/people/collegues/azamat.html');
        rmdir(ROOT_PATH.'/people/collegues');
    }

    public function testCreatePageWithFolder()
    {
        $this->app->createPageFile('people/azamat.html');
        $actual = file_get_contents(ROOT_PATH.'/people/azamat.html');
        $expected = file_get_contents(ROOT_PATH.'/../template.php');
        $this->assertEquals($expected, $actual);
        unlink(ROOT_PATH.'/people/azamat.html');
        rmdir(ROOT_PATH.'/people');
    }





}