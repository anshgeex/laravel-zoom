<?php

namespace MacsiDigital\Zoom\Support;

use MacsiDigital\Zoom\Facades\Client;
use MacsiDigital\API\Support\Entry as ApiEntry;
use MacsiDigital\API\Support\Authentication\JWT;

class Entry extends ApiEntry
{
    protected $modelNamespace = '\MacsiDigital\Zoom\\';

    protected $pageField = 'page_number';

    protected $maxQueries = '5';

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

    public function __construct()
    {
        if(config('zoom.max_api_calls_per_request') != null){
            $this->maxQueries = config('zoom.max_api_calls_per_request');
        }
    }

    public function newRequest()
    {
        if(config('zoom.authentication_method') == 'jwt'){
            return $this->jwtRequest();
        }elseif(config('zoom.authentication_method') == 'oauth2'){

        }
    }

    public function jwtRequest()
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
        /*$jwtToken = JWT::generateToken(['iss' => config('zoom.api_key'), 'exp' => time() + config('zoom.token_life')], config('zoom.api_secret'));

        return Client::baseUrl(config('zoom.base_url'))->withToken($jwtToken);*/
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
