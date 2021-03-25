<?php
@include_once __DIR__ . '/vendor/autoload.php';
@include_once __DIR__ . '/src/CloudFrontInvalidations.php';

use Liftric\CloudFrontInvalidations;

Kirby::plugin('liftric/cloudfrontinvalidations', [
    'options' => [
        'distributionID' => null,
        'awsAccessKeyID' => null,
        'awsSecretAccessKey' => null,
        'dependantUrlsForPage' => function ($hook, $page, $oldPage = null) {
            return $oldPage ? [$page->url(), $oldPage->url()] : $page->url();
        },
        'dependantUrlsForFile' => function ($hook, $file, $oldFile = null) {
            $clearParentOnly = in_array($hook, [
                'file.changeSort:after',
                'file.create:after',
                'file.update:after',
            ]);

            $urls = $clearParentOnly ? [$file->parent()->url()] : [$file->url(), $file->parent()->url()];
            if ($oldFile && !$clearParentOnly) {
                $urls[] = $oldFile->url();
                // Shouldn't need to add $oldFile->parent()->url() because the parent should be the same as the "new" file's parent.
            }
            return $urls;
        },
        'dependantUrlsForSite' => function ($hook, $site, $oldSite = null) {
            return $site->url();
        },
    ],
    'api' => [
        'routes' => [
            [
                'pattern' => 'cloudfront/invalidate',
                'method' => 'get',
                'action' => function () {
                    return CloudFrontInvalidations::invalidate();
                }
            ]
        ]
    ],
    'hooks' => [
        // Page
        'page.changeNum:after' => function ($newPage) {
            CloudFrontInvalidations::handlePageHook('page.changeNum:after', $newPage);
        },
        'page.changeSlug:after' => function ($newPage, $oldPage) {
            CloudFrontInvalidations::handlePageHook('page.changeSlug:after', $newPage, $oldPage);
        },
        'page.changeStatus:after' => function ($newPage) {
            CloudFrontInvalidations::handlePageHook('page.changeStatus:after', $newPage);
        },
        'page.changeTemplate:after' => function ($newPage) {
            CloudFrontInvalidations::handlePageHook('page.changeTemplate:after', $newPage);
        },
        'page.changeTitle:after' => function ($newPage) {
            CloudFrontInvalidations::handlePageHook('page.changeTitle:after', $newPage);
        },
        'page.create:after' => function ($page) {
            CloudFrontInvalidations::handlePageHook('page.create:after', $page);
        },
        'page.delete:after' => function ($status, $page) {
            CloudFrontInvalidations::handlePageHook('page.delete:after', $page);
        },
        'page.update:after' => function ($newPage, $oldPage) {
            CloudFrontInvalidations::handlePageHook('page.changeSlug:after', $newPage, $oldPage);
        },

        // File
        'file.changeName:after' => function ($newFile, $oldFile) {
            CloudFrontInvalidations::handleFileHook('file.changeName:after', $newFile, $oldFile);
        },
        'file.changeSort:after' => function ($newFile, $oldFile) {
            CloudFrontInvalidations::handleFileHook('file.changeSort:after', $newFile, $oldFile);
        },
        'file.create:after' => function ($file) {
            CloudFrontInvalidations::handleFileHook('file.create:after', $file);
        },
        'file.delete:after' => function ($status, $file) {
            CloudFrontInvalidations::handleFileHook('file.delete:after', $file);
        },
        'file.replace:after' => function ($newFile, $oldFile) {
            CloudFrontInvalidations::handleFileHook('file.replace:after', $newFile, $oldFile);
        },
        'file.update:after' => function ($newFile, $oldFile) {
            CloudFrontInvalidations::handleFileHook('file.update:after', $newFile, $oldFile);
        },

        // Site
        'site.update:after' => function ($newSite, $oldSite) {
            CloudFrontInvalidations::handleSiteHook('site.update:after', $newSite);
        },
    ],
]);
