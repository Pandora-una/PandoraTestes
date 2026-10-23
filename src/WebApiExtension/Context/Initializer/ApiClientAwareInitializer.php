<?php

/*
 * This file is part of the Behat WebApiExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Porte para o pandora-testes 3 (Guzzle 7, PHPUnit 12, PHP 8.4), a partir do dev-master
 * ff62c552, com o namespace e as frases de passo originais: os sistemas não mudam o behat.yml nem
 * os contextos (D10 da migração AND-4055). O behat/web-api-extension nunca teve release.
 */

namespace Behat\WebApiExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\WebApiExtension\Context\ApiClientAwareContext;
use GuzzleHttp\ClientInterface;

/**
 * Guzzle-aware contexts initializer.
 *
 * Sets Guzzle client instance to the ApiClientAwareContext.
 *
 * @author Frédéric G. Marand <fgm@osinet.fr>
 */
class ApiClientAwareInitializer implements ContextInitializer
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Initializes initializer.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof ApiClientAwareContext) {
            $context->setClient($this->client);
        }
    }
}
