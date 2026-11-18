<?php

declare(strict_types=1);

namespace BBAF\Core;

final class Purify
{
    private const ALLOWED = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'em' => [],
        'u' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'blockquote' => [],
        'hr' => [],
        'a' => ['href'],
    ];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="utf-8"><div id="bbaf-root">' . $html . '</div>';
        $document->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $document->getElementById('bbaf-root');
        if (!$root) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return nl2br(e($html));
        }
        self::walk($root);
        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $output;
    }

    private static function walk(\DOMNode $node): void
    {
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }
            if (!$child instanceof \DOMElement) {
                $node->removeChild($child);
                continue;
            }
            $tag = strtolower($child->tagName);
            if (!isset(self::ALLOWED[$tag])) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $allowedAttrs = self::ALLOWED[$tag];
            $remove = [];
            if ($child->hasAttributes()) {
                foreach (iterator_to_array($child->attributes) as $attribute) {
                    $name = strtolower($attribute->name);
                    if (!in_array($name, $allowedAttrs, true) || str_starts_with($name, 'on')) {
                        $remove[] = $attribute->name;
                        continue;
                    }
                    if ($name === 'href' && !self::safeHref($attribute->value)) {
                        $remove[] = $attribute->name;
                    }
                }
            }
            foreach ($remove as $name) {
                $child->removeAttribute($name);
            }
            if ($tag === 'a' && $child->hasAttribute('href')) {
                $href = $child->getAttribute('href');
                if (preg_match('#^https?://#i', $href)) {
                    $child->setAttribute('rel', 'noopener noreferrer');
                    $child->setAttribute('target', '_blank');
                }
            }
            self::walk($child);
        }
    }

    private static function safeHref(string $href): bool
    {
        $href = trim($href);
        if ($href === '') {
            return false;
        }
        if (preg_match('#^(javascript|data):#i', $href)) {
            return false;
        }

        return (bool) preg_match('#^(https?://|/|mailto:)#i', $href);
    }
}
