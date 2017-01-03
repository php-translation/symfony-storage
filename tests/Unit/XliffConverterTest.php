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

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Translation\MessageCatalogue;
use Translation\SymfonyStorage\XliffConverter;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class XliffConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testContentToCatalogue()
    {
        if (Kernel::VERSION_ID < 20800) {
            $this->markTestSkipped('Symfony <2.8 is not supported. ');
        }

        $content = file_get_contents(__DIR__.'/messages.en.xlf');
        $catalogue = XliffConverter::contentToCatalogue($content, 'en', 'messages');

        $this->assertEquals('en', $catalogue->getLocale());
        $this->assertEquals(['messages'], $catalogue->getDomains());
        $this->assertCount(2, $catalogue->all('messages'));
    }

    public function testCatalogueToContent()
    {
        $catalogue = new MessageCatalogue('en');
        $catalogue->add(['foobar' => 'bar']);
        $content = XliffConverter::catalogueToContent($catalogue, 'messages');

        $this->assertRegExp('|foobar|', $content);
    }
}
