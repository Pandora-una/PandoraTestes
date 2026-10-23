<?php

namespace PandoraTestesTeste;

use PandoraTestes\Context\PandoraContext;

/** O contexto do sistema (o FeatureContext do area-restrita estende o PandoraContext). */
class ContextoDeTeste extends PandoraContext
{
    public static function initializeZendFramework()
    {
    }

    public static function usaAplicacao($aplicacao)
    {
        self::$zendApp = $aplicacao;
        self::$_cleanAfterSuite = null;
    }

    public function valor($valor)
    {
        return $this->processValue($valor);
    }

    public function caminho($caminho)
    {
        return $this->locatePath($caminho);
    }
}
