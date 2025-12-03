<?php

namespace App\Services;

class NormalizerDateService
{
   function normalizeDate($value)
   {
      if (empty($value)) {
         return date('Y-m-d');
      }

      // Si ya viene con formato YYYY-MM-DD, lo regresamos tal cual
      if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
         return $value;
      }

      // Si viene como DD/MM/YYYY
      if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
         return "{$m[3]}-{$m[2]}-{$m[1]}";
      }

      // Si viene como MM-DD-YYYY ó algo que date() entienda
      $timestamp = strtotime($value);
      if ($timestamp !== false) {
         return date('Y-m-d', $timestamp);
      }

      return date('Y-m-d'); // fallback
   }
}
