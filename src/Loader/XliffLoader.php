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

/**
 * This class is an ugly hack to allow loading Xliff from string content.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class XliffLoader extends XliffFileLoader
{
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

        if (!method_exists($this, 'getVersionNumber')) {
            // Symfony 2.7
            throw new \RuntimeException('Cannot use XliffLoader::extractFromContent with Symfony 2.7');
        }

        $xliffVersion = NSA::invokeMethod($this, 'getVersionNumber', $dom);
        NSA::invokeMethod($this, 'validateSchema', $xliffVersion, $dom, NSA::invokeMethod($this, 'getSchema', $xliffVersion));

        if ('1.2' === $xliffVersion) {
            NSA::invokeMethod($this, 'extractXliff1', $dom, $catalogue, $domain);
        }

        if ('2.0' === $xliffVersion) {
            $this->extractXliff2($dom, $catalogue, $domain);
        }
    }


    /**
     * @param \DOMDocument     $dom
     * @param MessageCatalogue $catalogue
     * @param string           $domain
     */
    private function extractXliff2(\DOMDocument $dom, MessageCatalogue $catalogue, $domain)
    {
        $xml = simplexml_import_dom($dom);
        $encoding = strtoupper($dom->encoding);

        $xml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:2.0');

        foreach ($xml->xpath('//xliff:unit') as $unit) {
            $segment = $unit->segment;
            $source = $segment->source;

            // If the xlf file has another encoding specified, try to convert it because
            // simple_xml will always return utf-8 encoded values
            $target = $this->utf8ToCharset((string) (isset($segment->target) ? $segment->target : $source), $encoding);

            $catalogue->set((string) $source, $target, $domain);

            $metadata = array();
            if (isset($segment->target) && $segment->target->attributes()) {
                $metadata['target-attributes'] = array();
                foreach ($segment->target->attributes() as $key => $value) {
                    $metadata['target-attributes'][$key] = (string) $value;
                }
            }

            if (isset($unit->notes)) {
                $metadata['notes'] = array();
                foreach ($unit->notes->note as $noteNode) {
                    $note = [];
                    foreach ($noteNode->attributes() as $key => $value) {
                        $note[$key] = (string) $value;
                    }
                    $note['content'] = (string) $noteNode;
                    $metadata['notes'][] = $note;
                }
            }

            $catalogue->setMetadata((string) $source, $metadata, $domain);
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

    /**
     * Convert a UTF8 string to the specified encoding.
     *
     * @param string $content  String to decode
     * @param string $encoding Target encoding
     *
     * @return string
     */
    private function utf8ToCharset($content, $encoding = null)
    {
        if ('UTF-8' !== $encoding && !empty($encoding)) {
            return mb_convert_encoding($content, $encoding, 'UTF-8');
        }

        return $content;
    }
}
