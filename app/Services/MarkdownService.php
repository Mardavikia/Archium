<?php
declare(strict_types=1);

namespace Archium\Services;

/**
 * Renderer Markdown -> HTML sicuro.
 *
 * Strategia: l'input viene PRIMA escapato integralmente, poi si applicano le
 * trasformazioni Markdown. L'HTML grezzo nel testo rimane quindi inerte e
 * l'output contiene esclusivamente i tag generati qui sotto (whitelist per
 * costruzione). Link/immagini accettano solo http(s):// e /attachments/{id}.
 *
 * Subset supportato: H1-H6, **grassetto**, *corsivo*, `codice`, ```fenced```,
 * liste "- ", citazioni "> ", HR "---", [link](url), ![img](url).
 */
final class MarkdownService
{
    public function toHtml(string $markdown): string
    {
        $escaped = e(str_replace(["\r\n", "\r"], "\n", $markdown));
        $lines   = explode("\n", $escaped);

        $html    = '';
        $inCode  = false;
        $codeBuf = [];
        $listBuf = [];
        $quoteBuf = [];
        $paraBuf = [];

        $flushPara = function () use (&$html, &$paraBuf): void {
            if ($paraBuf !== []) {
                $html .= '<p>' . $this->inline(implode('<br>', $paraBuf)) . '</p>' . "\n";
                $paraBuf = [];
            }
        };
        $flushList = function () use (&$html, &$listBuf): void {
            if ($listBuf !== []) {
                $html .= '<ul>';
                foreach ($listBuf as $item) {
                    $html .= '<li>' . $this->inline($item) . '</li>';
                }
                $html .= '</ul>' . "\n";
                $listBuf = [];
            }
        };
        $flushQuote = function () use (&$html, &$quoteBuf): void {
            if ($quoteBuf !== []) {
                $html .= '<blockquote>' . $this->inline(implode('<br>', $quoteBuf)) . '</blockquote>' . "\n";
                $quoteBuf = [];
            }
        };
        $flushAll = function () use ($flushPara, $flushList, $flushQuote): void {
            $flushPara();
            $flushList();
            $flushQuote();
        };

        foreach ($lines as $line) {
            if (preg_match('/^```/', $line)) {
                $flushAll();
                if ($inCode) {
                    $html .= '<pre><code>' . implode("\n", $codeBuf) . '</code></pre>' . "\n";
                    $codeBuf = [];
                    $inCode = false;
                } else {
                    $inCode = true;
                }
                continue;
            }
            if ($inCode) {
                $codeBuf[] = $line;
                continue;
            }
            if (trim($line) === '') {
                $flushAll();
                continue;
            }
            if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
                $flushAll();
                $level = strlen($m[1]);
                $html .= '<h' . $level . '>' . $this->inline($m[2]) . '</h' . $level . '>' . "\n";
                continue;
            }
            if (preg_match('/^-{3,}$/', trim($line))) {
                $flushAll();
                $html .= "<hr>\n";
                continue;
            }
            if (preg_match('/^&gt;\s?(.*)$/', $line, $m)) { // '>' escapato
                $flushPara();
                $flushList();
                $quoteBuf[] = $m[1];
                continue;
            }
            if (preg_match('/^[-*]\s+(.*)$/', $line, $m)) {
                $flushPara();
                $flushQuote();
                $listBuf[] = $m[1];
                continue;
            }
            $paraBuf[] = $line;
        }

        if ($inCode && $codeBuf !== []) { // fence mai chiuso: emetti comunque come codice
            $html .= '<pre><code>' . implode("\n", $codeBuf) . '</code></pre>' . "\n";
        }
        $flushAll();

        return $html;
    }

    private function inline(string $text): string
    {
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/\*([^*\n]+)\*/', '<em>$1</em>', $text) ?? $text;

        // immagini e link: callback con validazione URL rigorosa
        $text = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)\s]+)\)/',
            fn (array $m): string => $this->image($m[1], $m[2]),
            $text
        ) ?? $text;
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            fn (array $m): string => $this->link($m[1], $m[2]),
            $text
        ) ?? $text;

        return $text;
    }

    /** URL consentiti: solo http(s) assoluti o allegati interni. */
    private function safeUrl(string $escapedUrl): ?string
    {
        $u = html_entity_decode($escapedUrl, ENT_QUOTES, 'UTF-8');
        if (preg_match('#^https?://#i', $u) || preg_match('#^/attachments/[0-9]+$#', $u)) {
            return $u;
        }
        return null;
    }

    private function link(string $label, string $url): string
    {
        $u = $this->safeUrl($url);
        if ($u === null) {
            return $label;
        }
        return '<a href="' . e($u) . '" rel="noopener noreferrer nofollow">' . $label . '</a>';
    }

    private function image(string $alt, string $url): string
    {
        $u = $this->safeUrl($url);
        if ($u === null) {
            return '<em>[immagine non consentita]</em>';
        }
        return '<img src="' . e($u) . '" alt="' . $alt . '" loading="lazy">';
    }
}