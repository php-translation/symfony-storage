<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\SymfonyStorage\Loader\Port;

use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Exception\InvalidArgumentException;

/**
 * This code is moved from the Symfony 3.4 repo. It will be removed when
 * we drop support for Symfony 3.3 and below.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyPort
{
    /**
     * @param \DOMDocument     $dom
     * @param MessageCatalogue $catalogue
     * @param string           $domain
     */
    public function extractXliff2(\DOMDocument $dom, MessageCatalogue $catalogue, $domain)
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

            $metadata = [];
            if (isset($segment->target) && $segment->target->attributes()) {
                $metadata['target-attributes'] = [];
                foreach ($segment->target->attributes() as $key => $value) {
                    $metadata['target-attributes'][$key] = (string) $value;
                }
            }

            if (isset($unit->notes)) {
                $metadata['notes'] = [];
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

    /**
     * Gets xliff file version based on the root "version" attribute.
     * Defaults to 1.2 for backwards compatibility.
     *
     * @param \DOMDocument $dom
     *
     * @throws InvalidArgumentException
     *
     * @return string
     *
     * @deprecated Will be removed when we drop support for SF2.7
     */
    public function getVersionNumber(\DOMDocument $dom)
    {
        /** @var \DOMNode $xliff */
        foreach ($dom->getElementsByTagName('xliff') as $xliff) {
            $version = $xliff->attributes->getNamedItem('version');
            if ($version) {
                return $version->nodeValue;
            }

            $namespace = $xliff->attributes->getNamedItem('xmlns');
            if ($namespace) {
                if (substr_compare('urn:oasis:names:tc:xliff:document:', $namespace->nodeValue, 0, 34) !== 0) {
                    throw new InvalidArgumentException(sprintf('Not a valid XLIFF namespace "%s"', $namespace));
                }

                return substr($namespace, 34);
            }
        }

        // Falls back to v1.2
        return '1.2';
    }

    /**
     * Validates and parses the given file into a DOMDocument.
     *
     * @param string       $file
     * @param \DOMDocument $dom
     * @param string       $schema source of the schema
     *
     * @throws InvalidResourceException
     *
     * @deprecated Will be removed when we drop support for SF2.7
     */
    public function validateSchema($file, \DOMDocument $dom, $schema)
    {
        $internalErrors = libxml_use_internal_errors(true);

        $disableEntities = libxml_disable_entity_loader(false);

        if (!@$dom->schemaValidateSource($schema)) {
            libxml_disable_entity_loader($disableEntities);

            throw new InvalidResourceException(sprintf('Invalid resource provided: "%s"; Errors: %s', $file, implode("\n", $this->getXmlErrors($internalErrors))));
        }

        libxml_disable_entity_loader($disableEntities);

        $dom->normalizeDocument();

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);
    }

    /**
     * @param $xliffVersion
     * @return string
     *
     * @deprecated Will be removed when we drop support for SF2.7
     */
    public function getSchema($xliffVersion)
    {
        if ('1.2' === $xliffVersion) {
            $schemaSource = file_get_contents(__DIR__.'/schema/dic/xliff-core/xliff-core-1.2-strict.xsd');
            $xmlUri = 'http://www.w3.org/2001/xml.xsd';
        } elseif ('2.0' === $xliffVersion) {
            $schemaSource = file_get_contents(__DIR__.'/schema/dic/xliff-core/xliff-core-2.0.xsd');
            $xmlUri = 'informativeCopiesOf3rdPartySchemas/w3c/xml.xsd';
        } else {
            throw new InvalidArgumentException(sprintf('No support implemented for loading XLIFF version "%s".', $xliffVersion));
        }

        return $this->fixXmlLocation($schemaSource, $xmlUri);
    }

    /**
     * Internally changes the URI of a dependent xsd to be loaded locally.
     *
     * @param string $schemaSource Current content of schema file
     * @param string $xmlUri       External URI of XML to convert to local
     *
     * @return string
     *
     * @deprecated Will be removed when we drop support for SF2.7
     */
    private function fixXmlLocation($schemaSource, $xmlUri)
    {
        $newPath = str_replace('\\', '/', __DIR__).'/schema/dic/xliff-core/xml.xsd';
        $parts = explode('/', $newPath);
        if (0 === stripos($newPath, 'phar://')) {
            $tmpfile = tempnam(sys_get_temp_dir(), 'sf2');
            if ($tmpfile) {
                copy($newPath, $tmpfile);
                $parts = explode('/', str_replace('\\', '/', $tmpfile));
            }
        }

        $drive = '\\' === DIRECTORY_SEPARATOR ? array_shift($parts).'/' : '';
        $newPath = 'file:///'.$drive.implode('/', array_map('rawurlencode', $parts));

        return str_replace($xmlUri, $newPath, $schemaSource);
    }
}
