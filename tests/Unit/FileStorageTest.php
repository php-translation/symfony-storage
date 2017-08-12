<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\SymfonyStorage\Tests\Unit;

use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Translation\Common\Model\Message;
use Translation\SymfonyStorage\FileStorage;
use Translation\SymfonyStorage\Loader\XliffLoader;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class FileStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        new FileStorage(new TranslationWriter(), new TranslationLoader(), ['foo']);
    }

    /**
     * @expectedException \LogicException
     */
    public function testConstructorInvalidLoader()
    {
        new FileStorage(new TranslationWriter(), new TranslationWriter(), ['foo']);
    }

    /**
     * @expectedException \LogicException
     */
    public function testConstructorEmptyArray()
    {
        new FileStorage(new TranslationWriter(), new TranslationLoader(), []);
    }

    public function testCreateNewCatalogue()
    {
        $writer = $this->getMockBuilder(TranslationWriter::class)
            ->setMethods(['writeTranslations'])
            ->disableOriginalConstructor()
            ->getMock();
        $writer->expects($this->once())
            ->method('writeTranslations')
            ->with(
                $this->isInstanceOf(MessageCatalogueInterface::class),
                'xlf',
                ['path' => 'foo']
            );

        $storage = new FileStorage($writer, new TranslationLoader(), ['foo']);
        $storage->create(new Message('key', 'domain', 'en', 'Message'));

        $writer = $this->getMockBuilder(TranslationWriter::class)
            ->setMethods(['writeTranslations'])
            ->disableOriginalConstructor()
            ->getMock();
        $writer->expects($this->once())
            ->method('writeTranslations')
            ->with(
                $this->isInstanceOf(MessageCatalogueInterface::class),
                'format',
                ['path' => 'bar', 'default_output_format' => 'format']
            );

        $storage = new FileStorage($writer, new TranslationLoader(), ['bar'], ['default_output_format' => 'format']);
        $storage->create(new Message('key', 'domain', 'en', 'Message'));
    }

    public function testCreateExistingCatalogue()
    {
        $writer = $this->getMockBuilder(TranslationWriter::class)
            ->setMethods(['writeTranslations'])
            ->disableOriginalConstructor()
            ->getMock();
        $writer->expects($this->once())
            ->method('writeTranslations')
            ->with(
                $this->isInstanceOf(MessageCatalogueInterface::class),
                'xlf',
                ['path' => __DIR__]
            );

        $loader = new TranslationLoader();
        $loader->addLoader('xlf', new XliffLoader());
        $storage = new FileStorage($writer, $loader, ['foo', __DIR__]);

        $storage->create(new Message('key', 'messages', 'en', 'Translation'));
    }

    public function testGet()
    {
        $writer = $this->getMockBuilder(TranslationWriter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $loader = new TranslationLoader();
        $loader->addLoader('xlf', new XliffLoader());
        $storage = new FileStorage($writer, $loader, [__DIR__]);

        $this->assertEquals('Bazbar', $storage->get('en', 'messages', 'test_1')->getTranslation());

        // Missing locale
        $this->assertEquals('test_1', $storage->get('sv', 'messages', 'test_1')->getTranslation());

        // Missing domain
        $this->assertEquals('test_1', $storage->get('en', 'xx', 'test_1')->getTranslation());

        // Missing key
        $this->assertEquals('miss', $storage->get('en', 'messages', 'miss')->getTranslation());
    }

    public function testUpdate()
    {
        $writer = $this->getMockBuilder(TranslationWriter::class)
            ->setMethods(['writeTranslations'])
            ->disableOriginalConstructor()
            ->getMock();
        $writer->expects($this->exactly(2))
            ->method('writeTranslations')
            ->with(
                $this->isInstanceOf(MessageCatalogueInterface::class),
                'xlf',
                ['path' => __DIR__]
            );

        $loader = new TranslationLoader();
        $loader->addLoader('xlf', new XliffLoader());
        $storage = new FileStorage($writer, $loader, [__DIR__]);

        $storage->update(new Message('key', 'messages', 'en', 'Translation'));
        $storage->update(new Message('test_1', 'messages', 'en', 'Translation'));
    }

    public function testDelete()
    {
        $writer = $this->getMockBuilder(TranslationWriter::class)
            ->setMethods(['writeTranslations'])
            ->disableOriginalConstructor()
            ->getMock();

        $writer->expects($this->once())
            ->method('writeTranslations')
            ->with(
                $this->callback(function(MessageCatalogueInterface $catalogue) {
                   return !$catalogue->defines('test_0', 'messages');
                }),
                'xlf',
                ['path' => __DIR__]
            );

        $loader = new TranslationLoader();
        $loader->addLoader('xlf', new XliffLoader());
        $storage = new FileStorage($writer, $loader, [__DIR__]);

        $storage->delete('en', 'messages', 'test_0');
    }

    public function testImport()
    {
        $writer = $this->getMockBuilder(TranslationWriter::class)
            ->setMethods(['writeTranslations'])
            ->disableOriginalConstructor()
            ->getMock();

        $writer->expects($this->once())
            ->method('writeTranslations')
            ->with(
                $this->callback(function(MessageCatalogueInterface $catalogue) {
                   return $catalogue->defines('test_4711', 'messages');
                }),
                'xlf',
                ['path' => __DIR__]
            );

        $loader = new TranslationLoader();
        $loader->addLoader('xlf', new XliffLoader());
        $storage = new FileStorage($writer, $loader, [__DIR__]);
        $catalogue = new MessageCatalogue('en', ['messages'=>['test_4711'=>'foobar']]);

        $storage->import($catalogue);
    }

    public function testExport()
    {
        $writer = $this->getMockBuilder(TranslationWriter::class)
            ->disableOriginalConstructor()
            ->getMock();


        $loader = new TranslationLoader();
        $loader->addLoader('xlf', new XliffLoader());
        $storage = new FileStorage($writer, $loader, [__DIR__]);

        $catalogue = new MessageCatalogue('en');
        $storage->export($catalogue);
        $this->assertTrue($catalogue->defines('test_0', 'messages'));
        $this->assertTrue($catalogue->defines('test_1', 'messages'));

        // Wrong locale
        $catalogue = new MessageCatalogue('sv');
        $storage->export($catalogue);
        $this->assertFalse($catalogue->defines('test_0', 'messages'));
    }
}
