<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Page content is GitHub-flavored markdown. toHtml() is the one way it becomes HTML (the page,
 * its excerpt and the Nova preview); fromTrixHtml() converts what the former Trix editor stored.
 *
 * Only admins write pages, so raw HTML in the markdown is kept. A line break inside a paragraph
 * stays a line break (as in GitHub comments), and the page title is the only <h1>: "# " in the
 * content renders as <h2>.
 */
class MarkdownService
{
    private ?MarkdownConverter $converter = null;

    public function toHtml(string $markdown): string
    {
        return (string) $this->converter()->convert($markdown);
    }

    public function fromTrixHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $converter = new HtmlConverter([
            'header_style' => 'atx',
            'hard_break' => true,
            'strip_placeholder_links' => true,
        ]);

        $markdown = $converter->convert($this->normalizedTrixHtml($html));
        // Trix ends many lines with &nbsp;; the converter escapes "_" in image alt texts and every "&" as entities.
        $markdown = str_replace('&#95;', '\\_', $markdown);
        $markdown = (string) preg_replace('/&amp;(?![a-zA-Z0-9#]+;)/', '&', $markdown);
        $markdown = implode("\n", array_map(rtrim(...), explode("\n", $markdown)));

        return trim($markdown)."\n";
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter === null) {
            $environment = new Environment([
                'html_input' => 'allow',
                'allow_unsafe_links' => false,
                'renderer' => ['soft_break' => "<br>\n"],
            ]);
            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new GithubFlavoredMarkdownExtension);
            $environment->addEventListener(DocumentParsedEvent::class, function (DocumentParsedEvent $event) {
                foreach ($event->getDocument()->iterator() as $node) {
                    if ($node instanceof Heading && $node->getLevel() === 1) {
                        $node->setLevel(2);
                    }
                }
            });
            $this->converter = new MarkdownConverter($environment);
        }

        return $this->converter;
    }

    /**
     * Trix writes attachments as <figure data-trix-attachment>, and paragraphs as <div>s whose
     * lines are separated by <br> (a line of only &nbsp; ends a paragraph). Rewritten as plain
     * <img>/<a> and <p>, the HTML converter turns them into markdown.
     */
    private function normalizedTrixHtml(string $html): string
    {
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $body = $document->getElementsByTagName('body')->item(0);

        foreach (iterator_to_array($document->getElementsByTagName('figure')) as $figure) {
            $replacement = $this->attachmentNode($document, $figure);
            if ($replacement !== null) {
                $figure->parentNode?->replaceChild($replacement, $figure);
            }
        }
        foreach (iterator_to_array($document->getElementsByTagName('h1')) as $heading) {
            $this->rename($heading, 'h2');
        }
        foreach (iterator_to_array($body->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'div') {
                foreach ($this->paragraphs($document, $child) as $paragraph) {
                    $body->insertBefore($paragraph, $child);
                }
                $body->removeChild($child);
            }
        }

        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }

        return $result;
    }

    private function attachmentNode(DOMDocument $document, DOMElement $figure): ?DOMNode
    {
        $attachment = json_decode(html_entity_decode($figure->getAttribute('data-trix-attachment')), true);
        if (! is_array($attachment) || ! is_string($attachment['url'] ?? $attachment['href'] ?? null)) {
            return null;
        }
        $url = $attachment['url'] ?? $attachment['href'];
        $attributes = json_decode(html_entity_decode($figure->getAttribute('data-trix-attributes')), true);
        $caption = is_array($attributes) && is_string($attributes['caption'] ?? null) ? $attributes['caption'] : null;
        $name = is_string($attachment['filename'] ?? null) ? $attachment['filename'] : basename((string) parse_url($url, PHP_URL_PATH));

        if (str_starts_with((string) ($attachment['contentType'] ?? ''), 'image/') || $figure->getElementsByTagName('img')->length > 0) {
            $image = $document->createElement('img');
            $image->setAttribute('src', $url);
            $image->setAttribute('alt', $caption ?? pathinfo($name, PATHINFO_FILENAME));
            if ($caption === null) {
                return $image;
            }
            // An edited caption was visible under the image; it stays visible as an emphasized line.
            $figure_content = $document->createDocumentFragment();
            $figure_content->appendChild($image);
            $figure_content->appendChild($document->createElement('br'));
            $figure_content->appendChild($document->createElement('em', htmlspecialchars($caption)));

            return $figure_content;
        }
        $link = $document->createElement('a');
        $link->setAttribute('href', $url);
        $link->appendChild($document->createTextNode($caption ?? $name));

        return $link;
    }

    /** @return list<DOMElement> the div's lines, grouped into <p>s (blocks such as <ul> stay as they are) */
    private function paragraphs(DOMDocument $document, DOMElement $div): array
    {
        $blocks = [];
        $lines = [[]];
        $flush = function () use (&$blocks, &$lines, $document) {
            $paragraph = null;
            foreach ($lines as $line) {
                $text = trim(str_replace("\u{00A0}", ' ', implode('', array_map(fn (DOMNode $node) => $node->textContent, $line))));
                $has_media = array_filter($line, fn (DOMNode $node) => $node instanceof DOMElement && ($node->tagName === 'img' || $node->getElementsByTagName('img')->length > 0));
                if ($text === '' && $has_media === []) {
                    if ($paragraph !== null) {
                        $blocks[] = $paragraph;
                        $paragraph = null;
                    }

                    continue;
                }
                if ($paragraph === null) {
                    $paragraph = $document->createElement('p');
                } else {
                    $paragraph->appendChild($document->createElement('br'));
                }
                foreach ($line as $node) {
                    $paragraph->appendChild($node);
                }
            }
            if ($paragraph !== null) {
                $blocks[] = $paragraph;
            }
            $lines = [[]];
        };

        foreach (iterator_to_array($div->childNodes) as $node) {
            if ($node instanceof DOMElement && $node->tagName === 'br') {
                $lines[] = [];
            } elseif ($node instanceof DOMElement && in_array($node->tagName, ['ul', 'ol', 'pre', 'blockquote', 'h2', 'h3', 'div'], true)) {
                $flush();
                $blocks[] = $node;
            } else {
                $lines[array_key_last($lines)][] = $node;
            }
        }
        $flush();

        return $blocks;
    }

    private function rename(DOMElement $element, string $tag): void
    {
        $renamed = $element->ownerDocument->createElement($tag);
        while ($element->firstChild !== null) {
            $renamed->appendChild($element->firstChild);
        }
        $element->parentNode?->replaceChild($renamed, $element);
    }
}
