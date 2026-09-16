<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | الأسطر التالية تحتوي على رسائل التحقق الافتراضية المستخدمة في النظام.
    |
    */

    'accepted'   => 'يجب الموافقة على :attribute.',
    'active_url' => ':attribute ليس رابطًا صالحًا.',
    'after' => 'يجب أن يكون :attribute تاريخًا بعد :date.',
    'after_or_equal' => 'يجب أن يكون :attribute تاريخًا بعد أو يساوي :date.',
    'alpha' => 'يجب أن يحتوي :attribute على أحرف فقط.',
    'alpha_dash' => 'يجب أن يحتوي :attribute على أحرف وأرقام وشرطات (_) أو (-) فقط.',
    'alpha_num'  => 'يجب أن يحتوي :attribute على أحرف وأرقام فقط.',
    'array'  => 'يجب أن يكون :attribute مصفوفة.',
    'before' => 'يجب أن يكون :attribute تاريخًا قبل :date.',
    'before_or_equal' => 'يجب أن يكون :attribute تاريخًا قبل أو يساوي :date.',
    'between' => [
        'numeric' => 'يجب أن تكون قيمة :attribute بين :min و :max.',
        'file'    => 'يجب أن يكون حجم :attribute بين :min و :max كيلوبايت.',
        'string'  => 'يجب أن يكون طول :attribute بين :min و :max أحرف.',
        'array'   => 'يجب أن يحتوي :attribute على بين :min و :max عناصر.',
    ],
    'boolean'   => 'يجب أن تكون قيمة :attribute صحيحة أو خاطئة.',
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'date' => ':attribute ليس تاريخًا صالحًا.',
    'date_equals' => 'يجب أن يكون :attribute مساويًا للتاريخ :date.',
    'date_format' => 'تنسيق :attribute غير مطابق للصيغة :format.',
    'different' => 'يجب أن يكون :attribute و :other مختلفين.',
    'digits'    => 'يجب أن يتكون :attribute من :digits أرقام.',
    'digits_between' => 'يجب أن يكون :attribute بين :min و :max أرقام.',
    'dimensions' => 'أبعاد الصورة في :attribute غير صالحة.',
    'distinct'   => 'قيمة :attribute مكررة.',
    'email' => 'يجب أن يكون :attribute بريدًا إلكترونيًا صالحًا.',
    'ends_with' => 'يجب أن ينتهي :attribute بأحد القيم التالية: :values',
    'exists' => 'القيمة المحددة في :attribute غير صالحة.',
    'file'   => 'يجب أن يكون :attribute ملفًا.',
    'filled' => 'يجب أن يحتوي الحقل :attribute على قيمة.',
    'gt' => [
        'numeric' => 'يجب أن يكون :attribute أكبر من :value.',
        'file'   => 'يجب أن يكون حجم :attribute أكبر من :value كيلوبايت.',
        'string' => 'يجب أن يكون طول :attribute أكبر من :value أحرف.',
        'array'  => 'يجب أن يحتوي :attribute على أكثر من :value عناصر.',
    ],
    'gte' => [
        'numeric' => 'يجب أن يكون :attribute أكبر من أو يساوي :value.',
        'file'   => 'يجب أن يكون حجم :attribute أكبر من أو يساوي :value كيلوبايت.',
        'string' => 'يجب أن يكون طول :attribute أكبر من أو يساوي :value أحرف.',
        'array'  => 'يجب أن يحتوي :attribute على :value عناصر أو أكثر.',
    ],
    'image' => 'يجب أن يكون :attribute صورة.',
    'in'    => 'القيمة المحددة في :attribute غير صالحة.',
    'in_array' => 'الحقل :attribute غير موجود في :other.',
    'integer'  => 'يجب أن يكون :attribute عددًا صحيحًا.',
    'ip' => 'يجب أن يكون :attribute عنوان IP صالحًا.',
    'ipv4' => 'يجب أن يكون :attribute عنوان IPv4 صالحًا.',
    'ipv6' => 'يجب أن يكون :attribute عنوان IPv6 صالحًا.',
    'json' => 'يجب أن يكون :attribute نص JSON صالحًا.',
    'lt' => [
        'numeric' => 'يجب أن يكون :attribute أقل من :value.',
        'file'   => 'يجب أن يكون حجم :attribute أقل من :value كيلوبايت.',
        'string' => 'يجب أن يكون طول :attribute أقل من :value أحرف.',
        'array'  => 'يجب أن يحتوي :attribute على أقل من :value عناصر.',
    ],
    'lte' => [
        'numeric' => 'يجب أن يكون :attribute أقل من أو يساوي :value.',
        'file'    => 'يجب أن يكون حجم :attribute أقل من أو يساوي :value كيلوبايت.',
        'string'  => 'يجب أن يكون طول :attribute أقل من أو يساوي :value أحرف.',
        'array'   => 'يجب ألا يحتوي :attribute على أكثر من :value عناصر.',
    ],
    'max' => [
        'numeric' => 'يجب ألا تكون قيمة :attribute أكبر من :max.',
        'file'    => 'يجب ألا يتجاوز حجم :attribute :max كيلوبايت.',
        'string' => 'يجب ألا يتجاوز طول :attribute :max أحرف.',
        'array'  => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
    ],
    'mimes' => 'يجب أن يكون :attribute من نوع: :values.',
    'mimetypes' => 'يجب أن يكون :attribute من نوع: :values.',
    'min' => [
        'numeric' => 'يجب ألا تقل قيمة :attribute عن :min.',
        'file'    => 'يجب ألا يقل حجم :attribute عن :min كيلوبايت.',
        'string'  => 'يجب ألا يقل طول :attribute عن :min أحرف.',
        'array'   => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
    ],
    'not_in' => 'القيمة المحددة في :attribute غير صالحة.',
    'not_regex' => 'تنسيق :attribute غير صالح.',
    'numeric' => 'يجب أن يكون :attribute رقمًا.',
    'present' => 'يجب أن يكون الحقل :attribute موجودًا.',
    'regex'    => 'تنسيق :attribute غير صالح.',
    'required' => 'الحقل :attribute مطلوب.',
    'required_if'     => 'الحقل :attribute مطلوب عندما تكون :other تساوي :value.',
    'required_unless' => 'الحقل :attribute مطلوب ما لم تكن :other ضمن :values.',
    'required_with'   => 'الحقل :attribute مطلوب عند وجود :values.',
    'required_with_all' => 'الحقل :attribute مطلوب عند وجود جميع :values.',
    'required_without'  => 'الحقل :attribute مطلوب عند عدم وجود :values.',
    'required_without_all' => 'الحقل :attribute مطلوب عند عدم وجود أي من :values.',
    'same' => 'يجب أن يتطابق :attribute مع :other.',
    'size' => [
        'numeric' => 'يجب أن تكون قيمة :attribute مساوية لـ :size.',
        'file'    => 'يجب أن يكون حجم :attribute :size كيلوبايت.',
        'string'  => 'يجب أن يكون طول :attribute :size أحرف.',
        'array'   => 'يجب أن يحتوي :attribute على :size عناصر.',
    ],
    'starts_with' => 'يجب أن يبدأ :attribute بأحد القيم التالية: :values',
    'string'   => 'يجب أن يكون :attribute نصًا.',
    'timezone' => 'يجب أن يكون :attribute نطاقًا زمنيًا صالحًا.',
    'unique'   => 'قيمة :attribute مستخدمة بالفعل.',
    'uploaded' => 'فشل رفع :attribute.',
    'url'  => 'تنسيق :attribute غير صالح.',
    'uuid' => 'يجب أن يكون :attribute معرف UUID صالحًا.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'رسالة مخصصة',
        ],
    ],

    'attributes' => [],

    'empty_template' => 'حقل "القالب" فارغ!',
    'empty_name'  => 'حقل "الاسم" فارغ!',
    'empty_email' => 'يرجى إدخال البريد الإلكتروني!',
    'wrong_email' => 'البريد الإلكتروني غير صحيح!',
];
