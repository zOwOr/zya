@extends('dashboard.body.main')

@section('container')
<div class="container-fluid">
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap">
        <div>
            <h4 class="mb-1 font-weight-bold">
                <i class="fa-solid fa-store text-primary mr-2"></i>Gestión de Sucursales
            </h4>
            <p class="text-muted mb-0 font-size-13">
                Configura los datos de las sucursales (dirección y teléfono) que se reflejan en el sistema y en las notas de venta / pólizas en PDF.
            </p>
        </div>
        <div>
            <a href="{{ route('branches.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus mr-1"></i>Nueva Sucursal
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fa-solid fa-check-circle mr-2"></i>{{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Main Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 5%;" class="text-center">#</th>
                            <th style="width: 25%;">Sucursal</th>
                            <th style="width: 40%;">Dirección (Encabezado PDF)</th>
                            <th style="width: 15%;">Teléfono</th>
                            <th style="width: 15%;" class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $branch)
                            <tr>
                                <td class="text-center text-muted font-size-12">{{ $branch->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm bg-primary-light rounded text-primary text-center p-2 mr-2" style="background: rgba(0, 102, 204, 0.1); width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-store font-size-16"></i>
                                        </div>
                                        <div>
                                            <span class="font-weight-bold text-dark font-size-14">{{ $branch->name }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if(!empty(trim($branch->address)))
                                        <div class="font-size-13 text-secondary" style="white-space: pre-line; line-height: 1.4;">
                                            {!! nl2br(e($branch->address)) !!}
                                        </div>
                                    @else
                                        <span class="badge badge-warning text-dark font-size-11">
                                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>Dirección pendiente
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty(trim($branch->phone)))
                                        <div class="font-size-13 font-weight-bold text-dark">
                                            <i class="fa-solid fa-phone text-muted mr-1 font-size-11"></i>{{ $branch->phone }}
                                        </div>
                                    @else
                                        <span class="badge badge-warning text-dark font-size-11">
                                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>Sin teléfono
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('branches.edit', $branch->id) }}" class="btn btn-sm btn-outline-primary" title="Editar información de sucursal">
                                            <i class="fa-solid fa-pen-to-square mr-1"></i>Editar
                                        </a>
                                        <form action="{{ route('branches.destroy', $branch->id) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('¿Está seguro de eliminar la sucursal {{ $branch->name }}? Esta acción no se puede deshacer.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger ml-1" type="submit" title="Eliminar sucursal">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-store-slash font-size-36 mb-2 d-block text-muted"></i>
                                    No hay sucursales registradas.
                                    <div class="mt-2">
                                        <a href="{{ route('branches.create') }}" class="btn btn-sm btn-primary">Crear la primera sucursal</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($branches->hasPages())
            <div class="card-footer bg-white d-flex justify-content-end py-2">
                {{ $branches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
