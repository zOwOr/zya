<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unitcost',
        'total',
        'is_dynamic',
        'dynamic_product_name',
        'dynamic_brand',
        'dynamic_model',
        'dynamic_imei',
        'dynamic_category_status',
        'dynamic_warranty_time',
        'dynamic_observations',
        'dynamic_product_code',
    ];

    protected $casts = [
        'is_dynamic' => 'boolean',
    ];

    protected $guarded = [
        'id',
    ];

    /**
     * Relación con el modelo Product (solo para productos del inventario).
     * Para productos dinámicos, esta relación devolverá null.
     */
    public function productRelation()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    /**
     * Accesor `product`: si el detalle es un producto dinámico, retorna
     * un objeto Product virtual con los datos almacenados en los campos
     * dinámicos. De lo contrario, retorna el producto real del inventario.
     * Esto garantiza compatibilidad total con todas las vistas existentes
     * que usan $item->product->campo.
     */
    public function getProductAttribute(): ?Product
    {
        if ($this->is_dynamic) {
            $virtual = new Product();
            $virtual->product_name    = $this->dynamic_product_name;
            $virtual->brand           = $this->dynamic_brand;
            $virtual->model           = $this->dynamic_model;
            $virtual->imei            = $this->dynamic_imei;
            $virtual->category_status = $this->dynamic_category_status;
            $virtual->warranty_time   = $this->dynamic_warranty_time;
            $virtual->observations    = $this->dynamic_observations;
            $virtual->product_code    = $this->dynamic_product_code;
            $virtual->product_image   = null;
            $virtual->stock_quantity  = 0;
            return $virtual;
        }

        return $this->productRelation;
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }
}
