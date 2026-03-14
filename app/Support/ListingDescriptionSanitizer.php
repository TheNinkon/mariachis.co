<?php

namespace App\Support;

class ListingDescriptionSanitizer
{
    public function sanitize(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        $document = new \DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $wrapped = '<?xml encoding="utf-8" ?><div id="listing-description-root">'.$html.'</div>';
        $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $root = $document->getElementById('listing-description-root');
        if (! $root instanceof \DOMElement) {
            return null;
        }

        $this->sanitizeNode($root, $document);

        $sanitized = '';
        foreach ($root->childNodes as $childNode) {
            $sanitized .= $document->saveHTML($childNode);
        }

        $sanitized = trim((string) $sanitized);

        return trim(strip_tags($sanitized)) === '' ? null : $sanitized;
    }

    private function sanitizeNode(\DOMNode $node, \DOMDocument $document): void
    {
        $allowedTags = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'a', 'h2', 'h3'];
        $children = [];

        foreach ($node->childNodes as $childNode) {
            $children[] = $childNode;
        }

        foreach ($children as $childNode) {
            if ($childNode instanceof \DOMComment) {
                $childNode->parentNode?->removeChild($childNode);
                continue;
            }

            if (! $childNode instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($childNode->tagName);

            if ($tag === 'b') {
                $this->replaceElementPreservingChildren($childNode, 'strong', $document);
                continue;
            }

            if ($tag === 'i') {
                $this->replaceElementPreservingChildren($childNode, 'em', $document);
                continue;
            }

            if ($tag === 'div') {
                $this->replaceElementPreservingChildren($childNode, 'p', $document);
                continue;
            }

            if (! in_array($tag, $allowedTags, true)) {
                $this->unwrapElement($childNode);
                continue;
            }

            $attributes = [];
            foreach ($childNode->attributes as $attribute) {
                $attributes[] = $attribute->nodeName;
            }

            foreach ($attributes as $attributeName) {
                if ($tag === 'a' && $attributeName === 'href') {
                    $href = trim((string) $childNode->getAttribute('href'));
                    if (preg_match('/^(https?:|mailto:|tel:)/i', $href) === 1) {
                        continue;
                    }
                }

                $childNode->removeAttribute($attributeName);
            }

            if ($tag === 'a') {
                $href = trim((string) $childNode->getAttribute('href'));
                if ($href === '' || preg_match('/^(https?:|mailto:|tel:)/i', $href) !== 1) {
                    $this->unwrapElement($childNode);
                    continue;
                }

                $childNode->setAttribute('rel', 'nofollow noopener noreferrer');
                $childNode->setAttribute('target', '_blank');
            }

            $this->sanitizeNode($childNode, $document);
        }
    }

    private function replaceElementPreservingChildren(\DOMElement $element, string $tagName, \DOMDocument $document): void
    {
        $replacement = $document->createElement($tagName);

        while ($element->firstChild) {
            $replacement->appendChild($element->firstChild);
        }

        $element->parentNode?->replaceChild($replacement, $element);
        $this->sanitizeNode($replacement, $document);
    }

    private function unwrapElement(\DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
