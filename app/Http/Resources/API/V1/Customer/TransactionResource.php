<?php

namespace App\Http\Resources\API\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $which=[];
        $whichname="";
        switch ($this->which) {
            case '1':
                $which=($this->type=='1') ? $this->deposit : $this->withdrawal;
                $whichname = "Deposit/Withdrawal";
                break;
            case '2':
                $which=$this->logistic;
                $whichname = "Logistics";
                break;
            case '3':
                $which=$this->order;
                $whichname = "Orders";
                break;
            default:
                $which=$which;
                $whichname = "Unknown";
                break;
        }
        return [
            'id'      =>  $this->id,
            'gmpid' => $this->gmpid,
            'customer' => $this->customer,
            'amount' => $this->amount,
            'type' => $this->type,
            'which' => $this->which,
            'typename' => ($this->type=='1') ? 'Credit' : 'Debit',
            'details' => $which,
            'currency' => $this->currency,
            'status'   => $this->status,
            'date'   => $this->created_at->toDateString(),
            'time'   => $this->created_at->toTimeString(),
        ];
    }
}
