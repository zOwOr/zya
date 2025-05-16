@extends('dashboard.body.main')

@section('container')
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                @if (session()->has('success'))
                    <div class="alert text-white bg-success" role="alert">
                        <div class="iq-alert-text">{{ session('success') }}</div>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                @endif
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
                    <div class="mb-3">
                        <h4 class="mb-3">Lista de reparaciónes</h4>
                        <p class="mb-0">Un Dashboard de roles le permite recopilar y visualizar fácilmente datos de
                            reparaciónes de equipos. </p>
                    </div>

                    <div class="container">
                        <a href="{{ route('repairs.create') }}" class="btn btn-primary mb-3">Nueva Reparación</a>

                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>IMEI</th>
                                    <th>Estado</th>
                                    <th>Ingreso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reparaciones as $rep)
                                    <tr>
                                        <td>{{ $rep->cliente }}</td>
                                        <td>{{ $rep->marca }}</td>
                                        <td>{{ $rep->modelo }}</td>
                                        <td>{{ $rep->imei }}</td>
                                        <td>{{ $rep->estado }}</td>
                                        <td>{{ $rep->fecha_ingreso }}</td>
                                        <td>
                                            <a href="{{ route('repairs.edit', $rep->id) }}" class="btn btn-sm btn-warning">
                                                ✏️ Editar
                                            </a>
                                            <a href="{{ route('repairs.show', $rep->id) }}" class="btn btn-sm btn-primary">
                                                ✏️ Ver
                                            </a>
                                            <form action="{{ route('repairs.destroy', $rep->id) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('¿Eliminar esta reparación?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">🗑 Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{ $reparaciones->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endsection
