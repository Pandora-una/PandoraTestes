<?php
// Devolve a requisição recebida em JSON. ?status=N responde com esse status.
$cabecalhos = [];
// só os que o Guzzle deriva do corpo; os outros vêm do objeto da requisição (o php -S junta ou
// descarta cabeçalho repetido conforme a versão)
foreach (['CONTENT_TYPE', 'CONTENT_LENGTH'] as $nome) {
    if (isset($_SERVER[$nome]) && $_SERVER[$nome] !== '') {
        $cabecalhos[str_replace('_', '-', $nome)] = $_SERVER[$nome];
    }
}
ksort($cabecalhos);
http_response_code(isset($_GET['status']) ? (int) $_GET['status'] : 200);
header('Content-Type: application/json');
echo json_encode([
    'metodo' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'cabecalhos' => $cabecalhos,
    'corpo' => file_get_contents('php://input'),
    'texto' => 'Resposta do ECO',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
