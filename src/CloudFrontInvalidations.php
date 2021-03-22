<?php

namespace Liftric;

use Aws\CloudFront\CloudFrontClient;
use Kirby\Cms\Page;
use Kirby\Http\Remote;
use Kirby\Toolkit\Collection;

class CloudFrontInvalidations
{
    protected const API_URL_BATCH_SIZE = 30;

    public static function handlePageHook($hook, $page, $oldPage = null)
    {
        $callback = option('liftric.cloudfrontinvalidations.dependantUrlsForPage');
        if ($callback && is_callable($callback)) {
            static::purgeURLs($callback($hook, $page, $oldPage));
        }
    }

    public static function handleFileHook($hook, $file, $oldFile = null)
    {
        $callback = option('liftric.cloudfrontinvalidations.dependantUrlsForFile');
        if ($callback && is_callable($callback)) {
            static::purgeURLs($callback($hook, $file, $oldFile));
        }
    }

    public static function handleSiteHook($hook, $site, $oldSite = null)
    {
        $callback = option('liftric.cloudfrontinvalidations.dependantUrlsForSite');
        if ($callback && is_callable($callback)) {
            static::purgeURLs($callback($hook, $site, $oldSite));
        }
    }

    public static function purgeURLs($pagesOrURLs)
    {
        if (!$pagesOrURLs) {
            return;
        }

        $cloudFrontDistributionId = option('liftric.cloudfrontinvalidations.cloudFrontDistributionId');
        $awsAccessKeyID = option('liftric.cloudfrontinvalidations.awsAccessKeyID');
        $awsSecretAccessKey = option('liftric.cloudfrontinvalidations.awsSecretAccessKey');
        if ('' == $cloudFrontDistributionId || '' == $awsAccessKeyID || '' == $awsSecretAccessKey) {
            return;
        }

        if ($pagesOrURLs instanceof Collection) {
            $pagesOrURLs = $pagesOrURLs->pluck('url');
        } elseif ($pagesOrURLs instanceof Page) {
            $pagesOrURLs = [$pagesOrURLs->url()];
        } elseif (!is_array($pagesOrURLs)) {
            $pagesOrURLs = [$pagesOrURLs];
        }

        $pagesOrURLs = array_map(function ($urlItem) {
            return $urlItem instanceof Page ? $urlItem->url() : (string)$urlItem;
        }, $pagesOrURLs);

        $pagesOrURLs = array_unique($pagesOrURLs);
        if (!count($pagesOrURLs)) {
            return;
        }

        $pagesOrURLs = array_map(function ($urlItem) {
            return parse_url($urlItem)['path'];
        }, $pagesOrURLs);

        $cloudFront = new CloudFrontClient([
            'version' => 'latest',
            'region' => 'eu-central-1',
            'credentials' => [
                'key' => $awsAccessKeyID,
                'secret' => $awsSecretAccessKey
            ]
        ]);

        $caller = '';

        foreach (array_chunk($pagesOrURLs, static::API_URL_BATCH_SIZE) as $urlBatch) {
            $items = array_values($urlBatch);
            $invalidation = [
                'DistributionId' => $cloudFrontDistributionId,
                'InvalidationBatch' => [
                    'CallerReference' => $caller,
                    'Paths' => [
                        'Items' => $items,
                        'Quantity' => count($items)
                    ]
                ]
            ];
            $cloudFront->createInvalidationAsync($invalidation);
        }
    }

}
