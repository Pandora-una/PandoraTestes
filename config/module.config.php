<?php
namespace PandoraTestes;

return array(
    'service_manager' => array(
        'factories' => array(
            'PandoraTestes\Fixture\FixtureBuilder' => 'PandoraTestes\Fixture\Factory\FixtureBuilderFactory',
        ),
        'invokables' => array(
            'PandoraTestes\Matcher\Factory\PandoraFactory' => 'PandoraTestes\Matcher\Factory\PandoraFactory',
        ),
    ),
);
