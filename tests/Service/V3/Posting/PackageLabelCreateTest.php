<?php

declare(strict_types=1);

namespace Gam6itko\OzonSeller\Tests\Service\V3\Posting;

use Gam6itko\OzonSeller\Exception\OzonSellerException;
use Gam6itko\OzonSeller\Service\V3\Posting\FbsService;
use Gam6itko\OzonSeller\Tests\Service\AbstractTestCase;
use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;

class PackageLabelCreateTest extends AbstractTestCase
{
    protected function getClass(): string
    {
        return FbsService::class;
    }

    protected function setUp(): void
    {
        self::assertTrue(method_exists(FbsService::class, 'packageLabelCreate'), 'packageLabelCreate is missing');
    }

    /** @dataProvider dataPostingNumbers */
    public function testCreatePreservesAllTasks(array $postingNumbers): void
    {
        $response = ['tasks' => [
            ['task_id' => 123, 'task_type' => 'big_label'],
            ['task_id' => 124, 'task_type' => 'small_label'],
        ]];
        $this->quickTest('packageLabelCreate', [$postingNumbers], [
            'POST', '/v3/posting/fbs/package-label/create', json_encode(['posting_numbers' => array_values($postingNumbers)]),
        ], json_encode($response), static function ($result) use ($response) {
            self::assertSame($response, $result);
        });
    }

    public function dataPostingNumbers(): iterable
    {
        yield [['example-1']];
        yield [array_fill(0, 1000, 'example-1')];
        yield [['first' => 'example-1', 'second' => 'example-2']];
    }

    /** @dataProvider dataInvalidPostingNumbers */
    public function testInvalidRequestDoesNotSendHttp(array $postingNumbers): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::never())->method('sendRequest');
        $svc = $this->createSvc($client, $this->createRequestFactory(), $this->createStreamFactory());
        $this->expectException(\LogicException::class);
        $svc->packageLabelCreate($postingNumbers);
    }

    public function dataInvalidPostingNumbers(): iterable
    {
        yield [[]];
        yield [array_fill(0, 1001, 'example-1')];
        yield [['']];
        yield [[123]];
    }

    public function testHttpError(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects(self::once())->method('sendRequest')->willReturn(new Response(400, [], '{"message":"Invalid posting_numbers"}'));
        $svc = $this->createSvc($client, $this->createRequestFactory(), $this->createStreamFactory());
        $this->expectException(OzonSellerException::class);
        $svc->packageLabelCreate(['example-1']);
    }
}
