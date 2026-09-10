<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\CarCatalog;
use Illuminate\Support\Facades\Storage;

class CarCatalogManager extends Component
{
    use WithFileUploads;

    public $cars;
    public $brand, $model, $years;
    public $brand_logo, $model_image;
    public $edit_id = null;

    public function mount()
    {
        $this->loadCars();
    }

    public function loadCars()
    {
        $this->cars = CarCatalog::orderBy('brand')->orderBy('model')->get();
    }

    public function save()
    {
        $this->validate([
            'brand' => 'required|string',
            'model' => 'required|string',
            'years' => 'required|string',
            'brand_logo' => 'nullable|image|max:1024',
            'model_image' => 'nullable|image|max:1024',
        ]);

        $brandLogoPath = $this->brand_logo ? $this->brand_logo->store('cars/brands', 'public') : null;
        $modelImagePath = $this->model_image ? $this->model_image->store('cars/models', 'public') : null;

                $car = CarCatalog::updateOrCreate(
            ['id' => $this->edit_id],
            [
                'brand' => ucfirst(strtolower($this->brand)),
                'model' => ucfirst(strtolower($this->model)),
                'years' => $this->years,
                'brand_logo' => $brandLogoPath,
                'model_image' => $modelImagePath,
            ]
        );
        // ✅ إرسال بيانات السيارة للموقع
        \App\Jobs\SyncCarCatalogJob::dispatch($car->id);
        $this->reset(['brand', 'model', 'years', 'brand_logo', 'model_image', 'edit_id']);
        $this->loadCars();
        session()->flash('success', 'Vehicle saved successfully!');
    }

    public function edit($id)
    {
        $car = CarCatalog::find($id);
        $this->edit_id = $car->id;
        $this->brand = $car->brand;
        $this->model = $car->model;
        $this->years = $car->years;
    }

        public function delete($id)
    {
        $car = CarCatalog::find($id);
        if ($car) {
            // ✅ إرسال أمر الحذف للموقع السحابي
            \App\Jobs\SyncCarCatalogDeleteJob::dispatch($car->brand, $car->model);
            
            // حذف السيارة من الـ POS المحلي
            $car->delete();
        }
        
        $this->loadCars();
        session()->flash('success', 'Vehicle deleted successfully from POS and Cloud!');
    }

    public function render()
    {
        return view('livewire.car-catalog-manager');
    }
}