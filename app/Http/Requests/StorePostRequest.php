<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only celebrities can create posts
        return $this->user() && $this->user()->role === 'celebrity';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:51200', // 50MB max
            'media_type' => 'nullable|in:image,video',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'المحتوى مطلوب',
            'content.max' => 'المحتوى لا يمكن أن يتجاوز 5000 حرف',
            'media.mimes' => 'نوع الملف غير مدعوم',
            'media.max' => 'حجم الملف لا يمكن أن يتجاوز 50 ميجابايت',
            'media_type.in' => 'نوع الميديا يجب أن يكون صورة أو فيديو',
        ];
    }
}
