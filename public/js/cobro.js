document.addEventListener('DOMContentLoaded', function () {
    var selectItem = document.getElementById('selectItem');
    var totalCobro = document.getElementById('totalCobro');
    var inputMontoPagado = document.getElementById('inputMontoPagado');
    var cambioCobro = document.getElementById('cambioCobro');
    var inputBuscarEstudiante = document.getElementById('inputBuscarEstudiante');
    var sugerenciasEstudiante = document.getElementById('sugerenciasEstudiante');
    var datosEstudiante = document.getElementById('datosEstudiante');
    var estudianteNombre = document.getElementById('estudianteNombre');
    var estudianteApellidos = document.getElementById('estudianteApellidos');
    var estudianteCarrera = document.getElementById('estudianteCarrera');
    var inputEstudianteId = document.getElementById('inputEstudianteId');
    var camposEstudiante = document.getElementById('camposEstudiante');
    var camposPago = document.getElementById('camposPago');
    var estudiantes = JSON.parse(document.getElementById('datosEstudiantes').textContent);

    function formatearMoneda(valor) {
        return 'Bs. ' + valor.toFixed(2);
    }
    function calcularTotal() {
        var opcion = selectItem.options[selectItem.selectedIndex];

        return opcion ? parseFloat(opcion.dataset.monto) || 0 : 0;
    }

    function recalcular() {
        var total = calcularTotal();
        var montoPagado = parseFloat(inputMontoPagado.value) || 0;
        var cambio = montoPagado - total;

        totalCobro.textContent = formatearMoneda(total);
        cambioCobro.textContent = formatearMoneda(cambio);
        cambioCobro.classList.toggle('text-danger', cambio < 0);
        cambioCobro.classList.toggle('text-success', cambio >= 0);
    }

    function actualizarCamposPago() {
        camposPago.disabled = ! selectItem.value;

        if (camposPago.disabled) {
            inputMontoPagado.value = '';
        }

        recalcular();
    }

    function ocultarSugerencias() {
        sugerenciasEstudiante.classList.add('d-none');
        sugerenciasEstudiante.innerHTML = '';
    }

    function limpiarEstudiante() {
        datosEstudiante.classList.add('d-none');
        inputEstudianteId.value = '';
        camposEstudiante.disabled = true;
        selectItem.value = '';
        actualizarCamposPago();
    }

    function seleccionarEstudiante(estudiante) {
        estudianteNombre.textContent = estudiante.nombre;
        estudianteApellidos.textContent = estudiante.apellidos;
        estudianteCarrera.textContent = estudiante.carrera;
        datosEstudiante.classList.remove('d-none');
        inputEstudianteId.value = estudiante.id;
        camposEstudiante.disabled = false;
        inputBuscarEstudiante.value = estudiante.etiqueta;
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

    selectItem.addEventListener('change', actualizarCamposPago);
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

    actualizarCamposPago();
});
