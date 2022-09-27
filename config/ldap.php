<?php

return [
    'ldap_host' => env('LDAP_HOST'),
    'ldap_guid_column' => env('LDAP_GUID_COLUMN', 'objectguid'),
    'ldap_mail_column' => env('LDAP_MAIL_COLUMN', 'mail'),
    'ldap_base_dn' => env('LDAP_BASE_DN'),
    'ldap_domain' => env('LDAP_DOMAIN'),
    'ldap_status' => env('LDAP_STATUS'),
];
