<?php

namespace App\Services;

use App\Services\HttpServiceProvider;
use App\Interfaces\BinToCountryCodeConverterInterface;
use App\Exceptions\Parser\InvalidJSONException;
use App\Exceptions\Service\ProviderException;

class RapidAPIBinChecker extends HttpServiceProvider implements BinToCountryCodeConverterInterface {

    private $host = 'bin-ip-checker.p.rapidapi.com';

    function __construct(private string $apiKey)
    {
        $this->newClient("https://{$this->host}");
    }

    private function lookup(int $bin): object
    {
        $response = self::$client->request('POST', "https://{$this->host}/?bin={$bin}", [
            'body' => \json_encode(['bin' => $bin]),
            'headers' => [
                'Content-Type' => 'application/json',
                'x-rapidapi-host' => $this->host,
                'x-rapidapi-key' => $this->apiKey,
            ],
        ]);

        $responseObject = \json_decode($response->getBody()->getContents());

        if (is_null($responseObject)) {
            throw new InvalidJSONException(__CLASS__);
        }

        if (!$responseObject->success || $responseObject->code != 200) {
            throw new ProviderException(__CLASS__ . ' lookup response code ' . $responseObject->code);
        }

        return $responseObject->BIN;
    }

    public function getCountryCode(int $bin): string
    {
        $lookup = $this->lookup($bin);

        if (!$lookup->valid) {
            throw new ProviderException(__CLASS__ . ' invalid bin ' . $bin);
        }

        return $lookup->country->alpha2;
    }
}
