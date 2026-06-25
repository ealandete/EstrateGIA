<?php
declare(strict_types=1);

/**
 * Generador de Documentos HTML Profesionales
 * Convierte Markdown a HTML con formato profesional, diagramas y diseño
 */

class DocGenerator {
    private string $template;
    
    public function __construct() {
        $this->template = $this->getTemplate();
    }
    
    public function generate(string $title, string $subtitle, string $content, array $meta = []): string {
        $toc = $this->generateTOC($content);
        $html = str_replace(
            ['{{TITLE}}', '{{SUBTITLE}}', '{{CONTENT}}', '{{TOC}}', '{{DATE}}', '{{VERSION}}', '{{PAGES}}'],
            [$title, $subtitle, $content, $toc, date('d/m/Y'), $meta['version'] ?? '2.1', $meta['pages'] ?? '45'],
            $this->template
        );
        return $html;
    }
    
    private function generateTOC(string $content): string {
        preg_match_all('/<h([23])[^>]*>(.*?)<\/h[23]>/i', $content, $matches);
        $toc = '<nav class="toc"><h3>Índice</h3><ul>';
        for ($i = 0; $i < count($matches[0]); $i++) {
            $level = $matches[1][$i];
            $text = strip_tags($matches[2][$i]);
            $id = 'section-' . $i;
            $toc .= "<li class='toc-h{$level}'><a href='#{$id}'>{$text}</a></li>";
        }
        $toc .= '</ul></nav>';
        return $toc;
    }
    
    private function getTemplate(): string {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{TITLE}} — EstrateGIA v{{VERSION}}</title>
    <style>
        :root {
            --primary: #1a73e8;
            --primary-dark: #0d47a1;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --purple: #6f42c1;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-600: #6c757d;
            --gray-900: #212529;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            line-height: 1.7;
            color: var(--gray-900);
            background: var(--gray-100);
        }
        .doc-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }
        .doc-header h1 { font-size: 2.5rem; margin-bottom: 10px; }
        .doc-header p { font-size: 1.2rem; opacity: 0.9; }
        .doc-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .doc-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            max-width: 1400px;
            margin: 0 auto;
            gap: 30px;
            padding: 30px;
        }
        .toc {
            position: sticky;
            top: 20px;
            height: fit-content;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .toc h3 {
            font-size: 1rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        .toc ul { list-style: none; }
        .toc li { margin-bottom: 8px; }
        .toc a {
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.85rem;
            display: block;
            padding: 4px 0;
            transition: color 0.2s;
        }
        .toc a:hover { color: var(--primary); }
        .toc-h3 { padding-left: 15px; }
        .doc-content {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        h2 {
            font-size: 1.8rem;
            color: var(--primary);
            margin: 40px 0 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--gray-200);
        }
        h3 {
            font-size: 1.3rem;
            color: var(--gray-900);
            margin: 30px 0 15px;
        }
        h4 {
            font-size: 1.1rem;
            margin: 20px 0 10px;
        }
        p { margin-bottom: 15px; }
        ul, ol { margin: 15px 0 15px 25px; }
        li { margin-bottom: 8px; }
        code {
            background: var(--gray-100);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Fira Code', monospace;
            font-size: 0.9em;
        }
        pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 20px 0;
        }
        pre code {
            background: none;
            padding: 0;
            color: inherit;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }
        th {
            background: var(--gray-100);
            font-weight: 600;
        }
        .diagram {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
            text-align: center;
        }
        .diagram-title {
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary);
        }
        .flow-box {
            display: inline-block;
            padding: 15px 25px;
            border-radius: 8px;
            margin: 10px;
            font-weight: 500;
            color: white;
        }
        .flow-arrow {
            font-size: 1.5rem;
            color: var(--gray-600);
            margin: 0 10px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid var(--primary);
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin: 20px 0;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid var(--warning);
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin: 20px 0;
        }
        .success-box {
            background: #d4edda;
            border-left: 4px solid var(--success);
            padding: 15px 20px;
            border-radius: 0 8px 8px 0;
            margin: 20px 0;
        }
        .screenshot {
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            overflow: hidden;
            margin: 20px 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .screenshot img {
            width: 100%;
            display: block;
        }
        .screenshot-caption {
            background: var(--gray-100);
            padding: 10px 15px;
            font-size: 0.85rem;
            color: var(--gray-600);
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-primary { background: var(--primary); color: white; }
        .badge-success { background: var(--success); color: white; }
        .badge-warning { background: var(--warning); color: var(--gray-900); }
        .badge-danger { background: var(--danger); color: white; }
        @media print {
            .toc { display: none; }
            .doc-container { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .doc-container { grid-template-columns: 1fr; }
            .toc { position: static; }
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <h1>{{TITLE}}</h1>
        <p>{{SUBTITLE}}</p>
        <div class="doc-meta">
            <span><i class="fas fa-calendar"></i> {{DATE}}</span>
            <span><i class="fas fa-code-branch"></i> v{{VERSION}}</span>
            <span><i class="fas fa-file-lines"></i> {{PAGES}} páginas</span>
        </div>
    </div>
    <div class="doc-container">
        {{TOC}}
        <main class="doc-content">
            {{CONTENT}}
        </main>
    </div>
</body>
</html>
HTML;
    }
}
