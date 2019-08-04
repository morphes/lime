<?php

namespace Lime;

class Request
{
    const API_URL = 'https://admin.lime-it.ru/';
    const API_USER = 'dufa-bot@lime-it.ru';
    const API_PASSWORD = '4e3fsyw4SW4Gu';
    const API_USER_ID = '614628';
    const INSTALLATION_ID = '41';
    const CASHDESK_ID = '116';
    const FILENAME = 'tokens';

    function query($url, $postFields, $headers = null)
    {
        $ch        = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_URL . $url);
        if($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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
            $tokens = $this->auth();
            $this->memorizeTokens($tokens);
            return $tokens;
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
        return $this->query("/api/CashdeskServer/GetShift?cashdeskId=" . self::CASHDESK_ID);
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
                if(is_array($tokens) && count($tokens) == 2) {
                    return [
                        'access_token' => $tokens[0],
                        'refresh_token' => $tokens[1]
                    ];
                }
            }
        }
        return [];
    }

    private function memorizeTokens($tokens)
    {
        if(isset($tokens['access_token']) && isset($tokens['refresh_token'])) {
            $tokens = implode(';', [$tokens['access_token'], $tokens['refresh_token']]);
            file_put_contents(self::FILENAME, $tokens);
        }
    }
}







