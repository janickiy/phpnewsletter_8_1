<?php

namespace App\Http\Requests\Admin\Smtp;

use App\Helpers\SendEmailHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'host' => [
                'required',
                'string',
                'max:255',
            ],
            'username' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
            ],
            'port' => [
                'required',
                'integer',
                'min:1',
            ],
            'timeout' => [
                'required',
                'integer',
                'min:1',
            ],
            'secure' => [
                'required',
                'in:no,ssl,tls',
            ],
            'authentication' => [
                'required',
                'in:no,plain,cram-md5',
            ],
        ];
    }

    /**
     * Verify the new SMTP credentials after the request passes field validation.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        if ($validator->fails()) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            if (
                SendEmailHelper::checkConnection(
                    $this->host,
                    $this->email,
                    $this->username,
                    $this->password,
                    $this->port,
                    $this->authentication,
                    $this->secure,
                    $this->timeout
                ) === false
            ) {
                $validator->errors()->add('connection', __('message.unable_connect_to_smtp'));
            }
        });
    }
}
