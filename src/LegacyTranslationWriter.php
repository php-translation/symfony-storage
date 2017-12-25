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
 * This writer is just a legacy wrapper for Symfony TranslationWriter
 * and provide a BC layer for Symfony 4.
 *
 * @author Victor Bocharsky <bocharsky.bw@gmail.com>
 */
class LegacyTranslationWriter
{
    /**
     * @var TranslationWriter
     */
    private $writer;

    public function __construct(TranslationWriter $writer)
    {
        $this->writer = $writer;
    }

    public function write(MessageCatalogue $catalogue, $format, $options = [])
    {
        $this->writer->writeTranslations($catalogue, $format, $options);
    }
}
