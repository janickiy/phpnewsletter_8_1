<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Die folgenden Sprachzeilen enthalten die Standard-Fehlermeldungen
    | des Validators.
    |
    */

    'accepted'   => ':attribute muss akzeptiert werden.',
    'active_url' => ':attribute ist keine gültige URL.',
    'after' => ':attribute muss ein Datum nach dem :date sein.',
    'after_or_equal' => ':attribute muss ein Datum nach oder gleich :date sein.',
    'alpha' => ':attribute darf nur Buchstaben enthalten.',
    'alpha_dash' => ':attribute darf nur Buchstaben, Zahlen, Bindestriche und Unterstriche enthalten.',
    'alpha_num'  => ':attribute darf nur Buchstaben und Zahlen enthalten.',
    'array'  => ':attribute muss ein Array sein.',
    'before' => ':attribute muss ein Datum vor dem :date sein.',
    'before_or_equal' => ':attribute muss ein Datum vor oder gleich :date sein.',
    'between' => [
        'numeric' => ':attribute muss zwischen :min und :max liegen.',
        'file'    => ':attribute muss zwischen :min und :max Kilobyte groß sein.',
        'string'  => ':attribute muss zwischen :min und :max Zeichen lang sein.',
        'array'   => ':attribute muss zwischen :min und :max Elemente enthalten.',
    ],
    'boolean'   => ':attribute muss wahr oder falsch sein.',
    'confirmed' => ':attribute stimmt nicht mit der Bestätigung überein.',
    'date' => ':attribute ist kein gültiges Datum.',
    'date_equals' => ':attribute muss ein Datum gleich :date sein.',
    'date_format' => ':attribute entspricht nicht dem Format :format.',
    'different' => ':attribute und :other müssen unterschiedlich sein.',
    'digits'    => ':attribute muss :digits Ziffern enthalten.',
    'digits_between' => ':attribute muss zwischen :min und :max Ziffern enthalten.',
    'dimensions' => ':attribute hat ungültige Bildabmessungen.',
    'distinct'   => ':attribute enthält einen doppelten Wert.',
    'email' => ':attribute muss eine gültige E-Mail-Adresse sein.',
    'ends_with' => ':attribute muss mit einem der folgenden Werte enden: :values',
    'exists' => 'Der ausgewählte Wert für :attribute ist ungültig.',
    'file'   => ':attribute muss eine Datei sein.',
    'filled' => ':attribute muss einen Wert enthalten.',
    'gt' => [
        'numeric' => ':attribute muss größer als :value sein.',
        'file'   => ':attribute muss größer als :value Kilobyte sein.',
        'string' => ':attribute muss länger als :value Zeichen sein.',
        'array'  => ':attribute muss mehr als :value Elemente enthalten.',
    ],
    'gte' => [
        'numeric' => ':attribute muss größer oder gleich :value sein.',
        'file'   => ':attribute muss größer oder gleich :value Kilobyte sein.',
        'string' => ':attribute muss länger oder gleich :value Zeichen sein.',
        'array'  => ':attribute muss :value oder mehr Elemente enthalten.',
    ],
    'image' => ':attribute muss ein Bild sein.',
    'in'    => 'Der ausgewählte Wert für :attribute ist ungültig.',
    'in_array' => ':attribute ist nicht in :other vorhanden.',
    'integer'  => ':attribute muss eine ganze Zahl sein.',
    'ip' => ':attribute muss eine gültige IP-Adresse sein.',
    'ipv4' => ':attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6' => ':attribute muss eine gültige IPv6-Adresse sein.',
    'json' => ':attribute muss ein gültiger JSON-String sein.',
    'lt' => [
        'numeric' => ':attribute muss kleiner als :value sein.',
        'file'   => ':attribute muss kleiner als :value Kilobyte sein.',
        'string' => ':attribute muss kürzer als :value Zeichen sein.',
        'array'  => ':attribute muss weniger als :value Elemente enthalten.',
    ],
    'lte' => [
        'numeric' => ':attribute muss kleiner oder gleich :value sein.',
        'file'    => ':attribute muss kleiner oder gleich :value Kilobyte sein.',
        'string'  => ':attribute muss kürzer oder gleich :value Zeichen sein.',
        'array'   => ':attribute darf nicht mehr als :value Elemente enthalten.',
    ],
    'max' => [
        'numeric' => ':attribute darf nicht größer als :max sein.',
        'file'    => ':attribute darf nicht größer als :max Kilobyte sein.',
        'string' => ':attribute darf nicht länger als :max Zeichen sein.',
        'array'  => ':attribute darf nicht mehr als :max Elemente enthalten.',
    ],
    'mimes' => ':attribute muss eine Datei vom Typ :values sein.',
    'mimetypes' => ':attribute muss eine Datei vom Typ :values sein.',
    'min' => [
        'numeric' => ':attribute muss mindestens :min sein.',
        'file'    => ':attribute muss mindestens :min Kilobyte groß sein.',
        'string'  => ':attribute muss mindestens :min Zeichen lang sein.',
        'array'   => ':attribute muss mindestens :min Elemente enthalten.',
    ],
    'not_in' => 'Der ausgewählte Wert für :attribute ist ungültig.',
    'not_regex' => 'Das Format von :attribute ist ungültig.',
    'numeric' => ':attribute muss eine Zahl sein.',
    'present' => ':attribute muss vorhanden sein.',
    'regex'    => 'Das Format von :attribute ist ungültig.',
    'required' => ':attribute ist ein Pflichtfeld.',
    'required_if'     => ':attribute ist erforderlich, wenn :other gleich :value ist.',
    'required_unless' => ':attribute ist erforderlich, außer wenn :other in :values enthalten ist.',
    'required_with'   => ':attribute ist erforderlich, wenn :values vorhanden ist.',
    'required_with_all' => ':attribute ist erforderlich, wenn alle :values vorhanden sind.',
    'required_without'  => ':attribute ist erforderlich, wenn :values nicht vorhanden ist.',
    'required_without_all' => ':attribute ist erforderlich, wenn keine der :values vorhanden sind.',
    'same' => ':attribute und :other müssen übereinstimmen.',
    'size' => [
        'numeric' => ':attribute muss :size sein.',
        'file'    => ':attribute muss :size Kilobyte groß sein.',
        'string'  => ':attribute muss :size Zeichen lang sein.',
        'array'   => ':attribute muss :size Elemente enthalten.',
    ],
    'starts_with' => ':attribute muss mit einem der folgenden Werte beginnen: :values',
    'string'   => ':attribute muss eine Zeichenkette sein.',
    'timezone' => ':attribute muss eine gültige Zeitzone sein.',
    'unique'   => ':attribute wurde bereits verwendet.',
    'uploaded' => ':attribute konnte nicht hochgeladen werden.',
    'url'  => 'Das Format von :attribute ist ungültig.',
    'uuid' => ':attribute muss eine gültige UUID sein.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'Benutzerdefinierte Meldung',
        ],
    ],

    'attributes' => [],

    'empty_template' => 'Das Feld "Vorlage" ist leer!',
    'empty_name'  => 'Das Feld "Name" ist leer!',
    'empty_email' => 'Bitte E-Mail eingeben!',
    'wrong_email' => 'Die E-Mail ist ungültig!',
];
