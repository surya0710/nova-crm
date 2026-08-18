<?php

return [
    'pricing_modes' => [
        'exclusive' => 'Tax exclusive',
        'inclusive' => 'Tax inclusive',
    ],

    'treatments' => [
        'standard' => 'Standard',
        'exempt' => 'Exempt',
        'zero_rated' => 'Zero-rated',
    ],

    'gst_registration_types' => [
        'regular' => 'Regular',
        'composition' => 'Composition',
        'unregistered' => 'Unregistered',
        'sez' => 'SEZ',
        'casual' => 'Casual taxable person',
        'nrtp' => 'Non-resident taxable person',
        'oidar' => 'OIDAR',
    ],

    'tax_registration_statuses' => [
        'registered' => 'Registered',
        'unregistered' => 'Unregistered',
        'cancelled' => 'Cancelled',
        'suspended' => 'Suspended',
    ],

    'tax_exemption_statuses' => [
        'not_exempt' => 'Not exempt',
        'exempt' => 'Exempt',
    ],

    'tax_preferences' => [
        'exclusive' => 'Tax exclusive',
        'inclusive' => 'Tax inclusive',
        'exempt' => 'Exempt',
        'zero_rated' => 'Zero-rated',
    ],

    /*
    | GSTIN state codes. `utgst` marks union territories that levy UTGST
    | instead of SGST for intra-state supplies.
    */
    'states' => [
        '01' => ['name' => 'Jammu and Kashmir', 'utgst' => false],
        '02' => ['name' => 'Himachal Pradesh', 'utgst' => false],
        '03' => ['name' => 'Punjab', 'utgst' => false],
        '04' => ['name' => 'Chandigarh', 'utgst' => true],
        '05' => ['name' => 'Uttarakhand', 'utgst' => false],
        '06' => ['name' => 'Haryana', 'utgst' => false],
        '07' => ['name' => 'Delhi', 'utgst' => false],
        '08' => ['name' => 'Rajasthan', 'utgst' => false],
        '09' => ['name' => 'Uttar Pradesh', 'utgst' => false],
        '10' => ['name' => 'Bihar', 'utgst' => false],
        '11' => ['name' => 'Sikkim', 'utgst' => false],
        '12' => ['name' => 'Arunachal Pradesh', 'utgst' => false],
        '13' => ['name' => 'Nagaland', 'utgst' => false],
        '14' => ['name' => 'Manipur', 'utgst' => false],
        '15' => ['name' => 'Mizoram', 'utgst' => false],
        '16' => ['name' => 'Tripura', 'utgst' => false],
        '17' => ['name' => 'Meghalaya', 'utgst' => false],
        '18' => ['name' => 'Assam', 'utgst' => false],
        '19' => ['name' => 'West Bengal', 'utgst' => false],
        '20' => ['name' => 'Jharkhand', 'utgst' => false],
        '21' => ['name' => 'Odisha', 'utgst' => false],
        '22' => ['name' => 'Chhattisgarh', 'utgst' => false],
        '23' => ['name' => 'Madhya Pradesh', 'utgst' => false],
        '24' => ['name' => 'Gujarat', 'utgst' => false],
        '26' => ['name' => 'Dadra and Nagar Haveli and Daman and Diu', 'utgst' => true],
        '27' => ['name' => 'Maharashtra', 'utgst' => false],
        '29' => ['name' => 'Karnataka', 'utgst' => false],
        '30' => ['name' => 'Goa', 'utgst' => false],
        '31' => ['name' => 'Lakshadweep', 'utgst' => true],
        '32' => ['name' => 'Kerala', 'utgst' => false],
        '33' => ['name' => 'Tamil Nadu', 'utgst' => false],
        '34' => ['name' => 'Puducherry', 'utgst' => false],
        '35' => ['name' => 'Andaman and Nicobar Islands', 'utgst' => true],
        '36' => ['name' => 'Telangana', 'utgst' => false],
        '37' => ['name' => 'Andhra Pradesh', 'utgst' => false],
        '38' => ['name' => 'Ladakh', 'utgst' => true],
        '97' => ['name' => 'Other Territory', 'utgst' => true],
        '99' => ['name' => 'Centre Jurisdiction', 'utgst' => false],
    ],

    'gstin_pattern' => '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
    'pan_pattern' => '/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/',
];
