<?php

/*
|--------------------------------------------------------------------------
| Wiki module
|--------------------------------------------------------------------------
| layout / menu: like every rapyd-admin module.
| permissions: "view wiki" reads every collection, "edit wiki" writes them
| (collections, documents, restoring a revision). Merged into
| auth.permissions / auth.role_permissions: AuthSeeder creates them.
*/
return [
    'layout'              => 'layout::admin',
    'menu_admin'          => 'wiki::menu',
    'menu_admin_position' => 20,

    'permissions' => ['view wiki', 'edit wiki'],
    'role_permissions' => [
        'operator' => ['view wiki', 'edit wiki'],
    ],

    // How many revisions a document keeps; the oldest go first. 0 = all of them.
    'revisions' => (int) env('WIKI_REVISIONS', 50),
];
