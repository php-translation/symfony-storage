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
                if (0 !== substr_compare('urn:oasis:names:tc:xliff:document:', $namespace->nodeValue, 0, 34)) {
                    throw new InvalidArgumentException(sprintf('Not a valid XLIFF namespace "%s"', $namespace));
                }

                return substr($namespace, 34);
            }
        }

        // Falls back to v1.2
        return '1.2';
    }

    /**
     * Extract messages and metadata from DOMDocument into a MessageCatalogue.
     *
     * @param \DOMDocument     $dom       Source to extract messages and metadata
     * @param MessageCatalogue $catalogue Catalogue where we'll collect messages and metadata
     * @param string           $domain    The domain
     *
     * @deprecated Will be removed when we drop support for SF2.7
     */
    public function extractXliff1(\DOMDocument $dom, MessageCatalogue $catalogue, $domain)
    {
        $xml = simplexml_import_dom($dom);
        $encoding = strtoupper($dom->encoding);

        $xml->registerXPathNamespace('xliff', 'urn:oasis:names:tc:xliff:document:1.2');
        foreach ($xml->xpath('//xliff:trans-unit') as $translation) {
            $attributes = $translation->attributes();

            if (!(isset($attributes['resname']) || isset($translation->source))) {
                continue;
            }

            $source = isset($attributes['resname']) && $attributes['resname'] ? $attributes['resname'] : $translation->source;
            // If the xlf file has another encoding specified, try to convert it because
            // simple_xml will always return utf-8 encoded values
            $target = $this->utf8ToCharset((string) (isset($translation->target) ? $translation->target : $source), $encoding);

            $catalogue->set((string) $source, $target, $domain);

            $metadata = [];
            if ($notes = $this->parseNotesMetadata($translation->note, $encoding)) {
                $metadata['notes'] = $notes;
            }

            if (isset($translation->target) && $translation->target->attributes()) {
                $metadata['target-attributes'] = [];
                foreach ($translation->target->attributes() as $key => $value) {
                    $metadata['target-attributes'][$key] = (string) $value;
                }
            }

            if (isset($attributes['id'])) {
                $metadata['id'] = (string) $attributes['id'];
            }

            $catalogue->setMetadata((string) $source, $metadata, $domain);
        }
    }

    /**
     * @param \SimpleXMLElement|null $noteElement
     * @param string|null            $encoding
     *
     * @return array
     *
     * @deprecated Will be removed when we drop support for SF2.7
     */
    private function parseNotesMetadata(\SimpleXMLElement $noteElement = null, $encoding = null)
    {
        $notes = [];

        if (null === $noteElement) {
            return $notes;
        }

        /** @var \SimpleXMLElement $xmlNote */
        foreach ($noteElement as $xmlNote) {
            $noteAttributes = $xmlNote->attributes();
            $note = ['content' => $this->utf8ToCharset((string) $xmlNote, $encoding)];
            if (isset($noteAttributes['priority'])) {
                $note['priority'] = (int) $noteAttributes['priority'];
            }

            if (isset($noteAttributes['from'])) {
                $note['from'] = (string) $noteAttributes['from'];
            }

            $notes[] = $note;
        }

        return $notes;
    }
}
