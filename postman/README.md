# 📮 Postman Collection - دليل الاستخدام

## 📥 كيفية الاستيراد

### 1. استيراد Collection

1. افتح Postman
2. اضغط على **Import**
3. اختر ملف `Complete_API_Collection.postman_collection.json`
4. اضغط **Import**

### 2. استيراد Environment

1. في Postman، اضغط على **Import**
2. اختر ملف `Frhty_Local.postman_environment.json`
3. اضغط **Import**
4. من القائمة العلوية، اختر **Frhty - Local Environment**

---

## 📂 محتويات Collection

### 1️⃣ Authentication (7 endpoints)

-   ✅ Register - تسجيل مستخدم جديد
-   ✅ Verify Registration OTP - تفعيل الحساب
-   ✅ Login (Send OTP) - إرسال OTP
-   ✅ Verify OTP (Login) - تسجيل الدخول
-   ✅ Complete Profile - إكمال البروفايل
-   ✅ Get Current User - بيانات المستخدم
-   ✅ Logout - تسجيل الخروج

### 2️⃣ Celebrities (Public) (6 endpoints)

-   ✅ Get All Celebrities - قائمة المشاهير
-   ✅ Search Celebrities - البحث
-   ✅ Get Celebrity Profile (Public) - بروفايل عام
-   ✅ Get Celebrity Profile (Authenticated) - بروفايل مع حالة المتابعة
-   ✅ Get Celebrity Followers - المتابعين
-   ✅ Get Celebrity Following - المتابَعين

### 3️⃣ Follow System (3 endpoints)

-   ✅ Follow Celebrity - متابعة
-   ✅ Unfollow Celebrity - إلغاء متابعة
-   ✅ Get My Following List - قائمة متابعاتي

### 4️⃣ Social Accounts (8 endpoints)

-   ✅ Get Available Platforms - المنصات المتاحة
-   ✅ Get All My Social Accounts - جميع حساباتي
-   ✅ Add Social Account (Full) - إضافة حساب كامل
-   ✅ Add Social Account (Platform Only) - منصة فقط
-   ✅ Add Social Account (With URL Only) - مع رابط فقط
-   ✅ Get Single Social Account - عرض حساب
-   ✅ Update Social Account - تحديث
-   ✅ Delete Social Account - حذف

**المجموع: 24 endpoint**

---

## 🔧 المتغيرات (Variables)

| المتغير        | القيمة الافتراضية       | الوصف                       |
| -------------- | ----------------------- | --------------------------- |
| `base_url`     | `http://localhost:8000` | رابط الـ API                |
| `auth_token`   | (فارغ)                  | يتم حفظه تلقائياً بعد Login |
| `phone`        | `0512345678`            | رقم الهاتف للاختبار         |
| `celebrity_id` | `1`                     | معرف المشهور للاختبار       |
| `account_id`   | `1`                     | معرف الحساب للاختبار        |

---

## 🚀 تدفق العمل المقترح

### للمتابع (Follower):

#### 1. التسجيل وتسجيل الدخول

```
1. Register → يحفظ phone تلقائياً
2. Verify Registration OTP → يحفظ auth_token تلقائياً
3. Complete Profile → إكمال البيانات
```

#### 2. البحث والمتابعة

```
4. Search Celebrities → البحث عن مشاهير
5. Get Celebrity Profile (Authenticated) → عرض البروفايل
6. Follow Celebrity → متابعة
7. Get My Following List → قائمة متابعاتي
```

### للمشهور (Celebrity):

#### 1. التسجيل

```
1. Register (مع role: "celebrity")
2. Verify Registration OTP
3. Complete Profile
```

#### 2. إضافة حسابات المنصات

```
4. Get Available Platforms → معرفة المنصات المتاحة
5. Add Social Account (Full) → إضافة سناب شات
6. Add Social Account (Full) → إضافة تيك توك
7. Add Social Account (Platform Only) → إضافة إنستجرام
8. Get All My Social Accounts → عرض جميع حساباتي
```

#### 3. مشاهدة الإحصائيات

```
9. Get Celebrity Profile → عرض بروفايلي
10. Get Celebrity Followers → متابعيني
```

---

## 🎯 ميزات خاصة في Collection

### 1. Auto-Save للـ Token

عند تسجيل الدخول بنجاح، يتم حفظ `auth_token` تلقائياً في Environment:

-   ✅ بعد Verify Registration OTP
-   ✅ بعد Verify OTP (Login)

### 2. Auto-Save للـ Phone

عند التسجيل أو Login، يتم حفظ `phone` تلقائياً:

-   ✅ بعد Register
-   ✅ بعد Login

### 3. Authorization التلقائي

جميع الـ endpoints المحمية تستخدم `{{auth_token}}` تلقائياً من Environment

### 4. أمثلة متعددة

لكل endpoint، توجد أمثلة مختلفة:

-   إضافة حساب كامل
-   إضافة منصة فقط
-   إضافة مع رابط فقط

---

## 📝 ملاحظات مهمة

### 1. OTP في Development

في بيئة التطوير، الـ OTP يُرجع في الـ Response:

```json
{
    "data": {
        "otp": "1234"
    }
}
```

استخدم `1234` للتحقق في جميع الحالات.

### 2. رقم الهاتف

يجب أن يكون رقم سعودي:

-   يبدأ بـ `05`
-   10 أرقام
-   مثال: `0512345678`

### 3. المنصات المدعومة

```
- snapchat
- tiktok
- youtube
- x
- instagram
- store
```

### 4. الأدوار (Roles)

```
- celebrity (مشهور)
- follower (متابع) - الافتراضي
```

---

## 🧪 اختبار سريع

### Test 1: تسجيل مستخدم جديد

```
1. افتح "Register"
2. غيّر رقم الهاتف إلى رقم جديد
3. اضغط Send
4. انسخ الـ OTP من الـ Response
5. افتح "Verify Registration OTP"
6. الصق الـ OTP
7. اضغط Send
8. ✅ تم حفظ auth_token تلقائياً
```

### Test 2: إضافة حساب سناب شات

```
1. تأكد من تسجيل الدخول (auth_token موجود)
2. افتح "Add Social Account (Full)"
3. اضغط Send
4. ✅ تم إضافة الحساب
```

### Test 3: متابعة مشهور

```
1. افتح "Get All Celebrities"
2. اضغط Send
3. انسخ id أحد المشاهير
4. ضعه في celebrity_id في Environment
5. افتح "Follow Celebrity"
6. اضغط Send
7. ✅ تمت المتابعة
```

---

## 🔍 استكشاف الأخطاء

### خطأ 401 Unauthorized

-   ✅ تأكد من وجود `auth_token` في Environment
-   ✅ تأكد من اختيار Environment الصحيح
-   ✅ سجل دخول مرة أخرى

### خطأ 422 Validation Error

-   ✅ تحقق من صيغة رقم الهاتف (05xxxxxxxx)
-   ✅ تحقق من أن المنصة من القائمة المدعومة
-   ✅ تحقق من عدم تكرار المنصة

### خطأ 404 Not Found

-   ✅ تحقق من `celebrity_id` أو `account_id`
-   ✅ تأكد من وجود البيانات في قاعدة البيانات

---

## 📚 موارد إضافية

-   [Social Accounts API Documentation](../docs/SOCIAL_ACCOUNTS_API.md)
-   [Celebrity Follow System API Documentation](../docs/CELEBRITY_FOLLOW_API.md)

---

## ✅ Checklist

قبل البدء، تأكد من:

-   [ ] استيراد Collection
-   [ ] استيراد Environment
-   [ ] اختيار Environment من القائمة العلوية
-   [ ] تشغيل السيرفر (`php artisan serve`)
-   [ ] تعديل `base_url` إذا لزم الأمر

---

**جاهز للاستخدام! 🚀**
