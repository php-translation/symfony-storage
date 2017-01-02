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

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface TranslationLoader
{
    /**
     * Loads translation messages from a directory to the catalogue.
     *
     * @param string           $directory the directory to look into
     * @param MessageCatalogue $catalogue the catalogue
     */
    public function loadMessages($directory, MessageCatalogue $catalogue);
}
