<div>
    @if(session()->has('success'))
        <div class="alert alert-success p-2 small fw-bold mb-3">{{ session('success') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card shadow-sm border-top-custom">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0">{{ $edit_id ? 'Edit Vehicle' : 'Add New Vehicle' }}</h6>
                </div>
                <div class="card-body">
                    <form wire:submit.prevent="save">
                        
                        {{-- ✅ عرض جميع الأخطاء إن وجدت --}}
                        @if ($errors->any())
                            <div class="alert alert-danger p-2 small mb-3">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-2">
                            <label class="form-label small fw-bold">Brand (e.g. Toyota)</label>
                            <input type="text" wire:model.live="brand" class="form-control form-control-sm" required>
                            @error('brand') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Model (e.g. Hilux)</label>
                            <input type="text" wire:model.live="model" class="form-control form-control-sm" required>
                            @error('model') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Years (e.g. 2005-2015)</label>
                            <input type="text" wire:model.live="years" class="form-control form-control-sm" required>
                            @error('years') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Brand Logo (Icon)</label>
                            <input type="file" wire:model="brand_logo" class="form-control form-control-sm">
                            @error('brand_logo') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Model Image (Car Photo)</label>
                            <input type="file" wire:model="model_image" class="form-control form-control-sm">
                            @error('model_image') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                            <span wire:loading.remove>Saving Vehicle</span>
                            <span wire:loading>Saving...</span>
                        </button>
                        
                        @if($edit_id)
                            <button type="button" wire:click="reset(['edit_id','brand','model','years'])" class="btn btn-secondary btn-sm w-100 mt-2">Cancel</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Years</th>
                                <th>Images</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cars as $car)
                                <tr>
                                    <td class="fw-bold">{{ $car->brand }}</td>
                                    <td>{{ $car->model }}</td>
                                    <td>{{ $car->years }}</td>
                                    <td>
                                        @if($car->brand_logo)
                                            <img src="{{ Storage::url($car->brand_logo) }}" style="width:30px; height:30px; object-fit:contain;">
                                        @endif
                                        @if($car->model_image)
                                            <img src="{{ Storage::url($car->model_image) }}" style="width:30px; height:30px; object-fit:contain;">
                                        @endif
                                    </td>
                                    <td>
                                        <button wire:click="edit({{ $car->id }})" class="btn btn-sm btn-outline-primary px-1 py-0">Edit</button>
                                        <button wire:click="delete({{ $car->id }})" class="btn btn-sm btn-outline-danger px-1 py-0">Del</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>