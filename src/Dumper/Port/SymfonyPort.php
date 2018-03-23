<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\SymfonyStorage\Dumper\Port;

use Symfony\Component\Translation\MessageCatalogue;

/**
 * This code is moved from the Symfony 3.4 repo. It will be removed when
 * we drop support for Symfony 3.3 and below.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyPort
{
    public function dumpXliff2($defaultLocale, MessageCatalogue $messages, $domain, array $options = [])
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $xliff = $dom->appendChild($dom->createElement('xliff'));
        $xliff->setAttribute('xmlns', 'urn:oasis:names:tc:xliff:document:2.0');
        $xliff->setAttribute('version', '2.0');
        $xliff->setAttribute('srcLang', str_replace('_', '-', $defaultLocale));
        $xliff->setAttribute('trgLang', str_replace('_', '-', $messages->getLocale()));

        $xliffFile = $xliff->appendChild($dom->createElement('file'));
        $xliffFile->setAttribute('id', $domain.'.'.$messages->getLocale());

        foreach ($messages->all($domain) as $source => $target) {
            $translation = $dom->createElement('unit');
            $translation->setAttribute('id', strtr(substr(base64_encode(hash('sha256', $source, true)), 0, 7), '/+', '._'));
            $name = $source;
            if (strlen($source) > 80) {
                $name = substr(md5($source), -7);
            }
            $translation->setAttribute('name', $name);
            $metadata = $messages->getMetadata($source, $domain);

            // Add notes section
            if ($this->hasMetadataArrayInfo('notes', $metadata)) {
                $notesElement = $dom->createElement('notes');
                foreach ($metadata['notes'] as $note) {
                    $n = $dom->createElement('note');
                    $n->appendChild($dom->createTextNode(isset($note['content']) ? $note['content'] : ''));
                    unset($note['content']);

                    foreach ($note as $name => $value) {
                        $n->setAttribute($name, $value);
                    }
                    $notesElement->appendChild($n);
                }
                $translation->appendChild($notesElement);
            }

            $segment = $translation->appendChild($dom->createElement('segment'));

            $s = $segment->appendChild($dom->createElement('source'));
            $s->appendChild($dom->createTextNode($source));

            // Does the target contain characters requiring a CDATA section?
            $text = 1 === preg_match('/[&<>]/', $target) ? $dom->createCDATASection($target) : $dom->createTextNode(
                $target
            );

            $targetElement = $dom->createElement('target');
            if ($this->hasMetadataArrayInfo('target-attributes', $metadata)) {
                foreach ($metadata['target-attributes'] as $name => $value) {
                    $targetElement->setAttribute($name, $value);
                }
            }

            $t = $segment->appendChild($targetElement);
            $t->appendChild($text);

            $xliffFile->appendChild($translation);
        }

        return $dom->saveXML();
    }

    /**
     * @param string     $key
     * @param array|null $metadata
     *
     * @return bool
     */
    private function hasMetadataArrayInfo($key, $metadata = null)
    {
        return null !== $metadata &&
            array_key_exists($key, $metadata) &&
            ($metadata[$key] instanceof \Traversable || is_array($metadata[$key]));
    }
}
