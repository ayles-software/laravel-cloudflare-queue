# Laravel Cloudflare Queue

A Laravel queue driver for Cloudflare Queues. This is still a work in progress.

## Configuration

Add the following to your `config/queue.php` file:

```php
'cloudflare' => [
    'driver' => 'cloudflare',
    'account_id'=> env('CLOUDFLARE_ACCOUNT_ID'),
    'queue_id'  => env('CLOUDFLARE_QUEUE_ID'),
    'api_token' => env('CLOUDFLARE_API_TOKEN'),
],
```

If are using CF workers and pushing raw jobs, you can set a raw handler that will be used to process the raw job:
```php
'cloudflare' => [
    'driver' => 'cloudflare',
    'raw_handler' => CloudflareRawJobHandler::class,
    'account_id'=> env('CLOUDFLARE_ACCOUNT_ID'),
    'queue_id'  => env('CLOUDFLARE_QUEUE_ID'),
    'api_token' => env('CLOUDFLARE_API_TOKEN'),
],
```

Example of a raw CF job:
```php
class WebhookEmailEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly array $data)
    {
        //
    }
    
    public function handle()
    {
        // do something
    }
}
```

Example of pushing a raw CF job to a Queue using JS:
```js
export default {
    async fetch(request, env, context) {
        await env.MY_QUEUE.send({
            food: "lemons", 
        });

        return new Response('Ok');
    }
}
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

