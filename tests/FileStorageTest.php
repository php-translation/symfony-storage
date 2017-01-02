<?php

namespace Translation\SymfonyStorage\Tests;

use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Translation\SymfonyStorage\FileStorage;

class FileStorageTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $storage = new FileStorage(new TranslationWriter(), new TranslationLoader(), ['foo']);
    }

    /**
     * @expectedException \LogicException
     */
    public function testConstructorInvalidLoader()
    {
        $storage = new FileStorage(new TranslationWriter(), new TranslationWriter(), ['foo']);
    }

    /**
     * @expectedException \LogicException
     */
    public function testConstructorEmptyArray()
    {
        $storage = new FileStorage(new TranslationWriter(), new TranslationLoader(), []);
    }
}
