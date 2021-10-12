<?php

declare(strict_types=1);

namespace Zeroseven\Semantilizer;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Zeroseven\Semantilizer\Widgets\CheckHeadings;

return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
    $services = $configurator->services();

    if ($containerBuilder->hasDefinition('dashboard')) {
        $services->defaults()
            ->autowire()
            ->autoconfigure()
            ->private();
        $services->load('Zeroseven\\Semantilizer\\', dirname(__DIR__) . '/Classes/*');

        $services->set('widgets.dashboard.widget.checkHeadingsWidget')
            ->class(CheckHeadings::class)
            ->arg('$view', new Reference('dashboard.views.widget'))
            ->tag('dashboard.widget', [
                'identifier' => 'checkHeadings',
                'groupNames' => 'systemInfo',
                'title' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang.xlf:widget.title',
                'description' => 'LLL:EXT:z7_semantilizer/Resources/Private/Language/locallang.xlf:widget.description',
                'iconIdentifier' => 'content-widget-list',
                'height' => 'medium',
                'width' => 'small'
            ])
        ;
    }
};
