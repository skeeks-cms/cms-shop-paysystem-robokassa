<?php
return [
    'components' => [
        'shop' => [
            'paysystemHandlers' => [
                'robokassa' => [
                    'class' => \skeeks\cms\shop\robokassa\RobokassaPaysystemHandler::class
                ]
            ],
        ],

        'log' => [
            'targets' => [
                [
                    'class'      => 'yii\log\FileTarget',
                    'levels'     => ['info', 'warning', 'error'],
                    'logVars'    => [],
                    'categories' => [\skeeks\cms\shop\robokassa\RobokassaPaysystemHandler::class, \skeeks\cms\shop\robokassa\controllers\RobokassaController::class],
                    'logFile'    => '@runtime/logs/robokassa-info.log',
                ],

                [
                    'class'      => 'yii\log\FileTarget',
                    'levels'     => ['error'],
                    'logVars'    => [],
                    'categories' => [\skeeks\cms\shop\robokassa\RobokassaPaysystemHandler::class, \skeeks\cms\shop\robokassa\controllers\RobokassaController::class],
                    'logFile'    => '@runtime/logs/robokassa-errors.log',
                ],
            ],
        ],
    ],
];