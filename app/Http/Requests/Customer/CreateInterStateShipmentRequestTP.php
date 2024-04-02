<?php

namespace App\Http\Requests\Customer;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateInterStateShipmentRequestTP extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            "pickupvehicle"=>['required'],
            "userid"=>['required'],
            "pickupdate"=>['required'],
            "gmppayment"=>['required'],
            "deliverymode"=>['required'],
            "pickupcenter" =>   ["required_if:deliverymode,1"],
            "customername"=>['required'],
            "customerphone"=>['required', 'numeric'],
            "customeraddress"=>['required'],
            "recipientname"=>['required'],
            "recipientphone"=>['required', 'numeric'],
            "recipientaddress"=>['required'],
            "sourceregion"=>['required'],
            "destinationregion"=>['required'],
            "totalweight"=>['required'],
            "totalamount"=>['required'],
            "itemtype"=>['required'],
            "itemweight"=>['required'],
            "itemvalue"=>['required'],
            //"name"=>['required'],
            //"sweighttype"=>['required'],
            // "squantity"=>['required'],
            // "slength"=>['required'],
            // "swidth"=>['required'],
            // "sheight"=>['required'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $message = array();
        foreach ($validator->errors()->all() as $error) {
            array_push($message, $error);
        }
        $response = response()->json([
            'status' => 'error',
            'message' => $message,
        ], 422);

        throw (new ValidationException($validator, $response))
            ->errorBag($this->errorBag)
            ->redirectTo($this->getRedirectUrl());
    }

    public function failedAuthorization()
    {
        throw new AuthorizationException("You don't have the authority to perform this resource");
    }
}

