<?php

namespace Liftric;

use Aws\CloudFront\CloudFrontClient;
use Kirby\Cms\Page;
use Kirby\Toolkit\Collection;
use Kirby\Toolkit\Str;

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

        $distributionID = option('liftric.cloudfrontinvalidations.distributionID');
        if ($distributionID == '') {
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
            return parse_url($urlItem)['path'] . '*';
        }, $pagesOrURLs);

        $cloudFrontConfig = [
            'version' => 'latest',
            'region' => 'eu-central-1', // basically irrelevant for AWS CloudFront invalidations
        ];

        $awsAccessKeyID = option('liftric.cloudfrontinvalidations.awsAccessKeyID');
        $awsSecretAccessKey = option('liftric.cloudfrontinvalidations.awsSecretAccessKey');
        if ($awsAccessKeyID != '' && $awsSecretAccessKey != '') {
            $cloudFrontConfig['credentials'] = [
                'key' => $awsAccessKeyID,
                'secret' => $awsSecretAccessKey
            ];
        }

        $cloudFront = new CloudFrontClient($cloudFrontConfig);

        foreach (array_chunk($pagesOrURLs, static::API_URL_BATCH_SIZE) as $urlBatch) {
            $items = array_values($urlBatch);
            $invalidation = [
                'DistributionId' => $distributionID,
                'InvalidationBatch' => [
                    'CallerReference' => Str::random(16),
                    'Paths' => [
                        'Items' => $items,
                        'Quantity' => count($items)
                    ]
                ]
            ];
            $cloudFront->createInvalidation($invalidation);
        }
    }
}
