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
final class XliffLoader extends XliffFileLoader
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

        $xliffVersion = '1.2';
        if (method_exists($this, 'getVersionNumber')) {
            $xliffVersion = NSA::invokeMethod($this, 'getVersionNumber', $dom);
        }

        if (method_exists($this, 'validateSchema')) {
            NSA::invokeMethod($this, 'validateSchema', $xliffVersion, $dom, NSA::invokeMethod($this, 'getSchema', $xliffVersion));
        }

        if ('1.2' === $xliffVersion) {
            NSA::invokeMethod($this, 'extractXliff1', $dom, $catalogue, $domain);
        }

        if ('2.0' === $xliffVersion) {
            NSA::invokeMethod($this, 'extractXliff2', $dom, $catalogue, $domain);
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
     * @param string               $content          An XML file path
     * @param string|callable|null $schemaOrCallable An XSD schema file path, a callable, or null to disable validation
     *
     * @return \DOMDocument
     *
     * @throws \InvalidArgumentException When loading of XML file returns error
     */
    private function loadFileContent($content, $schemaOrCallable = null)
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

        if (null !== $schemaOrCallable) {
            $internalErrors = libxml_use_internal_errors(true);
            libxml_clear_errors();

            $e = null;
            if (is_callable($schemaOrCallable)) {
                try {
                    $valid = call_user_func($schemaOrCallable, $dom, $internalErrors);
                } catch (\Exception $e) {
                    $valid = false;
                }
            } elseif (!is_array($schemaOrCallable) && is_file((string) $schemaOrCallable)) {
                $schemaSource = file_get_contents((string) $schemaOrCallable);
                $valid = @$dom->schemaValidateSource($schemaSource);
            } else {
                libxml_use_internal_errors($internalErrors);

                throw new \InvalidArgumentException('The schemaOrCallable argument has to be a valid path to XSD file or callable.');
            }

            if (!$valid) {
                $messages = $this->getXmlErrors($internalErrors);
                if (empty($messages)) {
                    $messages = ['The XML is not valid.'];
                }
                throw new \InvalidArgumentException(implode("\n", $messages), 0, $e);
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
