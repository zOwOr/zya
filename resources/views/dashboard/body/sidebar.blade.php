<div class="iq-sidebar sidebar-default">
    <div class="sidebar-header d-flex align-items-center justify-content-between">
        <a href="{{ route('dashboard') }}" class="sidebar-logo">
            <img src="{{ asset('assets/images/logo.png') }}" class="img-fluid rounded-normal light-logo" alt="logo">
        </a>
        <div class="sidebar-toggle-btn" onclick="document.body.classList.toggle('sidebar-main')" role="button" tabindex="0" title="Alternar menú lateral">
            <i class="fa-solid fa-bars"></i>
        </div>
    </div>

    <div class="data-scrollbar" data-scroll="1">
        <nav class="iq-sidebar-menu">
            <ul id="iq-sidebar-toggle" class="iq-menu">

                {{-- ===================== 1. PRINCIPAL ===================== --}}
                <li class="sidebar-section-title">
                    <span>Principal</span>
                </li>

                <li class="{{ Request::is('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}" class="svg-icon">
                        <i class="fa-solid fa-chart-pie text-primary"></i>
                        <span class="ml-3">Dashboard</span>
                    </a>
                </li>

                @if (auth()->user()->can('pos.menu'))
                    <li class="{{ Request::is('pos*') ? 'active' : '' }}">
                        <a href="{{ route('pos.index') }}" class="svg-icon">
                            <i class="fa-solid fa-cart-shopping text-success"></i>
                            <span class="ml-3">Punto de Venta</span>
                        </a>
                    </li>
                @endif

                {{-- ===================== 2. OPERACIONES ===================== --}}
                @if (auth()->user()->can('cash.menu') || auth()->user()->can('orders.menu') || auth()->user()->can('repairs.menu') || auth()->user()->can('tandas.menu'))
                    <li class="sidebar-section-title">
                        <span>Operaciones</span>
                    </li>

                    @if (auth()->user()->can('cash.menu'))
                        <li class="{{ Request::is('cash*') ? 'active' : '' }}">
                            <a href="{{ route('cash.index') }}" class="svg-icon">
                                <i class="fa-solid fa-vault text-info"></i>
                                <span class="ml-3">Corte y Caja</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->can('orders.menu'))
                        @php
                            $isOrdersActive = Request::is('orders*') || Request::is('pending*');
                        @endphp
                        <li class="{{ $isOrdersActive ? 'active' : '' }}">
                            <a href="#orders" class="{{ $isOrdersActive ? '' : 'collapsed' }}" data-toggle="collapse"
                                aria-expanded="{{ $isOrdersActive ? 'true' : 'false' }}">
                                <i class="fa-solid fa-receipt text-warning"></i>
                                <span class="ml-3">Órdenes y Ventas</span>
                                <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="10 15 15 20 20 15"></polyline>
                                    <path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                                </svg>
                            </a>
                            <ul id="orders" class="iq-submenu collapse {{ $isOrdersActive ? 'show' : '' }}"
                                data-parent="#iq-sidebar-toggle">
                                <li class="{{ Request::is('orders/pending*') ? 'active' : '' }}">
                                    <a href="{{ route('order.pendingOrders') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Pendientes</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('orders/complete*') ? 'active' : '' }}">
                                    <a href="{{ route('order.completeOrders') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Aprobadas</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('pending/due*') ? 'active' : '' }}">
                                    <a href="{{ route('order.pendingDue') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Pendiente de Pago</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->can('repairs.menu'))
                        <li class="{{ Request::is('repairs*') ? 'active' : '' }}">
                            <a href="{{ route('repairs.index') }}" class="svg-icon">
                                <i class="fa-solid fa-screwdriver-wrench text-danger"></i>
                                <span class="ml-3">Reparaciones</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->can('tandas.menu'))
                        <li class="{{ Request::is('tandas*') ? 'active' : '' }}">
                            <a href="{{ route('tandas.index') }}" class="svg-icon">
                                <i class="fa-solid fa-piggy-bank text-secondary"></i>
                                <span class="ml-3">Tandas / Ahorro</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ===================== 3. MÓDULO FINANCIERAS ===================== --}}
                @if (auth()->user()->can('financieras.menu'))
                    <li class="sidebar-section-title">
                        <span>Financiamiento</span>
                    </li>

                    @php
                        $isFinActive = Request::is('financieras*');
                    @endphp
                    <li class="{{ $isFinActive ? 'active' : '' }}">
                        <a href="#financieras" class="{{ $isFinActive ? '' : 'collapsed' }}" data-toggle="collapse"
                            aria-expanded="{{ $isFinActive ? 'true' : 'false' }}">
                            <i class="fa-solid fa-landmark text-primary"></i>
                            <span class="ml-3">Financieras</span>
                            <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="10 15 15 20 20 15"></polyline>
                                <path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                            </svg>
                        </a>
                        <ul id="financieras" class="iq-submenu collapse {{ $isFinActive ? 'show' : '' }}"
                            data-parent="#iq-sidebar-toggle">
                            <li class="{{ Request::is('financieras') && !request()->has('tab') ? 'active' : '' }}">
                                <a href="{{ route('financieras.index') }}">
                                    <i class="fa-solid fa-arrow-right"></i><span>Panel General</span>
                                </a>
                            </li>
                            @if (auth()->user()->can('financieras.ventas.menu') || auth()->user()->can('financieras.ventas.read'))
                                <li class="{{ Request::is('financieras/ventas*') || (Request::is('financieras*') && request('tab') == 'ventas') ? 'active' : '' }}">
                                    <a href="{{ route('financieras.index', ['tab' => 'ventas']) }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Ventas y Créditos</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->can('financieras.inventario.menu') || auth()->user()->can('financieras.inventario.read'))
                                <li class="{{ Request::is('financieras/inventario*') || (Request::is('financieras*') && request('tab') == 'inventario') ? 'active' : '' }}">
                                    <a href="{{ route('financieras.index', ['tab' => 'inventario']) }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Inventario Equipos</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->can('financieras.garantias.menu') || auth()->user()->can('financieras.garantias.read'))
                                <li class="{{ Request::is('financieras/garantias*') || (Request::is('financieras*') && request('tab') == 'garantias') ? 'active' : '' }}">
                                    <a href="{{ route('financieras.index', ['tab' => 'garantias']) }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Garantías</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->can('financieras.robos.menu') || auth()->user()->can('financieras.robos.read'))
                                <li class="{{ Request::is('financieras/robos*') || (Request::is('financieras*') && request('tab') == 'robos') ? 'active' : '' }}">
                                    <a href="{{ route('financieras.index', ['tab' => 'robos']) }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Reportes de Robo</span>
                                    </a>
                                </li>
                            @endif
                            @if (auth()->user()->can('financieras.catalogos.menu') || auth()->user()->can('financieras.catalogos.read'))
                                <li class="{{ Request::is('financieras/catalogos*') ? 'active' : '' }}">
                                    <a href="{{ route('financieras.catalogos.index') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Catálogos</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                {{-- ===================== 4. CATÁLOGO & STOCK ===================== --}}
                @if (auth()->user()->can('product.menu') || auth()->user()->can('stock.menu'))
                    <li class="sidebar-section-title">
                        <span>Inventario</span>
                    </li>

                    @if (auth()->user()->can('product.menu'))
                        @php
                            $isProductsActive = Request::is('products*') || Request::is('categories*');
                        @endphp
                        <li class="{{ $isProductsActive ? 'active' : '' }}">
                            <a href="#products" class="{{ $isProductsActive ? '' : 'collapsed' }}" data-toggle="collapse"
                                aria-expanded="{{ $isProductsActive ? 'true' : 'false' }}">
                                <i class="fa-solid fa-boxes-stacked text-info"></i>
                                <span class="ml-3">Productos</span>
                                <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="10 15 15 20 20 15"></polyline>
                                    <path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                                </svg>
                            </a>
                            <ul id="products" class="iq-submenu collapse {{ $isProductsActive ? 'show' : '' }}"
                                data-parent="#iq-sidebar-toggle">
                                <li class="{{ Request::is('products') ? 'active' : '' }}">
                                    <a href="{{ route('products.index') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Catálogo</span>
                                    </a>
                                </li>
                                @if (auth()->user()->can('product.create'))
                                    <li class="{{ Request::is('products/create') ? 'active' : '' }}">
                                        <a href="{{ route('products.create') }}">
                                            <i class="fa-solid fa-arrow-right"></i><span>Nuevo Producto</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->can('category.read'))
                                    <li class="{{ Request::is('categories*') ? 'active' : '' }}">
                                        <a href="{{ route('categories.index') }}">
                                            <i class="fa-solid fa-arrow-right"></i><span>Categorías</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->can('stock.menu'))
                        <li class="{{ Request::is('stock*') || Request::is('order/stock*') ? 'active' : '' }}">
                            <a href="{{ route('order.stockManage') }}" class="svg-icon">
                                <i class="fa-solid fa-warehouse text-warning"></i>
                                <span class="ml-3">Stock y Almacén</span>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ===================== 5. DIRECTORIO & RRHH ===================== --}}
                @if (auth()->user()->can('customer.menu') || auth()->user()->can('supplier.menu') || auth()->user()->can('employee.menu') || auth()->user()->can('salary.menu') || auth()->user()->can('attendence.menu'))
                    <li class="sidebar-section-title">
                        <span>Directorio & Personal</span>
                    </li>

                    @if (auth()->user()->can('customer.menu'))
                        <li class="{{ Request::is('customers*') ? 'active' : '' }}">
                            <a href="{{ route('customers.index') }}" class="svg-icon">
                                <i class="fa-solid fa-users text-success"></i>
                                <span class="ml-3">Clientes</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->can('supplier.menu'))
                        <li class="{{ Request::is('suppliers*') ? 'active' : '' }}">
                            <a href="{{ route('suppliers.index') }}" class="svg-icon">
                                <i class="fa-solid fa-truck-field text-secondary"></i>
                                <span class="ml-3">Proveedores</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->can('employee.menu'))
                        <li class="{{ Request::is('employees*') ? 'active' : '' }}">
                            <a href="{{ route('employees.index') }}" class="svg-icon">
                                <i class="fa-solid fa-id-badge text-primary"></i>
                                <span class="ml-3">Empleados</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->can('salary.menu'))
                        @php
                            $isSalaryActive = Request::is('advance-salary*') || Request::is('pay-salary*');
                        @endphp
                        <li class="{{ $isSalaryActive ? 'active' : '' }}">
                            <a href="#salary-menu" class="{{ $isSalaryActive ? '' : 'collapsed' }}" data-toggle="collapse"
                                aria-expanded="{{ $isSalaryActive ? 'true' : 'false' }}">
                                <i class="fa-solid fa-money-check-dollar text-success"></i>
                                <span class="ml-3">Nómina y Salarios</span>
                                <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="10 15 15 20 20 15"></polyline>
                                    <path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                                </svg>
                            </a>
                            <ul id="salary-menu" class="iq-submenu collapse {{ $isSalaryActive ? 'show' : '' }}"
                                data-parent="#iq-sidebar-toggle">
                                <li class="{{ Request::is('advance-salary*') ? 'active' : '' }}">
                                    <a href="{{ route('advance-salary.index') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Anticipos</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('pay-salary') ? 'active' : '' }}">
                                    <a href="{{ route('pay-salary.index') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Pagar Salario</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('pay-salary/history*') ? 'active' : '' }}">
                                    <a href="{{ route('pay-salary.payHistory') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Historial de Pagos</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if (auth()->user()->can('attendence.menu'))
                        @php
                            $isAttendenceActive = Request::is('employee/attendence*');
                        @endphp
                        <li class="{{ $isAttendenceActive ? 'active' : '' }}">
                            <a href="#attendence-menu" class="{{ $isAttendenceActive ? '' : 'collapsed' }}" data-toggle="collapse"
                                aria-expanded="{{ $isAttendenceActive ? 'true' : 'false' }}">
                                <i class="fa-solid fa-calendar-check text-info"></i>
                                <span class="ml-3">Asistencias</span>
                                <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="10 15 15 20 20 15"></polyline>
                                    <path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                                </svg>
                            </a>
                            <ul id="attendence-menu" class="iq-submenu collapse {{ $isAttendenceActive ? 'show' : '' }}"
                                data-parent="#iq-sidebar-toggle">
                                <li class="{{ Request::is('employee/attendence') ? 'active' : '' }}">
                                    <a href="{{ route('attendence.index') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Ver Asistencias</span>
                                    </a>
                                </li>
                                <li class="{{ Request::is('employee/attendence/create*') ? 'active' : '' }}">
                                    <a href="{{ route('attendence.create') }}">
                                        <i class="fa-solid fa-arrow-right"></i><span>Registrar Asistencia</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                @endif

                {{-- ===================== 6. ADMINISTRACIÓN & SISTEMA ===================== --}}
                @if (auth()->user()->can('branch.menu') || auth()->user()->can('branch.read') || auth()->user()->can('user.menu') || auth()->user()->can('user.read') || auth()->user()->can('roles.menu') || auth()->user()->can('database.menu'))
                    <li class="sidebar-section-title">
                        <span>Sistema & Ajustes</span>
                    </li>

                    @if (auth()->user()->can('branch.menu') || auth()->user()->can('branch.read'))
                        <li class="{{ Request::is('branches*') ? 'active' : '' }}">
                            <a href="{{ route('branches.index') }}" class="svg-icon">
                                <i class="fa-solid fa-store text-warning"></i>
                                <span class="ml-3">Sucursales</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->can('user.menu') || auth()->user()->can('user.read'))
                        <li class="{{ Request::is('users*') ? 'active' : '' }}">
                            <a href="{{ route('users.index') }}" class="svg-icon">
                                <i class="fa-solid fa-user-shield text-danger"></i>
                                <span class="ml-3">Usuarios</span>
                            </a>
                        </li>
                    @endif

                    @if (auth()->user()->can('roles.menu'))
                        @php
                            $isRolesActive = Request::is('role*') || Request::is('permission*');
                        @endphp
                        <li class="{{ $isRolesActive ? 'active' : '' }}">
                            <a href="#permission" class="{{ $isRolesActive ? '' : 'collapsed' }}" data-toggle="collapse"
                                aria-expanded="{{ $isRolesActive ? 'true' : 'false' }}">
                                <i class="fa-solid fa-key text-primary"></i>
                                <span class="ml-3">Roles y Permisos</span>
                                <svg class="svg-icon iq-arrow-right arrow-active" width="20" height="20"
                                    xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="10 15 15 20 20 15"></polyline>
                                    <path d="M4 4h7a4 4 0 0 1 4 4v12"></path>
                                </svg>
                            </a>
                            <ul id="permission" class="iq-submenu collapse {{ $isRolesActive ? 'show' : '' }}"
                                data-parent="#iq-sidebar-toggle">
                                @if (auth()->user()->can('permissions.read'))
                                    <li class="{{ Request::is(['permission', 'permission/create', 'permission/edit/*']) ? 'active' : '' }}">
                                        <a href="{{ route('permission.index') }}">
                                            <i class="fa-solid fa-arrow-right"></i><span>Catálogo Permisos</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->can('roles.read'))
                                    <li class="{{ Request::is(['role', 'role/create', 'role/edit/*']) ? 'active' : '' }}">
                                        <a href="{{ route('role.index') }}">
                                            <i class="fa-solid fa-arrow-right"></i><span>Catálogo Roles</span>
                                        </a>
                                    </li>
                                @endif
                                @if (auth()->user()->can('roles.edit'))
                                    <li class="{{ Request::is(['role/permission*']) ? 'active' : '' }}">
                                        <a href="{{ route('rolePermission.index') }}">
                                            <i class="fa-solid fa-arrow-right"></i><span>Asignar Permisos</span>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                @endif

            </ul>
        </nav>
        <div class="p-3"></div>
    </div>
</div>
