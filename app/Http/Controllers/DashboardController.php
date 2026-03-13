<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Lote;
use App\Models\LoteDetail;
use App\Models\ObjResponse;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Models\ProductMovement;
use App\Models\Visit;
use App\Models\VW_LatestProductMovements;
use App\Models\VW_User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class DashboardController extends Controller
{
   public function getDashboardStats(Request $request, Response $response)
   {
      $response->data = ObjResponse::DefaultResponse();
      try {
         $auth = Auth::user();
         $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',

            'seller_id' => 'nullable|array',
            // 'seller_id.*' => 'exists:employees,id',
            'folio' => 'nullable|string',

            'start_date_pre_activation' => 'nullable|date',
            'end_date_pre_activation' => 'nullable|date|after_or_equal:start_date_pre_activation',

            'folio' => 'nullable|string',

            'import_name' => 'nullable|string',

            'location_status' => 'nullable|in:Stock,Asignado,Distribuido',
            'activation_status' => 'nullable|in:Virgen,Pre-activado,Activado,Portado,Caducado',

            'product_type_id' => 'nullable|exists:product_types,id',

            'start_date_in_system' => 'nullable|date',
            'end_date_in_system' => 'nullable|date|after_or_equal:start_date_in_system',

            'search' => 'nullable|string|max:100',

            'pos_id' => 'nullable|exists:points_of_sale,id',
         ]);


         // $query = ProductMovement::query()
         $query = VW_LatestProductMovements::query()
            ->applyFilters($filters)
            ->orderBy('executed_at', 'asc')
            ->orderBy('product_id', 'asc');

         // Log::info($query->toSql());
         // Log::info($query->getBindings());

         // $list = (clone $query)->get();

         // Log::info("filtros" . json_encode($filters, true));
         // Ejecutar todas las consultas en paralelo
         $results = DB::transaction(function () use ($query,  $filters) {
            return [
               // 'list' => $list,
               'stats' => $this->getGeneralStats($query, $filters),
               // 'ported_products' => $this->getPortedProductsWithDetails($filters),
               'sellers_performance' => $this->getSellersDashboard($query, $filters),
               // 'movements_per_day' => $this->getMovementsPerDay($query, $filters),
               'points_of_sale' => $this->getPointsOfSaleWithInventory($filters),
               // 'portability_by_month' => $this->getPortabilityByMonth($filters),
               'top_sellers' => $this->getTopSellers($query, $filters),
               // 'status_distribution' => $this->getStatusDistribution($filters),
               // 'top_products' => $this->getTopProducts($filters),
               // 'visits_summary' => $this->getVisitsSummary($filters),
               // 'ported_products' => $this->getPortedProducts($filters),
               // 'get_portability_by_seller_report' => $this->getPortabilityBySellerReport($filters),
            ];
         });

         $response->data = ObjResponse::SuccessResponse();
         $response->data["message"] = 'Peticion satisfactoria | stats.';
         $response->data["result"] = $results;
      } catch (\Exception $ex) {
         $msg = "DashboardController ~ getDashboardStats ~ Hubo un error -> " . $ex->getMessage();
         Log::error($msg);
         Log::error('Stack Trace: ' . $ex->getTraceAsString());
         $response->data = ObjResponse::CatchResponse($msg);
      }

      return response()->json($response, $response->data["status_code"]);
   }

   private function getPortedProductsWithDetails(array $filters)
   {
      return Product::select([
         'products.id',
         'products.celular',
         'products.iccid',
         'products.imei',
         'products.producto',
         'products.fecha',
         'products.estatus',
         'products.activation_status',
         'products.location_status',
         'products.modelo',
         'products.marca',
         'products.tipo_sim',
         'products.updated_at as ported_date',
      ])
         ->with(['loteDetails.lote.seller'])
         ->where('products.activation_status', 'Portado')
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('products.updated_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->when(isset($filters['seller_id']) && count($filters['seller_id']) > 0, function ($q) use ($filters) {
            $q->whereHas('loteDetails.lote', function ($subQuery) use ($filters) {
               $subQuery->whereIn('seller_id', $filters['seller_id']);
            });
         })
         ->when(isset($filters['search']), function ($q) use ($filters) {
            $q->where(function ($query) use ($filters) {
               $query->where('celular', 'like', "%{$filters['search']}%")
                  ->orWhere('iccid', 'like', "%{$filters['search']}%")
                  ->orWhere('imei', 'like', "%{$filters['search']}%");
            });
         })
         ->orderBy('products.updated_at', 'desc')
         ->limit(100) // Limitar para no sobrecargar
         ->get()
         ->map(function ($product) {
            $loteDetail = $product->loteDetails->first();
            $seller = $loteDetail?->lote?->seller;
            // $personalInfo = $seller?->personalInfo;

            return [
               'product_id' => $product->id,
               'celular' => $product->celular,
               'iccid' => $product->iccid,
               'imei' => $product->imei,
               'producto' => $product->producto,
               'estatus' => $product->estatus,
               'activation_status' => $product->activation_status,
               'location_status' => $product->location_status,
               'modelo' => $product->modelo,
               'marca' => $product->marca,
               'tipo_sim' => $product->tipo_sim,
               'ported_date' => $product->ported_date,

               // Información del lote
               'lote_id' => $loteDetail?->lote_id,
               'lote_name' => $loteDetail?->lote?->lote,
               'folio' => $loteDetail?->lote?->folio,
               'preactivation_date' => $loteDetail?->lote?->preactivation_date,

               // Información del vendedor
               'seller_id' => $seller?->id,
               // 'seller_name' => $personalInfo?->full_name ?? 'No asignado',
               'seller_pin_color' => $seller?->pin_color ?? '#ccc',
               // 'seller_cellphone' => $personalInfo?->cellphone,
               // 'seller_position' => $personalInfo?->position,

               // Distribución si aplica
               'distributed_to' => $this->getDistributionPoint($product),
            ];
         });
   }

   private function getMovementsPerDay($query, array $filters)
   {
      try {
         $registers = (clone $query)->get();

         // Determinar rango de fechas (preferir filtros; si no vienen, usar primer/último registro)
         $startStr = isset($filters['start_date']) && $filters['start_date']
            ? $filters['start_date']
            : (count($registers) > 0 ? ($registers->first()->executed_at ?? $registers->first()['executed_at']) : null);

         $endStr = isset($filters['end_date']) && $filters['end_date']
            ? $filters['end_date']
            : (count($registers) > 0 ? ($registers->last()->executed_at ?? $registers->last()['executed_at']) : null);

         if (!$startStr || !$endStr) {
            return [
               'dates' => [],
               'assigned' => [],
               'distributed' => [],
               'activated' => [],
               'ported' => [],
            ];
         }

         try {
            $startDate = new \DateTime(substr($startStr, 0, 10));
            $endDate = new \DateTime(substr($endStr, 0, 10));
         } catch (\Exception $e) {
            return [
               'dates' => [],
               'assigned' => [],
               'distributed' => [],
               'activated' => [],
               'ported' => [],
            ];
         }

         // Asegurar orden correcto
         if ($startDate > $endDate) {
            $tmp = $startDate;
            $startDate = $endDate;
            $endDate = $tmp;
         }

         // Construir array de fechas (Y-m-d) sin usar Carbon
         $dates = [];
         $current = clone $startDate;
         while ($current <= $endDate) {
            $dates[] = $current->format('Y-m-d');
            $current->modify('+1 day');
         }

         // Helper para obtener conteos agrupados por día para un destino dado
         $getDailyCounts = function ($destination) use ($query, $filters) {
            $builder = (clone $query)->when(
               isset($filters['start_date']) && isset($filters['end_date']),
               function ($q) use ($filters) {
                  $q->whereBetween('executed_at', [$filters['start_date'], $filters['end_date']]);
               }
            );

            $rows = $builder->where('destination', $destination)
               ->selectRaw("DATE(executed_at) as day, COUNT(*) as count")
               ->groupBy('day')
               ->get()
               ->pluck('count', 'day')
               ->toArray();

            return $rows;
         };

         $assignedRows = $getDailyCounts('Asignado');
         $distributedRows = $getDailyCounts('Distribuido');
         $activatedRows = $getDailyCounts('Activado');
         $portedRows = $getDailyCounts('Portado');

         // Rellenar todas las fechas del rango con 0 cuando no existan registros
         $assigned = [];
         $distributed = [];
         $activated = [];
         $ported = [];

         foreach ($dates as $d) {
            $assigned[$d] = isset($assignedRows[$d]) ? (int) $assignedRows[$d] : 0;
            $distributed[$d] = isset($distributedRows[$d]) ? (int) $distributedRows[$d] : 0;
            $activated[$d] = isset($activatedRows[$d]) ? (int) $activatedRows[$d] : 0;
            $ported[$d] = isset($portedRows[$d]) ? (int) $portedRows[$d] : 0;
         }

         return [
            'dates' => $dates,
            'assigned' => $assigned,
            'distributed' => $distributed,
            'activated' => $activated,
            'ported' => $ported,
         ];
      } catch (\Exception $ex) {
         $msg = "DashboardController ~ getMovementsPerDay ~ Hubo un error -> " . $ex->getMessage();
         Log::error($msg);
         Log::error('Stack Trace: ' . $ex->getTraceAsString());
      }
   }

   private function getDistributionPoint(Product $product)
   {
      // Buscar si el producto está distribuido a un punto de venta
      $movement = ProductMovement::where('product_id', $product->id)
         ->where('action', 'distribuir')
         ->latest()
         ->first();

      if ($movement) {
         $pos = PointOfSale::find($movement->destination);
         return $pos ? [
            'pos_id' => $pos->id,
            'pos_name' => $pos->name,
            'pos_address' => $pos->address,
         ] : null;
      }

      return null;
   }

   public function getSellerDashboard(Request $request, Response $response)
   {
      $response->data = ObjResponse::DefaultResponse();
      try {
         $auth = Auth::user();
         $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',

            'seller_id' => 'nullable|array',
            // 'seller_id.*' => 'exists:employees,id',
            'folio' => 'nullable|string',

            'start_date_pre_activation' => 'nullable|date',
            'end_date_pre_activation' => 'nullable|date|after_or_equal:start_date_pre_activation',

            'folio' => 'nullable|string',

            'import_name' => 'nullable|string',

            'location_status' => 'nullable|in:Stock,Asignado,Distribuido',
            'activation_status' => 'nullable|in:Virgen,Pre-activado,Activado,Portado,Caducado',

            'product_type_id' => 'nullable|exists:product_types,id',

            'start_date_in_system' => 'nullable|date',
            'end_date_in_system' => 'nullable|date|after_or_equal:start_date_in_system',

            'search' => 'nullable|string|max:100',

            'pos_id' => 'nullable|exists:points_of_sale,id',
         ]);

         // $query = ProductMovement::query()
         $query = VW_LatestProductMovements::applyFilters($filters)
            ->where('seller_id', $auth->id)
            ->orderBy('executed_at', 'asc')
            ->orderBy('product_id', 'asc');

         // Log::info($query->toSql());
         // Log::info($query->getBindings());

         // $list = (clone $query)->get();

         // Ejecutar todas las consultas en paralelo
         $results = DB::transaction(function () use ($query, $filters) {
            return [
               // 'list' => $list,
               'stats' => $this->getGeneralStats($query, $filters),
               // 'ported_products' => $this->getPortedProductsWithDetails($filters),
               'sellers_performance' => $this->getSellersPerformance($filters),
               // 'points_of_sale' => $this->getPointsOfSaleWithInventory($filters),
               // 'portability_by_month' => $this->getPortabilityByMonth($filters),
               // 'top_sellers' => $this->getTopSellers($filters),
               // 'status_distribution' => $this->getStatusDistribution($filters),
               // 'top_products' => $this->getTopProducts($filters),
               // 'visits_summary' => $this->getVisitsSummary($filters),
               // 'ported_products' => $this->getPortedProducts($filters),
               // 'get_portability_by_seller_report' => $this->getPortabilityBySellerReport($filters),
            ];
         });

         $response->data = ObjResponse::SuccessResponse();
         $response->data["message"] = 'Peticion satisfactoria | stats.';
         $response->data["result"] = $results;
      } catch (\Exception $ex) {
         $msg = "DashboardController ~ getSellerDashboard ~ Hubo un error -> " . $ex->getMessage();
         Log::error($msg);
         Log::error('Stack Trace: ' . $ex->getTraceAsString());
         $response->data = ObjResponse::CatchResponse($msg);
      }

      return response()->json($response, $response->data["status_code"]);
   }
   private function getSellersPerformance(array $filters)
   {
      $usertAuth = Auth::user();
      if ($usertAuth->role_id === 3) $filters['seller_id'] = [$usertAuth->id];
      // Log::info(json_encode($filters, true));

      return VW_User::select([
         'id',
         'employee_id',
         'full_name',
         'name',
         'cellphone',
         'position',
         'department',
         'avatar',
         'pin_color', // Nuevo campo agregado
      ])
         ->where('role_id', 3) // Solo vendedores
         ->where('active', 1)
         ->when(
            isset($filters['seller_id']) && count($filters['seller_id']) > 0,
            function ($q) use ($filters) {
               $q->whereIn('id', $filters['seller_id']);
            }
         )
         ->get()
         ->map(function ($seller) use ($filters) {
            $sellerId = $seller->id;

            // Id Productos asignados
            $assignedProductsId = $this->getSellerAssignedProductsId($sellerId, $filters);
            $filters["productIds"] = $assignedProductsId;

            $assignedProducts = $this->getSellerFiltersProducts($sellerId, $filters);

            // Filtrar el collection de movimientos por la propiedad `destination`
            $stockAssignedProducts = $assignedProducts->filter(function ($item) {
               $dest = strtolower($item->destination ?? '');
               return in_array($dest, ['stock', 'asignado', 'asignados']);
            });

            $distributedProducts = $assignedProducts->filter(function ($item) {
               $dest = strtolower($item->destination ?? '');
               return in_array($dest, ['distribuido', 'distribuidos']);
            });

            $activeProducts = $assignedProducts->filter(function ($item) {
               $dest = strtolower($item->destination ?? '');
               return in_array($dest, ['activo', 'activado']);
            });

            $portedProducts = $assignedProducts->filter(function ($item) {
               $dest = strtolower($item->destination ?? '');
               return $dest === 'portado';
            });

            // Puntos de venta asignados
            $assignedPOS = $this->getSellerPointsOfSale($sellerId, $filters);

            // Visitas realizadas
            $dailyVisits = $this->getSellerVisits($sellerId, $filters, true);
            $visits = $this->getSellerVisits($sellerId, $filters);

            return [
               'seller_id' => $seller->employee_id,
               'seller_info' => [
                  'id' => $seller->id,
                  'full_name' => $seller->full_name,
                  'name' => $seller->name,
                  'cellphone' => $seller->cellphone,
                  'position' => $seller->position,
                  'department' => $seller->department,
                  'avatar' => $seller->avatar,
                  'pin_color' => $seller->pin_color ?? $this->generateColor($seller->employee_id),
               ],

               // Estadísticas de productos
               'products_stats' => [
                  'assigned' => $assignedProducts->count(),
                  'in_stock' => $stockAssignedProducts->count(),
                  'distributed' => $distributedProducts->count(),
                  'actived' => $activeProducts->count(),
                  'ported' => $portedProducts->count(),

                  // porcentaje
                  'assigned_rate' => $assignedProducts->count() > 0
                     ? round(($assignedProducts->count() / $assignedProducts->count()) * 100, 2)
                     : 0,
                  'in_stock_rate' => $assignedProducts->count() > 0
                     ? round(($stockAssignedProducts->count() / $assignedProducts->count()) * 100, 2)
                     : 0,
                  'distributed_rate' => $assignedProducts->count() > 0
                     ? round(($distributedProducts->count() / $assignedProducts->count()) * 100, 2)
                     : 0,
                  'actived_rate' => $assignedProducts->count() > 0
                     ? round(($activeProducts->count() / $assignedProducts->count()) * 100, 2)
                     : 0,
                  'ported_rate' => $assignedProducts->count() > 0
                     ? round(($portedProducts->count() / $assignedProducts->count()) * 100, 2)
                     : 0,

                  // eficacia y deficiencia
                  'efficiency' => $assignedProducts->count() > 0
                     ? round((($activeProducts->count() * 100) / $assignedProducts->count()), 2)
                     : 0,
                  'deficiency' => $assignedProducts->count() > 0
                     ? round((($portedProducts->count() * 100) / $assignedProducts->count()), 2)
                     : 0,

                  // Desglose por estatus
                  // 'by_activation_status' => $assignedProducts
                  //    ->groupBy('activation_status')
                  //    ->map->count(),

                  // 'by_location_status' => $assignedProducts
                  //    ->groupBy('location_status')
                  //    ->map->count(),
               ],

               // Puntos de venta
               'points_of_sale' => [
                  'total' => $assignedPOS->count(),
                  'list' => $assignedPOS->map(function ($pos) {
                     return [
                        'id' => $pos->id,
                        'name' => $pos->name,
                        'address' => $pos->address,
                        'contact' => $pos->contact_name,
                        'phone' => $pos->contact_phone,
                        'lat' => $pos->lat,
                        'lon' => $pos->lon,
                     ];
                  }),
               ],

               // Visitas
               'visits' => [
                  'daily' => $dailyVisits->count(),
                  'daily_rate' => $assignedPOS->count() > 0
                     ? round(($dailyVisits->count() / $assignedPOS->count()) * 100, 2)
                     : 0,
                  'total' => $visits->count(),
                  'by_type' => $visits->groupBy('visit_type')->map->count(),
                  'by_month' => $visits->groupBy(function ($visit) {
                     return $visit->created_at->format('Y-m');
                  })->map->count(),
                  'recent' => $visits->take(5)->map(function ($visit) {
                     return [
                        'date' => $visit->created_at,
                        'type' => $visit->visit_type,
                        'pos_name' => $visit->point_of_sale->name,
                        'chips_delivered' => $visit->chips_delivered,
                        'chips_sold' => $visit->chips_sold,
                     ];
                  }),
               ],

               // Lotes asignados
               'lotes' => Lote::where('seller_id', $sellerId)
                  ->when(
                     isset($filters['start_date']) && isset($filters['end_date']),
                     function ($q) use ($filters) {
                        $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
                     }
                  )
                  ->get()
                  ->map(function ($lote) {
                     return [
                        'id' => $lote->id,
                        'name' => $lote->lote,
                        'folio' => $lote->folio,
                        'quantity' => $lote->quantity,
                        'preactivation_date' => $lote->preactivation_date,
                     ];
                  }),
            ];
         });
   }

   private function getSellersDashboard($query, array $filters)
   {
      $usertAuth = Auth::user();
      if ($usertAuth->role_id === 3) $filters['seller_id'] = [$usertAuth->id];
      // Log::info(json_encode($filters, true));

      return VW_User::select([
         'id',
         'employee_id',
         'full_name',
         'name',
         'cellphone',
         'position',
         'department',
         'avatar',
         'pin_color', // Nuevo campo agregado
      ])
         ->where('role_id', 3) // Solo vendedores
         ->where('active', 1)
         ->when(
            isset($filters['seller_id']) && count($filters['seller_id']) > 0,
            function ($q) use ($filters) {
               $q->whereIn('id', $filters['seller_id']);
            }
         )
         ->get()
         ->map(function ($seller) use ($query, $filters) {
            $sellerId = $seller->id;

            $assignedProducts = (clone $query)->where('seller_id', $sellerId)->get();
            // Log::info($assignedProducts);

            // Filtrar el collection de movimientos por la propiedad `destination`
            // $stockAssignedProducts = $assignedProducts->filter(function ($item) {
            //    $dest = strtolower($item->destination ?? '');
            //    return in_array($dest, ['stock', 'asignado', 'asignados']);
            // });

            $distributedProducts = $assignedProducts->filter(function ($item) {
               $dest = strtolower($item->destination ?? '');
               return in_array($dest, ['distribuido', 'distribuidos']);
            });

            $activeProducts = $assignedProducts->filter(function ($item) {
               $dest = strtolower($item->destination ?? '');
               return in_array($dest, ['activo', 'activado']);
            });

            $portedProducts = $assignedProducts->filter(function ($item) {
               $dest = strtolower($item->destination ?? '');
               return $dest === 'portado';
            });

            // Puntos de venta asignados
            $assignedPOS = $this->getSellerPointsOfSale($sellerId, $filters);

            // Visitas realizadas
            $dailyVisits = $this->getSellerVisits($sellerId, $filters, true);
            $visits = $this->getSellerVisits($sellerId, $filters);

            return [
               'id' => $seller->id,
               'full_name' => $seller->full_name,
               // 'cellphone' => $seller->cellphone,
               // 'position' => $seller->position,
               // 'department' => $seller->department,
               // 'avatar' => $seller->avatar,
               'pin_color' => $seller->pin_color ?? $this->generateColor($seller->id),

               // // Estadísticas de productos
               'assigned' => $assignedProducts->count(),
               // 'in_stock' => $stockAssignedProducts->count(),
               'distributed' => $distributedProducts->count(),
               'actived' => $activeProducts->count(),
               'ported' => $portedProducts->count(),

               // // porcentaje
               // 'assigned_rate' => $assignedProducts->count() > 0
               //    ? round(($assignedProducts->count() / $assignedProducts->count()) * 100, 2)
               //    : 0,
               // 'in_stock_rate' => $assignedProducts->count() > 0
               //    ? round(($stockAssignedProducts->count() / $assignedProducts->count()) * 100, 2)
               //    : 0,
               // 'distributed_rate' => $assignedProducts->count() > 0
               //    ? round(($distributedProducts->count() / $assignedProducts->count()) * 100, 2)
               //    : 0,
               // 'actived_rate' => $assignedProducts->count() > 0
               //    ? round(($activeProducts->count() / $assignedProducts->count()) * 100, 2)
               //    : 0,
               // 'ported_rate' => $assignedProducts->count() > 0
               //    ? round(($portedProducts->count() / $assignedProducts->count()) * 100, 2)
               //    : 0,

               // eficacia y deficiencia
               'efficiency' => $assignedProducts->count() > 0
                  ? round((($activeProducts->count() * 100) / $assignedProducts->count()), 2)
                  : 0,
               'deficiency' => $assignedProducts->count() > 0
                  ? round((($portedProducts->count() * 100) / $assignedProducts->count()), 2)
                  : 0,



               // Puntos de venta
               'points_of_sale' => $assignedPOS->count(),

               // Visitas
               'visits' => 0, //$visits->count(),
               'daily' => 0, //$dailyVisits->count(),

               // Lotes asignados
               'lotes' => Lote::where('seller_id', $sellerId)
                  ->when(
                     isset($filters['start_date']) && isset($filters['end_date']),
                     function ($q) use ($filters) {
                        $q->whereDate('created_at', '>=', $filters['start_date'])->whereDate('created_at', '<=', $filters['end_date']);
                        // ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
                     }
                  )
                  ->get()
                  ->map(function ($lote) {
                     return [
                        'id' => $lote->id,
                        'name' => $lote->lote,
                        'folio' => $lote->folio,
                        'quantity' => $lote->quantity,
                        'preactivation_date' => $lote->preactivation_date,
                     ];
                  }),
            ];
         });
   }
   #borrar si la de arriba funciona
   // private function getSellersDashboard(array $filters)
   // {
   //    $usertAuth = Auth::user();
   //    if ($usertAuth->role_id === 3) $filters['seller_id'] = [$usertAuth->id];
   //    // Log::info(json_encode($filters, true));

   //    return VW_User::select([
   //       'id',
   //       'employee_id',
   //       'full_name',
   //       'name',
   //       'cellphone',
   //       'position',
   //       'department',
   //       'avatar',
   //       'pin_color', // Nuevo campo agregado
   //    ])
   //       ->where('role_id', 3) // Solo vendedores
   //       ->where('active', 1)
   //       ->when(
   //          isset($filters['seller_id']) && count($filters['seller_id']) > 0,
   //          function ($q) use ($filters) {
   //             $q->whereIn('id', $filters['seller_id']);
   //          }
   //       )
   //       ->get()
   //       ->map(function ($seller) use ($filters) {
   //          $sellerId = $seller->id;

   //          // Id Productos asignados
   //          $assignedProductsId = $this->getSellerAssignedProductsId($sellerId, $filters);
   //          $filters["productIds"] = $assignedProductsId;

   //          $assignedProducts = $this->getSellerFiltersProducts($sellerId, $filters);

   //          // Filtrar el collection de movimientos por la propiedad `destination`
   //          $stockAssignedProducts = $assignedProducts->filter(function ($item) {
   //             $dest = strtolower($item->destination ?? '');
   //             return in_array($dest, ['stock', 'asignado', 'asignados']);
   //          });

   //          $distributedProducts = $assignedProducts->filter(function ($item) {
   //             $dest = strtolower($item->destination ?? '');
   //             return in_array($dest, ['distribuido', 'distribuidos']);
   //          });

   //          $activeProducts = $assignedProducts->filter(function ($item) {
   //             $dest = strtolower($item->destination ?? '');
   //             return in_array($dest, ['activo', 'active']);
   //          });

   //          $portedProducts = $assignedProducts->filter(function ($item) {
   //             $dest = strtolower($item->destination ?? '');
   //             return $dest === 'portado';
   //          });

   //          // Puntos de venta asignados
   //          $assignedPOS = $this->getSellerPointsOfSale($sellerId, $filters);

   //          // Visitas realizadas
   //          $dailyVisits = $this->getSellerVisits($sellerId, $filters, true);
   //          $visits = $this->getSellerVisits($sellerId, $filters);

   //          return [
   //             'id' => $seller->id,
   //             'full_name' => $seller->full_name,
   //             // 'cellphone' => $seller->cellphone,
   //             // 'position' => $seller->position,
   //             // 'department' => $seller->department,
   //             // 'avatar' => $seller->avatar,
   //             'pin_color' => $seller->pin_color ?? $this->generateColor($seller->id),

   //             // // Estadísticas de productos
   //             'assigned' => $assignedProducts->count(),
   //             // 'in_stock' => $stockAssignedProducts->count(),
   //             'distributed' => $distributedProducts->count(),
   //             'actived' => $activeProducts->count(),
   //             'ported' => $portedProducts->count(),

   //             // // porcentaje
   //             // 'assigned_rate' => $assignedProducts->count() > 0
   //             //    ? round(($assignedProducts->count() / $assignedProducts->count()) * 100, 2)
   //             //    : 0,
   //             // 'in_stock_rate' => $assignedProducts->count() > 0
   //             //    ? round(($stockAssignedProducts->count() / $assignedProducts->count()) * 100, 2)
   //             //    : 0,
   //             // 'distributed_rate' => $assignedProducts->count() > 0
   //             //    ? round(($distributedProducts->count() / $assignedProducts->count()) * 100, 2)
   //             //    : 0,
   //             // 'actived_rate' => $assignedProducts->count() > 0
   //             //    ? round(($activeProducts->count() / $assignedProducts->count()) * 100, 2)
   //             //    : 0,
   //             // 'ported_rate' => $assignedProducts->count() > 0
   //             //    ? round(($portedProducts->count() / $assignedProducts->count()) * 100, 2)
   //             //    : 0,

   //             // eficacia y deficiencia
   //             'efficiency' => $assignedProducts->count() > 0
   //                ? round((($activeProducts->count() * 100) / $assignedProducts->count()), 2)
   //                : 0,
   //             'deficiency' => $assignedProducts->count() > 0
   //                ? round((($portedProducts->count() * 100) / $assignedProducts->count()), 2)
   //                : 0,



   //             // Puntos de venta
   //             'points_of_sale' => $assignedPOS->count(),

   //             // Visitas
   //             'visits' => 0, //$visits->count(),
   //             'daily' => 0, //$dailyVisits->count(),

   //             // Lotes asignados
   //             'lotes' => Lote::where('seller_id', $sellerId)
   //                ->when(
   //                   isset($filters['start_date']) && isset($filters['end_date']),
   //                   function ($q) use ($filters) {
   //                      $q->whereDate('created_at', '>=', $filters['start_date'])->whereDate('created_at', '<=', $filters['end_date']);
   //                      // ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
   //                   }
   //                )
   //                ->get()
   //                ->map(function ($lote) {
   //                   return [
   //                      'id' => $lote->id,
   //                      'name' => $lote->lote,
   //                      'folio' => $lote->folio,
   //                      'quantity' => $lote->quantity,
   //                      'preactivation_date' => $lote->preactivation_date,
   //                   ];
   //                }),
   //          ];
   //       });
   // }

   // Helper functions para estadísticas de vendedor
   private function getSellerAssignedProductsId($sellerId, $filters)
   {
      $list = Product::assignedToSeller($sellerId)
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('products.created_at', [$filters['start_date'], $filters['end_date']]);
         });


      // Log::info($list->toSql());
      // Log::info($list->getBindings());
      return $list->pluck('id');
   }
   // private function getSellerAssignedProducts($sellerId, $filters)
   // {
   //    $list = Product::assignedToSeller($sellerId)
   //       ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
   //          $q->whereBetween('products.created_at', [$filters['start_date'], $filters['end_date']]);
   //       });

   //    return $list->get();
   // }

   private function getSellerFiltersProducts($sellerId, $filters)
   {

      // CONSULTA DIRECTA A MOVIMIENTOS DE PRODUCTOS -> Obtener sólo el último movimiento por product_id
      $subQuery = ProductMovement::selectRaw('product_id, MAX(executed_at) as max_executed_at')
         ->when(isset($filters['productIds']), function ($q) use ($filters) {
            $q->whereIn('product_id', $filters['productIds']);
         })

         ->when(isset($filters['destination']), function ($q) use ($filters) {
            $q->where('destination', $filters['destination']);
         })
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('executed_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->groupBy('product_id');

      // $list = DB::table('product_movements as pm')
      //    ->joinSub($subQuery, 'latest', function ($join) {
      //       $join->on('pm.product_id', '=', 'latest.product_id')
      //          ->on('pm.executed_at', '=', 'latest.max_executed_at');
      //    })
      //    ->select('pm.*')
      //    ->orderBy('pm.executed_at', 'asc')
      //    ->orderBy('pm.product_id', 'asc');

      $list = DB::table('product_movements as pm')
         ->joinSub($subQuery, 'latest', function ($join) {
            $join->on('pm.product_id', '=', 'latest.product_id')
               ->on('pm.executed_at', '=', 'latest.max_executed_at');
         })
         ->join('products as p', 'p.id', '=', 'pm.product_id')
         ->leftJoin('lote_details as ld', 'ld.product_id', '=', 'p.id')
         ->leftJoin('lotes as l', 'l.id', '=', 'ld.lote_id')
         ->leftJoin('vw_users as s', 's.id', '=', 'l.seller_id')
         ->when(isset($sellerId), function ($q) use ($sellerId) {
            $q->where('l.seller_id', $sellerId);
         })
         ->select([
            'pm.*',

            // producto
            'p.iccid',
            'p.imei',
            'p.celular',
            'p.folio',
            'p.location_status',
            'p.activation_status',
            'p.product_type_id',
            'l.lote',
            'l.lada',
            'l.seller_id',
            's.username',
            's.full_name',
         ])
         ->orderBy('pm.executed_at', 'asc')
         ->orderBy('pm.product_id', 'asc');

      // Log::info($list->toSql());
      // Log::info($list->getBindings());
      return $list->get();

      // CONSULTA DESDE PRODUCTOS, ligandolos por lotes y movimientos
      // $list = Product::assignedToSeller($sellerId)
      //    ->whereHas('movements', function ($q) use ($filters) {
      //       $q->whereIn('product_id', $filters["productIds"])
      //          ->when(isset($filters['destination']), function ($q1) use ($filters) {
      //             $q1->where('destination', $filters['destination']);
      //          })
      //          ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q2) use ($filters) {
      //             $q2->whereBetween('executed_at', [$filters['start_date'], $filters['end_date']]);
      //          });
      //    });
      // Log::info($list->toSql());
      // Log::info($list->getBindings());
      // return $list->get();
   }



   // private function getSellerDistributedProducts($sellerId, $filters)
   // {
   //    $list = Product::where('location_status', 'Distribuido')
   //       ->whereHas('movements', function ($q) use ($sellerId) {
   //          $q->where('executed_by', $sellerId)
   //             ->where('action', 'distribuir');
   //       })
   //       ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
   //          $q->whereBetween('products.updated_at', [$filters['start_date'], $filters['end_date']]);
   //       });

   //    // Log::info($list->toSql());
   //    // Log::info($list->getBindings());
   //    return $list->get();
   // }

   // private function getSellerPortedProducts($sellerId, $filters)
   // {
   //    return Product::where('activation_status', 'Portado')
   //       ->whereHas('loteDetails.lote', function ($q) use ($sellerId) {
   //          $q->where('seller_id', $sellerId);
   //       })
   //       ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
   //          $q->whereBetween('products.updated_at', [$filters['start_date'], $filters['end_date']]);
   //       })
   //       ->get();
   // }

   private function getSellerPointsOfSale($sellerId, $filters)
   {
      // return PointOfSale::whereHas('visits', function ($q) use ($sellerId) {
      //    $q->where('seller_id', $sellerId);
      // })
      return PointOfSale::where('active', true)->where('seller_id', $sellerId)
         // ->orWhereHas('movements', function ($q) use ($sellerId) {
         //    $q->where('executed_by', $sellerId)
         //       ->where('action', 'distribuir')
         //       ->whereColumn('destination', 'points_of_sale.id');
         // })
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->get();
   }

   private function getSellerVisits($sellerId, $filters, $daily = false)
   {
      $list = Visit::where('seller_id', $sellerId)
         ->with('point_of_sale')
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
         });

      if ($daily) {
         $list->whereDate('created_at', now()->toDateString());
      }

      // Log::info($list->toSql());
      // Log::info($list->getBindings());
      return $list->orderBy('created_at', 'desc')->get();
   }

   private function getPointsOfSaleWithInventory(array $filters)
   {
      $pointsOfSale = PointOfSale::with([
         // 'products' => function ($q) use ($filters) {
         //    $q->when($filters['activation_status'] ?? null, function ($q, $status) {
         //       $q->where('activation_status', $status);
         //    })
         //       ->when($filters['location_status'] ?? null, function ($q, $status) {
         //          $q->where('location_status', $status);
         //       });
         // },
         'visits' => function ($q) use ($filters) {
            $q->when(
               isset($filters['start_date'], $filters['end_date']),
               fn($q) => $q->whereDate('created_at', '>=', $filters['start_date'])->whereDate('created_at', '<=', $filters['end_date'])
               // ->whereBetween('created_at', [
               //    $filters['start_date'],
               //    $filters['end_date'],
               // ])
            )->with([
               'seller:id,pin_color',
               // 'seller.personalInfo:id,employee_id,full_name,cellphone',
            ]);
         },

         // 'latestVisit' => function ($q) {
         //    $q->with([
         //       'seller:id,pin_color',
         //       // 'seller.personalInfo:id,employee_id,full_name,cellphone',
         //    ]);
         // },
      ])
         ->with(['seller'])
         ->when($filters['pos_id'] ?? null, fn($q, $posId) => $q->where('id', $posId))
         ->get();

      // Log::info("pointsOfSale" . $pointsOfSale);

      $lastDistributions = ProductMovement::select('destination', 'executed_at', 'executed_by', 'description')
         ->where('destination', 'Distribuido')
         // ->whereIn('destination', $pointsOfSale->pluck('id'))
         ->latest('executed_at')
         ->get();
      // ->groupBy('destination');

      // Log::info("lastDistributions" . $lastDistributions);


      // $topSellers = Visit::select('pos_id', 'seller_id', DB::raw('COUNT(*) as visit_count'))
      //    ->whereIn('pos_id', $pointsOfSale->pluck('id'))
      //    ->groupBy('pos_id', 'seller_id')
      //    ->get()
      //    ->groupBy('pos_id')
      //    ->map(fn($visits) => $visits->sortByDesc('visit_count')->first());


      // $sellerInfo = VW_User::whereIn(
      //    'employee_id',
      //    $topSellers->pluck('seller_id')->filter()
      // )->get()->keyBy('employee_id');
      // Log::info("sellerInfo" . $sellerInfo);


      return $pointsOfSale->map(function ($pos) use ($lastDistributions) {

         // $latestVisit = $pos->latestVisit;
         // $seller = $latestVisit?->seller;
         // $personalInfo = $seller?->personalInfo;

         // $products = $pos->products;

         // $lastDistribution = $lastDistributions[$pos->id]?->first();

         // $topSeller = $topSellers[$pos->id] ?? null;
         // $topSellerInfo = $topSeller
         //    ? $sellerInfo[$topSeller->seller_id] ?? null
         //    : null;

         return [
            'id' => $pos->id,
            'name' => $pos->name,
            'contact_name' => $pos->contact_name,
            'contact_phone' => $pos->contact_phone,
            'address' => $pos->address,
            'lat' => (float) $pos->lat,
            'lon' => (float) $pos->lon,
            'ubication' => $pos->ubication,
            'seller' => $pos->seller,
            'inventory' => [
               // 'products' => $products->count(),
               // 'by_activation_status' => $products->groupBy('activation_status')->map->count(),
               // 'by_location_status' => $products->groupBy('location_status')->map->count(),
               // 'ported_count' => $products->where('activation_status', 'Portado')->count(),
               // 'activated_count' => $products->where('activation_status', 'Activado')->count(),
            ],

            'visits' => [
               'total' => $pos->visits->count(),
               'by_type' => $pos->visits->groupBy('visit_type')->map->count(),

               // 'last_visit' => $latestVisit ? [
               //    'date' => $latestVisit->created_at,
               //    'type' => $latestVisit->visit_type,
               //    // 'seller_name' => $personalInfo?->full_name,
               //    'seller_pin_color' => $seller?->pin_color,
               //    'chips_delivered' => $latestVisit->chips_delivered,
               //    'chips_sold' => $latestVisit->chips_sold,
               //    'observations' => $latestVisit->observations,
               // ] : null,

               // 'top_seller' => $topSellerInfo ? [
               //    'name' => $topSellerInfo->full_name,
               //    'pin_color' => $topSellerInfo->pin_color,
               //    'visit_count' => $topSeller->visit_count,
               // ] : null,
            ],

            // 'last_distribution' => $lastDistribution ? [
            //    'date' => $lastDistribution->executed_at,
            //    'executed_by' => $lastDistribution->executed_by,
            //    'quantity' => $lastDistribution->description
            //       ? (int) preg_replace('/\D/', '', $lastDistribution->description)
            //       : 0,
            // ] : null,

            // 'primary_seller' => $seller ? [
            //    'id' => $seller->id,
            //    // 'name' => $personalInfo?->full_name,
            //    'pin_color' => $seller->pin_color,
            //    // 'cellphone' => $personalInfo?->cellphone,
            // ] : null,
         ];
      });
   }



   // private function getPointsOfSaleWithInventory(array $filters)
   // {
   //    return PointOfSale::with([
   //       'products' => function ($q) use ($filters) {
   //          $q->when(isset($filters['activation_status']), function ($q) use ($filters) {
   //             $q->where('activation_status', $filters['activation_status']);
   //          })
   //             ->when(isset($filters['location_status']), function ($q) use ($filters) {
   //                $q->where('location_status', $filters['location_status']);
   //             });
   //       },
   //       'visits' => function ($q) use ($filters) {
   //          $q->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
   //             $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
   //          })
   //             ->with('seller.personalInfo');
   //       },
   //       'latestVisit.seller.personalInfo',
   //    ])
   //       ->when(isset($filters['pos_id']), function ($q) use ($filters) {
   //          $q->where('id', $filters['pos_id']);
   //       })
   //       ->get()
   //       ->map(function ($pos) use ($filters) {
   //          $latestVisit = $pos->latestVisit;
   //          $seller = $latestVisit?->seller;
   //          $personalInfo = $seller?->personalInfo;

   //          // Productos en este POS
   //          $products = $pos->products;

   //          // Última distribución
   //          $lastDistribution = ProductMovement::where('destination', $pos->id)
   //             ->where('action', 'distribuir')
   //             ->latest()
   //             ->first();

   //          // Vendedor que más ha visitado
   //          $topSeller = Visit::where('pos_id', $pos->id)
   //             ->select('seller_id', DB::raw('COUNT(*) as visit_count'))
   //             ->groupBy('seller_id')
   //             ->orderByDesc('visit_count')
   //             ->first();

   //          $topSellerInfo = $topSeller ? VW_User::where('employee_id', $topSeller->seller_id)->first() : null;

   //          return [
   //             'id' => $pos->id,
   //             'name' => $pos->name,
   //             'contact_name' => $pos->contact_name,
   //             'contact_phone' => $pos->contact_phone,
   //             'address' => $pos->address,
   //             'lat' => (float) $pos->lat,
   //             'lon' => (float) $pos->lon,
   //             'ubication' => $pos->ubication,

   //             // Inventario
   //             'inventory' => [
   //                'products' => $products->count(),
   //                'by_activation_status' => $products->groupBy('activation_status')->map->count(),
   //                'by_location_status' => $products->groupBy('location_status')->map->count(),
   //                'ported_count' => $products->where('activation_status', 'Portado')->count(),
   //                'activated_count' => $products->where('activation_status', 'Activado')->count(),

   //                // Detalle de productos portados
   //                'ported_products' => $products->where('activation_status', 'Portado')
   //                   ->take(5)
   //                   ->map(function ($product) {
   //                      return [
   //                         'celular' => $product->celular,
   //                         'iccid' => $product->iccid,
   //                         'ported_date' => $product->updated_at,
   //                      ];
   //                   }),
   //             ],

   //             // Visitas
   //             'visits' => [
   //                'total' => $pos->visits->count(),
   //                'by_type' => $pos->visits->groupBy('visit_type')->map->count(),
   //                'last_visit' => $latestVisit ? [
   //                   'date' => $latestVisit->created_at,
   //                   'type' => $latestVisit->visit_type,
   //                   'seller_name' => $personalInfo?->full_name,
   //                   'seller_pin_color' => $seller?->pin_color,
   //                   'chips_delivered' => $latestVisit->chips_delivered,
   //                   'chips_sold' => $latestVisit->chips_sold,
   //                   'observations' => $latestVisit->observations,
   //                ] : null,

   //                'top_seller' => $topSellerInfo ? [
   //                   'name' => $topSellerInfo->full_name,
   //                   'pin_color' => $topSellerInfo->pin_color,
   //                   'visit_count' => $topSeller->visit_count,
   //                ] : null,
   //             ],

   //             // Última distribución
   //             'last_distribution' => $lastDistribution ? [
   //                'date' => $lastDistribution->executed_at,
   //                'executed_by' => $lastDistribution->executed_by,
   //                'quantity' => $lastDistribution->description ?
   //                   intval(preg_replace('/[^0-9]/', '', $lastDistribution->description)) : 0,
   //             ] : null,

   //             // Vendedor principal (basado en últimas visitas)
   //             'primary_seller' => $seller ? [
   //                'id' => $seller->id,
   //                'name' => $personalInfo?->full_name,
   //                'pin_color' => $seller->pin_color,
   //                'cellphone' => $personalInfo?->cellphone,
   //             ] : null,

   //             // Estadísticas de ventas
   //             'sales_stats' => [
   //                'total_chips_delivered' => $pos->visits->sum('chips_delivered'),
   //                'total_chips_sold' => $pos->visits->sum('chips_sold'),
   //                'total_chips_remaining' => $pos->visits->sum('chips_remaining'),
   //                'conversion_rate' => $pos->visits->sum('chips_delivered') > 0
   //                   ? round(($pos->visits->sum('chips_sold') / $pos->visits->sum('chips_delivered')) * 100, 2)
   //                   : 0,
   //             ],
   //          ];
   //       });
   // }

   private function getVisitsSummary(array $filters)
   {
      return Visit::select([
         DB::raw('COUNT(*) as visits'),
         DB::raw('SUM(chips_delivered) as total_delivered'),
         DB::raw('SUM(chips_sold) as total_sold'),
         DB::raw('SUM(chips_remaining) as total_remaining'),
         DB::raw('AVG(chips_sold) as avg_sold_per_visit'),
         'visit_type',
      ])
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->when(isset($filters['seller_id']) && count($filters['seller_id']) > 0, function ($q) use ($filters) {
            $q->whereIn('seller_id', $filters['seller_id']);
         })
         ->groupBy('visit_type')
         ->get()
         ->mapWithKeys(function ($item) {
            return [
               $item->visit_type => [
                  'visits' => $item->visits,
                  'total_delivered' => $item->total_delivered,
                  'total_sold' => $item->total_sold,
                  'total_remaining' => $item->total_remaining,
                  'avg_sold_per_visit' => round($item->avg_sold_per_visit, 2),
                  'conversion_rate' => $item->total_delivered > 0
                     ? round(($item->total_sold / $item->total_delivered) * 100, 2)
                     : 0,
               ]
            ];
         });
   }

   private function getGeneralStats(mixed $query, array $filters)
   {
      $totalProducts = (clone $query)->count();

      $totalInStock = (clone $query)->where('destination', 'Stock')->count();

      $totalPreActivated = (clone $query)->where('destination', 'Pre-activado')->count();

      $totalAssigned = (clone $query)->where('destination', 'Asignado')->count();

      $totalDistribuidos = (clone $query)->where('destination', 'Distribuido')->count();

      $totalActivated = (clone $query)->where('destination', 'Activado')->count();

      $totalPortados = (clone $query)->where('destination', 'Portado')->count();


      // Vendedores activos
      $activeSellers = VW_User::where('role_id', 3)
         ->where('active', 1)
         ->when(
            isset($filters['seller_id']) && count($filters['seller_id']) > 0,
            function ($q) use ($filters) {
               $q->whereIn('id', $filters['seller_id']);
            }
         )
         ->count();

      // Puntos de venta activos
      $activePOS = PointOfSale::where('active', 1)->count();

      // Visitas totales
      $totalVisits = Visit::when(
         isset($filters['start_date']),
         function ($q) use ($filters) {
            $q->whereDate('created_at', '>=', $filters['start_date']);
            // ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
         }
      )
         ->when(
            isset($filters['end_date']),
            function ($q) use ($filters) {
               $q->whereDate('created_at', '<=', $filters['end_date']);
               // ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            }
         )
         ->when(
            isset($filters['seller_id']) && count($filters['seller_id']) > 0,
            function ($q) use ($filters) {
               $q->whereIn('seller_id', $filters['seller_id']);
            }
         )
         ->count();

      return [
         'products' => $totalProducts,
         'inStock' => $totalInStock,
         'preActivated' => $totalPreActivated,
         'assigned' => $totalAssigned,
         'distributed' => $totalDistribuidos,
         'activated' => $totalActivated,
         'portados' => $totalPortados,
         'portability_rate' => $totalProducts > 0 ? round(($totalPortados / $totalProducts) * 100, 2) : 0,
         'activation_rate' => $totalProducts > 0 ? round(($totalActivated / $totalProducts) * 100, 2) : 0,
         'sellers' => $activeSellers,
         'points_of_sale' => $activePOS,
         'visits' => $totalVisits,
         'avg_products_per_seller' => $activeSellers > 0 ? round($totalProducts / $activeSellers, 2) : 0,
      ];
   }

   private function getPortabilityByMonth(array $filters)
   {
      return Product::where('activation_status', 'Portado')
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('updated_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->when(isset($filters['seller_id']) && count($filters['seller_id']) > 0, function ($q) use ($filters) {
            $q->whereHas('loteDetails.lote', function ($subQuery) use ($filters) {
               $subQuery->whereIn('seller_id', $filters['seller_id']);
            });
         })
         ->selectRaw('YEAR(updated_at) as year, MONTH(updated_at) as month, COUNT(*) as count')
         ->groupBy('year', 'month')
         ->orderBy('year', 'desc')
         ->orderBy('month', 'desc')
         ->limit(12)
         ->get()
         ->mapWithKeys(function ($item) {
            $key = $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            return [$key => $item->count];
         });
   }

   private function applyFilters($query, array $filters)
   {
      return $query
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->when(isset($filters['seller_id']) && count($filters['seller_id']) > 0, function ($q) use ($filters) {
            $q->whereHas('loteDetails.lote', function ($subQuery) use ($filters) {
               $subQuery->whereIn('seller_id', $filters['seller_id']);
            });
         })
         ->when(isset($filters['location_status']), function ($q) use ($filters) {
            $q->where('location_status', $filters['location_status']);
         })
         ->when(isset($filters['activation_status']), function ($q) use ($filters) {
            $q->where('activation_status', $filters['activation_status']);
         })
         ->when(isset($filters['product_type_id']), function ($q) use ($filters) {
            $q->where('product_type_id', $filters['product_type_id']);
         })
         ->when(isset($filters['search']), function ($q) use ($filters) {
            $q->where(function ($query) use ($filters) {
               $query->where('celular', 'like', "%{$filters['search']}%")
                  ->orWhere('iccid', 'like', "%{$filters['search']}%")
                  ->orWhere('imei', 'like', "%{$filters['search']}%")
                  ->orWhere('producto', 'like', "%{$filters['search']}%");
            });
         });
   }

   private function getTopSellers($query, array $filters)
   {
      try {
         // Consulta optimizada para obtener los mejores vendedores por portaciones

         // Log::info($query->toSql());
         // Log::info($query->getBindings());

         $listBestSeller = (clone $query)
            ->reorder() // 🔥 EL FIX CLAVE
            ->where('destination', 'Activado')
            ->groupBy('seller_id', 'full_name')
            ->select([
               'full_name as seller',
               DB::raw('COUNT(*) as data'),
            ])
            ->orderBy('data')
            ->limit(10)
            ->get();

         $listBadSeller = (clone $query)
            ->reorder() // 🔥 EL FIX CLAVE
            ->where('destination', 'Portado')
            ->groupBy('seller_id', 'full_name')
            ->select([
               'full_name as seller',
               DB::raw('COUNT(*) as data'),
            ])
            ->orderBy('data')
            ->limit(10)
            ->get();
         // Log::info($listBad->toSql());
         // Log::info($listBad->getBindings());
         // return $listBad->get();
         return [
            'bestSeller' => [
               'list' => $listBestSeller,
               'labels' => $listBestSeller->map(fn($r) => $r->seller ?? 'S/A')->values(),
               'data'   => $listBestSeller->map(fn($r) => (int) $r->data)->values(),
            ],
            'badSeller' => [
               'list' => $listBadSeller,
               'labels' => $listBadSeller->map(fn($r) => $r->seller ?? 'S/A')->values(),
               'data'   => $listBadSeller->map(fn($r) => (int) $r->data)->values(),
            ]
         ];





         // $topSellers = DB::table('products as p')
         //    ->select([
         //       'e.id',
         //       DB::raw('CONCAT(e.name, " ", e.plast_name) as seller_name'),
         //       'e.pin_color',
         //       DB::raw('COUNT(p.id) as port_count'),
         //       DB::raw('MAX(p.updated_at) as last_port_date')
         //    ])
         //    ->join('lote_details as ld', 'p.id', '=', 'ld.product_id')
         //    ->join('lotes as l', 'ld.lote_id', '=', 'l.id')
         //    ->join('vw_employees as e', 'l.seller_id', '=', 'e.id')
         //    ->where('p.activation_status', 'Portado')
         //    ->where('p.active', 1)

         //    // Aplicar filtros de fecha
         //    ->when(
         //       isset($filters['start_date']) && isset($filters['end_date']),
         //       function ($query) use ($filters) {
         //          return $query->whereBetween('p.updated_at', [
         //             $filters['start_date'],
         //             $filters['end_date']
         //          ]);
         //       }
         //    )

         //    // Filtrar por vendedores específicos
         //    ->when(
         //       isset($filters['seller_id']) && count($filters['seller_id']) > 0,
         //       function ($query) use ($filters) {
         //          return $query->whereIn('l.seller_id', $filters['seller_id']);
         //       }
         //    )

         //    // Filtrar por búsqueda
         //    ->when(
         //       isset($filters['search']),
         //       function ($query) use ($filters) {
         //          return $query->where(function ($q) use ($filters) {
         //             $q->where('p.celular', 'like', "%{$filters['search']}%")
         //                ->orWhere('p.iccid', 'like', "%{$filters['search']}%");
         //          });
         //       }
         //    )

         //    ->groupBy('e.id', 'e.pin_color', 'e.name', 'e.plast_name')
         //    ->orderByDesc('port_count')
         //    ->limit(10)
         //    ->get()
         //    ->map(function ($seller) {
         //       return [
         //          'id' => $seller->id,
         //          'name' => $seller->seller_name ?? 'Vendedor ' . $seller->id,
         //          'pin_color' => $seller->pin_color ?? $this->generateSellerColor($seller->id),
         //          'port_count' => (int) $seller->port_count,
         //          'last_port_date' => $seller->last_port_date,
         //       ];
         //    });

         // return $topSellers;
      } catch (\Exception $e) {
         Log::error('Error en getTopSellers: ' . $e->getMessage());
         Log::error('Stack Trace: ' . $e->getTraceAsString());

         // Retornar array vacío en caso de error
         return collect([]);
      }
   }

   private function generateColor($sellerId)
   {
      $colors = [
         '#FF6B6B',
         '#4ECDC4',
         '#FFD166',
         '#06D6A0',
         '#118AB2',
         '#EF476F',
         '#FFD166',
         '#06D6A0',
         '#073B4C',
         '#7209B7',
         '#F72585',
         '#3A0CA3',
         '#4361EE',
         '#4CC9F0',
         '#FF9E00',
      ];

      $index = $sellerId % count($colors);
      return $colors[$index];
   }

   private function getStatusDistribution(array $filters)
   {
      try {
         $distribution = Product::query()
            ->select([
               'activation_status',
               DB::raw('COUNT(*) as count')
            ])

            ->when(
               isset($filters['start_date']) && isset($filters['end_date']),
               function ($query) use ($filters) {
                  return $query->whereBetween('created_at', [
                     $filters['start_date'],
                     $filters['end_date']
                  ]);
               }
            )

            ->when(
               isset($filters['seller_id']) && count($filters['seller_id']) > 0,
               function ($query) use ($filters) {
                  $productIds = DB::table('lote_details')
                     ->join('lotes', 'lote_details.lote_id', '=', 'lotes.id')
                     ->whereIn('lotes.seller_id', $filters['seller_id'])
                     ->pluck('lote_details.product_id');

                  return $query->whereIn('id', $productIds);
               }
            )

            ->groupBy('activation_status')
            ->get();

         // Mapear a formato para frontend
         $result = $distribution->map(function ($item) {
            $colors = [
               'Virgen' => '#9e9e9e',       // Gris
               'Pre-activado' => '#ff9800', // Naranja
               'Activado' => '#4caf50',     // Verde
               'Portado' => '#2196f3',      // Azul
               'Caducado' => '#f44336'      // Rojo
            ];

            return [
               'status' => $item->activation_status,
               'label' => $this->getStatusLabel($item->activation_status),
               'count' => (int) $item->count,
               'color' => $colors[$item->activation_status] ?? '#607d8b'
            ];
         });

         return $result->values()->toArray();
      } catch (\Exception $e) {
         \Log::error('Error en getStatusDistribution: ' . $e->getMessage());
         return [];
      }
   }

   // Helper para etiquetas en español
   private function getStatusLabel($status)
   {
      $labels = [
         'Virgen' => 'Virgen',
         'Pre-activado' => 'Pre-activado',
         'Activado' => 'Activado',
         'Portado' => 'Portado',
         'Caducado' => 'Caducado'
      ];

      return $labels[$status] ?? $status;
   }

   public function getSellers()
   {
      $sellers = Employee::whereHas('role', function ($q) {
         $q->where('name', 'like', '%vendedor%');
      })
         ->select('id', 'name', 'pin_color')
         ->orderBy('name')
         ->get();

      return response()->json($sellers);
   }


   /**
    * Mostrar productos portados con información del vendedor (versión optimizada)
    */
   /**
    * Obtener productos portados con información del vendedor (versión directa)
    */
   public function getPortedProducts(array $filters = [])
   {
      try {
         $auth = Auth::user();

         // Construir consulta base
         $query = DB::table('products as p')
            ->select(
               'p.id',
               'p.iccid',
               'p.celular',
               'p.imei',
               'p.folio',
               'p.location_status',
               'p.updated_at as fecha_portabilidad',
               'pt.id as product_type_id',
               'pt.product_type as product_type',
               'u.id as seller_id',
               'u.username as seller_name',
               'u.email as seller_email',
               'l.id as lote_id',
               'l.lote as lote_nombre',
               'l.folio as lote_folio',
               'ld.assigned_at',
               'i.name',
               'iu.username as imported_by_name',
               'p.created_at as producto_creado',
               'p.activation_status'
            )
            ->leftJoin('product_types as pt', 'p.product_type_id', '=', 'pt.id')
            ->leftJoin('lote_details as ld', function ($join) {
               $join->on('p.id', '=', 'ld.product_id')
                  ->where('ld.active', true);
            })
            ->leftJoin('lotes as l', function ($join) {
               $join->on('ld.lote_id', '=', 'l.id')
                  ->where('l.active', true);
            })
            ->leftJoin('users as u', 'l.seller_id', '=', 'u.id')
            ->leftJoin('imports as i', 'p.import_id', '=', 'i.id')
            ->leftJoin('users as iu', 'i.uploaded_by', '=', 'iu.id')
            ->where('p.activation_status', 'Portado')
            ->where('p.active', true);
         // ->whereNull('p.deleted_at');

         // === APLICAR FILTROS ===

         // Filtro por tipo de producto
         if (!empty($filters['product_type_id'])) {
            $query->where('p.product_type_id', $filters['product_type_id']);
         }

         // Filtro por folio
         if (!empty($filters['folio'])) {
            $query->where('p.folio', 'LIKE', '%' . $filters['folio'] . '%');
         }

         // Filtro por ICCID
         if (!empty($filters['iccid'])) {
            $query->where('p.iccid', 'LIKE', '%' . $filters['iccid'] . '%');
         }

         // Filtro por teléfono
         if (!empty($filters['telefono'])) {
            $query->where('p.celular', 'LIKE', '%' . $filters['telefono'] . '%');
         }

         // Filtro por fecha de portabilidad
         if (!empty($filters['start_date'])) {
            $query->whereDate('p.updated_at', '>=', $filters['start_date']);
         }

         if (!empty($filters['end_date'])) {
            $query->whereDate('p.updated_at', '<=', $filters['end_date']);
         }

         // Filtro por vendedor (puede ser array o single)
         if (!empty($filters['seller_id'])) {
            if (is_array($filters['seller_id'])) {
               $query->whereIn('u.id', $filters['seller_id']);
            } else {
               $query->where('u.id', $filters['seller_id']);
            }
         }

         // Filtro por location_status
         if (!empty($filters['location_status'])) {
            if (is_array($filters['location_status'])) {
               $query->whereIn('p.location_status', $filters['location_status']);
            } else {
               $query->where('p.location_status', $filters['location_status']);
            }
         }

         // Filtro por punto de venta (si tienes esa relación)
         if (!empty($filters['pos_id'])) {
            $query->where('l.point_of_sale_id', $filters['pos_id']);
         }

         // Filtro de búsqueda general
         if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
               $q->where('p.iccid', 'LIKE', '%' . $search . '%')
                  ->orWhere('p.celular', 'LIKE', '%' . $search . '%')
                  ->orWhere('p.folio', 'LIKE', '%' . $search . '%')
                  ->orWhere('p.imei', 'LIKE', '%' . $search . '%')
                  ->orWhere('u.username', 'LIKE', '%' . $search . '%');
            });
         }

         // === RESTRICCIONES POR ROL ===
         if ($auth->role_id === 3) { // Vendedor
            $query->where('u.id', $auth->id);
         }

         // Ordenamiento
         $orderBy = $filters['order_by'] ?? 'p.updated_at';
         $orderDir = $filters['order_dir'] ?? 'desc';
         $query->orderBy($orderBy, $orderDir);

         // === EJECUTAR CONSULTA ===
         $result = [];

         if (!empty($filters['paginate']) && $filters['paginate']) {
            $perPage = $filters['per_page'] ?? 25;
            $paginated = $query->paginate($perPage);

            // Transformar resultados paginados
            $transformedData = $paginated->getCollection()->map(function ($item) {
               return $this->transformProductItem($item);
            });

            // Reemplazar colección
            $paginated->setCollection($transformedData);

            $result = [
               'data' => $paginated->items(),
               'pagination' => [
                  'current_page' => $paginated->currentPage(),
                  'per_page' => $paginated->perPage(),
                  'total' => $paginated->total(),
                  'last_page' => $paginated->lastPage(),
                  'from' => $paginated->firstItem(),
                  'to' => $paginated->lastItem()
               ]
            ];
         } else {
            $products = $query->get();

            // Transformar resultados
            $transformedData = $products->map(function ($item) {
               return $this->transformProductItem($item);
            });

            $result = [
               'data' => $transformedData->values()->toArray(),
               'pagination' => null
            ];
         }

         // === CALCULAR ESTADÍSTICAS ===
         $stats = $this->calculatePortabilityStats($result['data']);

         return [
            'success' => true,
            'data' => $result['data'],
            'stats' => $stats,
            'pagination' => $result['pagination'],
            'metadata' => [
               'total_records' => is_array($result['data']) ? count($result['data']) : $result['pagination']['total'] ?? 0,
               'filters_applied' => array_filter($filters, fn($value) => !is_null($value)),
               'generated_at' => now()->toDateTimeString()
            ]
         ];
      } catch (\Exception $ex) {
         \Log::error('Error en getPortedProducts: ' . $ex->getMessage());

         return [
            'success' => false,
            'error' => 'Error al obtener productos portados',
            'message' => $ex->getMessage(),
            'data' => [],
            'stats' => [],
            'pagination' => null,
            'metadata' => [
               'generated_at' => now()->toDateTimeString()
            ]
         ];
      }
   }
   /**
    * Transformar item de producto para respuesta
    */
   private function transformProductItem($item)
   {
      return [
         'id' => $item->id,
         'iccid' => $item->iccid,
         'celular' => $item->celular,
         'imei' => $item->imei,
         'folio' => $item->folio,
         'location_status' => $item->location_status,
         'fecha_portabilidad' => $item->fecha_portabilidad,
         'product_type' => $item->product_type_id ? [
            'id' => $item->product_type_id,
            'name' => $item->product_type_name
         ] : null,
         'vendedor' => $item->seller_id ? [
            'id' => $item->seller_id,
            'name' => $item->seller_name,
            'email' => $item->seller_email
         ] : null,
         'lote' => $item->lote_id ? [
            'id' => $item->lote_id,
            'nombre' => $item->lote_nombre,
            'folio' => $item->lote_folio,
            'asignado_en' => $item->assigned_at
         ] : null,
         'import_info' => $item->file_name ? [
            'file_name' => $item->file_name,
            'imported_by' => $item->imported_by_name
         ] : null,
         'timeline' => [
            'producto_creado' => $item->producto_creado,
            'portado' => $item->fecha_portabilidad
         ],
         'activation_status' => $item->activation_status
      ];
   }

   /**
    * Calcular estadísticas de portabilidad
    */
   private function calculatePortabilityStats(array $data): array
   {
      $collection = collect($data);

      if ($collection->isEmpty()) {
         return [
            'summary' => [
               'total' => 0,
               'assigned' => 0,
               'unassigned' => 0,
               'percentage_assigned' => 0
            ],
            'by_location_status' => [],
            'by_seller' => [],
            'by_product_type' => []
         ];
      }

      $total = $collection->count();
      $assigned = $collection->where('vendedor', '!=', null)->count();
      $unassigned = $collection->where('vendedor', null)->count();

      return [
         'summary' => [
            'total' => $total,
            'assigned' => $assigned,
            'unassigned' => $unassigned,
            'percentage_assigned' => $total > 0 ? round(($assigned / $total) * 100, 2) : 0
         ],
         'by_location_status' => $collection->groupBy('location_status')
            ->map(function ($items, $status) use ($total) {
               $count = count($items);
               return [
                  'status' => $status ?: 'Sin asignar',
                  'count' => $count,
                  'percentage' => round(($count / $total) * 100, 2)
               ];
            })->values()->toArray(),
         'by_seller' => $collection->where('vendedor', '!=', null)
            ->groupBy('vendedor.id')
            ->map(function ($items, $sellerId) use ($total) {
               $firstItem = $items[0];
               $count = count($items);
               return [
                  'seller_id' => $sellerId,
                  'seller_name' => $firstItem['vendedor']['name'],
                  'count' => $count,
                  'percentage' => round(($count / $total) * 100, 2)
               ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray(),
         'by_product_type' => $collection->where('product_type', '!=', null)
            ->groupBy('product_type.id')
            ->map(function ($items, $typeId) use ($total) {
               $firstItem = $items[0];
               $count = count($items);
               return [
                  'type_id' => $typeId,
                  'type_name' => $firstItem['product_type']['name'],
                  'count' => $count,
                  'percentage' => round(($count / $total) * 100, 2)
               ];
            })
            ->sortByDesc('count')
            ->values()
            ->toArray(),
         'timeline_stats' => [
            'earliest_portability' => $collection->min('fecha_portabilidad'),
            'latest_portability' => $collection->max('fecha_portabilidad')
         ]
      ];
   }

   /**
    * Obtener reporte de portabilidad por vendedor (versión directa)
    */
   public function getPortabilityBySellerReport(array $filters)
   {
      try {
         $auth = Auth::user();

         // Para vendedores, solo pueden ver su propio reporte
         if ($auth->role_id === 3) {
            $sellerId = $auth->id;
         } else {
            $sellerId = $filters['seller_id'] ?? null;
         }

         $query = DB::table('products as p')
            ->select(
               'u.id as seller_id',
               'u.username as seller_name',
               'u.email as seller_email',
               DB::raw('COUNT(DISTINCT p.id) as total_portados'),
               DB::raw('MIN(p.updated_at) as primera_portabilidad'),
               DB::raw('MAX(p.updated_at) as ultima_portabilidad'),
               DB::raw('GROUP_CONCAT(DISTINCT p.folio ORDER BY p.folio SEPARATOR ", ") as folios'),
               DB::raw('COUNT(DISTINCT p.folio) as total_folios'),
               DB::raw('COUNT(DISTINCT p.product_type_id) as tipos_producto_diferentes'),
               DB::raw('ROUND(AVG(DATEDIFF(p.updated_at, p.created_at)), 2) as dias_promedio_activacion')
            )
            ->join('lote_details as ld', function ($join) {
               $join->on('p.id', '=', 'ld.product_id')
                  ->where('ld.active', true);
            })
            ->join('lotes as l', function ($join) {
               $join->on('ld.lote_id', '=', 'l.id')
                  ->where('l.active', true);
            })
            ->join('users as u', 'l.seller_id', '=', 'u.id')
            ->where('p.activation_status', 'Portado')
            ->where('p.active', true)
            // ->whereNull('p.deleted_at')
            ->groupBy('u.id', 'u.username', 'u.email')
            ->orderBy('total_portados', 'desc');

         // Filtrar por vendedor específico
         if ($sellerId) {
            if (is_array($sellerId)) {
               $query->whereIn('u.id', $sellerId);
            } else {
               $query->where('u.id', $sellerId);
            }
         }

         // Filtrar por rango de fechas
         if (isset($filters['start_date'])) {
            $query->whereDate('p.updated_at', '>=', $filters['start_date']);
         }

         if (isset($filters['end_date'])) {
            $query->whereDate('p.updated_at', '<=', $filters['end_date']);
         }

         // Filtrar por tipo de producto
         if (isset($filters['product_type_id'])) {
            $query->where('p.product_type_id', $filters['product_type_id']);
         }

         // Filtrar por location_status
         if (isset($filters['location_status'])) {
            $query->where('p.location_status', $filters['location_status']);
         }

         $report = $query->get();

         // Procesar los resultados
         $processedReport = $report->map(function ($item) {
            return [
               'seller' => [
                  'id' => $item->seller_id,
                  'name' => $item->seller_name,
                  'email' => $item->seller_email
               ],
               'metrics' => [
                  'total_portados' => (int) $item->total_portados,
                  'total_folios' => (int) $item->total_folios,
                  'tipos_producto_diferentes' => (int) $item->tipos_producto_diferentes,
                  'dias_promedio_activacion' => (float) $item->dias_promedio_activacion
               ],
               'timeline' => [
                  'primera_portabilidad' => $item->primera_portabilidad,
                  'ultima_portabilidad' => $item->ultima_portabilidad
               ],
               'folios' => $item->folios ? explode(', ', $item->folios) : []
            ];
         });

         // Calcular estadísticas generales
         $summary = [
            'total_vendedores' => $report->count(),
            'total_productos_portados' => $report->sum('total_portados'),
            'total_folios' => $report->sum('total_folios'),
            'promedio_portados_por_vendedor' => $report->avg('total_portados') ?? 0,
            'vendedor_top' => $report->isNotEmpty() ? [
               'name' => $report->first()->seller_name,
               'total_portados' => $report->first()->total_portados
            ] : null
         ];

         // Retornar estructura directamente
         return [
            'success' => true,
            'data' => $processedReport,
            'summary' => $summary,
            'metadata' => [
               'total_records' => $processedReport->count(),
               'filters_applied' => array_filter($filters, fn($value) => !is_null($value)),
               'generated_at' => now()->toDateTimeString()
            ]
         ];
      } catch (\Exception $ex) {
         \Log::error('Error en getPortabilityBySellerReport: ' . $ex->getMessage());

         // Retornar estructura de error directamente
         return [
            'success' => false,
            'error' => 'Error al obtener reporte de portabilidad por vendedor',
            'message' => $ex->getMessage(),
            'data' => [],
            'summary' => [],
            'metadata' => [
               'generated_at' => now()->toDateTimeString()
            ]
         ];
      }
   }
}