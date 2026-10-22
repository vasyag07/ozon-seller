<?php

declare(strict_types=1);

namespace Gam6itko\OzonSeller\Tests\Service\V2\Posting;

use Gam6itko\OzonSeller\Exception\OzonSellerException;
use Gam6itko\OzonSeller\Service\V2\Posting\FbsService;
use Gam6itko\OzonSeller\Tests\Service\AbstractTestCase;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;

class PackageLabelGetTest extends AbstractTestCase
{
    protected function getClass(): string
    {
        return FbsService::class;
    }

    protected function setUp(): void
    {
        self::assertTrue(method_exists(FbsService::class, 'packageLabelGet'), 'packageLabelGet is missing');
    }

    /** @dataProvider dataStates */
    public function testGetPreservesJsonAndDoesNotDownloadFile(array $state): void
    {
        $this->quickTest('packageLabelGet', [123], ['POST', '/v2/posting/fbs/package-label/get', '{"task_id":123}'], json_encode($state), static function ($result) use ($state) {
            self::assertSame($state, $result);
        });
    }

    public function dataStates(): iterable
    {
        yield [['status' => ['code' => 'pending'], 'error' => [], 'file_url' => '']];
        yield [['status' => ['code' => 'in_progress'], 'error' => [], 'file_url' => '']];
        yield [[
            'status' => ['code' => 'completed', 'postings_count' => 2, 'printed_postings_count' => 1, 'unprinted_postings' => [
                ['posting_number' => 'example-2', 'message' => 'Label unavailable'],
            ]],
            'error' => [], 'file_url' => 'https://files.example.com/labels.pdf',
        ]];
        yield [['status' => ['code' => 'error'], 'error' => ['code' => 'LABEL_ERROR', 'message' => 'Cannot create label'], 'file_url' => '']];
    }

    public function testHttpError(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendRequest')->willReturn(new Response(404, [], '{"message":"Task not found"}'));
        $svc = $this->createSvc($client, $this->createRequestFactory(), $this->createStreamFactory());
        $this->expectException(OzonSellerException::class);
        $svc->packageLabelGet(123);
    }
}
