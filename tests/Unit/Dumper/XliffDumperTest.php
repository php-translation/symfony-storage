<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\SymfonyStorage\Tests\Unit\Dumper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\MessageCatalogue;
use Translation\SymfonyStorage\Dumper\XliffDumper;

class XliffDumperTest extends TestCase
{
    public function testDumpXliff12()
    {
        $catalogue = new MessageCatalogue('en');
        $catalogue->set('key0', 'trans0');
        $catalogue->set('key1', 'trans1');

        $dumper = new XliffDumper();
        $output = $dumper->formatCatalogue($catalogue, 'messages', ['xliff_version' => '1.2']);

        $this->assertContains('<source>key0</source>', $output);
        $this->assertContains('<target>trans0</target>', $output);
        $this->assertContains('<source>key1</source>', $output);
        $this->assertContains('<target>trans1</target>', $output);
    }

    public function testDumpXliff20Meta()
    {
        $catalogue = new MessageCatalogue('en');
        $catalogue->set('key0', 'trans0');
        $catalogue->setMetadata('key0', ['notes' => [
            ['content' => 'yes', 'category' => 'approved'],
            ['content' => 'new', 'category' => 'state'],
        ]]);

        $catalogue->set('key1', 'trans1');
        $catalogue->setMetadata('key1', ['notes' => [
            ['content' => 'cnt', 'priority' => '2'],
        ]]);

        $dumper = new XliffDumper();
        $output = $dumper->formatCatalogue($catalogue, 'messages', ['xliff_version' => '2.0']);

        $this->assertContains('<note category="approved">yes</note>', $output);
        $this->assertContains('<note category="state">new</note>', $output);
    }
}
