# Laravel Cloudflare Queue

A Laravel queue driver for Cloudflare Queues.

## Configuration

Add the following to your `config/queue.php` file:

```php
'cloudflare' => [
    'driver' => 'cloudflare',
    'raw' => false, // handles raw jobs that have been pushed not via laravel, e.g. CF workers
    'handler' => CloudflareRawJobHandler::class,
    'account_id'=> env('CLOUDFLARE_ACCOUNT_ID'),
    'queue_id'  => env('CLOUDFLARE_QUEUE_ID'),
    'api_token' => env('CLOUDFLARE_API_TOKEN'),
],
```

## Testing

This package includes a comprehensive test suite using [Pest PHP](https://pestphp.com/). To run the tests:

1. Install dependencies:
   ```bash
   composer install
   ```

2. Run the tests:
   ```bash
   ./vendor/bin/pest
   ```

