<?php

namespace App\Http\Requests\Auth;

use App\DTOs\Auth\UpdateProfileData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'nullable|min:6|confirmed',
        ];
    }

    public function dto(): UpdateProfileData
    {
        return new UpdateProfileData(
            email: $this->email,
            password: $this->password,
        );
    }
}
