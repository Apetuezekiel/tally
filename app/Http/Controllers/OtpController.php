<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OtpController extends Controller
{
    public function sendOTP(Request $request) {
        $sendOTP = new Client();
        $url = 'https://api.ng.termii.com/api/sms/otp/send';

        $body = [
            "api_key" => "TL808hAj1tENN1RqtWN8SATdnlHHYwfcWAn1p1KY1xX1UD4DNdNMfv8JhsKfQW",
            "message_type" => "ALPHANUMERIC",
            "to" => "2347033474198",
            "from" => "N-Alert",
            "channel" => "dnd",
            "pin_attempts" => 10,
            "pin_time_to_live" =>  5,
            "pin_length" => 6,
            "pin_placeholder" => "< 1234 >",
            "message_text" => "Your pin is < 1234 >",
            "pin_type" => "NUMERIC"
        ];

        $response = $sendOTP->request('GET', $url, [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
        $responseBody = 'TalTRF-'.$response->getBody()->getContents();
    }
}
