<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\SymfonyStorage;

use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Translation\SymfonyStorage\Loader\XliffLoader;

class XliffConverter
{
    /**
     * Create a catalogue from the contents of a XLIFF file.
     *
     * @param string $content
     * @param string $locale
     * @param string $domain
     *
     * @return MessageCatalogue
     */
    public static function contentToCatalogue($content, $locale, $domain)
    {
        $loader = new XliffLoader();
        $catalogue = new MessageCatalogue($locale);
        $loader->extractFromContent($content, $catalogue, $domain);

        return $catalogue;
    }

    /**
     * @param MessageCatalogue $catalogue
     * @param string           $domain
     *
     * @return string
     */
    public static function catalogueToContent(MessageCatalogue $catalogue, $domain)
    {
        $dumper = new XliffFileDumper();

        return $dumper->formatCatalogue($catalogue, $domain);
    }
}
