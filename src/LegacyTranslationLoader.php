<?php

namespace Translation\SymfonyStorage;

use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Reader\TranslationReader;

/**
 * This loader is just a legacy wrapper for Symfony TranslationReader
 * and provider a BC layer for Symfony 4.
 *
 * @author Victor Bocharsky <bocharsky.bw@gmail.com>
 */
class LegacyTranslationLoader implements TranslationLoader
{
    /**
     * @var TranslationReader
     */
    private $reader;

    public function __construct(TranslationReader $reader)
    {
        $this->reader = $reader;
    }

    public function loadMessages($directory, MessageCatalogue $catalogue)
    {
        $this->reader->read($directory, $catalogue);
    }
}
