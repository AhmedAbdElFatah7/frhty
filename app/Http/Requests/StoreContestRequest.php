<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only celebrities can create contests
        return $this->user() && $this->user()->role === 'celebrity';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Contest basic info
            'platform_id' => 'required|exists:platforms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'max_attempts' => 'required|integer|min:1|max:10',

            // Terms (optional)
            'terms' => 'nullable|array',
            'terms.*' => 'required|string|max:500',

            // Questions (required, at least 1)
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.option_1' => 'required|string|max:255',
            'questions.*.option_2' => 'required|string|max:255',
            'questions.*.option_3' => 'required|string|max:255',
            'questions.*.correct_answer' => 'required|in:1,2,3',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            // Contest messages
            'platform_id.required' => 'المنصة مطلوبة',
            'platform_id.exists' => 'المنصة المحددة غير موجودة',
            'title.required' => 'عنوان المسابقة مطلوب',
            'title.max' => 'عنوان المسابقة طويل جداً',
            'start_date.required' => 'تاريخ البداية مطلوب',
            'start_date.after_or_equal' => 'تاريخ البداية يجب أن يكون اليوم أو بعده',
            'end_date.required' => 'تاريخ النهاية مطلوب',
            'end_date.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            'max_attempts.required' => 'عدد المحاولات مطلوب',
            'max_attempts.min' => 'عدد المحاولات يجب أن يكون 1 على الأقل',
            'max_attempts.max' => 'عدد المحاولات لا يمكن أن يتجاوز 10',

            // Terms messages
            'terms.array' => 'الشروط يجب أن تكون مصفوفة',
            'terms.*.required' => 'نص الشرط مطلوب',
            'terms.*.max' => 'نص الشرط طويل جداً',

            // Questions messages
            'questions.required' => 'يجب إضافة سؤال واحد على الأقل',
            'questions.array' => 'الأسئلة يجب أن تكون مصفوفة',
            'questions.min' => 'يجب إضافة سؤال واحد على الأقل',
            'questions.*.question_text.required' => 'نص السؤال مطلوب',
            'questions.*.option_1.required' => 'الاختيار الأول مطلوب',
            'questions.*.option_2.required' => 'الاختيار الثاني مطلوب',
            'questions.*.option_3.required' => 'الاختيار الثالث مطلوب',
            'questions.*.correct_answer.required' => 'الإجابة الصحيحة مطلوبة',
            'questions.*.correct_answer.in' => 'الإجابة الصحيحة يجب أن تكون 1 أو 2 أو 3',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException('فقط المشاهير يمكنهم إنشاء مسابقات');
    }
}
