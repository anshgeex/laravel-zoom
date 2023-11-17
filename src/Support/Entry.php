<?php

namespace MacsiDigital\Zoom\Support;

use MacsiDigital\API\Support\Authentication\JWT;
use MacsiDigital\API\Support\Entry as ApiEntry;
use MacsiDigital\Zoom\Facades\Client;

class Entry extends ApiEntry
{
    protected $modelNamespace = '\MacsiDigital\Zoom\\';

    protected $pageField = 'page_number';

    protected $maxQueries = '5';

    protected $apiKey = null;

    protected $apiSecret = null;

    protected $tokenLife = null;

    protected $baseUrl = null;

    // Amount of pagination results per page by default, leave blank if should not paginate
    // Without pagination rate limits could be hit
    protected $defaultPaginationRecords = '30';

    // Max and Min pagination records per page, will vary by API server
    protected $maxPaginationRecords = '300';

    protected $resultsPageField = 'page_number';
    protected $resultsTotalPagesField = 'page_count';
    protected $resultsPageSizeField = 'page_size';
    protected $resultsTotalRecordsField = 'total_records';

    protected $allowedOperands = ['='];

    /**
     * Entry constructor.
     * @param $apiKey
     * @param $apiSecret
     * @param $tokenLife
     * @param $maxQueries
     * @param $baseUrl
     */
    public function __construct($apiKey = null, $apiSecret = null, $tokenLife = null, $maxQueries = null, $baseUrl = null)
    {
        $this->apiKey = $apiKey ? $apiKey : config('zoom.api_key');
        $this->apiSecret = $apiSecret ? $apiSecret : config('zoom.api_secret');
        $this->tokenLife = $tokenLife ? $tokenLife : config('zoom.token_life');
        $this->maxQueries = $maxQueries ? $maxQueries : (config('zoom.max_api_calls_per_request') ? config('zoom.max_api_calls_per_request') : $this->maxQueries);
        $this->baseUrl = $baseUrl ? $baseUrl : config('zoom.base_url');
    }

    public function newRequest()
    {
        if (config('zoom.authentication_method') == 'jwt') {
            return $this->jwtRequest();
        } elseif (config('zoom.authentication_method') == 'oauth2') {
            return $this->oauth2Request();
        }
    }

    public function jwtRequest()
    {
        $jwtToken = JWT::generateToken(['iss' => $this->apiKey, 'exp' => time() + $this->tokenLife], $this->apiSecret);

        return Client::baseUrl($this->baseUrl)->withToken($jwtToken);
    }

    public function oauth2Request()
    {
        $accountId = config('zoom.account_id');
        $clientId = config('zoom.client_id');
        $clientSecret = config('zoom.client_secret');
        $encoded = base64_encode($clientId.':'.$clientSecret);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://zoom.us/oauth/token?grant_type=account_credentials&account_id='.$accountId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic '.$encoded,
            ),
        ));

        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new \ErrorException( curl_error($curl));
        }
        $res = json_decode($response);
        curl_close($curl);
        return Client::baseUrl(config('zoom.base_url'))->withToken($res->access_token);
    }
}
