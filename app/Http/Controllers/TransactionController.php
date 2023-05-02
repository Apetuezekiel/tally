<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transactions;
use App\Models\Wallet_users;
use App\Models\Banks;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Ramsey\Uuid\Uuid;
use App\Jobs\ConfirmP2PTransfer;
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{

    ////////////////////////// - BEGINS ** DECLARING REUSABLE FUNCTIONS - /////////////////////

    public function getCurrentTime(){
        return date('Y-m-d H:i:s');
    }

    public function generateTransactionId(){
        $prefixCred = 'Tal-cre-'; // Prefix to distinguish transaction ID
        $prefixDeb = 'Tal-deb-'; // Prefix to distinguish transaction ID

        $transactionIdCred = uniqid($prefixCred, false); // Generate unique transaction ID
        $debTransId = explode('Tal-cre-', $transactionIdCred);
        $transactionIdDeb = $prefixDeb . $debTransId[1];

        $transIDs = array($transactionIdCred, $transactionIdDeb);

        return $transIDs;
    }

    public function updateTransactionTableP2P($request, $clientPhone, $transactionIdDeb, $dest_account, $transactionIdCred){
        // Update Transaction Table with sender details
        $transactionSender = new Transactions;
        $transactionSender->user_id = $clientPhone;
        $transactionSender->transaction_amount = $request->transaction_amount;
        $transactionSender->transaction_type = 'deposit';
        $transactionSender->transaction_method = 'transfer';
        $transactionSender->charges = '100';
        $transactionSender->transaction_id = $transactionIdDeb;
        $transactionSender->source_acct = $clientPhone;
        $transactionSender->destination_acct = $dest_account;
        $transactionSender->destination_bank = 'Tally';
        $transactionSender->save();


        // Update Transaction Table with receiver details
        $transactionReceiver = new Transactions;
        $transactionReceiver->user_id = $dest_account;
        $transactionReceiver->transaction_amount = $request->transaction_amount;
        $transactionReceiver->transaction_type = 'credit';
        $transactionReceiver->transaction_method = 'transfer';
        // $transactionReceiver->charges = 'fgahfgakfg';
        $transactionReceiver->charges = '0';
        $transactionReceiver->transaction_id = $transactionIdCred;
        $transactionReceiver->source_acct = $clientPhone;
        $transactionReceiver->destination_acct = $dest_account;
        $transactionReceiver->destination_bank = 'Tally';
        $transactionReceiver->save();
    }

    public function getBeneficiaryBank($request){
        $beneficiaryBank = Banks::where('bankName', $request->beneficiaryBank)->first();
        $beneficiaryBankCode = $beneficiaryBank->bankCode;
        $beneficiaryBankName = $beneficiaryBank->bankName;

        return array($beneficiaryBankCode, $beneficiaryBankName);
    }

    public function checkKey($apikey){
        $keys = 'ezekielPa$$';
        $secret = 'apetuLock';

        $sig = hash_hmac('sha256', $keys, $secret);

        if ($apikey == $sig){
            return true;

        } else {
            return false;
        }
    }

    public function getAllBanks(){
        $allBanks = Banks::all();
        return response()->json($allBanks);
    }

    public function grabUserFromToken($request){
        $key = env('JWT_SECRET');
        $token = explode(" ", $request->header("authorization"))[1];
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        $decodedArr = json_decode(json_encode($decoded), true);

        $clientName = $decodedArr['email'];
        $clientPhone = $decodedArr['mobile_phone'];

        return array($clientName, $clientPhone);
    }
    public function confirmTransactionPin($request){
        list(, $clientPhone) = $this->grabUserFromToken($request);

        // Get the user record from the database
        $wallet_user = Wallet_users::where('phone_no', $clientPhone)->first();

        // Check if the hashed pin matches the user's input
        if (Hash::check($request->transaction_pin, $wallet_user->transaction_pin)) {
            return true;
        } else {
            return false;
        }
    }

    ////////////////////////// - ENDS ** DECLARING REUSABLE FUNCTIONS - /////////////////////

        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function fetchWallet(Request $request){

        ////////////////////////////////- DECODE TOKEN - GRAB USER DETAILS - ////////////////////////////////////////////////////////////////
        list(, $clientPhone) = $this->grabUserFromToken($request);
        /////////////////////////////////// -ENDED- //////////////////////////////////////////////////////////////////////////
        // return $clientPhone;

        // SET UP OTP
        $otp = strval(rand(100000, 999999));
        $message = "Your OTP is: $otp and expires in 5 minutes";


        $wallet_user = Wallet_users::where('phone_no', $clientPhone)->first();
        if(!$wallet_user){

            // Store the OTP and its expiry time in cache
            $expiresAt = Carbon::now()->addMinutes(5); // Set the OTP to expire in 5 minutes
            Cache::put('otp_' . $clientPhone, $otp, $expiresAt);
            // return response()->json(['status' => 'otp stored and sent']);

            $sendOTP = new Client();
            $url = env('TERMII_SMS_BASE_URL').'send';

            $body = [
                "to" => '234'.substr($clientPhone ,1),
                "from" => env('TERMII_CLIENT_ID'),
                "sms" => $message,
                "type" => "plain",
                "channel" => "generic",
                "api_key" => env('TERMII_API_KEY'),
            ];

            $response = $sendOTP->request('POST', $url, [
                'body' => json_encode($body),
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            $responseData = $response->getBody()->getContents();
            $decodeResponseData = json_decode($responseData, true);
            if ($decodeResponseData['message'] == "Successfully Sent") {
                return response()->json(['message' => "OTP sent to $clientPhone Verify to create Wallet", "username" => null, 'phone_no' => $clientPhone, 'balance' => null], 200);
            }
        } else {
            $response = [
                "message" => "Success",
                "username" => $wallet_user->username,
                "phone_no" => $wallet_user->phone_no,
                "balance" => $wallet_user->balance
            ];
            return response()->json($response, 200);
        };

    }

    //VERIFY PHONE NUMBER AND CREATE WALLET FOR NEW USER
    // public function verifyOTP(Request $request)
    // {
    //     ////////////////////////////////- DECODE TOKEN - GRAB USER DETAILS - ////////////////////////////////////////////////////////////////
    //     list($clientName, $clientPhone) = $this->grabUserFromToken($request);
    //     /////////////////////////////////// -ENDED- //////////////////////////////////////////////////////////////////////////

    //     // Retrieve the OTP and its expiry time from cache
    //     $cachedOtp = Cache::get('otp_' . $clientPhone);
    //     $expiresAt = Cache::get('otp_expires_' . $clientPhone);
    //     // return $expiresAt;

    //     // / Check if the OTP is still valid
    //     if ($cachedOtp && $cachedOtp === $request->otp && Carbon::now()->lt($expiresAt)) {
    //         // OTP is valid
    //         Cache::forget('otp_' . $clientPhone);
    //         Cache::forget('otp_expires_' . $clientPhone);

    //         return response()->json(['status' => 'otp verified and forgotten']);
    //     }


    //     // // Verify Number
    //     // if (DB::table('wallet_users')->where('phone_no', $clientPhone)->value('otp') == $request->otp) {

    //     // // Update User record in DB
    //     // $user = Wallet_users::where('phone_no', $clientPhone)->first();
    //     // $user->verified = '1';
    //     // $user->save();
    //     // return response()->json(['wallet_bal' => '0', 'Message' => 'New user created']);
    //     // }
    // }

    public function verifyOTP(Request $request){
        ////////////////////////////////- DECODE TOKEN - GRAB USER DETAILS - ////////////////////////////////////////////////////////////////
        list($clientName, $clientPhone) = $this->grabUserFromToken($request);
        /////////////////////////////////// -ENDED- //////////////////////////////////////////////////////////////////////////

        // Retrieve the OTP and its expiry time from cache
        $cachedOtp = Cache::get('otp_' . $clientPhone);
        $expiresAt = Cache::get('otp_expires_' . $clientPhone);
        // return $expiresAt;

        // / Check if the OTP is still valid
        if ($cachedOtp && $cachedOtp === $request->otp && Carbon::now()->lt($expiresAt)) {
            // OTP is valid
            Cache::forget('otp_' . $clientPhone);
            Cache::forget('otp_expires_' . $clientPhone);

            //CREATE WALLET
            $user = new Wallet_users();
            $user->username = $clientName;
            $user->phone_no = $clientPhone;
            $user->save();

            return response()->json(['status' => "success", 'message' => 'OTP verified'], 200);
        } else {
            return response()->json(['status' => "Failed", 'message' => 'Wrong OTP']);
        }
    }

    public function peertopeer(Request $request){
        ////////////////////////////////- DECODE TOKEN - GRAB USER DETAILS - ////////////////////////////////////////////////////////////////
        list($clientName, $clientPhone) = $this->grabUserFromToken($request);
        /////////////////////////////////// -ENDED- ////////////////////////////////////////////////////////////////////////////////////////

        if ($this->confirmTransactionPin($request)){
            // return "We can work";
            if ($clientPhone){
                // Grab destination Account
                $dest_account = $request->dest_account;

                // Confirm if Destination Account is a Tally User
                $dest_account_exists = Wallet_users::where('phone_no', $dest_account)->first();

                // Confirm if Destination Account is a Tally User
                $source_account_exists = Wallet_users::where('phone_no', $clientPhone)->first();

                // Generate credit and debit transaction IDs
                list($transactionIdCred, $transactionIdDeb) = $this->generateTransactionId();

                // Move funds from Source Account to Destination Account
                if ($source_account_exists){
                    if ($dest_account_exists) {
                        $sourceUser = Wallet_users::where('phone_no', $clientPhone)->first();
                        if ($sourceUser->balance >= $request->transaction_amount) {
                            $sourceUser->balance -= $request->transaction_amount;
                            $sourceUser->save();

                            $destinationUser = Wallet_users::where('phone_no', $dest_account)->first();
                            $pickDestinationUserRecord = Wallet_users::find($destinationUser->id);
                            $pickDestinationUserRecord->balance += $request->transaction_amount;
                            $pickDestinationUserRecord->save();

                            // Update transaction records on the Transactions Table
                            $this->updateTransactionTableP2P($request, $clientPhone, $transactionIdDeb, $dest_account, $transactionIdCred);

                            // Send Success response
                            return response()->json(['Successful' => 'Transaction Completed', "Transaction_ref" => $transactionIdDeb], 200);;

                        } else {
                            // Source account does not have enough funds for initiated transaction
                            return response()->json(['Unsuccessful' => 'Insufficient Balance'], 200);;
                        }

                    } else {
                        //Destination account is not a Tally user
                        return response()->json(['Message' => 'Recipient is not a Tally user'], 401);
                    }
                } else {
                    return response()->json(['Message' => "User Wallet doesn't exist"], 401);
                }
                return $dest_account_exists;
                } else {
                    return response()->json(['status' => "Failed", 'message' => "Not a valid token"], 401);
                }
        } else {
            return response()->json(['status' => "Failed", 'message' => "Wrong Pin"], 401);
        }
    }

    public function userTransactions(Request $request){
        ////////////////////////////////- DECODE TOKEN - GRAB USER DETAILS - /////////////////////////////////////////////////
        list($clientName, $clientPhone) = $this->grabUserFromToken($request);
        /////////////////////////////////// -ENDED- //////////////////////////////////////////////////////////////////////////

        if ($clientPhone) {
            $userPhone = $clientPhone;
            $columnName = 'destination_acct';

            // get the records for the specified user and limit to 20 records
            $records = DB::table('transactions')
                        ->where('user_id', $userPhone)
                        ->take(20)
                        ->get();

            // calculate the mode of the specified column for the user's records
            $mode = collect($records)->mode($columnName);

            // arrange the records based on the mode of the specified column
            $sortedRecords = collect($records)->sortBy(function ($record) use ($columnName, $mode) {
                return $record->$columnName == $mode ? 0 : 1;
            });

            return $sortedRecords;
        } else {
            return response()->json(['status' => "Failed", 'message' => "Not a valid token"], 401);
        }
    }

    public function fetchNIPAccount(Request $request){
        ////////////////////////////////- DECODE TOKEN - GRAB USER DETAILS - ////////////////////////////////////////////////////////////////
        list(, $clientPhone) = $this->grabUserFromToken($request);
        /////////////////////////////////// -ENDED- //////////////////////////////////////////////////////////////////////////

        if ($clientPhone){
            // Instantiate Guzzle client
        $client = new Client();

        // Define API endpoint URL
        $url = env('BANK_BASE_URL') . 'GetNIPAccount';
        list($beneficiaryBankCode, $beneficiaryBankName) = $this->getBeneficiaryBank($request);
       //  return $token = Str::random(60);

        // Define request body
        $body = [
           "accountNumber" => $request->accountNumber,
           "beneficiaryBank" => $beneficiaryBankCode,
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


        $responseBody = $response->getBody()->getContents();

        // Convert response body from JSON to an array
        $responseData = json_decode($responseBody, true);

        return $responseData;
        }
    }

    public function fetchProvidusAccount(Request $request){

        ////////////////////////////////- DECODE TOKEN - GRAB USER DETAILS - ////////////////////////////////////////////////////////////////
        list(, $clientPhone) = $this->grabUserFromToken($request);
        /////////////////////////////////// -ENDED- /////////////////////////////////////////////////////////////////////////////////////////

        if ($clientPhone) {
            // Instantiate Guzzle client
            $client = new Client();

            // Define API endpoint URL
            $url = env('BANK_BASE_URL') . 'GetProvidusAccount';

            // Define request body
            $body = [
               "accountNumber" => $request->accountNumber,
               "userName" => "test",
               "password" => "test"
            ];

            // Make API request
            $response = $client->request('POST', $url, [
                'body' => json_encode($body),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ]);

            $responseBody = $response->getBody()->getContents();

            // Convert response body from JSON to an array
            $responseData = json_decode($responseBody, true);

            return response()->json(['status' => "Successful", 'data' => $responseData], 200);
        } else {
            return response()->json(['status' => "Failed", 'message' => "Not a valid token"], 401);
        }
    }

    public function updateTallyNumber(Request $request, $id){
        $user = Wallet_users::find($id);
    }

    public function creditWalletCard(Request $request){
        ////////////////////////////////- DECODE TOKEN - GRAB USER DETAILS - ////////////////////////////////////////////////////////////////
        list(, $clientPhone) = $this->grabUserFromToken($request);
        /////////////////////////////////// -ENDED- /////////////////////////////////////////////////////////////////////////////////////////

        $charges = 100;

        if ($clientPhone) {
            // Add a requery api call to confirm transaction here before crediting wallet
            $wallet_user = Wallet_users::where('phone_no', $clientPhone)->first();
            $wallet_user->balance += $request->transaction_amount;
            $wallet_user->save();

            // Debit Cash Account - Liability
            $cash_account = Wallet_users::where('phone_no', env('CASH_ACCOUNT'))->first();
            $cash_account->balance -= $request->transaction_amount;
            $cash_account->save();

            // Credit Charges Account - Assets
            $charges_account = Wallet_users::where('phone_no', env('CHARGES_ACCOUNT'))->first();
            $charges_account->balance += $charges;
            $charges_account->save();

            // Deduct charges from sender's account - Add this to a Job
            $wallet_user2 = Wallet_users::where('phone_no', $clientPhone)->first();
            $wallet_user2->balance -= $charges;
            $wallet_user2->save();

            return response()->json(['status' => "Successful", 'message' => "Your Wallet has been credited with $request->transaction_amount"], 200);
        } else {
            return response()->json(['status' => "Failed", 'message' => "Not a valid token"], 401);
        }
    }

    public function setTransactionPin(Request $request){
        list(, $clientPhone) = $this->grabUserFromToken($request);
        if ($clientPhone){
            // Hash the transaction pin
            $hashed_pin = Hash::make($request->transaction_pin);

            $wallet_user = Wallet_users::where('phone_no', $clientPhone)->first();
            $wallet_user->transaction_pin = $hashed_pin;
            $wallet_user->save();

            return response()->json(['status'=> "success", 'message' => "Transaction pin set"],200);
        } else {
            return response()->json(['status' => "Failed", 'message' => "Not a valid user token"], 401);
        }

    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        if(!$this->checkKey($request->header('api_key'))){
            $data = [
                'status' => 200,
                'message' => "Unauthorized user"
            ];

            return response()->json($data);
        };

        //Get all data from database
        $transactions = Transactions::all();

        // $jwtToken = $request->header('Authorization');
        // $jwtSecret = 'secret';

        // try {
        //     $decoded = JWT::decode($jwtToken, $jwtSecret, array('HS256'));
        //     dd($decoded);
        // } catch (\Exception $e) {
        //     echo 'Token is invalid';
        // }

        return response()->json($transactions);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){
        // post data to database from user
        // return $request;

        //Validation
        $this->validate($request, [
            'user_id' => 'required',
            'transaction_amount' => 'required',
            'transaction_type' => 'required',
            'transaction_method' => 'required',
            'charges' => 'required',

        ]);


        $transaction = new Transactions();

        $transaction->user_id = $request->input('user_id');
        $transaction->transaction_amount = $request->input('transaction_amount');
        $transaction->transaction_type = $request->input('transaction_type');
        $transaction->transaction_method = $request->input('transaction_method');
        $transaction->charges = $request->input('charges');

        $transaction->save();
        return response()->json($transaction);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Give one item from transactions table
        $transaction = Transactions::find($id);

        return response()->json($transaction);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id){
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id){
        // update - ID
               //Validation
               $this->validate($request, [
                'user_id' => 'required',
                'transaction_amount' => 'required',
                'transaction_type' => 'required',
                'transaction_method' => 'required',
                'charges' => 'required',

            ]);


            $transaction = Transactions::find($id);
            $transaction->user_id = $request->input('user_id');
            $transaction->transaction_amount = $request->input('transaction_amount');
            $transaction->transaction_type = $request->input('transaction_type');
            $transaction->transaction_method = $request->input('transaction_method');
            $transaction->charges = $request->input('charges');

            $transaction->save();
            return response()->json($transaction);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){
        //delete - ID
        $transaction = Transactions::find($id);
        $transaction->delete();
        return response()->json('Product deleted successfully');
    }
}
