<?php

namespace App\Providers;

use App\Models\Activation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Import;
use App\Models\Lote;
use App\Models\LoteDetail;
use App\Models\Menu;
use App\Models\Notification;
use App\Models\NotificationTarget;
use App\Models\PointOfSale;
use App\Models\Portability;
use App\Models\Position;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\ProductMovement;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\User;
use App\Models\Visit;
use App\Observers\GlobalModelObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Lista EXPLÍCITA de modelos a observar
     * Máximo rendimiento - sin reflection, sin filesystem
     */
    protected $observableModels = [
        Activation::class,
        Department::class,
        Employee::class,
        Import::class,
        Lote::class,
        LoteDetail::class,
        Menu::class,
        Notification::class,
        NotificationTarget::class,
        PointOfSale::class,
        Portability::class,
        Position::class,
        Product::class,
        ProductDetail::class,
        ProductMovement::class,
        ProductType::class,
        Role::class,
        User::class,
        Visit::class,
        // Agregar manualmente SOLO los modelos que necesitan auditoría
    ];
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar el observer global para todos los modelos
        // Model::observe(GlobalModelObserver::class);
        // Department::observe(GlobalModelObserver::class);

        foreach ($this->observableModels as $model) {
            $model::observe(GlobalModelObserver::class);
        }

        // Log solo en desarrollo
        // if (app()->isLocal()) {
        //     \Log::info('Observers registrados para: ' . count($this->observableModels) . ' modelos');
        // }
    }
}
