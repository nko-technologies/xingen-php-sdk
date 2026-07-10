<?php

declare(strict_types=1);

namespace Xingen\Sdk\Tests;

use PHPUnit\Framework\TestCase;
use Xingen\Sdk\Internal\Json;
use Xingen\Sdk\Invoices\InvoiceRecord;
use Xingen\Sdk\Paging\Page;

final class PageDeserializationTest extends TestCase
{
    public function testPageDecodesContentAndIgnoresUnknownSpringDataFields(): void
    {
        $data = (string) file_get_contents(__DIR__ . '/Fixtures/page-of-invoices.json');

        $page = Page::fromWire(Json::decode($data), InvoiceRecord::fromWire(...));

        $this->assertSame(1, $page->totalElements);
        $this->assertSame(1, $page->totalPages);
        $this->assertSame(0, $page->number);
        $this->assertSame(20, $page->size);
        $this->assertTrue($page->first);
        $this->assertTrue($page->last);
        $this->assertSame(1, $page->numberOfElements);
        $this->assertFalse($page->empty);

        $this->assertCount(1, $page->content);
        $this->assertSame('inv_01HXYZ', $page->content[0]->id);
        $this->assertNotNull($page->content[0]->invoice);
        $this->assertSame('INV-1', $page->content[0]->invoice->invoiceNumber);
    }
}
