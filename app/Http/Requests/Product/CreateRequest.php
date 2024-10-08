<?php

namespace App\Http\Requests\Product;

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
            'name' => 'required|max:255',
            'store' => 'required',
            'category' => 'required',
            'price' => ['required', 'numeric'],
            'description' => 'required',
            'price' => ['required', 'numeric'],
            'quantity' => 'required|numeric|min:1',
            'weight' => 'required|numeric|min:0.01',
            'height' => 'required|numeric|min:1',
            'length' => 'required|numeric|min:1',
            'width' => 'required|numeric|min:1',
            'images.*' => 'required|image|mimes:jpg,png,jpeg,svg|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'images.mimes' => 'Only jpeg,png and bmp images are allowed',
            'images.max' => 'Sorry! Maximum allowed size for an image is 20MB',
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
