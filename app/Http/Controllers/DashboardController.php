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
            'seller_id.*' => 'exists:employees,id',
            'location_status' => 'nullable|in:Stock,Asignado,Distribuido',
            'activation_status' => 'nullable|in:Virgen,Pre-activado,Activado,Portado,Caducado',
            'product_type_id' => 'nullable|exists:product_types,id',
            'search' => 'nullable|string|max:100',
            'pos_id' => 'nullable|exists:points_of_sale,id',
         ]);

         // Estadísticas principales (se ejecutan en paralelo)
         // $stats = DB::transaction(function () use ($filters) {
         //    $query = Product::query()->active();

         //    // Aplicar filtros
         //    if (isset($filters['start_date']) && isset($filters['end_date'])) {
         //       $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
         //    }

         //    if (isset($filters['seller_id'])) {
         //       // Obtener productos asignados a estos vendedores a través de lotes
         //       $loteIds = Lote::whereIn('seller_id', $filters['seller_id'])
         //          ->pluck('id');

         //       $productIds = LoteDetail::whereIn('lote_id', $loteIds)
         //          ->pluck('product_id');

         //       $query->whereIn('id', $productIds);
         //    }

         //    if (isset($filters['location_status'])) {
         //       $query->where('location_status', $filters['location_status']);
         //    }

         //    if (isset($filters['activation_status'])) {
         //       $query->where('activation_status', $filters['activation_status']);
         //    }

         //    // 1. Totales generales
         //    $totalProducts = (clone $query)->count();
         //    $totalActivated = (clone $query)->where('activation_status', 'Activado')->count();
         //    $totalPortados = (clone $query)->where('activation_status', 'Portado')->count();

         //    // 2. Portabilidad por mes
         //    $portabilityByMonth = Product::where('activation_status', 'Portado')
         //       ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
         //          $q->whereBetween('updated_at', [$filters['start_date'], $filters['end_date']]);
         //       })
         //       ->selectRaw('MONTH(updated_at) as month, COUNT(*) as count')
         //       ->groupBy('month')
         //       ->orderBy('month')
         //       ->get()
         //       ->mapWithKeys(function ($item) {
         //          return [$item->month => $item->count];
         //       });

         //    // 3. Top vendedores por portaciones
         //    $topSellers = DB::table('products as p')
         //       ->join('lote_details as ld', 'p.id', '=', 'ld.product_id')
         //       ->join('lotes as l', 'ld.lote_id', '=', 'l.id')
         //       ->join('employees as e', 'l.seller_id', '=', 'e.id')
         //       ->where('p.activation_status', 'Portado')
         //       ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
         //          $q->whereBetween('p.updated_at', [$filters['start_date'], $filters['end_date']]);
         //       })
         //       ->select('e.id', 'e.name', DB::raw('COUNT(p.id) as port_count'))
         //       ->groupBy('e.id', 'e.name')
         //       ->orderByDesc('port_count')
         //       ->limit(10)
         //       ->get();

         //    // 4. Productos más portados
         //    $topProducts = Product::where('activation_status', 'Portado')
         //       ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
         //          $q->whereBetween('updated_at', [$filters['start_date'], $filters['end_date']]);
         //       })
         //       ->select('producto', DB::raw('COUNT(*) as count'))
         //       ->groupBy('producto')
         //       ->orderByDesc('count')
         //       ->limit(5)
         //       ->get();

         //    // 5. Distribución por estatus
         //    $statusDistribution = $query->select('activation_status', DB::raw('COUNT(*) as count'))
         //       ->groupBy('activation_status')
         //       ->get()
         //       ->mapWithKeys(function ($item) {
         //          return [$item->activation_status => $item->count];
         //       });

         //    // 6. Números portados con vendedor
         //    $portedNumbers = Product::where('activation_status', 'Portado')
         //       ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
         //          $q->whereBetween('updated_at', [$filters['start_date'], $filters['end_date']]);
         //       })
         //       ->with(['loteDetails.lote.seller'])
         //       ->select('celular', 'iccid', 'producto', 'updated_at as port_date')
         //       ->orderBy('updated_at', 'desc')
         //       ->limit(50)
         //       ->get()
         //       ->map(function ($product) {
         //          $seller = $product->loteDetails->first()?->lote?->seller;
         //          return [
         //             'celular' => $product->celular,
         //             'iccid' => $product->iccid,
         //             'producto' => $product->producto,
         //             'port_date' => $product->port_date,
         //             'vendedor' => $seller ? $seller->name : 'No asignado',
         //             'vendedor_pin_color' => $seller ? $seller->pin_color : '#ccc',
         //          ];
         //       });

         //    // 7. Puntos de venta con inventario
         //    $pointsOfSale = PointOfSale::with(['products' => function ($q) {
         //       $q->where('location_status', 'Distribuido');
         //    }, 'visits.seller'])
         //       ->get()
         //       ->map(function ($pos) {
         //          $lastVisit = $pos->visits->sortByDesc('visit_date')->first();
         //          return [
         //             'id' => $pos->id,
         //             'name' => $pos->name,
         //             'lat' => $pos->lat,
         //             'lng' => $pos->lng,
         //             'address' => $pos->address,
         //             'inventory_count' => $pos->products->count(),
         //             'activated_count' => $pos->products->where('activation_status', 'Activado')->count(),
         //             'portado_count' => $pos->products->where('activation_status', 'Portado')->count(),
         //             'last_visit' => $lastVisit ? $lastVisit->visit_date : null,
         //             'last_seller' => $lastVisit ? $lastVisit->seller->name : null,
         //             'seller_pin_color' => $lastVisit ? $lastVisit->seller->pin_color : '#ccc',
         //             'total_visits' => $pos->visits->count(),
         //          ];
         //       });



         //    return [
         //       'stats' => [
         //          'total_products' => $totalProducts,
         //          'total_activated' => $totalActivated,
         //          'total_portados' => $totalPortados,
         //          'portability_rate' => $totalProducts > 0 ? round(($totalPortados / $totalProducts) * 100, 2) : 0,
         //       ],
         //       'portability_by_month' => $portabilityByMonth,
         //       'top_sellers' => $topSellers,
         //       'top_products' => $topProducts,
         //       'status_distribution' => $statusDistribution,
         //       'ported_numbers' => $portedNumbers,
         //       'points_of_sale' => $pointsOfSale,
         //    ];
         // });

         // Ejecutar todas las consultas en paralelo
         $results = DB::transaction(function () use ($filters) {
            return [
               'stats' => $this->getGeneralStats($filters),
               'ported_products' => $this->getPortedProductsWithDetails($filters),
               'sellers_performance' => $this->getSellersPerformance($filters),
               'points_of_sale' => $this->getPointsOfSaleWithInventory($filters),
               'portability_by_month' => $this->getPortabilityByMonth($filters),
               'top_sellers' => $this->getTopSellers($filters),
               'status_distribution' => $this->getStatusDistribution($filters),
               // 'top_products' => $this->getTopProducts($filters),
               'visits_summary' => $this->getVisitsSummary($filters),
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
         ->with(['loteDetails.lote.seller.personalInfo'])
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
            $personalInfo = $seller?->personalInfo;

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
               'seller_name' => $personalInfo?->full_name ?? 'No asignado',
               'seller_pin_color' => $seller?->pin_color ?? '#ccc',
               'seller_cellphone' => $personalInfo?->cellphone,
               'seller_position' => $personalInfo?->position,

               // Distribución si aplica
               'distributed_to' => $this->getDistributionPoint($product),
            ];
         });
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

   private function getSellersPerformance(array $filters)
   {
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
               $q->whereIn('employee_id', $filters['seller_id']);
            }
         )
         ->get()
         ->map(function ($seller) use ($filters) {
            $sellerId = $seller->employee_id;

            // Productos asignados
            $assignedProducts = $this->getSellerAssignedProducts($sellerId, $filters);

            // Productos distribuidos
            $distributedProducts = $this->getSellerDistributedProducts($sellerId, $filters);

            // Productos portados
            $portedProducts = $this->getSellerPortedProducts($sellerId, $filters);

            // Puntos de venta asignados
            $assignedPOS = $this->getSellerPointsOfSale($sellerId, $filters);

            // Visitas realizadas
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
                  'total_assigned' => $assignedProducts->count(),
                  'total_distributed' => $distributedProducts->count(),
                  'total_ported' => $portedProducts->count(),
                  'portability_rate' => $assignedProducts->count() > 0
                     ? round(($portedProducts->count() / $assignedProducts->count()) * 100, 2)
                     : 0,

                  // Desglose por estatus
                  'by_activation_status' => $assignedProducts
                     ->groupBy('activation_status')
                     ->map->count(),

                  'by_location_status' => $assignedProducts
                     ->groupBy('location_status')
                     ->map->count(),
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
                  'total' => $visits->count(),
                  'by_type' => $visits->groupBy('visit_type')->map->count(),
                  'by_month' => $visits->groupBy(function ($visit) {
                     return Carbon::parse($visit->created_at)->format('Y-m');
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

   // Helper functions para estadísticas de vendedor
   private function getSellerAssignedProducts($sellerId, $filters)
   {
      return Product::whereHas('loteDetails.lote', function ($q) use ($sellerId) {
         $q->where('seller_id', $sellerId);
      })
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('products.created_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->get();
   }

   private function getSellerDistributedProducts($sellerId, $filters)
   {
      return Product::where('location_status', 'Distribuido')
         ->whereHas('movements', function ($q) use ($sellerId) {
            $q->where('executed_by', $sellerId)
               ->where('action', 'distribuir');
         })
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('products.updated_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->get();
   }

   private function getSellerPortedProducts($sellerId, $filters)
   {
      return Product::where('activation_status', 'Portado')
         ->whereHas('loteDetails.lote', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
         })
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('products.updated_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->get();
   }

   private function getSellerPointsOfSale($sellerId, $filters)
   {
      return PointOfSale::whereHas('visits', function ($q) use ($sellerId) {
         $q->where('seller_id', $sellerId);
      })
         ->orWhereHas('movements', function ($q) use ($sellerId) {
            $q->where('executed_by', $sellerId)
               ->where('action', 'distribuir')
               ->whereColumn('destination', 'points_of_sale.id');
         })
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->get();
   }

   private function getSellerVisits($sellerId, $filters)
   {
      return Visit::where('seller_id', $sellerId)
         ->with('point_of_sale')
         ->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
            $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
         })
         ->orderBy('created_at', 'desc')
         ->get();
   }

   private function getPointsOfSaleWithInventory(array $filters)
   {
      return PointOfSale::with([
         'products' => function ($q) use ($filters) {
            $q->when(isset($filters['activation_status']), function ($q) use ($filters) {
               $q->where('activation_status', $filters['activation_status']);
            })
               ->when(isset($filters['location_status']), function ($q) use ($filters) {
                  $q->where('location_status', $filters['location_status']);
               });
         },
         'visits' => function ($q) use ($filters) {
            $q->when(isset($filters['start_date']) && isset($filters['end_date']), function ($q) use ($filters) {
               $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
            })
               ->with('seller.personalInfo');
         },
         'latestVisit.seller.personalInfo',
      ])
         ->when(isset($filters['pos_id']), function ($q) use ($filters) {
            $q->where('id', $filters['pos_id']);
         })
         ->get()
         ->map(function ($pos) use ($filters) {
            $latestVisit = $pos->latestVisit;
            $seller = $latestVisit?->seller;
            $personalInfo = $seller?->personalInfo;

            // Productos en este POS
            $products = $pos->products;

            // Última distribución
            $lastDistribution = ProductMovement::where('destination', $pos->id)
               ->where('action', 'distribuir')
               ->latest()
               ->first();

            // Vendedor que más ha visitado
            $topSeller = Visit::where('pos_id', $pos->id)
               ->select('seller_id', DB::raw('COUNT(*) as visit_count'))
               ->groupBy('seller_id')
               ->orderByDesc('visit_count')
               ->first();

            $topSellerInfo = $topSeller ? VW_User::where('employee_id', $topSeller->seller_id)->first() : null;

            return [
               'id' => $pos->id,
               'name' => $pos->name,
               'contact_name' => $pos->contact_name,
               'contact_phone' => $pos->contact_phone,
               'address' => $pos->address,
               'lat' => (float) $pos->lat,
               'lon' => (float) $pos->lon,
               'ubication' => $pos->ubication,

               // Inventario
               'inventory' => [
                  'total_products' => $products->count(),
                  'by_activation_status' => $products->groupBy('activation_status')->map->count(),
                  'by_location_status' => $products->groupBy('location_status')->map->count(),
                  'ported_count' => $products->where('activation_status', 'Portado')->count(),
                  'activated_count' => $products->where('activation_status', 'Activado')->count(),

                  // Detalle de productos portados
                  'ported_products' => $products->where('activation_status', 'Portado')
                     ->take(5)
                     ->map(function ($product) {
                        return [
                           'celular' => $product->celular,
                           'iccid' => $product->iccid,
                           'ported_date' => $product->updated_at,
                        ];
                     }),
               ],

               // Visitas
               'visits' => [
                  'total' => $pos->visits->count(),
                  'by_type' => $pos->visits->groupBy('visit_type')->map->count(),
                  'last_visit' => $latestVisit ? [
                     'date' => $latestVisit->created_at,
                     'type' => $latestVisit->visit_type,
                     'seller_name' => $personalInfo?->full_name,
                     'seller_pin_color' => $seller?->pin_color,
                     'chips_delivered' => $latestVisit->chips_delivered,
                     'chips_sold' => $latestVisit->chips_sold,
                     'observations' => $latestVisit->observations,
                  ] : null,

                  'top_seller' => $topSellerInfo ? [
                     'name' => $topSellerInfo->full_name,
                     'pin_color' => $topSellerInfo->pin_color,
                     'visit_count' => $topSeller->visit_count,
                  ] : null,
               ],

               // Última distribución
               'last_distribution' => $lastDistribution ? [
                  'date' => $lastDistribution->executed_at,
                  'executed_by' => $lastDistribution->executed_by,
                  'quantity' => $lastDistribution->description ?
                     intval(preg_replace('/[^0-9]/', '', $lastDistribution->description)) : 0,
               ] : null,

               // Vendedor principal (basado en últimas visitas)
               'primary_seller' => $seller ? [
                  'id' => $seller->id,
                  'name' => $personalInfo?->full_name,
                  'pin_color' => $seller->pin_color,
                  'cellphone' => $personalInfo?->cellphone,
               ] : null,

               // Estadísticas de ventas
               'sales_stats' => [
                  'total_chips_delivered' => $pos->visits->sum('chips_delivered'),
                  'total_chips_sold' => $pos->visits->sum('chips_sold'),
                  'total_chips_remaining' => $pos->visits->sum('chips_remaining'),
                  'conversion_rate' => $pos->visits->sum('chips_delivered') > 0
                     ? round(($pos->visits->sum('chips_sold') / $pos->visits->sum('chips_delivered')) * 100, 2)
                     : 0,
               ],
            ];
         });
   }

   private function getVisitsSummary(array $filters)
   {
      return Visit::select([
         DB::raw('COUNT(*) as total_visits'),
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
                  'total_visits' => $item->total_visits,
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

   private function getGeneralStats(array $filters)
   {
      $query = Product::query();

      // Aplicar filtros
      $this->applyFilters($query, $filters);

      $totalProducts = (clone $query)->count();
      $totalActivated = (clone $query)->where('activation_status', 'Activado')->count();
      $totalPortados = (clone $query)->where('activation_status', 'Portado')->count();
      $totalDistribuidos = (clone $query)->where('location_status', 'Distribuido')->count();

      // Vendedores activos
      $activeSellers = VW_User::where('role_id', 3)
         ->where('active', 1)
         ->when(
            isset($filters['seller_id']) && count($filters['seller_id']) > 0,
            function ($q) use ($filters) {
               $q->whereIn('employee_id', $filters['seller_id']);
            }
         )
         ->count();

      // Puntos de venta activos
      $activePOS = PointOfSale::where('active', 1)->count();

      // Visitas totales
      $totalVisits = Visit::when(
         isset($filters['start_date']) && isset($filters['end_date']),
         function ($q) use ($filters) {
            $q->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
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
         'total_products' => $totalProducts,
         'total_activated' => $totalActivated,
         'total_portados' => $totalPortados,
         'total_distribuidos' => $totalDistribuidos,
         'portability_rate' => $totalProducts > 0 ? round(($totalPortados / $totalProducts) * 100, 2) : 0,
         'activation_rate' => $totalProducts > 0 ? round(($totalActivated / $totalProducts) * 100, 2) : 0,
         'active_sellers' => $activeSellers,
         'active_points_of_sale' => $activePOS,
         'total_visits' => $totalVisits,
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

   private function getTopSellers(array $filters)
   {
      try {
         // Consulta optimizada para obtener los mejores vendedores por portaciones
         $topSellers = DB::table('products as p')
            ->select([
               'e.id',
               DB::raw('CONCAT(pi.name, " ", pi.plast_name) as seller_name'),
               'e.pin_color',
               DB::raw('COUNT(p.id) as port_count'),
               DB::raw('MAX(p.updated_at) as last_port_date')
            ])
            ->join('lote_details as ld', 'p.id', '=', 'ld.product_id')
            ->join('lotes as l', 'ld.lote_id', '=', 'l.id')
            ->join('employees as e', 'l.seller_id', '=', 'e.id')
            ->leftJoin('personal_infos as pi', 'e.id', '=', 'pi.employee_id')
            ->where('p.activation_status', 'Portado')
            ->where('p.active', 1)

            // Aplicar filtros de fecha
            ->when(
               isset($filters['start_date']) && isset($filters['end_date']),
               function ($query) use ($filters) {
                  return $query->whereBetween('p.updated_at', [
                     $filters['start_date'],
                     $filters['end_date']
                  ]);
               }
            )

            // Filtrar por vendedores específicos
            ->when(
               isset($filters['seller_id']) && count($filters['seller_id']) > 0,
               function ($query) use ($filters) {
                  return $query->whereIn('l.seller_id', $filters['seller_id']);
               }
            )

            // Filtrar por búsqueda
            ->when(
               isset($filters['search']),
               function ($query) use ($filters) {
                  return $query->where(function ($q) use ($filters) {
                     $q->where('p.celular', 'like', "%{$filters['search']}%")
                        ->orWhere('p.iccid', 'like', "%{$filters['search']}%");
                  });
               }
            )

            ->groupBy('e.id', 'e.pin_color', 'pi.name', 'pi.plast_name')
            ->orderByDesc('port_count')
            ->limit(10)
            ->get()
            ->map(function ($seller) {
               return [
                  'id' => $seller->id,
                  'name' => $seller->seller_name ?? 'Vendedor ' . $seller->id,
                  'pin_color' => $seller->pin_color ?? $this->generateSellerColor($seller->id),
                  'port_count' => (int) $seller->port_count,
                  'last_port_date' => $seller->last_port_date,
               ];
            });

         return $topSellers;
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
}