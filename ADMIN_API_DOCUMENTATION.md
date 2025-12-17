# 📚 Farhty Admin API Documentation

## 📋 نظرة عامة

هذا الملف يحتوي على توثيق كامل لـ Admin API الخاص بتطبيق Farhty. يتيح هذا الـ API للمسؤولين (Admins) إدارة المستخدمين والاطلاع على الإحصائيات.

---

## 🔧 الإعداد

### المتطلبات

-   Laravel 11+
-   PHP 8.2+
-   MySQL/PostgreSQL
-   Laravel Sanctum للـ Authentication

### الـ Base URL

```
http://localhost:8000/api/admin
```

### إنشاء Admin User

```bash
php artisan db:seed --class=AdminSeeder
```

**بيانات الدخول الافتراضية:**

-   📱 Phone: `01000000000`
-   🔑 Password: `password123`

---

## 🔐 Authentication (المصادقة)

### 1. تسجيل الدخول (Login)

**Endpoint:** `POST /api/admin/login`

**Headers:**

```
Accept: application/json
Content-Type: application/json
```

**Body:**

```json
{
    "phone": "01000000000",
    "password": "password123"
}
```

**Response (Success - 200):**

```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "user": {
            "id": 1,
            "name": "Admin",
            "phone": "01000000000",
            "is_admin": true
        },
        "token": "1|abcdefghijklmnopqrstuvwxyz..."
    }
}
```

**Response (Error - 401):**

```json
{
    "success": false,
    "message": "Invalid credentials."
}
```

**Response (Error - 403):**

```json
{
    "success": false,
    "message": "Unauthorized. Admin access required."
}
```

---

### 2. تسجيل الخروج (Logout)

**Endpoint:** `POST /api/admin/logout`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Response (Success - 200):**

```json
{
    "success": true,
    "message": "Logged out successfully."
}
```

---

### 3. الحصول على بيانات الأدمن الحالي (Me)

**Endpoint:** `GET /api/admin/me`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Response (Success - 200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Admin",
        "phone": "01000000000",
        "is_admin": true
    }
}
```

---

## 👥 User Management (إدارة المستخدمين)

### 1. عرض جميع المستخدمين (Index)

**Endpoint:** `GET /api/admin/users`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Query Parameters:**
| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `role` | string | فلترة حسب الدور | `follower` أو `celebrity` |
| `search` | string | بحث بالاسم أو اليوزرنيم أو الموبايل | `ahmed` |
| `page` | integer | رقم الصفحة | `1` |

**Examples:**

```
GET /api/admin/users
GET /api/admin/users?role=follower
GET /api/admin/users?search=ahmed
GET /api/admin/users?role=celebrity&search=محمد&page=2
```

**Response (Success - 200):**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "Ahmed",
                "user_name": "ahmed123",
                "phone": "01234567890",
                "role": "follower",
                "gender": "male",
                "verified": true,
                "completed": true,
                "is_admin": false,
                "created_at": "2024-01-01T00:00:00.000000Z",
                "updated_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "first_page_url": "...",
        "from": 1,
        "last_page": 1,
        "last_page_url": "...",
        "per_page": 15,
        "to": 1,
        "total": 1
    }
}
```

---

### 2. عرض مستخدم واحد (Show)

**Endpoint:** `GET /api/admin/users/{id}`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Response (Success - 200):**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Ahmed",
        "user_name": "ahmed123",
        "phone": "01234567890",
        "role": "follower",
        "image": null,
        "cover": null,
        "gender": "male",
        "verified": true,
        "completed": true,
        "is_admin": false,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "updated_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

**Response (Error - 404):**

```json
{
    "success": false,
    "message": "User not found."
}
```

---

### 3. تعديل مستخدم (Update)

**Endpoint:** `PUT /api/admin/users/{id}`

**Headers:**

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}
```

**Body (جميع الحقول اختيارية):**

```json
{
    "name": "New Name",
    "user_name": "new_username",
    "phone": "01111111111",
    "role": "celebrity",
    "gender": "female",
    "verified": true,
    "completed": true,
    "is_admin": false,
    "password": "newpassword123"
}
```

**Available Fields:**
| Field | Type | Validation |
|-------|------|------------|
| `name` | string | max:255 |
| `user_name` | string | max:255, unique |
| `phone` | string | unique |
| `role` | string | in:follower,celebrity |
| `gender` | string | in:male,female,other |
| `verified` | boolean | - |
| `completed` | boolean | - |
| `is_admin` | boolean | - |
| `password` | string | min:6 |

**Response (Success - 200):**

```json
{
    "success": true,
    "message": "User updated successfully.",
    "data": {
        "id": 1,
        "name": "New Name",
        "user_name": "new_username",
        "phone": "01111111111",
        "role": "celebrity",
        "gender": "female",
        "verified": true,
        "completed": true,
        "is_admin": false
    }
}
```

---

### 4. حذف مستخدم (Delete)

**Endpoint:** `DELETE /api/admin/users/{id}`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Response (Success - 200):**

```json
{
    "success": true,
    "message": "User deleted successfully."
}
```

**Response (Error - 400):**

```json
{
    "success": false,
    "message": "You cannot delete yourself."
}
```

**Response (Error - 404):**

```json
{
    "success": false,
    "message": "User not found."
}
```

---

## 📊 Statistics (الإحصائيات)

### 1. جميع الإحصائيات (All Statistics)

**Endpoint:** `GET /api/admin/statistics`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Response (Success - 200):**

```json
{
    "success": true,
    "data": {
        "users": {
            "total": 100,
            "followers": 80,
            "celebrities": 20,
            "admins": 2,
            "verified": 50,
            "new_today": 5,
            "new_this_week": 20,
            "new_this_month": 50,
            "gender_distribution": {
                "male": 60,
                "female": 35,
                "other": 5
            }
        },
        "content": {
            "total_posts": 500,
            "total_stories": 200,
            "active_stories": 50,
            "posts_today": 10,
            "stories_today": 5
        },
        "engagement": {
            "total_likes": 5000,
            "total_follows": 1000,
            "total_messages": 3000,
            "total_conversations": 500,
            "total_notifications": 2000,
            "likes_today": 100,
            "messages_today": 50
        },
        "contests": {
            "total_contests": 50,
            "active_contests": 10,
            "total_attempts": 500,
            "contests_today": 2,
            "attempts_today": 20
        }
    }
}
```

---

### 2. إحصائيات المستخدمين فقط

**Endpoint:** `GET /api/admin/statistics/users`

**Response:**

```json
{
    "success": true,
    "data": {
        "total": 100,
        "followers": 80,
        "celebrities": 20,
        "admins": 2,
        "verified": 50,
        "new_today": 5,
        "new_this_week": 20,
        "new_this_month": 50,
        "gender_distribution": {
            "male": 60,
            "female": 35,
            "other": 5
        }
    }
}
```

---

### 3. إحصائيات المحتوى فقط

**Endpoint:** `GET /api/admin/statistics/content`

**Response:**

```json
{
    "success": true,
    "data": {
        "total_posts": 500,
        "total_stories": 200,
        "active_stories": 50,
        "posts_today": 10,
        "stories_today": 5
    }
}
```

---

### 4. إحصائيات التفاعل فقط

**Endpoint:** `GET /api/admin/statistics/engagement`

**Response:**

```json
{
    "success": true,
    "data": {
        "total_likes": 5000,
        "total_follows": 1000,
        "total_messages": 3000,
        "total_conversations": 500,
        "total_notifications": 2000,
        "likes_today": 100,
        "messages_today": 50
    }
}
```

---

### 5. إحصائيات المسابقات فقط

**Endpoint:** `GET /api/admin/statistics/contests`

**Response:**

```json
{
    "success": true,
    "data": {
        "total_contests": 50,
        "active_contests": 10,
        "total_attempts": 500,
        "contests_today": 2,
        "attempts_today": 20
    }
}
```

---

## 🔒 الحماية والصلاحيات

### Middleware المستخدمة

1. **`auth:sanctum`** - التحقق من صحة الـ Token
2. **`is_admin`** - التحقق من أن المستخدم Admin

### أكواد الأخطاء

| Code | Description                                   |
| ---- | --------------------------------------------- |
| 200  | نجاح العملية                                  |
| 400  | خطأ في الطلب (مثل محاولة حذف النفس)           |
| 401  | غير مصرح (Invalid credentials أو Token منتهي) |
| 403  | ممنوع (المستخدم ليس Admin)                    |
| 404  | غير موجود                                     |
| 422  | خطأ في التحقق من البيانات                     |

---

## 📁 هيكل الملفات

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Dashboard/
│   │       ├── AuthController.php       # مصادقة الأدمن
│   │       ├── UserController.php       # إدارة المستخدمين
│   │       └── StatisticsController.php # الإحصائيات
│   └── Middleware/
│       └── IsAdmin.php                  # التحقق من الأدمن
├── Models/
│   └── User.php                         # (تم إضافة is_admin)
database/
├── migrations/
│   └── 2025_12_16_..._add_password_and_is_admin_to_users_table.php
└── seeders/
    └── AdminSeeder.php                  # إنشاء Admin افتراضي
routes/
└── api.php                              # Routes الأدمن
bootstrap/
└── app.php                              # تسجيل Middleware
```

---

## 🚀 Postman Collection

يمكنك استيراد ملف `Farhty_Admin_API.postman_collection.json` في Postman لتجربة جميع الـ API endpoints.

### خطوات الاستيراد:

1. افتح Postman
2. اضغط على **Import**
3. اختر الملف `Farhty_Admin_API.postman_collection.json`
4. غير الـ `base_url` إذا لزم الأمر
5. ابدأ بـ **Login** للحصول على Token
6. Token سيتم حفظه تلقائياً واستخدامه في باقي الطلبات

---

## 📝 ملاحظات مهمة

1. **الـ Token** يجب إرساله في كل request (ما عدا Login) في الـ Header:

    ```
    Authorization: Bearer {your_token}
    ```

2. **الـ Pagination** في قائمة المستخدمين تعرض 15 مستخدم في الصفحة

3. **لا يمكن للأدمن حذف نفسه** لمنع إغلاق الحساب عن طريق الخطأ

4. **كلمة السر** عند التحديث يتم تشفيرها تلقائياً

---

## 📞 الدعم

إذا واجهتك أي مشكلة، تأكد من:

-   ✅ تشغيل الـ Migration: `php artisan migrate`
-   ✅ تشغيل الـ Seeder: `php artisan db:seed --class=AdminSeeder`
-   ✅ إرسال الـ Headers الصحيحة
-   ✅ استخدام Token صالح
