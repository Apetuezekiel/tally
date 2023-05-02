<?php

namespace App\Http\Controllers;

use App\Jobs\ConfirmBankTransferJob;
use Illuminate\Http\Request;
use App\Models\Wallet_users;
use App\Models\Banks;
use App\Models\BankTransactions;
use App\Models\PendingTrans;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Token;
use Tymon\JWTAuth\JWTManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ProvToProvTransactionController extends Controller
{
    public function grabUserFromToken($request){
        $key = env('JWT_SECRET');
            $token = explode(" ", $request->header("authorization"))[1];
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            $decodedArr = json_decode(json_encode($decoded), true);
            // return $decodedArr;

            $clientName = $decodedArr['email'];
            $clientPhone = $decodedArr['mobile_phone'];

            return array($clientName, $clientPhone);
    }

    public function updateTransactionTableP2P($request, $clientPhone, $rrnResponseBody){
        $transactionSender = new BankTransactions();
        $transactionSender->user_id = $clientPhone;
        $transactionSender->transaction_amount = $request->transactionAmount;
        $transactionSender->transaction_type = 'deposit';
        $transactionSender->transaction_method = 'transfer';
        $transactionSender->charges = '100';
        $transactionSender->transaction_id = $rrnResponseBody;
        $transactionSender->source_acct = $clientPhone;
        $transactionSender->destination_acct = $request->creditAccount;
        $transactionSender->destination_acct_name = $request->creditAccountName;
        $transactionSender->destination_bank = "Providus";
        $transactionSender->save();
    }

    public function provToProv(Request $request){
        ////////////////////////////////- DECODE TOKEN - GRAB USER DETAILS - ////////////////////////////////////////////////////////////////
        list($clientName, $clientPhone) = $this->grabUserFromToken($request);
        /////////////////////////////////// -ENDED- //////////////////////////////////////////////////////////////////////////
        $transaction_charges = 100;

        $sourceUser = Wallet_users::where('phone_no', $clientPhone)->first();
        if ($sourceUser->balance >= $request->transaction_amount + $transaction_charges) {

        //Grab the Bank code for the beneficiary bank

        // Instantiate Guzzle client
        $client = new Client();

        // Define API endpoint URL
        $url = env('BANK_BASE_URL') .'ProvidusFundTransfer';

        // Define request body

        //Grab the Bank code for the beneficiary bank
        $transactionReference = new Client();
        $rrnUrl = env('RRN_URL');
        $rrnResponse = $transactionReference->request('GET', $rrnUrl, [
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);
        $rrnResponseBody = 'TalTRF-'.$rrnResponse->getBody()->getContents();
        // return $rrnResponseBody;

        $body = [
            "creditAccount" => $request->creditAccount,
            "debitAccount" => env('CENTRAL-ACCT'),
            "transactionAmount" =>  $request->transactionAmount,
            "currencyCode" => "NGN",
            "narration" => $request->narration,
            "transactionReference" => $rrnResponseBody,
            "userName" => "test",
            "password" => "test"
        ];

        // Make API request
        $response = $client->request('POST', $url, [
            'body' => json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);


        // Add transaction record to pending transaction table
        $PendingTransRecord = new PendingTrans;
        $PendingTransRecord->tally_account = $clientPhone;
        $PendingTransRecord->amount = $request->transactionAmount;
        $PendingTransRecord->dest_account = $request->creditAccount;
        $PendingTransRecord->dest_bank = 'Providus';
        $PendingTransRecord->trans_ref = $rrnResponseBody;
        $PendingTransRecord->transaction_type = 'credit';
        $PendingTransRecord->transaction_method = 'transfer';
        $PendingTransRecord->charges = '0';
        $PendingTransRecord->processed = '0';
        $PendingTransRecord->status = 'processing';
        $PendingTransRecord->save();
        $initialResponse = [
            'status' => 'processing',
            'message' => 'Transaction in progress'
        ];
        echo json_encode($initialResponse);

        // $PendingTransRecordResponse = new Response('Transaction Pending');
        // $PendingTransRecordResponse->send();
        // return response('Transaction pending');

        // Get response body as string
        $responseBody = $response->getBody()->getContents();

        // Convert response body from JSON to an array
        $responseData = json_decode($responseBody, true);
        if($responseData["responseCode"] == '00'){
            $sourceUser = Wallet_users::where('phone_no', $clientPhone)->first();
            $charges_account = Wallet_users::where('phone_no', env('CASH_ACCOUNT'))->first();
            $cash_account = Wallet_users::where('phone_no', env('CHARGES_ACCOUNT'))->first();
            // $transaction_id = uniqid($)

            // return $sourceUser;

            // Update a record in the "pendin_trans"
                $pendingTrans = PendingTrans::where('trans_ref', $rrnResponseBody)->first();
                $pendingTrans->processed = 1;
                $pendingTrans->charges = '100';
                $pendingTrans->status = 'Completed';
                $pendingTrans->save();

                //Deduct transferred funds and charges from user wallet
                if ($sourceUser->balance >= $request->transaction_amount) {
                    $sourceUser->balance -= $request->transactionAmount;
                    $sourceUser->save();

                    //Credit charges into the Charges Account
                    $charges_account->balance += $transaction_charges;
                    $charges_account->save();

                    //Credit Cash Account
                    $cash_account->balance += $request->transactionAmount;
                    $cash_account->save();
                } else {

                    return response()->json(['message' => 'Insufficient Wallet Balance'], 200);
                }

            // Update records on the Transaction Table
            $this->updateTransactionTableP2P($request, $clientPhone, $rrnResponseBody);
            // ConfirmBankTransferJob::dispatch($rrnResponseBody);

            return response()->json(['Successful' => 'Transaction Completed', 'Transaction ref' => $rrnResponseBody], 200);
        } else {
            return response()->json(['Failed' => 'Transaction Truncated'], 401);

        }
    } else {
        return response()->json(['message' => 'Insufficient Wallet Balance'], 200);
    }
    }
}
