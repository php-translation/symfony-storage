<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\SymfonyStorage\Loader;

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Util\XliffExtractor;
use Symfony\Component\Translation\Util\XliffUtils;

/**
 * Make sure we can load Xliff file content. Symfony does only support loading form files.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class XliffLoader implements LoaderInterface
{
    private $extractor;

    public function __construct(XliffExtractor $extractor)
    {
        $this->extractor = $extractor;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        if (!stream_is_local($resource)) {
            throw new InvalidResourceException(sprintf('This is not a local file "%s".', $resource));
        }

        if (!file_exists($resource)) {
            throw new NotFoundResourceException(sprintf('File "%s" not found.', $resource));
        }

        $catalogue = new MessageCatalogue($locale);
        $content = file_get_contents($resource);
        $this->extractFromContent($content, $catalogue, $domain);

        if (class_exists('Symfony\Component\Config\Resource\FileResource')) {
            $catalogue->addResource(new FileResource($resource));
        }

        return $catalogue;
    }

    /**
     * @param string           $content   xml content
     * @param MessageCatalogue $catalogue
     * @param string           $domain
     */
    public function extractFromContent($content, MessageCatalogue $catalogue, $domain)
    {
        try {
            $dom = $this->loadFileContent($content);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidResourceException(sprintf('Unable to load data: %s', $e->getMessage()), $e->getCode(), $e);
        }

        $xliffVersion = XliffUtils::getVersionNumber($dom);

        if ('1.2' === $xliffVersion) {
            $this->extractor->extractXliff1($dom, $catalogue, $domain);
        }

        if ('2.0' === $xliffVersion) {
            $this->extractor->extractXliff2($dom, $catalogue, $domain);
        }
    }

    /**
     * Loads an XML file.
     *
     * Taken and modified from Symfony\Component\Config\Util\XmlUtils
     *
     * @author Fabien Potencier <fabien@symfony.com>
     * @author Martin Hasoň <martin.hason@gmail.com>
     *
     * @param string $content An XML file path
     *
     * @return \DOMDocument
     *
     * @throws \InvalidArgumentException When loading of XML file returns error
     */
    private function loadFileContent($content)
    {
        if ('' === trim($content)) {
            throw new \InvalidArgumentException('Content does not contain valid XML, it is empty.');
        }

        $internalErrors = libxml_use_internal_errors(true);
        $disableEntities = libxml_disable_entity_loader(true);
        libxml_clear_errors();

        $dom = new \DOMDocument();
        $dom->validateOnParse = true;
        if (!$dom->loadXML($content, LIBXML_NONET | (defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))) {
            libxml_disable_entity_loader($disableEntities);

            throw new \InvalidArgumentException(implode("\n", static::getXmlErrors($internalErrors)));
        }

        $dom->normalizeDocument();

        libxml_use_internal_errors($internalErrors);
        libxml_disable_entity_loader($disableEntities);

        foreach ($dom->childNodes as $child) {
            if (XML_DOCUMENT_TYPE_NODE === $child->nodeType) {
                throw new \InvalidArgumentException('Document types are not allowed.');
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $dom;
    }

    private function getXmlErrors($internalErrors)
    {
        $errors = [];
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf(
                '[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ?: 'n/a',
                $error->line,
                $error->column
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $errors;
    }
}
