<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\SymfonyStorage\Tests\Unit\Loader;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\MessageCatalogue;
use Translation\SymfonyStorage\Loader\XliffLoader;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class XliffLoaderTest extends TestCase
{
    /**
     * @expectedException \Symfony\Component\Translation\Exception\InvalidResourceException
     */
    public function testEmptyContent()
    {
        $loader = new XliffLoader();
        $loader->extractFromContent(' ', new MessageCatalogue('en'), 'messages');
    }

    public function testInvalidContent()
    {
        $loader = new XliffLoader();

        try {
            $loader->extractFromContent('Foobar', new MessageCatalogue('en'), 'messages');
        } catch (InvalidResourceException $e) {
            $invalidArgument = $e->getPrevious();
            $this->assertNotNull($invalidArgument);
            $this->assertContains('[ERROR 4] Start tag expected', $invalidArgument->getMessage());

            return;
        }
        $this->fail('XliffLoader must throw exception on invalid XML');
    }

    public function testXliff12()
    {
        $content = file_get_contents(__DIR__.'/../../Fixtures/single-file/messages.en.xlf');
        $catalogue = new MessageCatalogue('en');
        (new XliffLoader())->extractFromContent($content, $catalogue, 'messages');
        $this->assertTrue($catalogue->defines('test_0'));
        $this->assertTrue($catalogue->defines('test_1'));
    }

    public function testXliff20()
    {
        $content = <<<'XML'
<xliff xmlns="urn:oasis:names:tc:xliff:document:2.0" version="2.0"
 srcLang="en-US" trgLang="sv">
 <file id="f1" original="Example">
  <skeleton href="Example"/>
  <unit id="1">
   <segment>
    <source>key0</source>
    <target>Foo</target>
   </segment>
  </unit>
  <unit id="2">
   <segment>
    <source>key1</source>
    <target>Bar</target>
   </segment>
  </unit>
 </file>
</xliff>
XML;

        $catalogue = new MessageCatalogue('en');
        (new XliffLoader())->extractFromContent($content, $catalogue, 'messages');
        $this->assertTrue($catalogue->defines('key0'));
        $this->assertTrue($catalogue->defines('key1'));
    }

    public function testXliff20Meta()
    {
        $content = file_get_contents(__DIR__.'/../../Fixtures/meta.en.xlf');

        $catalogue = new MessageCatalogue('en');
        (new XliffLoader())->extractFromContent($content, $catalogue, 'messages');
        $this->assertTrue($catalogue->defines('foo'));
        $metadata = $catalogue->getMetadata('foo');
        $this->assertNotEmpty($metadata);
        $this->assertCount(3, $metadata['notes']);

        $this->assertEquals('state', $metadata['notes'][0]['category']);
        $this->assertEquals('new', $metadata['notes'][0]['content']);

        $this->assertEquals('approved', $metadata['notes'][1]['category']);
        $this->assertEquals('true', $metadata['notes'][1]['content']);

        $this->assertEquals('section', $metadata['notes'][2]['category']);
        $this->assertEquals('1', $metadata['notes'][2]['priority']);
        $this->assertEquals('user login', $metadata['notes'][2]['content']);
    }

    public function testXliff20FromResource()
    {
        $file = __DIR__.'/../../Fixtures/meta.en.xlf';

        $catalogue = (new XliffLoader())->load($file, 'en', 'messages');
        $this->assertTrue($catalogue->defines('foo'));
        $metadata = $catalogue->getMetadata('foo');
        $this->assertNotEmpty($metadata);
        $this->assertCount(3, $metadata['notes']);

        $this->assertEquals('state', $metadata['notes'][0]['category']);
        $this->assertEquals('new', $metadata['notes'][0]['content']);

        $this->assertEquals('approved', $metadata['notes'][1]['category']);
        $this->assertEquals('true', $metadata['notes'][1]['content']);

        $this->assertEquals('section', $metadata['notes'][2]['category']);
        $this->assertEquals('1', $metadata['notes'][2]['priority']);
        $this->assertEquals('user login', $metadata['notes'][2]['content']);
    }
}
