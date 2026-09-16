<?php

return [

    'accepted'   => ':attribute को स्वीकार करना आवश्यक है।',
    'active_url' => ':attribute एक वैध URL नहीं है।',
    'after' => ':attribute की तिथि :date के बाद होनी चाहिए।',
    'after_or_equal' => ':attribute की तिथि :date के बाद या उसके बराबर होनी चाहिए।',
    'alpha' => ':attribute में केवल अक्षर ही हो सकते हैं।',
    'alpha_dash' => ':attribute में केवल अक्षर, संख्या, डैश और अंडरस्कोर हो सकते हैं।',
    'alpha_num'  => ':attribute में केवल अक्षर और संख्याएँ हो सकती हैं।',
    'array'  => ':attribute एक ऐरे होना चाहिए।',
    'before' => ':attribute की तिथि :date से पहले होनी चाहिए।',
    'before_or_equal' => ':attribute की तिथि :date से पहले या उसके बराबर होनी चाहिए।',

    'between' => [
        'numeric' => ':attribute :min और :max के बीच होना चाहिए।',
        'file'    => ':attribute :min और :max किलोबाइट के बीच होना चाहिए।',
        'string'  => ':attribute :min और :max अक्षरों के बीच होना चाहिए।',
        'array'   => ':attribute में :min से :max तक आइटम होने चाहिए।',
    ],

    'boolean'   => ':attribute फ़ील्ड सत्य या असत्य होना चाहिए।',
    'confirmed' => ':attribute की पुष्टि मेल नहीं खाती।',
    'date' => ':attribute एक वैध तिथि नहीं है।',
    'date_equals' => ':attribute की तिथि :date के बराबर होनी चाहिए।',
    'date_format' => ':attribute का प्रारूप :format से मेल नहीं खाता।',
    'different' => ':attribute और :other अलग होने चाहिए।',
    'digits'    => ':attribute :digits अंकों का होना चाहिए।',
    'digits_between' => ':attribute :min और :max अंकों के बीच होना चाहिए।',
    'dimensions' => ':attribute की छवि के आयाम अमान्य हैं।',
    'distinct'   => ':attribute फ़ील्ड में डुप्लिकेट मान है।',
    'email' => ':attribute एक वैध ईमेल पता होना चाहिए।',
    'ends_with' => ':attribute निम्न में से किसी एक से समाप्त होना चाहिए: :values',
    'exists' => 'चयनित :attribute अमान्य है।',
    'file'   => ':attribute एक फ़ाइल होना चाहिए।',
    'filled' => ':attribute फ़ील्ड में मान होना चाहिए।',

    'gt' => [
        'numeric' => ':attribute :value से बड़ा होना चाहिए।',
        'file'   => ':attribute :value किलोबाइट से बड़ा होना चाहिए।',
        'string' => ':attribute :value अक्षरों से अधिक होना चाहिए।',
        'array'  => ':attribute में :value से अधिक आइटम होने चाहिए।',
    ],

    'gte' => [
        'numeric' => ':attribute :value से बड़ा या बराबर होना चाहिए।',
        'file'   => ':attribute :value किलोबाइट से बड़ा या बराबर होना चाहिए।',
        'string' => ':attribute :value अक्षरों से बड़ा या बराबर होना चाहिए।',
        'array'  => ':attribute में कम से कम :value आइटम होने चाहिए।',
    ],

    'image' => ':attribute एक छवि होनी चाहिए।',
    'in'    => 'चयनित :attribute अमान्य है।',
    'in_array' => ':attribute फ़ील्ड :other में मौजूद नहीं है।',
    'integer'  => ':attribute एक पूर्णांक होना चाहिए।',
    'ip' => ':attribute एक वैध IP पता होना चाहिए।',
    'ipv4' => ':attribute एक वैध IPv4 पता होना चाहिए।',
    'ipv6' => ':attribute एक वैध IPv6 पता होना चाहिए।',
    'json' => ':attribute एक वैध JSON स्ट्रिंग होना चाहिए।',

    'lt' => [
        'numeric' => ':attribute :value से कम होना चाहिए।',
        'file'   => ':attribute :value किलोबाइट से कम होना चाहिए।',
        'string' => ':attribute :value अक्षरों से कम होना चाहिए।',
        'array'  => ':attribute में :value से कम आइटम होने चाहिए।',
    ],

    'lte' => [
        'numeric' => ':attribute :value से कम या बराबर होना चाहिए।',
        'file'    => ':attribute :value किलोबाइट से कम या बराबर होना चाहिए।',
        'string'  => ':attribute :value अक्षरों से कम या बराबर होना चाहिए।',
        'array'   => ':attribute में :value से अधिक आइटम नहीं होने चाहिए।',
    ],

    'max' => [
        'numeric' => ':attribute :max से अधिक नहीं होना चाहिए।',
        'file'    => ':attribute :max किलोबाइट से अधिक नहीं होना चाहिए।',
        'string' => ':attribute :max अक्षरों से अधिक नहीं होना चाहिए।',
        'array'  => ':attribute में :max से अधिक आइटम नहीं होने चाहिए।',
    ],

    'mimes' => ':attribute निम्न प्रकार की फ़ाइल होनी चाहिए: :values।',
    'mimetypes' => ':attribute निम्न प्रकार की फ़ाइल होनी चाहिए: :values।',

    'min' => [
        'numeric' => ':attribute कम से कम :min होना चाहिए।',
        'file'    => ':attribute कम से कम :min किलोबाइट होना चाहिए।',
        'string'  => ':attribute कम से कम :min अक्षरों का होना चाहिए।',
        'array'   => ':attribute में कम से कम :min आइटम होने चाहिए।',
    ],

    'not_in' => 'चयनित :attribute अमान्य है।',
    'not_regex' => ':attribute का प्रारूप अमान्य है।',
    'numeric' => ':attribute एक संख्या होना चाहिए।',
    'present' => ':attribute फ़ील्ड मौजूद होना चाहिए।',
    'regex'    => ':attribute का प्रारूप अमान्य है।',
    'required' => ':attribute फ़ील्ड आवश्यक है।',
    'required_if'     => ':attribute फ़ील्ड आवश्यक है जब :other का मान :value हो।',
    'required_unless' => ':attribute फ़ील्ड आवश्यक है जब तक :other :values में न हो।',
    'required_with'   => ':attribute फ़ील्ड आवश्यक है जब :values मौजूद हो।',
    'required_with_all' => ':attribute फ़ील्ड आवश्यक है जब :values सभी मौजूद हों।',
    'required_without'  => ':attribute फ़ील्ड आवश्यक है जब :values मौजूद न हो।',
    'required_without_all' => ':attribute फ़ील्ड आवश्यक है जब :values में से कोई भी मौजूद न हो।',
    'same' => ':attribute और :other मेल खाने चाहिए।',

    'size' => [
        'numeric' => ':attribute :size होना चाहिए।',
        'file'    => ':attribute :size किलोबाइट होना चाहिए।',
        'string'  => ':attribute :size अक्षरों का होना चाहिए।',
        'array'   => ':attribute में :size आइटम होने चाहिए।',
    ],

    'starts_with' => ':attribute निम्न में से किसी एक से शुरू होना चाहिए: :values',
    'string'   => ':attribute एक स्ट्रिंग होना चाहिए।',
    'timezone' => ':attribute एक वैध टाइमज़ोन होना चाहिए।',
    'unique'   => ':attribute पहले से ही उपयोग में है।',
    'uploaded' => ':attribute अपलोड करने में विफल रहा।',
    'url'  => ':attribute का प्रारूप अमान्य है।',
    'uuid' => ':attribute एक वैध UUID होना चाहिए।',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    'attributes' => [],

    'empty_template' => '"Template" फ़ील्ड खाली है!',
    'empty_name'  => '"Name" फ़ील्ड खाली है!',
    'empty_email' => 'ईमेल दर्ज करें!',
    'wrong_email' => 'ईमेल गलत है!',
];
