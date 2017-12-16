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
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Reader\TranslationReader;
use Symfony\Component\Translation\Writer\TranslationWriter;
use Translation\Common\Model\Message;
use Translation\Common\Storage;
use Translation\Common\TransferableStorage;

/**
 * This storage uses Symfony's writer and reader.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class FileStorage implements Storage, TransferableStorage
{
    /**
     * @var TranslationWriter
     */
    private $writer;

    /**
     * @var TranslationReader
     */
    private $reader;

    /**
     * @var array directory path
     */
    private $dir;

    /**
     * @var array with option to the dumper
     */
    private $options;

    /**
     * @var MessageCatalogue[] Fetched catalogies
     */
    private $catalogues;

    /**
     * @param TranslationWriter $writer
     * @param TranslationReader $reader
     * @param array             $dir
     * @param array             $options
     */
    public function __construct(TranslationWriter $writer, TranslationReader $reader, array $dir, array $options = [])
    {
        if (empty($dir)) {
            throw new \LogicException('Third parameter of FileStorage cannot be empty');
        }

        if (!array_key_exists('xliff_version', $options)) {
            // Set default value for xliff version.
            $options['xliff_version'] = '2.0';
        }

        $this->writer = $writer;
        $this->reader = $reader;
        $this->dir = $dir;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function get($locale, $domain, $key)
    {
        $catalogue = $this->getCatalogue($locale);
        $translation = $catalogue->get($key, $domain);

        return new Message($key, $domain, $locale, $translation);
    }

    /**
     * {@inheritdoc}
     */
    public function create(Message $m)
    {
        $catalogue = $this->getCatalogue($m->getLocale());
        if (!$catalogue->defines($m->getKey(), $m->getDomain())) {
            $catalogue->set($m->getKey(), $m->getTranslation(), $m->getDomain());
            $this->writeCatalogue($catalogue, $m->getLocale(), $m->getDomain());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(Message $m)
    {
        $catalogue = $this->getCatalogue($m->getLocale());
        $catalogue->set($m->getKey(), $m->getTranslation(), $m->getDomain());
        $this->writeCatalogue($catalogue, $m->getLocale(), $m->getDomain());
    }

    /**
     * {@inheritdoc}
     */
    public function delete($locale, $domain, $key)
    {
        $catalogue = $this->getCatalogue($locale);
        $messages = $catalogue->all($domain);
        unset($messages[$key]);

        $catalogue->replace($messages, $domain);
        $this->writeCatalogue($catalogue, $locale, $domain);
    }

    /**
     * {@inheritdoc}
     */
    public function export(MessageCatalogueInterface $catalogue)
    {
        $locale = $catalogue->getLocale();
        $catalogue->addCatalogue($this->getCatalogue($locale));
    }

    /**
     * {@inheritdoc}
     */
    public function import(MessageCatalogueInterface $catalogue)
    {
        $domains = $catalogue->getDomains();
        foreach ($domains as $domain) {
            $this->writeCatalogue($catalogue, $catalogue->getLocale(), $domain);
        }
    }

    /**
     * Save catalogue back to file.
     *
     * @param MessageCatalogue $catalogue
     * @param string           $domain
     */
    private function writeCatalogue(MessageCatalogue $catalogue, $locale, $domain)
    {
        $resources = $catalogue->getResources();
        $options = $this->options;
        $written = false;
        foreach ($resources as $resource) {
            $path = (string) $resource;
            if (preg_match('|/'.$domain.'\.'.$locale.'\.([a-z]+)$|', $path, $matches)) {
                $options['path'] = str_replace($matches[0], '', $path);
                $this->writer->writeTranslations($catalogue, $matches[1], $options);
                $written = true;
            }
        }

        if ($written) {
            // We have written the translation to a file.
            return;
        }

        $options['path'] = reset($this->dir);
        $format = isset($options['default_output_format']) ? $options['default_output_format'] : 'xlf';
        $this->writer->writeTranslations($catalogue, $format, $options);
    }

    /**
     * @param string $locale
     *
     * @return MessageCatalogue
     */
    private function getCatalogue($locale)
    {
        if (empty($this->catalogues[$locale])) {
            $this->loadCatalogue($locale, $this->dir);
        }

        return $this->catalogues[$locale];
    }

    /**
     * Load catalogue from files.
     *
     * @param string $locale
     * @param array  $dirs
     */
    private function loadCatalogue($locale, array $dirs)
    {
        $currentCatalogue = new MessageCatalogue($locale);
        foreach ($dirs as $path) {
            if (is_dir($path)) {
                $this->reader->read($path, $currentCatalogue);
            }
        }

        $this->catalogues[$locale] = $currentCatalogue;
    }
}
