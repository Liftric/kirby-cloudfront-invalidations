# kirby-cloudfront-invalidations

Kirby plugin for automatic slug invalidation in AWS CloudFront

## Installation

### Composer

```
composer require liftric/kirby-cloudfront-invalidations
```

## Setup

If you rely on the default credential provider chain, you just need to set the `distributionId`:

```php
'liftric.cloudfrontinvalidations.distributionID' => 'YOUR_CF_DISTRIBUTION_ID'
```

## Options

### `liftric.cloudfrontinvalidations.distributionID`
This must be set to your AWS CloudFront distribution ID (available in the AWS CloudFront Distributions dashboard).

### `liftric.cloudfrontinvalidations.awsAccessKeyID` (optional)
This must be set to the AWS access key ID of your AWS account.

### `liftric.cloudfrontinvalidations.awsSecretAccessKey` (optional)
This must be set to the AWS secret access key of your AWS account.

### `liftric.cloudfrontinvalidations.dependantUrlsForPage` (optional)
This must be a function that returns what URL(s) should be cleared after a page modification.

### `liftric.cloudfrontinvalidations.dependantUrlsForFile` (optional)
This must be a function that returns what URL(s) should be cleared after a file modification.

### `liftric.cloudfrontinvalidations.dependantUrlsForSite` (optional)
This must be a function that returns what URL(s) should be cleared after a site modification.

## Example config

An some more advanced config, we use a larger `dependantUrlsForPage` function to collect from the old page as well the new page all related pages, considers the `search` page and prepares the urls for both languages, `de` and `en`. Of course this example could be much cleaner and maybe a bit more generic in some circumstances but should only demonstrate a different use case.

In addition to that, we also exclude the `/api` route from caching by sending a custom header which disallows caching. Having done both, we are able to use AWS CloudFront for a larger customer without any problems.

```php
<?php
return [
    'liftric.cloudfrontinvalidations.dependantUrlsForPage' => function ($hook, $page, $oldPage = null) {
        $pages = new Pages();
        foreach ([$page, $oldPage] as $p) {
            if (!$p) continue;
            $pages->add($p);
            $pages->add($p->parents());
            foreach ([$p->related_products(), $p->related_pages()] as $q) {
                if (!$q) continue;
                if ($q->isNotEmpty()) {
                    $pages->add($q->toPages(','));
                }
            }
        }
        $pages->add(new Page(['slug' => 'search']));
        $pages = $pages->toArray(function ($page) {
            return array($page->urlForLanguage('de'), $page->urlForLanguage('en'));
        });
        $pages = array_values($pages);
        $pages = array_merge(...$pages);
        return $pages;
    },
    'hooks' => [
        'route:after' => function ($result, $path) {
            if (Str::startsWith($path, 'api/')) {
                header('Cache-Control: private, no-cache, no-store');
            }
        }
    ]
];
```

## License

MIT

## Kudos

- [Neil Daniels](https://github.com/neildaniels) of [The Streamable](https://thestreamable.com) for creating the [kirby-clear-cloudflare-cache plugin](https://github.com/thestreamable/kirby-clear-cloudflare-cache).
