<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\SymfonyStorage\Dumper;

use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class XliffDumper extends XliffFileDumper
{
    /**
     * Alias for formatCatalogue to provide a BC bridge.
     *
     * @param MessageCatalogue $messages
     * @param string           $domain
     * @param array            $options
     *
     * @return string
     */
    public function getFormattedCatalogue(MessageCatalogue $messages, $domain, array $options = [])
    {
        if (method_exists($this, 'formatCatalogue')) {
            return parent::formatCatalogue($messages, $domain, $options);
        }

        return $this->format($messages, $domain);
    }
}
