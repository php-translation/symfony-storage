<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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
    public function testDumpXliff2Meta()
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
