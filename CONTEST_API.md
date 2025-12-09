# Contest API Documentation

## 📋 نظرة عامة

API كامل لإدارة المسابقات - إنشاء، عرض، والمشاركة في المسابقات.

---

## 🔐 Authentication

-   **Public Routes:** عرض المسابقات (index, show)
-   **Protected Routes:** إنشاء مسابقة (store) - Celebrity فقط

---

## 📝 API Endpoints

### 1. إنشاء مسابقة (Celebrity Only)

**Endpoint:** `POST /api/contests`  
**Auth:** Required (Bearer Token)  
**Role:** Celebrity only

#### Request Body:

```json
{
    "platform_id": 1,
    "title": "مسابقة TikTok الكبرى",
    "description": "اختبر معلوماتك عن السعودية",
    "start_date": "2025-12-10 00:00:00",
    "end_date": "2025-12-20 23:59:59",
    "max_attempts": 3,
    "terms": [
        "يجب أن تكون متابعاً للحساب",
        "العمر 18 سنة فأكثر",
        "يجب المشاركة من داخل السعودية"
    ],
    "questions": [
        {
            "question_text": "ما هي عاصمة السعودية؟",
            "option_1": "الرياض",
            "option_2": "جدة",
            "option_3": "مكة",
            "correct_answer": "1"
        },
        {
            "question_text": "كم عدد مناطق السعودية؟",
            "option_1": "10",
            "option_2": "13",
            "option_3": "15",
            "correct_answer": "2"
        },
        {
            "question_text": "ما هو اليوم الوطني السعودي؟",
            "option_1": "23 سبتمبر",
            "option_2": "1 يناير",
            "option_3": "15 مارس",
            "correct_answer": "1"
        }
    ]
}
```

#### Success Response (201):

```json
{
    "success": true,
    "message": "تم إنشاء المسابقة بنجاح",
    "data": {
        "contest": {
            "id": 1,
            "title": "مسابقة TikTok الكبرى",
            "description": "اختبر معلوماتك عن السعودية",
            "start_date": "2025-12-10 00:00:00",
            "end_date": "2025-12-20 23:59:59",
            "max_attempts": 3,
            "is_active": true,
            "platform": {
                "id": 1,
                "name": "tiktok",
                "display_name": "TikTok"
            },
            "celebrity": {
                "id": 1,
                "name": "أحمد محمد",
                "user_name": "ahmed_celebrity"
            },
            "terms": [
                {
                    "id": 1,
                    "term": "يجب أن تكون متابعاً للحساب",
                    "order": 1
                },
                {
                    "id": 2,
                    "term": "العمر 18 سنة فأكثر",
                    "order": 2
                },
                {
                    "id": 3,
                    "term": "يجب المشاركة من داخل السعودية",
                    "order": 3
                }
            ],
            "questions": [
                {
                    "id": 1,
                    "question_text": "ما هي عاصمة السعودية؟",
                    "options": {
                        "1": "الرياض",
                        "2": "جدة",
                        "3": "مكة"
                    },
                    "order": 1
                },
                {
                    "id": 2,
                    "question_text": "كم عدد مناطق السعودية؟",
                    "options": {
                        "1": "10",
                        "2": "13",
                        "3": "15"
                    },
                    "order": 2
                },
                {
                    "id": 3,
                    "question_text": "ما هو اليوم الوطني السعودي؟",
                    "options": {
                        "1": "23 سبتمبر",
                        "2": "1 يناير",
                        "3": "15 مارس"
                    },
                    "order": 3
                }
            ],
            "questions_count": 3,
            "terms_count": 3,
            "created_at": "2025-12-09 14:00:00"
        }
    }
}
```

#### Error Responses:

**401 Unauthorized:**

```json
{
    "message": "Unauthenticated."
}
```

**403 Forbidden:**

```json
{
    "message": "فقط المشاهير يمكنهم إنشاء مسابقات"
}
```

**422 Validation Error:**

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "platform_id": ["المنصة مطلوبة"],
        "title": ["عنوان المسابقة مطلوب"],
        "questions": ["يجب إضافة سؤال واحد على الأقل"]
    }
}
```

---

### 2. عرض جميع المسابقات النشطة

**Endpoint:** `GET /api/contests`  
**Auth:** Not Required (Public)

#### Success Response (200):

```json
{
    "success": true,
    "data": {
        "contests": [
            {
                "id": 1,
                "title": "مسابقة TikTok الكبرى",
                "description": "اختبر معلوماتك عن السعودية",
                "start_date": "2025-12-10 00:00:00",
                "end_date": "2025-12-20 23:59:59",
                "max_attempts": 3,
                "platform": {
                    "id": 1,
                    "name": "tiktok",
                    "display_name": "TikTok"
                },
                "celebrity": {
                    "id": 1,
                    "name": "أحمد محمد",
                    "user_name": "ahmed_celebrity"
                },
                "questions_count": 3,
                "terms_count": 3,
                "is_active": true
            }
        ],
        "total": 1
    }
}
```

---

### 3. عرض تفاصيل مسابقة محددة

**Endpoint:** `GET /api/contests/{id}`  
**Auth:** Not Required (Public)

#### Success Response (200):

```json
{
    "success": true,
    "data": {
        "contest": {
            "id": 1,
            "title": "مسابقة TikTok الكبرى",
            "description": "اختبر معلوماتك عن السعودية",
            "start_date": "2025-12-10 00:00:00",
            "end_date": "2025-12-20 23:59:59",
            "max_attempts": 3,
            "is_active": true,
            "platform": {
                "id": 1,
                "name": "tiktok",
                "display_name": "TikTok"
            },
            "celebrity": {
                "id": 1,
                "name": "أحمد محمد",
                "user_name": "ahmed_celebrity",
                "image": "http://localhost:8000/storage/users/profile.jpg"
            },
            "terms": [
                {
                    "id": 1,
                    "term": "يجب أن تكون متابعاً للحساب",
                    "order": 1
                },
                {
                    "id": 2,
                    "term": "العمر 18 سنة فأكثر",
                    "order": 2
                }
            ],
            "questions": [
                {
                    "id": 1,
                    "question_text": "ما هي عاصمة السعودية؟",
                    "options": {
                        "1": "الرياض",
                        "2": "جدة",
                        "3": "مكة"
                    },
                    "order": 1
                }
            ],
            "questions_count": 3,
            "terms_count": 2
        }
    }
}
```

#### Error Response (404):

```json
{
    "success": false,
    "message": "المسابقة غير موجودة"
}
```

---

## 📊 Validation Rules

### Contest Fields:

| Field          | Type     | Required | Rules                         |
| -------------- | -------- | -------- | ----------------------------- |
| `platform_id`  | integer  | ✅ Yes   | Must exist in platforms table |
| `title`        | string   | ✅ Yes   | Max 255 characters            |
| `description`  | string   | ❌ No    | -                             |
| `start_date`   | datetime | ✅ Yes   | Today or future               |
| `end_date`     | datetime | ✅ Yes   | After start_date              |
| `max_attempts` | integer  | ✅ Yes   | Between 1-10                  |

### Terms (Optional):

| Field     | Type   | Required                   | Rules              |
| --------- | ------ | -------------------------- | ------------------ |
| `terms`   | array  | ❌ No                      | -                  |
| `terms.*` | string | ✅ Yes (if array provided) | Max 500 characters |

### Questions (Required):

| Field                        | Type   | Required | Rules                    |
| ---------------------------- | ------ | -------- | ------------------------ |
| `questions`                  | array  | ✅ Yes   | Min 1 question           |
| `questions.*.question_text`  | string | ✅ Yes   | -                        |
| `questions.*.option_1`       | string | ✅ Yes   | Max 255 characters       |
| `questions.*.option_2`       | string | ✅ Yes   | Max 255 characters       |
| `questions.*.option_3`       | string | ✅ Yes   | Max 255 characters       |
| `questions.*.correct_answer` | string | ✅ Yes   | Must be "1", "2", or "3" |

---

## 🧪 Testing with cURL

### Create Contest:

```bash
curl -X POST http://localhost:8000/api/contests \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "platform_id": 1,
    "title": "مسابقة TikTok",
    "description": "اختبر معلوماتك",
    "start_date": "2025-12-10 00:00:00",
    "end_date": "2025-12-20 23:59:59",
    "max_attempts": 3,
    "terms": ["يجب أن تكون متابعاً"],
    "questions": [
      {
        "question_text": "ما هي عاصمة السعودية؟",
        "option_1": "الرياض",
        "option_2": "جدة",
        "option_3": "مكة",
        "correct_answer": "1"
      }
    ]
  }'
```

### Get All Contests:

```bash
curl -X GET http://localhost:8000/api/contests
```

### Get Contest Details:

```bash
curl -X GET http://localhost:8000/api/contests/1
```

---

## 💡 ملاحظات مهمة

### 1. الأمان:

-   ✅ الإجابات الصحيحة **لا تُرسل** في response الـ show/index
-   ✅ فقط المشاهير يمكنهم إنشاء مسابقات
-   ✅ استخدام Transaction للحفاظ على سلامة البيانات

### 2. الترتيب:

-   ✅ الشروط تُرتب تلقائياً حسب الترتيب في الـ array
-   ✅ الأسئلة تُرتب تلقائياً حسب الترتيب في الـ array

### 3. التواريخ:

-   ✅ `start_date` يجب أن يكون اليوم أو بعده
-   ✅ `end_date` يجب أن يكون بعد `start_date`

### 4. المحاولات:

-   ✅ `max_attempts` بين 1-10 محاولات

---

## 🎯 Use Cases

### Celebrity Creating Contest:

```
1. Celebrity logs in → gets token
2. Celebrity creates contest with:
   - Platform (TikTok, Instagram, etc.)
   - Title & Description
   - Start/End dates
   - Terms (optional)
   - Questions (3 options each)
3. Contest is created and active
4. Users can now see and participate
```

### User Viewing Contests:

```
1. User opens app (no login required)
2. User sees all active contests
3. User clicks on contest
4. User sees:
   - Contest details
   - Terms & conditions
   - Questions (without correct answers)
5. User can start attempt (requires login)
```

---

**Version:** 1.0.0  
**Date:** 2025-12-09  
**Status:** ✅ Ready to Use
