<?php

use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Promise\PromiseInterface;
use Monolog\Logger;
use Solarium\Client as SolrClient;

class LogPenHandler
{
    private $handler;
    private Logger $logger;
    private SolrClient $solr;

    public function __construct($handler, Logger $logger)
    {
        $this->handler = $handler;
        $this->logger = $logger;

        $parts = parse_url(ConfiguracaoSEI::getInstance()->getValor('Solr', 'Servidor', false));

        $host = $parts['host'] ?? 'solr';
        $port = $parts['port'] ?? 8983;
        $config = [
            'endpoint' => [
                'localhost' => [
                    'host' => $host,
                    'port' => $port,
                    'core' => 'mod-sei-pen'
                ]
            ]
            ];

        $adapter = new Solarium\Core\Client\Adapter\Curl();
        $eventDispatcher = new Symfony\Component\EventDispatcher\EventDispatcher();
        $solr = new SolrClient($adapter, $eventDispatcher, $config);  
        $endpoint = $solr->getEndpoint();
        $endpoint->setAuthentication(ConfiguracaoSEI::getInstance()->getValor('Solr', 'Usuario', false), ConfiguracaoSEI::getInstance()->getValor('Solr', 'Senha', false));

        $this->solr = $solr;
    }

    public function __invoke($request, array $options): PromiseInterface
    {
        $start = microtime(true);

        return ($this->handler)($request, $options)
            ->then(
                function ($response) use ($request, $start) {

                    $duration = (microtime(true) - $start) * 1000;
                    $uri = (string) $request->getUri();
                    $data = [
                        'request' => [
                            'method' => $request->getMethod(),
                            'uri' => $uri,
                            'headers' => $request->getHeaders(),
                            'body' => (string) $request->getBody(),
                        ],
                        'response' => [
                            'status' => $response->getStatusCode(),
                            'headers' => $response->getHeaders(),
                            'body' => str_contains($uri, 'componentes-digitais')
                                    ? '[BODY OMITIDO]'
                                    : (string) $response->getBody(),
                        ],
                        'duration_ms' => $duration,
                    ];

                    // 1) Monolog (log estruturado)
                    $this->logger->info('API CALL', $data);

                    // 2) Solr (persistência)
                    $this->saveToSolr($data);
                    $response->getBody()->rewind();

                    return $response;
                },
                function ($reason) use ($request, $start) {

                    $duration = (microtime(true) - $start) * 1000;

                    $data = [
                        'request' => [
                            'method' => $request->getMethod(),
                            'uri' => (string) $request->getUri(),
                            'headers' => $request->getHeaders(),
                            'body' => (string) $request->getBody(),
                        ],
                        'error' => (string) $reason,
                        'duration_ms' => $duration,
                    ];

                    $this->logger->error('API ERROR', $data);
                    $this->saveToSolr($data);

                    throw $reason;
                }
            );
    }

    private function saveToSolr(array $data): void
    {
        $update = $this->solr->createUpdate();
        $doc = $update->createDocument();

        $doc->id = uniqid('api_', true);
        $doc->url = $data['request']['uri'];
        $doc->method = $data['request']['method'];
        $doc->request_headers = json_encode($data['request']['headers']);
        $doc->request_body = $data['request']['body'] ?? null;

        $doc->response_status = $data['response']['status'] ?? null;
        $doc->response_headers = json_encode($data['response']['headers'] ?? []);
        $doc->response_body = $data['response']['body'] ?? null;

        $doc->duration_ms = $data['duration_ms'];
        $doc->created_at = gmdate('Y-m-d\TH:i:s\Z');

        $update->addDocument($doc);
        $update->addCommit();

        try {
            $this->solr->update($update);
        } catch (Exception $e) {
            LogSEI::getInstance()->gravar(PENIntegracao::getInstance()->getNome() . ": Erro ao enviar logs para Solr. Detalhes: " . $e->getMessage());
        }
    }
}