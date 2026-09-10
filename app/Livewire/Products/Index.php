<?php

namespace App\Livewire\Products;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Supplier;
use App\Models\ProductDetail;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    // Recherche et filtrage
    public $search = '';
    public $selected_category = '';

    // Données de l'article
    public $product_id, $name, $barcode, $category_id, $unit_id, $supplier_id, $location;
    public $purchase_price = 0;
    public $price_1 = 0;
    public $price_2 = 0;
    public $price_3 = 0;
    public $price_4 = 0;
    public $current_stock = 0;
    public $min_stock_alert = 5;

    // Système de boîte / carton
    public $box_barcode;
    public $package_items_count = 1; 
    public $input_type = 'pieces'; 
    public $box_count = 0; 

    // Variables pour la boutique en ligne (E-commerce)
    public $sku;
    public $type;
    public $material;
    public $colors_input;
    public $gallery_images = []; 

    // Détails du produit (Rich Text)
    public $details_content;
    public $details_published = true;

    // Image de l'article
    public $photo;
    public $existing_image;

    // Fenêtres rapides (Modals)
    public $new_category_name;
    public $new_unit_name;
    public $new_supplier_name;
    public $new_supplier_phone;

    public $is_edit = false;
    public $activeTab = 'basic';

    public function addSupplierQuickly()
    {
        $this->validate([
            'new_supplier_name' => 'required|string|max:255|unique:suppliers,name',
            'new_supplier_phone' => 'nullable|string|max:20'
        ]);

        Supplier::create([
            'name' => $this->new_supplier_name,
            'phone' => $this->new_supplier_phone
        ]);

        $this->new_supplier_name = '';
        $this->new_supplier_phone = '';

        $this->dispatch('close-modal', modalId: 'quickSupplierModal');
        session()->flash('success', 'Nouveau fournisseur ajouté avec succès !');
    }

    public function resetFields()
    {
        $this->reset([
            'product_id', 'name', 'barcode', 'category_id', 'unit_id', 'supplier_id', 'location',
            'purchase_price', 'price_1', 'price_2', 'price_3', 'price_4', 'current_stock', 'min_stock_alert',
            'photo', 'existing_image', 'is_edit',
            'sku', 'type', 'material', 'colors_input', 'gallery_images',
            'details_content', 'details_published',
            'box_barcode', 'package_items_count', 'input_type', 'box_count'
        ]);
        $this->activeTab = 'basic';
        $this->package_items_count = 1;
        $this->input_type = 'pieces';
        $this->dispatch('editor-reset');
    }

    public function generateRandomBarcode()
    {
        do {
            $code = '210' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $code)->exists());

        $this->barcode = $code;
    }

    public function addCategoryQuickly()
    {
        $this->validate(['new_category_name' => 'required|string|max:255|unique:categories,name']);
        Category::create(['name' => $this->new_category_name]);
        $this->new_category_name = '';
        $this->dispatch('close-modal', modalId: 'quickCategoryModal');
        session()->flash('success', 'Nouvelle catégorie ajoutée avec succès !');
    }

    public function addUnitQuickly()
    {
        $this->validate(['new_unit_name' => 'required|string|max:255|unique:units,name']);
        Unit::create(['name' => $this->new_unit_name]);
        $this->new_unit_name = '';
        $this->dispatch('close-modal', modalId: 'quickUnitModal');
        session()->flash('success', 'Nouvelle unité ajoutée avec succès !');
    }

    public function removePhoto()
    {
        $this->photo = null;
        $this->existing_image = null;
    }

    public function saveProduct()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:products,barcode,' . $this->product_id,
            'purchase_price' => 'required|numeric|min:0',
            'price_1' => 'required|numeric|min:0',
            'photo' => 'nullable|image|max:2048',
            'package_items_count' => 'required|integer|min:1',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:2048'
        ], [
            'name.required' => 'Le nom de l\'article est un champ obligatoire.',
            'barcode.required' => 'Le code-barres est requis, veuillez le saisir ou le générer.',
            'barcode.unique' => 'Ce code-barres est déjà enregistré pour un autre article.',
            'purchase_price.required' => 'Veuillez saisir le prix d\'achat.',
            'price_1.required' => 'Veuillez saisir le prix de vente (détail).',
            'photo.image' => 'Le fichier doit être une image valide.',
            'photo.max' => 'La taille de l\'image ne doit pas dépasser 2 Mo.',
        ]);

        $final_stock = (float) $this->current_stock;
        if ($this->input_type === 'boxes' && !$this->is_edit) {
            $final_stock = (float) $this->box_count * (int) $this->package_items_count;
        }

        $imagePath = $this->existing_image;
        if ($this->photo) {
            $imagePath = $this->photo->store('products', 'public');
        }

        $galleryPaths = [];
        if (!empty($this->gallery_images)) {
            foreach ($this->gallery_images as $img) {
                $galleryPaths[] = $img->store('products/gallery', 'public');
            }
        }

        $colorsArray = !empty($this->colors_input) ? array_map('trim', explode(',', $this->colors_input)) : null;

        $product = Product::updateOrCreate(
            ['id' => $this->product_id],
            [
                'name' => $this->name,
                'barcode' => $this->barcode,
                'category_id' => $this->category_id ?: null,
                'unit_id' => $this->unit_id ?: null,
                'supplier_id' => $this->supplier_id ?: null,
                'location' => $this->location ?: null,
                'purchase_price' => $this->purchase_price ?: 0,
                'price_1' => $this->price_1 ?: 0,
                'price_2' => $this->price_2 ?: ($this->price_1 ?: 0),
                'price_3' => $this->price_3 ?: ($this->price_1 ?: 0),
                'price_4' => $this->price_4 ?: ($this->price_1 ?: 0),
                'current_stock' => $final_stock, 
                'min_stock_alert' => $this->min_stock_alert ?: 5, 
                'image' => $imagePath,
                'images' => !empty($galleryPaths) ? $galleryPaths : null,
                'package_items_count' => $this->package_items_count ?: 1, 
                'box_barcode' => $this->box_barcode ?: null, 
                'sku' => $this->sku,
                'type' => $this->type,
                'material' => $this->material,
                'colors' => $colorsArray,
            ]
        );

        if ($this->details_content || $this->details_published) {
            ProductDetail::updateOrCreate(
                ['product_id' => $product->id],
                [
                    'content' => $this->details_content ?: null,
                    'is_published' => $this->details_published ?? true,
                ]
            );
        }

        session()->flash('success', $this->is_edit ? 'L\'article a été mis à jour avec succès !' : 'L\'article a été ajouté avec succès !');
        $this->resetFields();
    }

    public function editProduct($id)
    {
        //$product = Product::with('details')->findOrFail($id);
        $product = Product::findOrFail($id);
        $this->product_id = $product->id;
        $this->name = $product->name;
        $this->barcode = $product->barcode;
        $this->category_id = $product->category_id;
        $this->unit_id = $product->unit_id;
        $this->supplier_id = $product->supplier_id;
        $this->location = $product->location;
        $this->purchase_price = $product->purchase_price;
        $this->price_1 = $product->price_1;
        $this->price_2 = $product->price_2;
        $this->price_3 = $product->price_3;
        $this->price_4 = $product->price_4;
        $this->current_stock = $product->current_stock;
        $this->min_stock_alert = $product->min_stock_alert;
        $this->existing_image = $product->image ?? null;
        $this->photo = null;
        
        $this->box_barcode = $product->box_barcode;
        $this->package_items_count = $product->package_items_count;
        $this->input_type = 'pieces'; 

        $this->sku = $product->sku;
        $this->type = $product->type;
        $this->material = $product->material;
        
        $this->colors_input = is_array($product->colors) ? implode(', ', $product->colors) : $product->colors;
        
        $this->gallery_images = [];

        $this->is_edit = true;
        $this->activeTab = 'basic';

        if ($product->details) {
            $this->details_content = $product->details->content;
            $this->details_published = $product->details->is_published;
            $this->dispatch('editor-set-content', content: $product->details->content);
        } else {
            $this->details_content = null;
            $this->details_published = true;
            $this->dispatch('editor-set-content', content: '');
        }
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);

        \App\Models\SaleItem::where('product_id', $id)->delete();
        \App\Models\PurchaseItem::where('product_id', $id)->delete();
        ProductDetail::where('product_id', $id)->delete();

        $product->delete();

        session()->flash('success', 'L\'article a été supprimé définitivement.');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $searchTerm = $this->search;
        $catTerm = $this->selected_category;

        $productsList = Product::when($searchTerm, function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('barcode', 'like', '%' . $searchTerm . '%')
                      ->orWhere('box_barcode', 'like', '%' . $searchTerm . '%')
                      ->orWhere('location', 'like', '%' . $searchTerm . '%');
            })
            ->when($catTerm, function ($query) use ($catTerm) {
                $query->where('category_id', $catTerm);
            })
            ->paginate(10);

        return view('livewire.products.index', [
            'productsList' => $productsList,
            'categories' => Category::all(),
            'units' => Unit::all(),
            'suppliers' => Supplier::all(),
        ]);
    }
}