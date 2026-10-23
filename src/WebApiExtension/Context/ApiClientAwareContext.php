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

namespace Behat\WebApiExtension\Context;

use Behat\Behat\Context\Context;
use GuzzleHttp\ClientInterface;

/**
 * Guzzle Client-aware interface for contexts.
 *
 * @author Frédéric G. Marand <fgm@osinet.fr>
 *
 * @see WebApiAwareInitializer
 */
interface ApiClientAwareContext extends Context
{
    /**
     * Sets Guzzle Client instance.
     *
     * @param \GuzzleHttp\Client $client Guzzle client.
     *
     * @return void
     */
    public function setClient(ClientInterface $client);
}
