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
use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;

/**
 * This loader is just a wrapper for Symfony TranslationLoader
 * and provide a BC layer for Symfony 2.7 to 3.3.
 *
 * @author Victor Bocharsky <bocharsky.bw@gmail.com>
 */
final class LegacyTranslationReader // implements Symfony\Component\Translation\Reader\TranslationReaderInterface
{
    /**
     * @var TranslationLoader
     */
    private $loader;

    public function __construct($loader)
    {
        if (!$loader instanceof TranslationLoader) {
            throw new \LogicException(sprintf('PHP-Translation/SymfonyStorage does not support a TranslationReader of type "%s".', get_class($loader)));
        }
        $this->loader = $loader;
    }

    public function read($directory, MessageCatalogue $catalogue)
    {
        $this->loader->loadMessages($directory, $catalogue);
    }
}
