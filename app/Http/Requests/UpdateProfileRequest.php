<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $userId = $this->user()->id;

        return [
            'name' => 'nullable|string|max:255',
            'user_name' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'user_name')->ignore($userId),
            ],
            'gender' => 'nullable|in:male,female,other',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:6144', // 6MB
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.string' => 'الاسم يجب أن يكون نصاً',
            'name.max' => 'الاسم لا يمكن أن يتجاوز 255 حرف',
            'user_name.string' => 'اسم المستخدم يجب أن يكون نصاً',
            'user_name.max' => 'اسم المستخدم لا يمكن أن يتجاوز 255 حرف',
            'user_name.regex' => 'اسم المستخدم يجب أن يحتوي على حروف وأرقام و _ فقط',
            'user_name.unique' => 'اسم المستخدم مستخدم من قبل',
            'gender.in' => 'الجنس يجب أن يكون male أو female أو other',
            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'الصورة يجب أن تكون من نوع: jpeg, png, jpg, gif',
            'image.max' => 'حجم الصورة لا يمكن أن يتجاوز 6 ميجابايت',
        ];
    }
}
