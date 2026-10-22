# Ozon-seller API client
[![tests](https://github.com/gam6itko/ozon-seller/actions/workflows/tests.yaml/badge.svg)](https://github.com/gam6itko/ozon-seller/actions/workflows/tests.yaml)
[![Coverage Status](https://coveralls.io/repos/github/gam6itko/ozon-seller/badge.svg?branch=master)](https://coveralls.io/github/gam6itko/ozon-seller?branch=master)

[![Latest Stable Version](https://poser.pugx.org/gam6itko/ozon-seller/v)](//packagist.org/packages/gam6itko/ozon-seller) 
[![Total Downloads](https://poser.pugx.org/gam6itko/ozon-seller/downloads)](//packagist.org/packages/gam6itko/ozon-seller) 
[![Latest Unstable Version](https://poser.pugx.org/gam6itko/ozon-seller/v/unstable)](//packagist.org/packages/gam6itko/ozon-seller) 
[![License](https://poser.pugx.org/gam6itko/ozon-seller/license)](//packagist.org/packages/gam6itko/ozon-seller)

## Документация Ozon Api 

<https://docs.ozon.ru/api/seller> 


## Установка

```shell
composer require vasyag07/ozon-seller
```

Для взаимодействия библиотеки с Ozon-Api нужно дополнительно установить реализации [PSR-18: HTTP Client](https://packagist.org/providers/psr/http-client-implementation) и [PSR-17: HTTP Factories](https://packagist.org/providers/psr/http-factory-implementation).

### Использование с Symfony
https://symfony.com/doc/current/components/http_client.html#psr-18-and-psr-17

```shell
composer require symfony/http-client
composer require nyholm/psr7
```

```php
use Gam6itko\OzonSeller\Service\V1\ProductService;
use Symfony\Component\HttpClient\Psr18Client;

$config = [$_SERVER['CLIENT_ID'], $_SERVER['API_KEY'], $_SERVER['API_URL']];
$client = new Psr18Client();
$svc = new ProductService($config, $client);
//do stuff
```

### Использование без Symfony

```shell
composer require php-http/guzzle6-adapter
```

```php
use Gam6itko\OzonSeller\Service\V1\CategoriesService;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\StreamFactory;

$config = [
    'clientId' => '<ozon seller client-id>',
    'apiKey' => '<ozon seller api-key>',
    'host' => 'http://cb-api.ozonru.me/'
];
$client = new GuzzleAdapter(new GuzzleClient());
$requestFactory = new RequestFactory();
$streamFactory = new StreamFactory();

$svc = new CategoriesService($config, $client, $requestFactory, $streamFactory);
//do stuff
```

## Реализованные методы

Чтобы узнать какой класс и метод реализуют запрос на нужный URL воспользуйтесь скриптом `bin/is_realized.php`

```shell script
php bin/is_realized.php | grep /v2/posting/fbs/get
```
output
```shell script
/v2/posting/fbs/get: Gam6itko\OzonSeller\Service\V2\Posting\FbsService::get
/v2/posting/fbs/get-by-barcode: NotRealized
```

Автор не всегда успевает добавлять реализации новых методов.
Если нужного вам метода нет в библиотеке, то не стесняйтесь открыть issue или PR.

## Примеры использования
Больше примеров смотрите в папке `tests/Service/`

### Categories

```php
use Gam6itko\OzonSeller\Service\V1\CategoriesService;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

$config = [
    'clientId' => '<ozon seller client-id>',
    'apiKey' => '<ozon seller api-key>',
    'host' => 'http://cb-api.ozonru.me/' //sandbox
];
$adapter = new GuzzleAdapter(new GuzzleClient());
$svc = new CategoriesService($config, $adapter);

//Server Response example: https://cb-api.ozonru.me/apiref/en/#t-title_categories
$categoryTree = $svc->tree();

//Server Response example: https://cb-api.ozonru.me/apiref/en/#t-title_get_categories_attributes
$attributes = $svc->attributes(17038826);
```

### Posting Crossborder

#### get info 

`/v2/posting/crossborder/get`

```php
use Gam6itko\OzonSeller\Service\V2\Posting\CrossborderService;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

$config = [
    'clientId' => '<ozon seller client-id>',
    'apiKey' => '<ozon seller api-key>',
    'host' => 'http://cb-api.ozonru.me/'
];
$adapter = new GuzzleAdapter(new GuzzleClient());
$svc = new CrossborderService($config, $adapter);

$postingNumber = '39268230-0002-3';
$orderArr = $svc->get($postingNumber);
echo json_encode($orderArr);
```

```json
{
  "result": [
    {
      "address": {
        "address_tail": "г. Москва, ул. Центральная, 1",
        "addressee": "Петров Иван Владимирович",
        "city": "Москва",
        "comment": "",
        "country": "Россия",
        "district": "",
        "phone": "+7 495 123-45-67",
        "region": "Москва",
        "zip_code": "101000"
      },
      "auto_cancel_date": "2019-11-18T11:30:11.571Z",
      "cancel_reason_id": 76,
      "created_at": "2019-11-18T11:30:11.571Z",
      "customer_email": "petrov@email.com",
      "customer_id": 60006,
      "in_process_at": "2019-11-18T11:30:11.571Z",
      "order_id": 77712345,
      "order_nr": "1111444",
      "posting_number": "39268230-0002-3",
      "products": [
        {
          "name": "Фитнес-браслет",
          "offer_id": "DEP-1234",
          "price": "1900.00",
          "quantity": 1,
          "sku": 100056
        }
      ],
      "shipping_provider_id": 0,
      "status": "awaiting_approve",
      "tracking_number": ""
    }
  ]
}
```

### Список отправлений FBS V4

Для `POST /v4/posting/fbs/list` используйте `Service\V4\Posting\FbsService::list(array $requestData): array`.
Метод получает одну страницу и возвращает весь ответ с полями `postings`, `cursor` и `has_next`.

```php
use Gam6itko\OzonSeller\Service\V4\Posting\FbsService;

// $config и PSR-18 $client создаются как в примерах выше.
$svc = new FbsService($config, $client);
$request = [
    'filter' => [
        'since' => '2026-10-20T00:00:00Z',
        'to' => '2026-10-21T00:00:00Z',
        'statuses' => ['awaiting_packaging', 'awaiting_deliver'],
        // 'warehouse_ids' => [123456789],
    ],
    'limit' => 100,
    'cursor' => '',
    'sort_dir' => 'ASC',
    'with' => ['analytics_data' => true, 'barcodes' => true],
];
$page = $svc->list($request);
$postings = $page['postings'];

// Следующая страница — отдельный вызов с тем же фильтром.
if ($page['has_next']) {
    if ('' === $page['cursor'] || $request['cursor'] === $page['cursor']) {
        throw new \RuntimeException('Ozon вернул пустой или повторяющийся курсор');
    }
    $request['cursor'] = $page['cursor'];
    $nextPage = $svc->list($request);
}
```

При переходе с V3 замените `dir` на `sort_dir`, а `offset` — на `cursor`.
Старые параметры `dir`/`offset` не преобразуются и не отправляются. Значения `sort_dir`
`asc`/`desc` (в том числе из `SortDirection`) преобразуются в `ASC`/`DESC`.
Поля `filter` и целочисленный `limit` от 1 до 100 обязательны; при неверных значениях
выбрасывается `LogicException` до HTTP-запроса. Период `since`–`to` по документации — не больше года.
Новые фильтры включают `statuses`, `warehouse_ids`, `delivery_method_ids`, `provider_ids`;
вложенные поля `filter` и `with` передаются без фильтрации по правилам V3.
Также поддерживается верхнеуровневый `translit`. Обход страниц выполняет приложение;
следует ограничивать число запросов и проверять продвижение курсора.
Контракт `Service\V3\Posting\FbsService::list()` сохранён, автоматического перехода на V4 нет.

### Список складов V2

Вместо `Service\V1\WarehouseService::list()` используйте `Service\V2\WarehouseService::list(array $requestData = []): array`.
Новый метод возвращает страницу `['warehouses' => [...], 'cursor' => '...', 'has_next' => true]`,
а не прежний массив `result`. По умолчанию отправляются `limit = 10` и `cursor = ''`.
Можно передать `warehouse_ids` (не более 200 идентификаторов). `limit` — целое число от 1 до 200.
Данные сверх ограничения не обрезаются: SDK выбрасывает `LogicException`.

```php
use Gam6itko\OzonSeller\Service\V2\WarehouseService;

$svc = new WarehouseService($config, $client);
$request = ['limit' => 100, 'cursor' => ''];
$warehouses = [];

// Приложение управляет обходом страниц; ограничение защищает от бесконечного цикла.
for ($pageNumber = 0; $pageNumber < 1000; ++$pageNumber) {
    $page = $svc->list($request);
    $warehouses = array_merge($warehouses, $page['warehouses']);
    if (!$page['has_next']) {
        break;
    }
    if ('' === $page['cursor'] || $request['cursor'] === $page['cursor']) {
        throw new \RuntimeException('Ozon вернул пустой или повторяющийся курсор');
    }
    $request['cursor'] = $page['cursor'];
}
if ($page['has_next']) {
    throw new \RuntimeException('Превышено ограничение количества страниц складов');
}
```

Старый метод V1 сохранён с пометкой `@deprecated` и не перенаправляется на V2.

### Асинхронные этикетки FBS

Синхронный `Service\V2\Posting\FbsService::packageLabel()` устаревает: по сохранённой
документации его отключение назначено на 2 ноября 2026 года. Вместо одного запроса PDF
нужно создать задания, затем получить состояние каждого задания и отдельно скачать готовый файл.

```php
use Gam6itko\OzonSeller\Service\V3\Posting\FbsService as FbsServiceV3;
use Gam6itko\OzonSeller\Service\V2\Posting\FbsService as FbsServiceV2;

$creator = new FbsServiceV3($config, $client);
$reader = new FbsServiceV2($config, $client);

$response = $creator->packageLabelCreate(['example-posting-1', 'example-posting-2']);
foreach ($response['tasks'] as $task) {
    // Сохраните task_id и task_type всех заданий, включая big_label и small_label.
    $state = $reader->packageLabelGet($task['task_id']);
    // Это JSON: status — объект, error — объект, file_url — ссылка на файл.
    // Ошибки отдельных отправлений доступны в status.unprinted_postings.
    if (!empty($state['file_url'])) {
        $fileUrl = $state['file_url'];
        // Скачайте файл отдельным HTTP-запросом без Api-Key и Client-Id.
    }
    // Если файл ещё не готов, повторите получение состояния позже с тем же task_id.
    // Обработайте error и задайте предел времени/числа попыток в приложении.
}
```

`packageLabelCreate(array $postingNumbers): array` отправляет `posting_numbers` (новое имя во
множественном числе), принимает от 1 до 1000 непустых строк и возвращает весь ответ с `tasks`.
`packageLabelGet(int $taskId): array` возвращает весь ответ с `status`, `error`, `file_url`.
Оба метода выполняют по одному запросу, без ожидания готовности, выбора первого задания или скачивания PDF.
Ошибки задания при HTTP 200 сохраняются в ответе; HTTP-ошибки обрабатываются общим транспортом SDK.
Документация рекомендует создавать задания через 45–60 секунд после сборки заказа; SDK не добавляет задержку.
Старый `packageLabel()` сохранён с прежним возвращаемым типом `string` и пометкой `@deprecated`.

### Products

#### import

`/v1/product/import`

```php
use Gam6itko\OzonSeller\Service\V1\ProductService;
use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;

$config = [
    'clientId' => '<ozon seller client-id>',
    'apiKey' => '<ozon seller api-key>',
    // use prod host by default
];
$adapter = new GuzzleAdapter(new GuzzleClient());
$svcProduct = new ProductService($config, $adapter);
$product = [
    'barcode'        => '8801643566784',
    'description'    => 'Red Samsung Galaxy S9 with 512GB',
    'category_id'    => 17030819,
    'name'           => 'Samsung Galaxy S9',
    'offer_id'       => 'REDSGS9-512',
    'price'          => '79990',
    'old_price'      => '89990',
    'premium_price'  => '75555',
    'vat'            => '0',
    'vendor'         => 'Samsung',
    'vendor_code'    => 'SM-G960UZPAXAA',
    'height'         => 77,
    'depth'          => 11,
    'width'          => 120,
    'dimension_unit' => 'mm',
    'weight'         => 120,
    'weight_unit'    => 'g',
    'images'         => [
        [
            'file_name' => 'https://ozon-st.cdn.ngenix.net/multimedia/c1200/1022555115.jpg',
            'default'   => true,
        ],
        [
            'file_name' => 'https://ozon-st.cdn.ngenix.net/multimedia/c1200/1022555110.jpg',
            'default'   => false,
        ],
        [
            'file_name' => 'https://ozon-st.cdn.ngenix.net/multimedia/c1200/1022555111.jpg',
            'default'   => false,
        ],
    ],
    'attributes'     => [
        [
            'id'    => 8229,
            'value' => '4747',
        ],
        [
            'id'    => 9048,
            'value' => 'Samsung Galaxy S9',
        ],
        [
            'id'    => 4742,
            'value' => '512 ГБ',
        ],

        [
            'id'         => 4413,
            'collection' => ['1', '2', '13'],
        ],
        [
            'id'                 => 4018,
            'complex_collection' => [
                [
                    'collection' => [
                        [
                            'id'    => 4068,
                            'value' => 'Additional video',
                        ],
                        [
                            'id'    => 4074,
                            'value' => '5_-NKRVn7IQ',
                        ],
                    ],
                ],
                [
                    'collection' => [
                        [
                            'id'    => 4068,
                            'value' => 'Another one video',
                        ],
                        [
                            'id'    => 4074,
                            'value' => '5_-NKRVn7IQ',
                        ],
                    ],
                ],
            ],
        ],
    ],
];

$svcProduct->import($product);
// or
$svcProduct->import([$product, $product1, $product2, ...]);
// or
$res = $svcProduct->import(['items' => [$product, $product1, $product2, ...] ]);
echo $res['task_id']; // save it for checking by `importInfo`
```
