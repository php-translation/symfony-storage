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

use Nyholm\NSA;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Translation\SymfonyStorage\Loader\Port\SymfonyPort;

/**
 * This class is an ugly hack to allow loading Xliff from string content.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class XliffLoader extends XliffFileLoader
{
    /**
     * @var SymfonyPort|null
     */
    private $sfPort;

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

        if (method_exists($this, 'getVersionNumber')) {
            $xliffVersion = NSA::invokeMethod($this, 'getVersionNumber', $dom);
            NSA::invokeMethod($this, 'validateSchema', $xliffVersion, $dom, NSA::invokeMethod($this, 'getSchema', $xliffVersion));
        } else {
            // Symfony 2.7
            if (null === $this->sfPort) {
                $this->sfPort = new SymfonyPort();
            }
            $xliffVersion = $this->sfPort->getVersionNumber($dom);
            $this->sfPort->validateSchema($xliffVersion, $dom, $this->sfPort->getSchema($xliffVersion));
        }

        if ('1.2' === $xliffVersion) {
            NSA::invokeMethod($this, 'extractXliff1', $dom, $catalogue, $domain);
        }

        if ('2.0' === $xliffVersion) {
            if (null === $this->sfPort) {
                $this->sfPort = new SymfonyPort();
            }
            $this->sfPort->extractXliff2($dom, $catalogue, $domain);
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
            if ($child->nodeType === XML_DOCUMENT_TYPE_NODE) {
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
            $errors[] = sprintf('[%s %s] %s (in %s - line %d, column %d)',
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
