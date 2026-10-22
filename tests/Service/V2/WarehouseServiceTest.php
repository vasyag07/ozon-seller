<?php

declare(strict_types=1);

namespace Gam6itko\OzonSeller\Tests\Service\V2;

use Gam6itko\OzonSeller\Exception\OzonSellerException;
use Gam6itko\OzonSeller\Service\V2\WarehouseService;
use Gam6itko\OzonSeller\Tests\Service\AbstractTestCase;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;

class WarehouseServiceTest extends AbstractTestCase
{
    protected function getClass(): string
    {
        return WarehouseService::class;
    }

    protected function setUp(): void
    {
        self::assertTrue(class_exists(WarehouseService::class), 'Warehouse V2 service is missing');
    }

    /** @dataProvider dataPages */
    public function testList(array $request, array $expected, array $page): void
    {
        $this->quickTest('list', [$request], ['POST', '/v2/warehouse/list', json_encode($expected)], json_encode($page), static function ($result) use ($page) {
            self::assertSame($page, $result);
        });
    }

    public function dataPages(): iterable
    {
        yield 'defaults' => [[], ['limit' => 10, 'cursor' => ''], ['warehouses' => [], 'cursor' => '', 'has_next' => false]];
        yield 'first page' => [
            ['limit' => 1, 'warehouse_ids' => ['a' => '123'], 'offset' => 50],
            ['limit' => 1, 'cursor' => '', 'warehouse_ids' => ['123']],
            ['warehouses' => [['warehouse_id' => 123]], 'cursor' => 'next-page', 'has_next' => true],
        ];
        yield 'last page' => [
            ['limit' => 200, 'cursor' => 'next-page', 'warehouse_ids' => range(1, 200)],
            ['limit' => 200, 'cursor' => 'next-page', 'warehouse_ids' => range(1, 200)],
            ['warehouses' => [['warehouse_id' => 124]], 'cursor' => '', 'has_next' => false],
        ];
    }

    /** @dataProvider dataInvalidRequests */
    public function testInvalidRequestDoesNotSendHttp(array $request): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendRequest');
        $svc = $this->createSvc($client, $this->createRequestFactory(), $this->createStreamFactory());
        $this->expectException(\LogicException::class);
        $svc->list($request);
    }

    public function dataInvalidRequests(): iterable
    {
        yield [['limit' => 0]];
        yield [['limit' => 201]];
        yield [['limit' => '10']];
        yield [['warehouse_ids' => range(1, 201)]];
        yield [['warehouse_ids' => '123']];
    }

    public function testHttpError(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendRequest')->willReturn(new Response(403, [], '{"code":7,"message":"Invalid Api-Key"}'));
        $svc = $this->createSvc($client, $this->createRequestFactory(), $this->createStreamFactory());
        $this->expectException(OzonSellerException::class);
        $svc->list();
    }
}
