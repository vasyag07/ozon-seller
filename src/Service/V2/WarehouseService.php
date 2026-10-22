<?php

declare(strict_types=1);

namespace Gam6itko\OzonSeller\Service\V2;

use Gam6itko\OzonSeller\Service\AbstractService;
use Gam6itko\OzonSeller\Utils\ArrayHelper;

class WarehouseService extends AbstractService
{
    private $path = '/v2/warehouse';

    /**
     * @see https://docs.ozon.ru/api/seller/#operation/WarehouseListV2
     *
     * @return array One page with warehouses, cursor and has_next
     */
    public function list(array $requestData = []): array
    {
        $default = [
            'limit'  => 10,
            'cursor' => '',
        ];

        $requestData = array_merge(
            $default,
            ArrayHelper::pick($requestData, array_merge(array_keys($default), ['warehouse_ids']))
        );

        if (!isset($requestData['limit']) || !is_int($requestData['limit']) || $requestData['limit'] < 1 || $requestData['limit'] > 200) {
            throw new \LogicException('Parameter `limit` must be an integer between 1 and 200');
        }
        if (array_key_exists('warehouse_ids', $requestData)) {
            if (!is_array($requestData['warehouse_ids']) || count($requestData['warehouse_ids']) > 200) {
                throw new \LogicException('Parameter `warehouse_ids` must be an array of at most 200 IDs');
            }
            $requestData['warehouse_ids'] = array_values($requestData['warehouse_ids']);
        }

        // Keep pagination alongside warehouses instead of extracting the legacy result field.
        return $this->request('POST', "{$this->path}/list", $requestData, true, false);
    }
}
