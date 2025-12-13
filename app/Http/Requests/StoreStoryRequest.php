<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'media' => 'nullable|file|mimes:jpeg,jpg,png,gif,mp4,mov,avi|max:51200', // Max 50MB
            'media_type' => 'nullable|in:image,video',
            'caption' => 'nullable|string|max:500',
            'contest_id' => 'nullable|exists:contests,id',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'media.file' => 'الملف المرفق يجب أن يكون صورة أو فيديو',
            'media.mimes' => 'نوع الملف غير مدعوم. الأنواع المدعومة: jpeg, jpg, png, gif, mp4, mov, avi',
            'media.max' => 'حجم الملف يجب ألا يتجاوز 50 ميجابايت',
            'media_type.in' => 'نوع الملف يجب أن يكون صورة أو فيديو',
            'caption.max' => 'النص التوضيحي يجب ألا يتجاوز 500 حرف',
            'contest_id.exists' => 'المسابقة المحددة غير موجودة',
        ];
    }
}
