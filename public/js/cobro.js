document.addEventListener('DOMContentLoaded', function () {
    var selectItem = document.getElementById('selectItem');
    var inputCantidad = document.getElementById('inputCantidad');
    var btnAgregarItem = document.getElementById('btnAgregarItem');
    var tablaItems = document.getElementById('tablaItems');
    var filaSinItems = document.getElementById('filaSinItems');
    var totalCobro = document.getElementById('totalCobro');
    var inputMontoPagado = document.getElementById('inputMontoPagado');
    var pagadoCobro = document.getElementById('pagadoCobro');
    var cambioCobro = document.getElementById('cambioCobro');
    var faltaCobro = document.getElementById('faltaCobro');
    var filaCambio = document.getElementById('filaCambio');
    var filaFalta = document.getElementById('filaFalta');
    var avisoParcial = document.getElementById('avisoParcial');
    var inputBuscarEstudiante = document.getElementById('inputBuscarEstudiante');
    var sugerenciasEstudiante = document.getElementById('sugerenciasEstudiante');
    var datosEstudiante = document.getElementById('datosEstudiante');
    var estudianteNombre = document.getElementById('estudianteNombre');
    var estudianteApellidos = document.getElementById('estudianteApellidos');
    var estudianteCarrera = document.getElementById('estudianteCarrera');
    var deudasEstudiante = document.getElementById('deudasEstudiante');
    var tablaDeudas = document.getElementById('tablaDeudas');
    var inputEstudianteId = document.getElementById('inputEstudianteId');
    var camposEstudiante = document.getElementById('camposEstudiante');
    var camposPago = document.getElementById('camposPago');
    var estudiantes = JSON.parse(document.getElementById('datosEstudiantes').textContent);
    var items = JSON.parse(document.getElementById('datosItems').textContent);

    // item_id => { item, cantidad }
    var carrito = {};

    function formatearMoneda(valor) {
        return 'Bs. ' + valor.toFixed(2);
    }

    function redondear(valor) {
        return Math.round(valor * 100) / 100;
    }

    function calcularTotal() {
        return redondear(Object.keys(carrito).reduce(function (suma, id) {
            return suma + carrito[id].item.monto * carrito[id].cantidad;
        }, 0));
    }

    function recalcular() {
        var total = calcularTotal();
        var recibido = parseFloat(inputMontoPagado.value) || 0;
        var pagado = Math.min(recibido, total);
        var diferencia = redondear(recibido - total);
        var esParcial = recibido > 0 && diferencia < 0;

        totalCobro.textContent = formatearMoneda(total);
        pagadoCobro.textContent = formatearMoneda(pagado);
        cambioCobro.textContent = formatearMoneda(Math.max(0, diferencia));
        faltaCobro.textContent = formatearMoneda(Math.max(0, -diferencia));

        filaCambio.classList.toggle('d-none', esParcial);
        filaFalta.classList.toggle('d-none', ! esParcial);
        avisoParcial.classList.toggle('d-none', ! esParcial);
    }

    function crearCelda(texto, clase) {
        var td = document.createElement('td');
        td.textContent = texto;
        if (clase) {
            td.className = clase;
        }
        return td;
    }

    function renderCarrito() {
        Array.prototype.slice.call(tablaItems.querySelectorAll('tr[data-item]')).forEach(function (fila) {
            fila.remove();
        });

        var ids = Object.keys(carrito);
        filaSinItems.classList.toggle('d-none', ids.length > 0);

        ids.forEach(function (id, indice) {
            var linea = carrito[id];
            var fila = document.createElement('tr');
            fila.dataset.item = id;

            fila.appendChild(crearCelda(linea.item.nombre));
            fila.appendChild(crearCelda(formatearMoneda(linea.item.monto), 'text-end'));

            var tdCantidad = document.createElement('td');
            var inputLinea = document.createElement('input');
            inputLinea.type = 'number';
            inputLinea.min = '1';
            inputLinea.max = '999';
            inputLinea.value = linea.cantidad;
            inputLinea.className = 'form-control form-control-sm';
            inputLinea.name = 'items[' + indice + '][cantidad]';
            inputLinea.addEventListener('input', function () {
                var cantidad = parseInt(inputLinea.value, 10);
                if (cantidad >= 1) {
                    linea.cantidad = Math.min(cantidad, 999);
                    fila.querySelector('.subtotal').textContent = formatearMoneda(linea.item.monto * linea.cantidad);
                    recalcular();
                }
            });
            tdCantidad.appendChild(inputLinea);

            var oculto = document.createElement('input');
            oculto.type = 'hidden';
            oculto.name = 'items[' + indice + '][item_id]';
            oculto.value = id;
            tdCantidad.appendChild(oculto);
            fila.appendChild(tdCantidad);

            fila.appendChild(crearCelda(formatearMoneda(linea.item.monto * linea.cantidad), 'text-end subtotal'));

            var tdQuitar = document.createElement('td');
            var btnQuitar = document.createElement('button');
            btnQuitar.type = 'button';
            btnQuitar.className = 'btn btn-sm btn-outline-danger';
            btnQuitar.textContent = '×';
            btnQuitar.title = 'Quitar';
            btnQuitar.addEventListener('click', function () {
                delete carrito[id];
                renderCarrito();
            });
            tdQuitar.appendChild(btnQuitar);
            fila.appendChild(tdQuitar);

            tablaItems.appendChild(fila);
        });

        camposPago.disabled = ids.length === 0;
        if (camposPago.disabled) {
            inputMontoPagado.value = '';
        }

        recalcular();
    }

    function agregarItem() {
        var item = items.find(function (i) {
            return String(i.id) === selectItem.value;
        });
        var cantidad = parseInt(inputCantidad.value, 10) || 1;

        if (! item) {
            return;
        }

        if (carrito[item.id]) {
            carrito[item.id].cantidad = Math.min(carrito[item.id].cantidad + cantidad, 999);
        } else {
            carrito[item.id] = { item: item, cantidad: Math.min(cantidad, 999) };
        }

        selectItem.value = '';
        inputCantidad.value = 1;
        renderCarrito();
    }

    // Solo ítems generales o asignados a la carrera del estudiante.
    function cargarItems(carreraId) {
        selectItem.innerHTML = '<option value="">Selecciona un ítem</option>';

        items.filter(function (item) {
            return item.carreras.length === 0 || item.carreras.indexOf(carreraId) !== -1;
        }).forEach(function (item) {
            var opcion = document.createElement('option');
            opcion.value = item.id;
            opcion.textContent = item.nombre + ' — ' + formatearMoneda(item.monto)
                + (item.carreras.length ? ' (específico de la carrera)' : '');
            selectItem.appendChild(opcion);
        });
    }

    function renderDeudas(deudas) {
        tablaDeudas.innerHTML = '';
        deudasEstudiante.classList.toggle('d-none', deudas.length === 0);

        deudas.forEach(function (deuda) {
            var fila = document.createElement('tr');
            fila.appendChild(crearCelda(deuda.comprobante || '—'));
            fila.appendChild(crearCelda(deuda.detalle));
            fila.appendChild(crearCelda(formatearMoneda(deuda.total), 'text-end'));
            fila.appendChild(crearCelda(formatearMoneda(deuda.pagado), 'text-end'));
            fila.appendChild(crearCelda(formatearMoneda(deuda.saldo), 'text-end fw-bold text-danger'));

            var tdAccion = document.createElement('td');
            var enlace = document.createElement('a');
            enlace.href = deuda.url;
            enlace.className = 'btn btn-sm btn-warning';
            enlace.textContent = 'Pagar saldo';
            tdAccion.appendChild(enlace);
            fila.appendChild(tdAccion);

            tablaDeudas.appendChild(fila);
        });
    }

    function ocultarSugerencias() {
        sugerenciasEstudiante.classList.add('d-none');
        sugerenciasEstudiante.innerHTML = '';
    }

    function limpiarEstudiante() {
        datosEstudiante.classList.add('d-none');
        deudasEstudiante.classList.add('d-none');
        inputEstudianteId.value = '';
        camposEstudiante.disabled = true;
        carrito = {};
        renderCarrito();
    }

    function seleccionarEstudiante(estudiante) {
        estudianteNombre.textContent = estudiante.nombre;
        estudianteApellidos.textContent = estudiante.apellidos;
        estudianteCarrera.textContent = estudiante.carrera;
        datosEstudiante.classList.remove('d-none');
        inputEstudianteId.value = estudiante.id;
        camposEstudiante.disabled = false;
        inputBuscarEstudiante.value = estudiante.etiqueta;
        cargarItems(estudiante.carrera_id);
        renderDeudas(estudiante.deudas || []);
        ocultarSugerencias();
    }

    function renderSugerencias(coincidencias) {
        sugerenciasEstudiante.innerHTML = '';

        if (coincidencias.length === 0) {
            var vacio = document.createElement('div');
            vacio.className = 'list-group-item text-muted';
            vacio.textContent = 'Sin coincidencias';
            sugerenciasEstudiante.appendChild(vacio);
        } else {
            coincidencias.forEach(function (estudiante) {
                var boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'list-group-item list-group-item-action';
                boton.textContent = estudiante.etiqueta;
                boton.dataset.id = estudiante.id;
                sugerenciasEstudiante.appendChild(boton);
            });
        }

        sugerenciasEstudiante.classList.remove('d-none');
    }

    function buscarCoincidencias(texto) {
        var busqueda = texto.toLowerCase();

        return estudiantes.filter(function (estudiante) {
            var contenido = (estudiante.nombre + ' ' + estudiante.apellidos + ' ' + estudiante.ci).toLowerCase();

            return contenido.indexOf(busqueda) !== -1;
        }).slice(0, 8);
    }

    btnAgregarItem.addEventListener('click', agregarItem);
    selectItem.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            agregarItem();
        }
    });
    inputCantidad.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            agregarItem();
        }
    });
    inputMontoPagado.addEventListener('input', recalcular);

    inputBuscarEstudiante.addEventListener('input', function () {
        limpiarEstudiante();

        var texto = inputBuscarEstudiante.value.trim();

        if (! texto) {
            ocultarSugerencias();
            return;
        }

        renderSugerencias(buscarCoincidencias(texto));
    });

    inputBuscarEstudiante.addEventListener('blur', function () {
        setTimeout(ocultarSugerencias, 150);
    });

    sugerenciasEstudiante.addEventListener('mousedown', function (evento) {
        var boton = evento.target.closest('button[data-id]');

        if (! boton) {
            return;
        }

        evento.preventDefault();

        var estudiante = estudiantes.find(function (item) {
            return String(item.id) === boton.dataset.id;
        });

        if (estudiante) {
            seleccionarEstudiante(estudiante);
        }
    });

    renderCarrito();
});
