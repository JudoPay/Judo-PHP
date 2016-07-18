<?php

namespace Judopay;

use Guzzle\Plugin\Log\LogPlugin;
use Judopay\Exception\ApiException;

class Request implements \Psr\Log\LoggerAwareInterface
{
    protected $configuration;
    protected $client;
    protected $logger;

    public function __construct(\Judopay\Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->logger = $this->configuration->get('logger');
    }

    public function setClient(\Guzzle\Http\Client $client)
    {
        $this->client = $client;

        // Set headers
        $this->client->setDefaultOption(
            'headers',
            array(
                'API-Version' => $this->configuration->get('apiVersion'),
                'Accept' => 'application/json; charset=utf-8',
                'Content-Type' => 'application/json'
            )
        );

        // Use CA cert bundle to verify SSL connection
        $this->client->setSslVerification(__DIR__.'/../../cert/digicert_sha256_ca.pem');

        // Set up logging
        $adapter = new \Guzzle\Log\PsrLogAdapter(
            $this->logger
        );
        $logPlugin = new LogPlugin($adapter, $this->configuration->get('httpLogFormat'));

        // Set user agent
        $this->client->setUserAgent($this->configuration->get('userAgent'));

        // Attach the plugin to the client, which will in turn be attached to all
        // requests generated by the client
        $this->client->addSubscriber($logPlugin);
    }

    /**
     * Make a GET request to the specified resource path
     *
     * @param string $resourcePath
     **/
    public function get($resourcePath)
    {
        $endpointUrl = $this->configuration->get('endpointUrl');
        $guzzleRequest = $this->client->get(
            $endpointUrl.'/'.$resourcePath
        );

        return $this->send($guzzleRequest);
    }

    /**
     * Make a POST request to the specified resource path
     *
     * @param string $resourcePath
     * @param array  $data
     **/
    public function post($resourcePath, $data)
    {
        $endpointUrl = $this->configuration->get('endpointUrl');
        $guzzleRequest = $this->client->post(
            $endpointUrl.'/'.$resourcePath,
            array(),
            $data
        );

        return $this->send($guzzleRequest);
    }

    public function setRequestAuthentication(\Guzzle\Http\Message\Request $request)
    {
        $oauthAccessToken = $this->configuration->get('oauthAccessToken');

        // Do we have an oAuth2 access token?
        if (!empty($oauthAccessToken)) {
            $request->setHeader('Authorization', 'Bearer '.$oauthAccessToken);
        } else {
            // Otherwise, use basic authentication
            $request->setAuth(
                $this->configuration->get('apiToken'),
                $this->configuration->get('apiSecret')
            );
        }

        return $request;
    }

    /* PSR-3 */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    // @todo Ideally, configuration should be a separate dependency
    public function getConfiguration()
    {
        return $this->configuration;
    }

    protected function send(\Guzzle\Http\Message\Request $guzzleRequest)
    {
        $guzzleRequest = $this->setRequestAuthentication($guzzleRequest);

        try {
            $guzzleResponse = $guzzleRequest->send();
        } catch (\Guzzle\Http\Exception\BadResponseException $e) {
            // Guzzle throws an exception when it encounters a 4xx or 5xx error
            // Rethrow the exception so we can raise our custom exception classes
            throw ApiException::factory($e->getResponse());
        }

        return $guzzleResponse;
    }
}
