<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteProfileRequest extends FormRequest
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
        $user = $this->user();
        $isCelebrity = $user->role === 'celebrity';

        $rules = [
            'user_name' => 'required|string|max:255|unique:users,user_name,' . $user->id,
            'gender' => 'required|string|in:male,female,other',
            'image' => 'nullable|max:6000',
        ];

        // Social accounts validation based on role
        if ($isCelebrity) {
            $rules['platforms_provided'] = [
                'required',
                function ($attribute, $value, $fail) {
                    $request = request();
                    $hasAtLeastOne = $request->filled('tiktok_url') ||
                        $request->filled('snapchat_url') ||
                        $request->filled('youtube_url') ||
                        $request->filled('x_url') ||
                        $request->filled('instagram_url') ||
                        $request->filled('store_url');

                    if (!$hasAtLeastOne) {
                        $fail('يجب إضافة رابط منصة واحدة على الأقل');
                    }
                }
            ];

            // TikTok
            $rules['tiktok_url'] = 'nullable|url|max:500';
            $rules['tiktok_followers'] = 'nullable|integer|min:0';

            // Snapchat
            $rules['snapchat_url'] = 'nullable|url|max:500';
            $rules['snapchat_followers'] = 'nullable|integer|min:0';

            // YouTube
            $rules['youtube_url'] = 'nullable|url|max:500';
            $rules['youtube_followers'] = 'nullable|integer|min:0';

            // X (Twitter)
            $rules['x_url'] = 'nullable|url|max:500';
            $rules['x_followers'] = 'nullable|integer|min:0';

            // Instagram
            $rules['instagram_url'] = 'nullable|url|max:500';
            $rules['instagram_followers'] = 'nullable|integer|min:0';

            // Store
            $rules['store_url'] = 'nullable|url|max:500';
            $rules['store_followers'] = 'nullable|integer|min:0';
        } else {
            // Followers cannot add social accounts
            $rules['tiktok_url'] = 'prohibited';
            $rules['tiktok_followers'] = 'prohibited';
            $rules['snapchat_url'] = 'prohibited';
            $rules['snapchat_followers'] = 'prohibited';
            $rules['youtube_url'] = 'prohibited';
            $rules['youtube_followers'] = 'prohibited';
            $rules['x_url'] = 'prohibited';
            $rules['x_followers'] = 'prohibited';
            $rules['instagram_url'] = 'prohibited';
            $rules['instagram_followers'] = 'prohibited';
            $rules['store_url'] = 'prohibited';
            $rules['store_followers'] = 'prohibited';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_name.required' => 'اسم المستخدم مطلوب',
            'user_name.unique' => 'اسم المستخدم مستخدم بالفعل',
            'user_name.max' => 'اسم المستخدم يجب ألا يتجاوز 255 حرفاً',

            'gender.required' => 'الجنس مطلوب',
            'gender.in' => 'الجنس المحدد غير صالح',

            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'صيغة الصورة غير مدعومة (jpeg, png, jpg, gif)',
            'image.max' => 'حجم الصورة يجب ألا يتجاوز 2 ميجابايت',

            // Platform URLs
            '*.url' => 'رابط الحساب غير صالح',
            '*.max' => 'رابط الحساب طويل جداً',

            // Followers
            '*.integer' => 'عدد المتابعين يجب أن يكون رقماً',
            '*.min' => 'عدد المتابعين لا يمكن أن يكون سالباً',

            // Prohibited for followers
            '*.prohibited' => 'لا يمكن إضافة منصات لحساب المتابع',
        ];
    }
}
