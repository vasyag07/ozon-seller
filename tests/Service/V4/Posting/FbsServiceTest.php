<?php

declare(strict_types=1);

namespace Gam6itko\OzonSeller\Tests\Service\V4\Posting;

use Gam6itko\OzonSeller\Enum\SortDirection;
use Gam6itko\OzonSeller\Exception\OzonSellerException;
use Gam6itko\OzonSeller\Service\HasOrdersInterface;
use Gam6itko\OzonSeller\Service\V4\Posting\FbsService;
use Gam6itko\OzonSeller\Tests\Service\AbstractTestCase;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @coversDefaultClass \Gam6itko\OzonSeller\Service\V4\Posting\FbsService
 */
class FbsServiceTest extends AbstractTestCase
{
    protected function getClass(): string
    {
        return FbsService::class;
    }

    /** @covers ::list */
    public function testListPreservesV4ParametersAndWholePage(): void
    {
        $request = [
            'filter' => [
                'since'                    => '2026-10-20T00:00:00Z',
                'to'                       => '2026-10-21T00:00:00Z',
                'statuses'                 => ['awaiting_packaging', 'awaiting_deliver'],
                'warehouse_ids'            => [3092889984],
                'delivery_method_ids'      => [123456789],
                'provider_ids'             => [123],
                'order_numbers'            => ['12345-1'],
                'order_id'                 => 123456,
                'integration_type_flow'    => ['ozon'],
                'is_blr_traceable'         => false,
                'last_changed_status_date' => [
                    'from' => '2026-10-20T00:00:00Z',
                    'to'   => '2026-10-21T00:00:00Z',
                ],
            ],
            'limit'    => 100,
            'cursor'   => 'previous-page',
            'sort_dir' => 'DESC',
            'translit' => true,
            'with'     => [
                'analytics_data' => true,
                'barcodes'       => true,
                'financial_data' => true,
                'legal_info'     => true,
                'translit'       => true,
            ],
        ];
        $page = [
            'postings' => [['posting_number' => '12345-1', 'status' => 'awaiting_deliver']],
            'cursor'   => 'next-page',
            'has_next' => true,
        ];

        $this->assertListRequest($request, $request, $page);
    }

    /**
     * @covers ::list
     * @dataProvider dataPageBoundaries
     */
    public function testListPageBoundaries(int $limit, array $page): void
    {
        $this->assertListRequest(
            ['filter' => [], 'limit' => $limit],
            ['filter' => new \stdClass(), 'limit' => $limit],
            $page
        );
    }

    public function dataPageBoundaries(): iterable
    {
        yield [1, ['postings' => [], 'cursor' => '', 'has_next' => false]];
        yield [100, ['postings' => [['posting_number' => 'last-1']], 'cursor' => 'last', 'has_next' => false]];
    }

    /** @covers ::list */
    public function testListUsesV4SortingAndIgnoresLegacyPagination(): void
    {
        $this->assertListRequest(
            ['filter' => [], 'limit' => 10, 'sort_dir' => SortDirection::ASC, 'dir' => 'desc', 'offset' => 50, 'with' => []],
            ['filter' => new \stdClass(), 'limit' => 10, 'sort_dir' => 'ASC', 'with' => new \stdClass()],
            ['postings' => [], 'cursor' => '', 'has_next' => false]
        );
    }

    /**
     * @covers ::list
     * @dataProvider dataInvalidRequests
     */
    public function testListRejectsInvalidRequiredParameters(array $request): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendRequest');
        $svc = $this->createSvc($client, $this->createRequestFactory(), $this->createStreamFactory());
        self::assertTrue(method_exists($svc, 'list'));
        $this->expectException(\LogicException::class);
        $svc->list($request);
    }

    public function dataInvalidRequests(): iterable
    {
        yield 'missing limit' => [['filter' => []]];
        yield 'zero limit' => [['filter' => [], 'limit' => 0]];
        yield 'negative limit' => [['filter' => [], 'limit' => -1]];
        yield 'limit too large' => [['filter' => [], 'limit' => 101]];
        yield 'non-integer limit' => [['filter' => [], 'limit' => 1.5]];
        yield 'string limit' => [['filter' => [], 'limit' => '10']];
        yield 'missing filter' => [['limit' => 10]];
        yield 'invalid filter' => [['filter' => 'invalid', 'limit' => 10]];
        yield 'invalid sorting' => [['filter' => [], 'limit' => 10, 'sort_dir' => 'sideways']];
    }

    /** @covers ::list */
    public function testListPropagatesHttpError(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendRequest')
            ->willReturn(new Response(403, [], '{"code":7,"message":"Invalid Api-Key","details":[]}'));
        $svc = $this->createSvc($client, $this->createRequestFactory(), $this->createStreamFactory());
        self::assertTrue(method_exists($svc, 'list'));
        $this->expectException(OzonSellerException::class);
        $this->expectExceptionMessage('Invalid Api-Key');
        $svc->list(['filter' => [], 'limit' => 10]);
    }

    private function assertListRequest(array $request, array $expected, array $page): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendRequest')
            ->willReturnCallback(static function (RequestInterface $httpRequest) use ($expected, $page) {
                self::assertSame('POST', $httpRequest->getMethod());
                self::assertSame('https://packagist.org/v4/posting/fbs/list', (string) $httpRequest->getUri());
                self::assertSame('123', $httpRequest->getHeaderLine('Client-Id'));
                self::assertSame('api-key', $httpRequest->getHeaderLine('Api-Key'));
                self::assertSame('application/json', $httpRequest->getHeaderLine('Content-Type'));
                self::assertJsonStringEqualsJsonString(json_encode($expected), (string) $httpRequest->getBody());

                return new Response(200, [], json_encode($page));
            });
        $svc = $this->createSvc($client, $this->createRequestFactory(), $this->createStreamFactory());
        self::assertTrue(method_exists($svc, 'list'), 'V4 list method is missing');
        self::assertInstanceOf(HasOrdersInterface::class, $svc);
        self::assertSame($page, $svc->list($request));
    }
}
