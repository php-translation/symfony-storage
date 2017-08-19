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

use Nyholm\NSA;
use Symfony\Component\Translation\Dumper\XliffFileDumper;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Translation\SymfonyStorage\Dumper\Port\SymfonyPort;

/**
 * XliffFileDumper generates xliff files from a message catalogue.
 *
 * This class provides support for both SF2.7 and SF3.0+
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Michel Salib <michelsalib@hotmail.com>
 */
final class XliffDumper extends XliffFileDumper
{
    /**
     * @var SymfonyPort|null
     */
    private $sfPort;

    /**
     * {@inheritdoc}
     */
    public function formatCatalogue(MessageCatalogue $messages, $domain, array $options = [])
    {
        $xliffVersion = '1.2';
        if (array_key_exists('xliff_version', $options)) {
            $xliffVersion = $options['xliff_version'];
        }

        if (array_key_exists('default_locale', $options)) {
            $defaultLocale = $options['default_locale'];
        } else {
            $defaultLocale = \Locale::getDefault();
        }

        if ('1.2' === $xliffVersion) {
            if (method_exists($this, 'dumpXliff1')) {
                return NSA::invokeMethod($this, 'dumpXliff1', $defaultLocale, $messages, $domain, $options);
            } else {
                // Symfony 2.7
                return $this->format($messages, $domain);
            }
        }

        if ('2.0' === $xliffVersion) {
            if (null === $this->sfPort) {
                $this->sfPort = new SymfonyPort();
            }

            return $this->sfPort->dumpXliff2($defaultLocale, $messages, $domain, $options);
        }

        throw new InvalidArgumentException(
            sprintf('No support implemented for dumping XLIFF version "%s".', $xliffVersion)
        );
    }

    /**
     * To support Symfony 2.7.
     */
    protected function format(MessageCatalogue $messages, $domain)
    {
        return $this->formatCatalogue($messages, $domain, ['xliff_version' => '2.0']);
    }
}
