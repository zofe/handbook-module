<?php

namespace App\Modules\Handbook;

use Zofe\Rapyd\Modules\RapydModuleServiceProvider;

/**
 * The handbook packaged on its own: same folder layout as a module in app/Modules,
 * plus this provider. bootAppModule() loads the migrations, views, routes, the
 * "handbook::" Livewire components and Lang/; config.php is merged as config('handbook')
 * with its permissions. Copied to app/Modules/Handbook it keeps working: the
 * provider steps aside and the app's ModuleServiceProvider takes over.
 */
class HandbookServiceProvider extends RapydModuleServiceProvider
{
    protected string $moduleName = 'Handbook';

    protected ?string $modulePath = __DIR__;

    public function boot(): void
    {
        if ($this->isEjected()) {
            return;
        }

        $this->bootAppModule('handbook');
    }
}
