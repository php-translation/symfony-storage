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
use Symfony\Component\Translation\Writer\TranslationWriter;
use Translation\SymfonyStorage\FileStorage;

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
}
