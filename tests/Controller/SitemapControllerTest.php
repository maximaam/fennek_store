<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SitemapControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = self::createClient();
        $client->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/xml');

        $content = $client->getResponse()->getContent();
        $xml = new \DOMDocument();
        self::assertTrue($xml->loadXML($content));
        self::assertSame('urlset', $xml->documentElement->localName);
        self::assertGreaterThan(0, $xml->getElementsByTagName('url')->length);
    }
}
