<?php

namespace Translation\SymfonyStorage\Loader\Port;

use Symfony\Component\Translation\MessageCatalogue;

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
}
