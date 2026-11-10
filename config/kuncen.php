<?php

return [
    /*
     * Admin roles (admins.role) that may still change data in a closed
     * period (tutup buku), close a year and open it again. Comma separated.
     * Give the role to an account with:
     *   update admins set role = 'superadmin' where id = ...;
     */
    'period_unlock_roles' => array_filter(array_map('trim', explode(',', env('PERIOD_UNLOCK_ROLES', 'superadmin')))),

    /* Account that receives the profit of a closed year. Created when missing. */
    'retained_earnings_account' => ['code' => '3003', 'name' => 'Laba Ditahan'],
];
