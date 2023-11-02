<?php

namespace App\Jobs\Customer;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PhoneOTPJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->send_otp($this->details['phone'], $this->details['otp']);
    }

    public function send_otp($phone, $otp){

        $client = new \GuzzleHttp\Client();

        $response = $client->request('POST', 'https://api.sendchamp.com/api/v1/sms/send', [
            'body' => '{"to":["'.$phone.'"],"sender_name":"SAlert","message":"Please enter the code '.$otp.' to continue your registration on Gavice Logistics","route":"dnd"}',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.env('SENDCHAMP'),
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);

    }
}
