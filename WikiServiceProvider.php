<?php

namespace App\Modules\Wiki;

use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

/**
 * The wiki packaged on its own: same folder layout as a module in app/Modules,
 * plus this provider. bootAppModule() loads the migrations, views, routes, the
 * "wiki::" Livewire components and Lang/; config.php is merged as config('wiki')
 * with its permissions. Copied to app/Modules/Wiki it keeps working: the
 * provider steps aside and the app's ModuleServiceProvider takes over.
 */
class WikiServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Wiki';

    protected ?string $modulePath = __DIR__;

    public function boot(): void
    {
        if ($this->isEjected()) {
            return;
        }

        $this->bootAppModule('wiki');
    }
}
