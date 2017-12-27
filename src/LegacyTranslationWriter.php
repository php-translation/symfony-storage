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

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\TranslationWriter;

/**
 * This writer is just a wrapper for Symfony TranslationWriter
 * and provide a BC layer for Symfony 2.7 to 3.3.
 *
 * @author Victor Bocharsky <bocharsky.bw@gmail.com>
 */
final class LegacyTranslationWriter // implements Symfony\Component\Translation\Writer\TranslationWriterInterface
{
    private $writer;

    public function __construct($writer)
    {
        // If not Translation writer from sf 2.7 to 3.3
        if (!$writer instanceof TranslationWriter) {
            throw new \LogicException(sprintf('PHP-Translation/SymfonyStorage does not support a TranslationWriter of type "%s".', get_class($writer)));
        }

        $this->writer = $writer;
    }

    public function write(MessageCatalogue $catalogue, $format, $options = [])
    {
        $this->writer->writeTranslations($catalogue, $format, $options);
    }
}
