@extends('dashboard.body.main')

@section('container')
    <div class="container-fluid">
        <div class="row">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Detalles del pedido</h4>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('order.contract', $order->id) }}" target="_blank" class="btn btn-primary">
                                🖨️ Imprimir Contrato
                            </a>
                        </div>
                    </div>


                    <div class="card-body">
                        <!-- begin: Show Data -->
                        <div class="form-group row align-items-center">
                            <div class="col-md-12">
                                <div class="profile-img-edit">
                                    <div class="crm-profile-img-edit">
                                        <img class="crm-profile-pic rounded-circle avatar-100" id="image-preview"
                                            src="{{ $order->customer && $order->customer->tit_photo ? asset('storage/customers/' . $order->customer->tit_photo) : asset('storage/customers/default.png') }}"
                                            alt="profile-pic">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <table class="table mb-0">
                            <tbody class="ligth-body">
                                @foreach ($orderDetails as $item)
                                    <tr>
                                        <td>
                                            @php
                                                $productName =
                                                    $item->product->product_name ?? 'El Producto ha sido eliminado';
                                                $isPrestamo = stripos($productName, 'Prestamo') !== false;
                                            @endphp

                                            <form action="{{ route('video.upload') }}" method="POST"
                                                enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="order_id" value="{{ $order->id }}">

                                                @if ($orderDetailVideo)
                                                    <!-- Si ya existe un video, mostramos el video guardado y opción para actualizarlo -->
                                                    <div class="mb-3">
                                                        <label class="form-label">Video guardado</label><br>
                                                        <video width="400" controls>
                                                            <source
                                                                src="{{ asset('storage/videos/' . $orderDetailVideo->video) }}"
                                                                type="video/mp4">
                                                            Tu navegador no soporta el elemento de video.
                                                        </video>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label for="video" class="form-label">Actualizar
                                                            video</label>
                                                        <input type="file" name="video" id="video"
                                                            class="form-control" accept="video/*" onchange="previewVideo()">
                                                        @error('video')
                                                            <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                @else
                                                    <!-- Si no existe un video, mostramos el formulario para subir uno nuevo -->
                                                    <div class="mb-3">
                                                        <label for="video" class="form-label">Seleccionar
                                                            video</label>
                                                        <input type="file" name="video" id="video"
                                                            class="form-control" accept="video/*" required
                                                            onchange="previewVideo()">
                                                        @error('video')
                                                            <div class="text-danger">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                @endif

                                                <div class="mb-3">
                                                    <label class="form-label">Vista previa del video</label><br>
                                                    <video id="videoPreview" width="400" controls
                                                        style="display: none;"></video>
                                                </div>

                                                <button type="submit" class="btn btn-primary">
                                                    {{ $orderDetailVideo ? 'Actualizar Video' : 'Subir Video' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>






                        <div class="row align-items-center">
                            <div class="form-group col-md-12">
                                <label>Nombre</label>
                                <input type="text" class="form-control bg-white"
                                    value="{{ $order->customer ? $order->customer->tit_name : 'El cliente ha sido eliminado' }}"
                                    readonly>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Email</label>
                                <input type="text" class="form-control bg-white"
                                    value="{{ $order->customer ? $order->customer->tit_email : 'El cliente ha sido eliminado' }}"
                                    readonly>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Teléfono</label>
                                <input type="text" class="form-control bg-white"
                                    value="{{ $order->customer ? $order->customer->tit_phone : 'El cliente ha sido eliminado' }}"
                                    readonly>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Fecha de la Orden</label>
                                <input type="text" class="form-control bg-white" value="{{ $order->order_date }}"
                                    readonly>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Nota de Venta</label>
                                <input class="form-control bg-white" id="buying_date" value="{{ $order->invoice_no }}"
                                    readonly />
                            </div>

                            <div class="form-group  col-md-6">
                                <form action="{{ route('order.updateDeviceId') }}" method="POST" class="mb-3">
                                    @csrf
                                    <input type="hidden" name="order_id" value="{{ $order->id }}">

                                    <div class="">
                                        <label for="device_id">Device ID</label>
                                        <input type="text" name="device_id" id="device_id" class="form-control mb-2"
                                            value="{{ old('device_id', $order->device_id ?? '') }}" required>
                                    </div>

                                    <button type="submit" class="btn btn-warning">Actualizar Device ID</button>
                                </form>
                            </div>




                            <div class="form-group col-md-6">
                                <label>Status del Pago</label>
                                <input class="form-control bg-white" id="expire_date"
                                    value="{{ $order->payment_status }}" readonly />
                            </div>


                            <div class="form-group col-md-6">
                                <form action="{{ route('order.updateAmount') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="order_id" value="{{ $order->id }}">

                                    <div class="row align-items-center ">
                                        <div class="form-group col-md-6">
                                            <label>Cantidad Pagada</label>
                                            <input type="number" class="form-control" name="pay"
                                                value="{{ $order->pay }}">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label>Cantidad Debida</label>
                                            <input type="number" class="form-control" name="due"
                                                value="{{ $order->due }}">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label>Justificación</label>
                                        <textarea class="form-control" name="justification" rows="3" required
                                            placeholder="Ingrese una justificación para el movimiento..."></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Actualizar Cantidades</button>
                                </form>
                            </div>



                        </div>
                        <!-- end: Show Data -->

                        @if ($order->order_status == 'pending')
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="d-flex align-items-center list-action">
                                        <form action="{{ route('order.updateStatus') }}" method="POST"
                                            style="margin-bottom: 5px">
                                            @method('put')
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $order->id }}">
                                            <button type="submit" class="btn btn-success mr-2 border-none"
                                                data-toggle="tooltip" data-placement="top" title=""
                                                data-original-title="Complete">Aprobar Orden</button>

                                            <a class="btn btn-danger mr-2" data-toggle="tooltip" data-placement="top"
                                                title="" data-original-title="Cancel" href="#"
                                                onclick="event.preventDefault(); document.getElementById('cancel-form').submit();">Cancelar</a>

                                        </form>
                                        <form id="cancel-form" action="{{ route('order.delete', $order->id) }}"
                                            method="POST" style="display: none;">
                                            @method('DELETE')
                                            @csrf
                                        </form>

                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Historial de Movimientos</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Movimiento</th>
                                    <th>Justificación</th>
                                    <th>Diferencia pendiente a pagar</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->movements ?? [] as $movement)
                                    <tr>
                                        <td>{{ $movement->user->name ?? 'Usuario desconocido' }}</td>
                                        <td>{{ $movement->movement_type }}</td>
                                        <td>{{ $movement->justification }}</td>
                                        <td>{{ $movement->amount }}</td>
                                        <td>{{ $movement->created_at }}</td>

                                    </tr>
                                @endforeach
                            </tbody>

                        </table>
                    </div>
                </div>
            </div>
            <!-- end: Show Data -->
            <div class="col-lg-12">
                <div class="table-responsive rounded mb-3">
                    <table class="table mb-0">
                        <thead class="bg-white text-uppercase">
                            <tr class="ligth ligth-data">
                                <th>No.</th>
                                <th>Foto</th>
                                <th>Nombre del Producto</th>
                                <th>Codigo del Producto</th>
                                <th>Cantidad</th>
                                <th>Precio</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody class="ligth-body">
                            @foreach ($orderDetails as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <img class="avatar-60 rounded"
                                            src="{{ $item->product && $item->product->product_image ? asset('storage/product/' . $item->product->product_image) : asset('assets/images/product/default.webp') }}">

                                    </td>
                                    <td>{{ $item->product->product_name ?? 'El Producto ha sido eliminado' }}</td>
                                    <td>{{ $item->product->product_code ?? 'El Producto ha sido eliminado' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>${{ $item->unitcost }}</td>
                                    <td>${{ $item->total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Page end  -->
    </div>

    @include('components.preview-img-form')
@endsection
