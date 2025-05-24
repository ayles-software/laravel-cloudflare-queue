# Laravel Cloudflare Queue

A Laravel queue driver for Cloudflare Queues.

## Configuration

Add the following to your `config/queue.php` file:

```php
'cloudflare' => [
    'driver' => 'cloudflare',
    'account_id'=> env('CLOUDFLARE_ACCOUNT_ID'),
    'queue_id'  => env('CLOUDFLARE_QUEUE_ID'),
    'api_token' => env('CLOUDFLARE_API_TOKEN'),
    'batch_size' => env('CLOUDFLARE_QUEUE_BATCH_SIZE', 10), // Optional: Number of messages to retrieve per request (default: 10)
],
```

## Features

- **Batch Processing**: This queue driver can process multiple jobs at once, improving throughput and reducing API calls.
- **Configurable Batch Size**: You can configure how many messages are retrieved per request using the `batch_size` option.
- **Custom Worker**: Includes a custom queue worker that can handle multiple jobs returned from the queue.

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

