<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'assetManager' => [
            'bundles' => [
                'yii\bootstrap\BootstrapAsset' => [
                    'css' => ['css/bootstrap.min.css'],
                    'jsOptions' => ['position' => 1],
                    'js' => ['js/bootstrap.min.js'],
                ],
                'yii\bootstrap\BootstrapPluginAsset' => [
                    'js' => ['js/bootstrap.min.js'],
                    'jsOptions' => ['position' => 1]
                ],
                'yii\web\JqueryAsset' => [
                    'js' => ['jquery.min.js'],
                    'jsOptions' => ['position' => 1]
                ]
            ]
        ],
    ],
];
