<?php

namespace App\Http\Requests\Haulage;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateRequest extends FormRequest
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
            'name' => 'required|string',
            'address' => ['required', 'string', 'max:300'],
            //'inspectiondate' => ['required', 'string', 'max:300'],
            'phone' => ['required', 'string', 'max:300'],
            'source_region' => ['required', 'string'],
            'destination_region' => ['required', 'string'],
            'description' => ['required', 'string'],
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Name is required!',
            'description.required' => 'Haulage detailed description is required!',
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
