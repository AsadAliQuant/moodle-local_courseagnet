<?php

// This file is part of Course Agent - AI Course Creator Plugin for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * File content extractor for Course Agent.
 * Extracts plain text from various document formats.
 *
 * @package   local_courseagent
 * @copyright 2026 Course Agent
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.Commenting.InlineComment.InvalidEndChar
namespace local_courseagent;

/**
 * Extracts readable plain text from uploaded documents.
 * Supported: TXT, MD, CSV, RTF, DOCX, PPTX, ODT, EPUB, PDF (basic).
 *
 * Class name is lowercase to match the file name (extractor.php). Moodle's
 * autoloader maps `local_courseagent\Extractor` to `classes/Extractor.php`
 * literally, breaking on case-sensitive filesystems (Linux).
 */
// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps,PSR1.Classes.ClassDeclaration.MissingNamespace
class extractor
{
    /**
     * Extract text from an uploaded file.
     *
     * @param string $tmppath  Path to the temporary uploaded file
     * @param string $filename Original filename (used to detect extension)
     * @param int    $maxchars Maximum characters to return (default 100 000)
     * @return string Extracted plain text
     * @throws \Exception on unreadable or unsupported file
     */
    public function extract(string $tmppath, string $filename, int $maxchars = 100000): string
    {
        if (!is_readable($tmppath)) {
            throw new \Exception('Uploaded file is not readable on the server.');
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        switch ($ext) {
            case 'txt':
            case 'md':
            case 'csv':
                $text = file_get_contents($tmppath);
                break;

            case 'rtf':
                $text = $this->extractRtf($tmppath);
                break;

            case 'docx':
                $text = $this->extractZipXml($tmppath, 'word/document.xml', ['w:t', 'w:delText']);
                break;

            case 'pptx':
                $text = $this->extractZipXmlGlob($tmppath, 'ppt/slides/slide*.xml', ['a:t']);
                break;

            case 'odt':
                $text = $this->extractZipXml($tmppath, 'content.xml', ['text:p', 'text:span', 'text:h']);
                break;

            case 'epub':
                $text = $this->extractEpub($tmppath);
                break;

            case 'pdf':
                $text = $this->extractPdf($tmppath);
                break;

            default:
                throw new \Exception(
                    'Unsupported file type ".' . $ext . '". Accepted: TXT, PDF, DOCX, PPTX, ODT, RTF, MD, CSV, EPUB.'
                );
        }

        // Normalise whitespace.
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/(\r\n|\r|\n){3,}/', "\n\n", $text);
        $text = trim($text);

        if (empty($text)) {
            throw new \Exception('Could not extract any readable text from "' . htmlspecialchars($filename) . '". The file may be empty, image-based, or DRM-protected.');
        }

        // Truncate to character limit.
        if (mb_strlen($text) > $maxchars) {
            $text = mb_substr($text, 0, $maxchars);
            $text .= "\n\n[Content truncated at {$maxchars} characters]";
        }

        return $text;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Strip RTF control words and return plain text.
     */
    private function extractRtf(string $path): string
    {
        $raw = file_get_contents($path);
        // Remove RTF control words and groups.
        $text = preg_replace('/\\\\[a-z]+\-?[0-9]*[ ]?/', '', $raw);
        $text = preg_replace('/\{|\}/', '', $text);
        $text = preg_replace('/\\\\\'[0-9a-f]{2}/i', '', $text);
        $text = str_replace(['\\n', '\\r'], "\n", $text);
        return $text;
    }

    /**
     * Extract text from a single XML entry inside a ZIP archive (e.g. DOCX, ODT).
     *
     * @param string   $zippath  Path to the ZIP file
     * @param string   $xmlentry Entry path inside the ZIP
     * @param string[] $tags     XML element names whose text content to collect
     */
    private function extractZipXml(string $zippath, string $xmlentry, array $tags): string
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('PHP ZipArchive extension is required to read this file type.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zippath) !== true) {
            throw new \Exception('Could not open ZIP-based document. The file may be corrupted.');
        }

        $xml = $zip->getFromName($xmlentry);
        $zip->close();

        if ($xml === false) {
            throw new \Exception('Expected entry "' . $xmlentry . '" not found inside the archive.');
        }

        return $this->xmlTagsToText($xml, $tags);
    }

    /**
     * Extract text from multiple XML entries matching a glob pattern (e.g. PPTX slides).
     *
     * @param string   $zippath  Path to the ZIP file
     * @param string   $pattern  Glob-style pattern (only * wildcard supported)
     * @param string[] $tags     XML element names whose text content to collect
     */
    private function extractZipXmlGlob(string $zippath, string $pattern, array $tags): string
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('PHP ZipArchive extension is required to read this file type.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($zippath) !== true) {
            throw new \Exception('Could not open ZIP-based document. The file may be corrupted.');
        }

        // Convert glob pattern to regex.
        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';
        $parts = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match($regex, $name)) {
                $xml = $zip->getFromIndex($i);
                if ($xml !== false) {
                    $parts[] = $this->xmlTagsToText($xml, $tags);
                }
            }
        }
        $zip->close();

        return implode("\n\n", $parts);
    }

    /**
     * Parse XML and concatenate text from the given element names.
     */
    private function xmlTagsToText(string $xml, array $tags): string
    {
        // Suppress XML warnings for malformed namespace prefixes.
        $dom = new \DOMDocument();
        @$dom->loadXML($xml);

        $parts = [];
        foreach ($tags as $tag) {
            // Try both with and without namespace prefix.
            $localname = strpos($tag, ':') !== false ? explode(':', $tag)[1] : $tag;
            $nodes = $dom->getElementsByTagName($localname);
            foreach ($nodes as $node) {
                $t = trim($node->textContent);
                if ($t !== '') {
                    $parts[] = $t;
                }
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Extract text from EPUB (ZIP of XHTML files).
     */
    private function extractEpub(string $path): string
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('PHP ZipArchive extension is required to read EPUB files.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \Exception('Could not open EPUB file. It may be corrupted.');
        }

        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/\.(xhtml|html|htm|xml)$/i', $name) && strpos($name, '__MACOSX') === false) {
                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    // Strip HTML tags.
                    $parts[] = trim(html_entity_decode(strip_tags($content), ENT_QUOTES, 'UTF-8'));
                }
            }
        }
        $zip->close();

        return implode("\n\n", array_filter($parts));
    }

    /**
     * Extract text from a PDF using regex heuristics.
     * Works for most basic PDFs; image-only PDFs will yield empty/partial text.
     */
    private function extractPdf(string $path): string
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \Exception('Could not read the PDF file.');
        }

        $text = '';

        // Extract text from BT...ET blocks (PDF text stream operators).
        preg_match_all('/BT(.*?)ET/s', $raw, $blocks);
        foreach ($blocks[1] as $block) {
            // Match Tj / TJ / ' / " text-showing operators.
            preg_match_all('/\(([^)]*)\)\s*T[j\'"]/', $block, $tj);
            foreach ($tj[1] as $t) {
                $text .= $this->decodePdfString($t) . ' ';
            }

            preg_match_all('/\[(.*?)\]\s*TJ/s', $block, $tjarray);
            foreach ($tjarray[1] as $t) {
                preg_match_all('/\(([^)]*)\)/', $t, $inner);
                foreach ($inner[1] as $part) {
                    $text .= $this->decodePdfString($part);
                }
                $text .= ' ';
            }
        }

        // Fallback: also grab any readable ASCII sequences between stream markers.
        if (mb_strlen(trim($text)) < 50) {
            preg_match_all('/stream(.*?)endstream/s', $raw, $streams);
            foreach ($streams[1] as $s) {
                $visible = preg_replace('/[^\x20-\x7E\n]/', ' ', $s);
                $visible = preg_replace('/\s{4,}/', "\n", $visible);
                $text   .= $visible . "\n";
            }
        }

        return $text;
    }

    /**
     * Decode a raw PDF string literal (handle basic escape sequences and octal).
     */
    private function decodePdfString(string $s): string
    {
        // Handle escape sequences.
        $s = str_replace(['\\n', '\\r', '\\t', '\\\\', '\\(', '\\)'], ["\n", "\r", "\t", '\\', '(', ')'], $s);
        // Handle octal escapes \ddd.
        $s = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $s);
        return $s;
    }
}
