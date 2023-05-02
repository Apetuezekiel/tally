<?php

namespace App\Jobs;
use App\Models\Transactions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class ConfirmP2PTransfer extends Job
{
    // use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     * @return void
     */

     protected $transactionIdDeb;

    public function __construct($transactionIdDeb)
    {
        $this->transactionIdDeb = $transactionIdDeb;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return 1234;
        return $this->transactionIdDeb;
        $data = "Hello, world!";
        $file_path = storage_path('app/file.txt');

        if (!file_exists($file_path)) {
            $handle = fopen($file_path, 'w');
            fwrite($handle, $data);
            fclose($handle);
        } else {
            file_put_contents($file_path, $data, FILE_APPEND);
        }

        echo('345678');
        return $this->transactionIdDeb;
        // $transaction = Transactions::where('transaction_id', $transaction_id)->first();
        // returntransaction;
    }
}
