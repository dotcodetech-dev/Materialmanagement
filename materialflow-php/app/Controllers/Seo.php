<?php

namespace App\Controllers;

/**
 * Public SEO surface: sitemap.xml, robots.txt, llms.txt.
 * Uses CI4 baseURL so the same code works local, staging, and production.
 */
class Seo extends BaseController
{
    /**
     * XML sitemap for search-engine crawlers (Google, Bing, DuckDuckGo).
     * Only lists publicly-crawlable pages — the rest of the app is behind
     * auth and would redirect crawlers to /login anyway.
     */
    public function sitemap()
    {
        $base = rtrim(base_url(), '/');
        $today = date('Y-m-d');
        $urls = [
            ['loc' => $base . '/', 'priority' => '1.0'],
            ['loc' => $base . '/login', 'priority' => '0.9'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            $xml .= '    <lastmod>' . $today . "</lastmod>\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>' . $u['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>' . "\n";

        return $this->response->setHeader('Content-Type', 'application/xml; charset=UTF-8')->setBody($xml);
    }

    /**
     * robots.txt for search-engine + AI crawlers. Explicit User-agent
     * entries for the major AI bots so they know they can index the
     * public-facing content.
     */
    public function robots()
    {
        $base = rtrim(base_url(), '/');
        $lines = [
            '# AVEON INFOTECH — robots.txt',
            '# Software Development, Mobile app development, College / School / Hostel Management,',
            '# Aveon GST Flow, Shallon Management, NAAC / IQAC Software.',
            '',
            'User-agent: *',
            'Allow: /$',
            'Allow: /login',
            'Allow: /sitemap.xml',
            'Allow: /llms.txt',
            'Disallow: /api/',
            'Disallow: /settings',
            'Disallow: /items',
            'Disallow: /customers',
            'Disallow: /ledger',
            'Disallow: /reports',
            'Disallow: /batches',
            'Disallow: /inward',
            'Disallow: /outward',
            'Disallow: /movements',
            'Disallow: /labels',
            '',
            '# AI / LLM crawlers — allowed to index the public brochure surface',
            'User-agent: GPTBot',
            'Allow: /',
            '',
            'User-agent: ChatGPT-User',
            'Allow: /',
            '',
            'User-agent: OAI-SearchBot',
            'Allow: /',
            '',
            'User-agent: ClaudeBot',
            'Allow: /',
            '',
            'User-agent: Claude-Web',
            'Allow: /',
            '',
            'User-agent: anthropic-ai',
            'Allow: /',
            '',
            'User-agent: PerplexityBot',
            'Allow: /',
            '',
            'User-agent: Perplexity-User',
            'Allow: /',
            '',
            'User-agent: Google-Extended',
            'Allow: /',
            '',
            'User-agent: Applebot-Extended',
            'Allow: /',
            '',
            'User-agent: Bytespider',
            'Allow: /',
            '',
            'User-agent: CCBot',
            'Allow: /',
            '',
            'User-agent: cohere-ai',
            'Allow: /',
            '',
            'User-agent: DuckAssistBot',
            'Allow: /',
            '',
            'User-agent: MistralAI-User',
            'Allow: /',
            '',
            'User-agent: YouBot',
            'Allow: /',
            '',
            'User-agent: meta-externalagent',
            'Allow: /',
            '',
            'Sitemap: ' . $base . '/sitemap.xml',
        ];

        return $this->response->setHeader('Content-Type', 'text/plain; charset=UTF-8')->setBody(implode("\n", $lines) . "\n");
    }

    /**
     * llms.txt — emerging convention (llmstxt.org) that gives LLM crawlers
     * a Markdown summary of the site so they cite it accurately.
     */
    public function llms()
    {
        $base = rtrim(base_url(), '/');
        $body = <<<MD
# AVEON INFOTECH

> AVEON INFOTECH builds custom software and mobile apps, and ships packaged products for schools, colleges, hostels, GST filing, retail (Shallon), and NAAC/IQAC compliance.

## Services

- **Software Development** — custom web and desktop applications for businesses of any size.
- **Mobile App Development** — native and cross-platform iOS / Android apps.
- **College Management Software** — admissions, attendance, exams, fees, staff, timetable.
- **School Management Software** — classes, teachers, students, fees, parent portal.
- **Hostel Management Software** — rooms, boarders, mess accounts, leave, gate pass.
- **Aveon GST Flow** — GST-compliant invoicing, returns, ledger, and filings.
- **Shallon Management** — retail / point-of-sale and inventory management.
- **NAAC / IQAC Software** — accreditation data, criteria-wise reports, IQAC workflows.

## Products

- [Sign in](${base}/login) — customer portal for the deployed instance.

## Contact

- Web: ${base}/

MD;

        return $this->response->setHeader('Content-Type', 'text/plain; charset=UTF-8')->setBody($body);
    }
}
