<?php

namespace App\Service;

use League\CommonMark\CommonMarkConverter;

/**
 * Convertit le contenu Markdown d'un article en HTML, et en extrait un
 * sommaire ("Sommaire") à partir des titres de niveau 2 (##), avec des
 * ancres pour la navigation — comme dans la maquette du blog.
 */
class ArticleRenderer
{
    private CommonMarkConverter $converter;

    public function __construct()
    {
        $this->converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * @return array{html: string, toc: array<int, array{id: string, title: string}>}
     */
    public function render(string $markdown): array
    {
        $html = (string) $this->converter->convert($markdown);
        $toc = [];

        $html = preg_replace_callback(
            '/<h2>(.*?)<\/h2>/i',
            function (array $matches) use (&$toc) {
                $title = trim(strip_tags($matches[1]));
                $id = 'section-' . (count($toc) + 1) . '-' . $this->slugify($title);
                $toc[] = ['id' => $id, 'title' => $title];

                return sprintf('<h2 id="%s">%s</h2>', $id, $matches[1]);
            },
            $html
        );

        return ['html' => $html, 'toc' => $toc];
    }

    private function slugify(string $text): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower($transliterated);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';

        return trim($text, '-') ?: 'section';
    }
}
