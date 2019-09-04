<?php

namespace Lime;

class Request
{
    const API_URL = 'https://admin.lime-it.ru/';
    const API_USER = 'sazon@nxt.ru';
    const API_PASSWORD = 'everest1024';
    const API_USER_ID = '614628';
    const INSTALLATION_ID = '79';
    const CASHDESK_ID = '210';
    const FILENAME = 'tokens';
    const REFRESH_TIME = '3600';
    const PROCESSING_ID = '161';

    function query($url, $postFields, $headers = null, $method = 'POST')
    {
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_URL . $url);
        if($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response, true);
        if (isset($response["error"])) {
            exit;
        }
        return $response;
    }

    function refreshToken($refreshToken)
    {
        return $this->query("/connect/token", [
            'client_id' => 'Jade.Api',
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ]);
    }

    function auth()
    {
        return $this->query("/connect/token", [
            "client_id" => "Jade.Api",
            "grant_type" => "password",
            "username" => self::API_USER,
            "password" => self::API_PASSWORD
        ]);
    }

    function init()
    {
        if($tokens = $this->getTokens()) {
            return $tokens;
        } else {
            return $this->newTokens();
        }
    }

    function checkInstallation()
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            $postData = '{"filter":{"canWrite":false,"id":' . self::INSTALLATION_ID . '},"page":{"skip":0,"take":25}}';
            return $this->query("/api/Installations/Select", $postData, $headers);
        }
    }

    function cashdesk()
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            $postData = '{"filter":{"canWrite":false,"id":' . self::CASHDESK_ID . '},"page":{"skip":0,"take":25}}';
            return $this->query("/api/Cashdesks/Select", $postData, $headers);
        }
    }

    function currentShift()
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            $postData = '{"filter":{"canWrite":false,"id":' . self::CASHDESK_ID . '},"page":{"skip":0,"take":25}}';
            $shift = $this->query("/api/CashdeskServer/GetShift?cashdeskId=" . self::CASHDESK_ID, $postData, $headers);
            if(!$shift) {
                return $this->openShift();
            }
            return $shift;
        }
    }

    function openShift()
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {
            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            $postData = [
                'cashdeskId' => self::CASHDESK_ID]
            ;
            return $this->query("/api/CashdeskServer/OpenShift?cashdeskId=" . self::CASHDESK_ID, $postData, $headers);
        }
    }

    private function getTokens()
    {
        if(file_exists(self::FILENAME)) {

            $tokens = file_get_contents(self::FILENAME);

            if($tokens) {

                $tokens = explode(';', $tokens);

                if(is_array($tokens) && count($tokens) == 3) {
                    if(time() - $tokens[2] > self::REFRESH_TIME) {
                        return $this->newTokens();
                    } else {
                        return [
                            'access_token' => $tokens[0],
                            'refresh_token' => $tokens[1],
                            'time' => $tokens[2]
                        ];
                    }
                }
            }
        }
        return [];
    }

    private function newTokens()
    {
        $tokens = $this->auth();
        $this->memorizeTokens($tokens);
        return $tokens;
    }

    private function memorizeTokens($tokens)
    {
        if(isset($tokens['access_token']) && isset($tokens['refresh_token'])) {
            $tokens = implode(';', [$tokens['access_token'], $tokens['refresh_token'], time()]);
            file_put_contents(self::FILENAME, $tokens);
        }
    }

    public function items()
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            return $this->query("/api/CashdeskServer/GetAllGoodTypesAndCategoriesForInstallation?cashdeskId=" . self::CASHDESK_ID . "&installationId=" . self::INSTALLATION_ID, [], $headers, 'GET');
        }
    }

    public function generateCard()
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            return $this->query("/api/CashdeskServer/GenerateCard?cashdeskId=" . self::CASHDESK_ID . "&installationId=" . self::INSTALLATION_ID, [], $headers);
        }
    }

    public function putCheck($postData)
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            return $this->query("/api/CashdeskServer/PutCheck", $postData, $headers);
        }
    }

    public function generateQr($cardId)
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            return $this->query("/api/CashdeskServer/GenerateQr?cardId=" . $cardId, [], $headers, 'GET');
        }
    }

    public function serverTime()
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            return $this->query("/api/CashdeskServer/GetServerCurrentTime", [], $headers, 'GET');
        }
    }

    public function getNextCheckId()
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            return $this->query("/api/CashdeskServer/GetNextCheckId", [], $headers, 'GET');
        }
    }

    public function generateRight($buildQuery)
    {
        $tokens = $this->init();
        if(isset($tokens['access_token'])) {

            $accessToken = $tokens['access_token'];
            $headers   = [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json"
            ];
            return $this->query("/api/CashdeskServer/GetOrCreateRight?". $buildQuery, [], $headers, 'GET');
        }
    }

    public function order($items)
    {
        $time = $this->serverTime();
        $checkId = $this->getNextCheckId();

        $order = $qrs = [];
        $order['order'] = [];
        for ($j = 0; $j < sizeof($items); $j++) {
            $card = $this->generateCard();
            if($card && $cardId = $card['card']['id']) {
                $qrCode = $this->generateQr($cardId);
                $vars           = [];
                $vars['cardId'] = $cardId;
                $vars['cashdeskId'] = self::CASHDESK_ID;
                $vars['goodTypeId'] = $items[$j]["limeid"];
                $vars['price'] = $items[$j]["price"];
                $vars['time']       = $time;
                $right = $this->generateRight(http_build_query($vars));
                $vars['rightId'] = $right['id'];
                $vars['qrCode'] = $qrCode;
                $order['order'][] = $vars;
                $qrs[] = $qrCode;
            }
        }
        $limeOrder = [];
        $limeOrder['check'] = [
            'installationTime' => substr($time, 0, 19),
            'id' => $checkId,
            'installationId' => self::INSTALLATION_ID,
            'cashdeskId' => self::CASHDESK_ID,
            'userId' => self::API_USER_ID,
            'time' => $time,
            'type' => 0,
            'administrative' => false
        ];

        for ($j = 0; $j < sizeof($order['order']); $j++) {
            $entry = [
                'entry' => $checkId,
                'id' => 0,
                'amount' => 1,
                'rightId' => $order['order'][$j]['rightId'],
                'cardId' => $order['order'][$j]['cardId'],
                'goodTypeId' => $order['order'][$j]['goodTypeId'],
                'price' => $this->gettextbal($order['order'][$j]["price"]),
                'basePrice' => $this->gettextbal($order['order'][$j]["price"]),
                'returnCheckEntryId' => null,
                'printedInQr' => true,
                'count' => 1
            ];

            $limeOrder["entries"][$j]['entry'] = $entry;

            $payments = [
                'checkEntryId' => 0,
                'id' => 0,
                'processingId' => self::PROCESSING_ID,
                'amount' => $this->gettextbal($order['order'][$j]["price"]),
                'transactionReference' => 'Genbank',
                'checkEntryDiscounts' => ""
            ];
            $limeOrder["entries"][$j]["payments"][0] = $payments;
            $limeOrder["entries"][$j]["checkEntryDiscounts"] = [];
        }
        $limeOrder["cardChanges"] = [];
        $postLimeOrder = json_encode($limeOrder);
        $this->putCheck($postLimeOrder);
        return $qrs;
    }

    function gettextbal($b)
    {
        if($b==0)
        {
            $r="0.00";
        }
        else
        {
            $r="".($b/100)."";
            $t=explode(".",$r);
            if(!isset($t[1])){$t[1]="00";}else{if(strlen($t[1])==1){$t[1]=$t[1]."0";}}
            $r=implode(".",$t);
        }
        return $r;
    }
}







