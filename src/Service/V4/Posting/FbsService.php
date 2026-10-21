<?php

declare(strict_types=1);

namespace Gam6itko\OzonSeller\Service\V4\Posting;

use Gam6itko\OzonSeller\Enum\PostingScheme;
use Gam6itko\OzonSeller\Service\AbstractService;
use Gam6itko\OzonSeller\Service\HasOrdersInterface;
use Gam6itko\OzonSeller\Utils\ArrayHelper;
use Gam6itko\OzonSeller\Utils\WithResolver;

class FbsService extends AbstractService implements HasOrdersInterface
{
    private $path = '/v4/posting/fbs';

    /**
     * Fetch one page; pass the returned cursor to the next call while has_next is true.
     *
     * @see https://docs.ozon.ru/api/seller/#operation/PostingFbsList
     *
     * @return array Full response with postings, cursor and has_next
     */
    public function list(array $requestData): array
    {
        if (!isset($requestData['limit']) || !is_int($requestData['limit']) || $requestData['limit'] < 1 || $requestData['limit'] > 100) {
            throw new \LogicException('Parameter `limit` must be an integer between 1 and 100');
        }
        if (!isset($requestData['filter']) || !is_array($requestData['filter'])) {
            throw new \LogicException('Parameter `filter` must be an array');
        }

        $body = ArrayHelper::pick($requestData, ['filter', 'limit', 'cursor', 'sort_dir', 'with', 'translit']);
        if (array_key_exists('sort_dir', $body)) {
            if (!is_string($body['sort_dir']) || !in_array(strtoupper($body['sort_dir']), ['ASC', 'DESC'], true)) {
                throw new \LogicException('Parameter `sort_dir` must be ASC or DESC');
            }
            $body['sort_dir'] = strtoupper($body['sort_dir']);
        }

        // V4 has its own filter/with fields; do not apply the V3 field lists or defaults.
        $body['filter'] = $body['filter'] ?: new \stdClass();
        if (isset($body['with']) && [] === $body['with']) {
            $body['with'] = new \stdClass();
        }

        // Pagination fields live at the top level, outside the legacy result wrapper.
        return $this->request('POST', "{$this->path}/list", $body, true, false);
    }

    /**
     * @see https://docs.ozon.ru/api/seller/#operation/PostingAPI_ShipFbsPostingV4
     *
     * @return array list of postings IDs
     */
    public function ship(array $packages, string $postingNumber, array $options = []): array
    {
        foreach ($packages as &$package) {
            $package = ArrayHelper::pick($package, ['products']);
        }

        $body = [
            'packages'       => $packages,
            'posting_number' => $postingNumber,
            'with'           => WithResolver::resolve($options, 3, PostingScheme::FBS, __FUNCTION__),
        ];

        return $this->request('POST', "$this->path/ship", $body);
    }

    /**
     * @see https://docs.ozon.ru/api/seller/#operation/PostingAPI_GetProductExemplarStatus
     */
    public function getExemplarsStatus($postingNumber): array
    {
        $body = [
            'posting_number' => $postingNumber,
        ];

        return $this->request('POST', '/v4/fbs/posting/product/exemplar/status', $body);
    }
}
