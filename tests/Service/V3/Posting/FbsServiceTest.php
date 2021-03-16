<?php declare(strict_types=1);

namespace Gam6itko\OzonSeller\Tests\Service\V3\Posting;

use Gam6itko\OzonSeller\Service\V3\Posting\FbsService;
use Gam6itko\OzonSeller\Tests\Service\AbstractTestCase;

/**
 * @author Alexander Strizhak <gam6itko@gmail.com>
 *
 * @coversDefaultClass \Gam6itko\OzonSeller\Service\V3\Posting\FbsService
 */
class FbsServiceTest extends AbstractTestCase
{
    protected function getClass(): string
    {
        return FbsService::class;
    }

    /**
     * @covers ::list
     */
    public function testList(): void
    {
        $this->quickTest(
            'list',
            [],
            [
                'POST',
                '/v3/posting/fbs/list',
                '{"with":{"analytics_data":false,"barcodes":false,"financial_data":false},"filter":[],"dir":"asc","offset":0,"limit":10}',
            ]
        );
    }

    /**
     * @covers ::unfulfilledList
     */
    public function testUnfulfilledList(): void
    {
        $this->quickTest(
            'unfulfilledList',
            [],
            [
                'POST',
                '/v3/posting/fbs/unfulfilled/list',
                '{"with":{"analytics_data":false,"barcodes":false,"financial_data":false},"filter":[],"dir":"asc","offset":0,"limit":10}',
            ]
        );
    }
}
