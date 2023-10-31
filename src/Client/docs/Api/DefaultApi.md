# OpenAPI\Client\DefaultApi

All URIs are relative to https://plausible.io/api/plugins, except if the operation defines another base path.

| Method | HTTP request | Description |
| ------------- | ------------- | ------------- |
| [**plausibleWebPluginsAPIControllersGoalsCreate()**](DefaultApi.md#plausibleWebPluginsAPIControllersGoalsCreate) | **PUT** /v1/goals | Get or create Goal |
| [**plausibleWebPluginsAPIControllersGoalsDelete()**](DefaultApi.md#plausibleWebPluginsAPIControllersGoalsDelete) | **DELETE** /v1/goals/{id} | Delete Goal by ID |
| [**plausibleWebPluginsAPIControllersGoalsGet()**](DefaultApi.md#plausibleWebPluginsAPIControllersGoalsGet) | **GET** /v1/goals/{id} | Retrieve Goal by ID |
| [**plausibleWebPluginsAPIControllersGoalsIndex()**](DefaultApi.md#plausibleWebPluginsAPIControllersGoalsIndex) | **GET** /v1/goals | Retrieve Goals |
| [**plausibleWebPluginsAPIControllersSharedLinksCreate()**](DefaultApi.md#plausibleWebPluginsAPIControllersSharedLinksCreate) | **PUT** /v1/shared_links | Get or create Shared Link |
| [**plausibleWebPluginsAPIControllersSharedLinksGet()**](DefaultApi.md#plausibleWebPluginsAPIControllersSharedLinksGet) | **GET** /v1/shared_links/{id} | Retrieve Shared Link by ID |
| [**plausibleWebPluginsAPIControllersSharedLinksIndex()**](DefaultApi.md#plausibleWebPluginsAPIControllersSharedLinksIndex) | **GET** /v1/shared_links | Retrieve Shared Links |


## `plausibleWebPluginsAPIControllersGoalsCreate()`

```php
plausibleWebPluginsAPIControllersGoalsCreate($goal_create_request): \OpenAPI\Client\Model\PlausibleWebPluginsAPIControllersGoalsCreate201Response
```

Get or create Goal

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basic_auth
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new OpenAPI\Client\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$goal_create_request = new \OpenAPI\Client\Model\GoalCreateRequest(); // \OpenAPI\Client\Model\GoalCreateRequest | Goal params

try {
    $result = $apiInstance->plausibleWebPluginsAPIControllersGoalsCreate($goal_create_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->plausibleWebPluginsAPIControllersGoalsCreate: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **goal_create_request** | [**\OpenAPI\Client\Model\GoalCreateRequest**](../Model/GoalCreateRequest.md)| Goal params | [optional] |

### Return type

[**\OpenAPI\Client\Model\PlausibleWebPluginsAPIControllersGoalsCreate201Response**](../Model/PlausibleWebPluginsAPIControllersGoalsCreate201Response.md)

### Authorization

[basic_auth](../../README.md#basic_auth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `plausibleWebPluginsAPIControllersGoalsDelete()`

```php
plausibleWebPluginsAPIControllersGoalsDelete($id)
```

Delete Goal by ID

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basic_auth
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new OpenAPI\Client\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 123; // int | Goal ID

try {
    $apiInstance->plausibleWebPluginsAPIControllersGoalsDelete($id);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->plausibleWebPluginsAPIControllersGoalsDelete: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Goal ID | |

### Return type

void (empty response body)

### Authorization

[basic_auth](../../README.md#basic_auth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `plausibleWebPluginsAPIControllersGoalsGet()`

```php
plausibleWebPluginsAPIControllersGoalsGet($id): \OpenAPI\Client\Model\Goal
```

Retrieve Goal by ID

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basic_auth
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new OpenAPI\Client\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 123; // int | Goal ID

try {
    $result = $apiInstance->plausibleWebPluginsAPIControllersGoalsGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->plausibleWebPluginsAPIControllersGoalsGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Goal ID | |

### Return type

[**\OpenAPI\Client\Model\Goal**](../Model/Goal.md)

### Authorization

[basic_auth](../../README.md#basic_auth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `plausibleWebPluginsAPIControllersGoalsIndex()`

```php
plausibleWebPluginsAPIControllersGoalsIndex($limit, $after, $before): \OpenAPI\Client\Model\GoalListResponse
```

Retrieve Goals

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basic_auth
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new OpenAPI\Client\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$limit = 10; // int | Maximum entries per page
$after = 'after_example'; // string | Cursor value to seek after - generated internally
$before = 'before_example'; // string | Cursor value to seek before - generated internally

try {
    $result = $apiInstance->plausibleWebPluginsAPIControllersGoalsIndex($limit, $after, $before);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->plausibleWebPluginsAPIControllersGoalsIndex: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **limit** | **int**| Maximum entries per page | [optional] |
| **after** | **string**| Cursor value to seek after - generated internally | [optional] |
| **before** | **string**| Cursor value to seek before - generated internally | [optional] |

### Return type

[**\OpenAPI\Client\Model\GoalListResponse**](../Model/GoalListResponse.md)

### Authorization

[basic_auth](../../README.md#basic_auth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `plausibleWebPluginsAPIControllersSharedLinksCreate()`

```php
plausibleWebPluginsAPIControllersSharedLinksCreate($shared_link_create_request): \OpenAPI\Client\Model\SharedLink
```

Get or create Shared Link

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basic_auth
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new OpenAPI\Client\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$shared_link_create_request = new \OpenAPI\Client\Model\SharedLinkCreateRequest(); // \OpenAPI\Client\Model\SharedLinkCreateRequest | Shared Link params

try {
    $result = $apiInstance->plausibleWebPluginsAPIControllersSharedLinksCreate($shared_link_create_request);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->plausibleWebPluginsAPIControllersSharedLinksCreate: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **shared_link_create_request** | [**\OpenAPI\Client\Model\SharedLinkCreateRequest**](../Model/SharedLinkCreateRequest.md)| Shared Link params | [optional] |

### Return type

[**\OpenAPI\Client\Model\SharedLink**](../Model/SharedLink.md)

### Authorization

[basic_auth](../../README.md#basic_auth)

### HTTP request headers

- **Content-Type**: `application/json`
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `plausibleWebPluginsAPIControllersSharedLinksGet()`

```php
plausibleWebPluginsAPIControllersSharedLinksGet($id): \OpenAPI\Client\Model\SharedLink
```

Retrieve Shared Link by ID

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basic_auth
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new OpenAPI\Client\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$id = 123; // int | Shared Link ID

try {
    $result = $apiInstance->plausibleWebPluginsAPIControllersSharedLinksGet($id);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->plausibleWebPluginsAPIControllersSharedLinksGet: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **id** | **int**| Shared Link ID | |

### Return type

[**\OpenAPI\Client\Model\SharedLink**](../Model/SharedLink.md)

### Authorization

[basic_auth](../../README.md#basic_auth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)

## `plausibleWebPluginsAPIControllersSharedLinksIndex()`

```php
plausibleWebPluginsAPIControllersSharedLinksIndex($limit, $after, $before): \OpenAPI\Client\Model\SharedLinkListResponse
```

Retrieve Shared Links

### Example

```php
<?php
require_once(__DIR__ . '/vendor/autoload.php');


// Configure HTTP basic authorization: basic_auth
$config = OpenAPI\Client\Configuration::getDefaultConfiguration()
              ->setUsername('YOUR_USERNAME')
              ->setPassword('YOUR_PASSWORD');


$apiInstance = new OpenAPI\Client\Api\DefaultApi(
    // If you want use custom http client, pass your client which implements `GuzzleHttp\ClientInterface`.
    // This is optional, `GuzzleHttp\Client` will be used as default.
    new GuzzleHttp\Client(),
    $config
);
$limit = 10; // int | Maximum entries per page
$after = 'after_example'; // string | Cursor value to seek after - generated internally
$before = 'before_example'; // string | Cursor value to seek before - generated internally

try {
    $result = $apiInstance->plausibleWebPluginsAPIControllersSharedLinksIndex($limit, $after, $before);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->plausibleWebPluginsAPIControllersSharedLinksIndex: ', $e->getMessage(), PHP_EOL;
}
```

### Parameters

| Name | Type | Description  | Notes |
| ------------- | ------------- | ------------- | ------------- |
| **limit** | **int**| Maximum entries per page | [optional] |
| **after** | **string**| Cursor value to seek after - generated internally | [optional] |
| **before** | **string**| Cursor value to seek before - generated internally | [optional] |

### Return type

[**\OpenAPI\Client\Model\SharedLinkListResponse**](../Model/SharedLinkListResponse.md)

### Authorization

[basic_auth](../../README.md#basic_auth)

### HTTP request headers

- **Content-Type**: Not defined
- **Accept**: `application/json`

[[Back to top]](#) [[Back to API list]](../../README.md#endpoints)
[[Back to Model list]](../../README.md#models)
[[Back to README]](../../README.md)
