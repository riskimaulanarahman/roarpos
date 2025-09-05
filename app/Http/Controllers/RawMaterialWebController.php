<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialMovement;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class RawMaterialWebController extends Controller
{
    public function index(Request $request)
    {
        $q = RawMaterial::query();
        if ($request->filled('search')) {
            $s = $request->input('search');
            $q->where(function($qq) use ($s){
                $qq->where('name','like',"%$s%")
                   ->orWhere('sku','like',"%$s%");
            });
        }
        $materials = $q->orderBy('name')->paginate(15);
        return view('pages.raw_materials.index', compact('materials'));
    }

    public function create()
    {
        return view('pages.raw_materials.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => ['required','string','max:50','unique:raw_materials,sku'],
            'name' => ['required','string','max:255'],
            'unit' => ['required','in:g,ml,pcs,kg,l'],
            'unit_cost' => ['required','numeric','min:0'],
            'stock_qty' => ['nullable','numeric','min:0'],
            'min_stock' => ['nullable','numeric','min:0'],
        ]);
        RawMaterial::create($data);
        return redirect()->route('raw-materials.index')->with('success','Bahan dibuat');
    }

    public function edit(RawMaterial $raw_material)
    {
        return view('pages.raw_materials.edit', ['material' => $raw_material]);
    }

    public function update(Request $request, RawMaterial $raw_material)
    {
        $data = $request->validate([
            'sku' => ['required','string','max:50','unique:raw_materials,sku,'.$raw_material->id],
            'name' => ['required','string','max:255'],
            'unit' => ['required','in:g,ml,pcs,kg,l'],
            'unit_cost' => ['required','numeric','min:0'],
            'stock_qty' => ['required','numeric','min:0'],
            'min_stock' => ['required','numeric','min:0'],
        ]);
        $raw_material->update($data);
        return redirect()->route('raw-materials.index')->with('success','Bahan diperbarui');
    }

    public function adjustForm(RawMaterial $raw_material)
    {
        return view('pages.raw_materials.adjust_stock', ['material' => $raw_material]);
    }

    public function adjust(Request $request, RawMaterial $raw_material, InventoryService $inventory)
    {
        $data = $request->validate([
            'qty_change' => ['required','numeric','not_in:0'],
            'unit_cost' => ['nullable','numeric','min:0'],
            'notes' => ['nullable','string'],
        ]);
        $inventory->adjustStock($raw_material, (float)$data['qty_change'], 'adjustment', $data['unit_cost'] ?? null, 'manual_adjustment', $raw_material->id, $data['notes'] ?? null);
        return redirect()->route('raw-materials.movements', $raw_material->id)->with('success','Stok diperbarui');
    }

    public function movements(RawMaterial $raw_material)
    {
        $movements = $raw_material->movements()->orderByDesc('occurred_at')->paginate(30);
        return view('pages.raw_materials.movements', [
            'material' => $raw_material,
            'movements' => $movements,
        ]);
    }
}

