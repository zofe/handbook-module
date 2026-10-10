<?php

/*
|--------------------------------------------------------------------------
| Handbook module
|--------------------------------------------------------------------------
| layout / menu: like every rapyd-admin module.
| permissions: "view handbook" reads every collection, "edit handbook" writes them
| (collections, documents, restoring a revision). Merged into
| auth.permissions / auth.role_permissions: AuthSeeder creates them.
*/
return [
    'layout'              => 'layout::admin',
    'menu_admin'          => 'handbook::menu',
    'menu_admin_position' => 20,

    'permissions' => ['view handbook', 'edit handbook'],
    'role_permissions' => [
        'operator' => ['view handbook', 'edit handbook'],
    ],

    // How many revisions a document keeps; the oldest go first. 0 = all of them.
    'revisions' => (int) env('HANDBOOK_REVISIONS', 50),
];
