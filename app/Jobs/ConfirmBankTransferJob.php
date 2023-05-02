<?php

// namespace App\Jobs;

// class ConfirmBankTransferJob extends Job
// {
//     /**
//      * Create a new job instance.
//      *
//      * @return void
//      */
//     public function __construct()
//     {
//         //
//     }

//     /**
//      * Execute the job.
//      *
//      * @return void
//      */
//     public function handle()
//     {
//         //
//     }
// }

namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;

// class ConfirmBankTransfer extends Job
// {
//     /**
//      * Create a new job instance.
//      *
//      * @return void
//      */
//     public function __construct()
//     {
//         //
//     }

//     /**
//      * Execute the job.
//      *
//      * @return void
//      */
//     public function handle()
//     {
//         echo "Bank transfer confirmation job done!";
//     }
// }

// class ConfirmBankTransferJob implements ShouldQueue
class ConfirmBankTransferJob extends Job implements ShouldQueue
{
    // use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rrnResponseBody;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($rrnResponseBody)
    {
        $this->rrnResponseBody = $rrnResponseBody;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return "I ran this job";
        $client = new Client();

        // Define API endpoint URL
        $url = env('BANK_BASE_URL') . 'GetProvidusTransactionStatus';

        // Define request body
        $body = [
            "transactionReference" => $this->rrnResponseBody,
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

// Get response body as string
$responseBody = $response->getBody()->getContents();

// Convert response body from JSON to an array
$responseData = json_decode($responseBody, true);
return $responseData;
        echo "Bank transfer confirmation job done!";
    }

        /**
     * Get the delay time for the job.
     *
     * @return \DateTimeInterface|\DateInterval|int|null
     */
    public function delay($rrnResponseBody)
    {
        return now()->addMinutes(1);
    }
}
