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

    // The editor saves at every pause: the saves of one person within these minutes are one
    // revision of the history, like Outline does. 0 = a revision per save.
    'revision_window' => (int) env('HANDBOOK_REVISION_WINDOW', 10),
];
