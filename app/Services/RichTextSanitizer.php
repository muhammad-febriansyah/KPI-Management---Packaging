<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class RichTextSanitizer
{
    /**
     * Tags the Quill toolbar (bold, italic, underline, strike, lists,
     * blockquote, link) can actually produce. Anything else is stripped
     * before it reaches the database, since the stored value is rendered
     * back as raw HTML.
     *
     * @var list<string>
     */
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'u', 's', 'ol', 'ul', 'li', 'a', 'blockquote'];

    /**
     * Strip everything but the formatting the report editor's toolbar can
     * produce, so stored HTML can be rendered back without risking stored XSS.
     */
    public function sanitize(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8"?><div>'.$html.'</div>', LIBXML_NOENT | LIBXML_NONET);
        libxml_clear_errors();

        $wrapper = $document->getElementsByTagName('div')->item(0);

        if (! $wrapper instanceof DOMElement) {
            return null;
        }

        $this->cleanChildren($wrapper);

        $output = '';
        foreach (iterator_to_array($wrapper->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }

        $output = trim($output);

        return $output !== '' ? $output : null;
    }

    /**
     * Recurses into each child first so a disallowed wrapper can be safely
     * unwrapped afterwards without losing already-cleaned nested content.
     */
    private function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $node->removeChild($child);

                continue;
            }

            $this->cleanChildren($child);

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style'], true)) {
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            $this->cleanAttributes($child);
        }
    }

    private function cleanAttributes(DOMElement $element): void
    {
        $tag = strtolower($element->tagName);

        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $keepHref = $tag === 'a' && $attribute->name === 'href' && ! $this->isDangerousUrl($attribute->value);

            if (! $keepHref) {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($tag === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer nofollow');
        }
    }

    private function isDangerousUrl(string $value): bool
    {
        $value = strtolower(trim($value));

        return str_starts_with($value, 'javascript:') || str_starts_with($value, 'data:') || str_starts_with($value, 'vbscript:');
    }
}
