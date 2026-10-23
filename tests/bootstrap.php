<?php
/**
 * A mesma suíte roda nas duas pilhas:
 * - 2.x (ZF2, PHP 7.2): PTESTES_VENDOR aponta para o vendor de um sistema (o area-restrita), com as
 *   versões de produção (Behat 3.7, web-api-extension dev-master, Guzzle 6, php-matcher 2.0.1,
 *   data-fixtures 1.3, ORM 2.5); o código deste repositório entra na frente do instalado lá;
 * - 3.x (Laminas, PHP 8.4): o vendor deste repositório.
 */
date_default_timezone_set('America/Sao_Paulo');

$vendor = getenv('PTESTES_VENDOR') ?: __DIR__ . '/../vendor';
$autoload = require $vendor . '/autoload.php';
$autoload->addPsr4('PandoraTestesTeste\\', __DIR__ . '/src');

if (getenv('PTESTES_VENDOR')) {
    $autoload->add('PandoraTestes', __DIR__ . '/../src', true);
    require_once __DIR__ . '/../Module.php';
    if (class_exists('PHPUnit_Framework_Error_Deprecated')) {
        \PHPUnit_Framework_Error_Deprecated::$enabled = false;
    }
    if (class_exists('Doctrine\Common\Annotations\AnnotationRegistry')) {
        \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader('class_exists');
    }
}

if (! is_dir($vendor . '/laminas/laminas-servicemanager')) {
    spl_autoload_register(function ($classe) {
        if (strpos($classe, 'Laminas\\') === 0) {
            $original = 'Zend\\' . substr($classe, strlen('Laminas\\'));
            if (class_exists($original) || interface_exists($original)) {
                class_alias($original, $classe);
            }
        }
    });
}
