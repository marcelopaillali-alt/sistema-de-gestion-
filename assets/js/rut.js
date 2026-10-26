// Da formato 12.345.678-9 al RUT mientras se escribe.
function formatRut(value) {
    var rut = value.toUpperCase().replace(/[^0-9K]/g, '');
    if (!rut) return '';

    var digitoVerificador = rut.length > 1 ? rut.slice(-1) : '';
    var cuerpo = (digitoVerificador ? rut.slice(0, -1) : rut)
        .replace(/K/g, '')
        .slice(0, 8);
    var cuerpoFormateado = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    return cuerpoFormateado + (digitoVerificador ? '-' + digitoVerificador : '');
}

// Consulta al servidor si el RUT ya existe y bloquea el envío del formulario si es así.
function activarVerificacionRut(input) {
    var formulario = input.form;
    var campoId = formulario ? formulario.querySelector('input[name="id"]') : null;
    var aviso = document.createElement('div');
    aviso.className = 'invalid-feedback';
    aviso.textContent = 'Este RUT ya está registrado.';
    input.insertAdjacentElement('afterend', aviso);

    var temporizador = null;
    var ultimaConsulta = 0;

    function verificar() {
        var limpio = input.value.replace(/[^0-9K]/gi, '');
        if (limpio.length < 8) {
            marcar(false);
            return Promise.resolve(false);
        }
        var consulta = ++ultimaConsulta;
        var rutaPagina = window.location.pathname.match(/\/(admin|usuario)\/(.*)$/);
        var prefijo = rutaPagina ? '../'.repeat(rutaPagina[2].split('/').length) : '';
        var url = prefijo + 'verificar_rut.php' +
            '?rut=' + encodeURIComponent(limpio) +
            (campoId ? '&excluir=' + encodeURIComponent(campoId.value) : '');
        return fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function(respuesta) { return respuesta.json(); })
            .then(function(datos) {
                if (consulta === ultimaConsulta) marcar(!!datos.existe);
                return !!datos.existe;
            })
            .catch(function() { return false; });
    }

    function marcar(existe) {
        input.classList.toggle('is-invalid', existe);
        input.setCustomValidity(existe ? 'Este RUT ya está registrado.' : '');
    }

    input.addEventListener('input', function() {
        clearTimeout(temporizador);
        temporizador = setTimeout(verificar, 400);
    });
    input.addEventListener('blur', verificar);

    if (formulario) {
        formulario.addEventListener('submit', function(evento) {
            if (input.classList.contains('is-invalid')) {
                evento.preventDefault();
                input.reportValidity();
            }
        });
    }
}

document.querySelectorAll('input[name="rut"]').forEach(function(input) {
    activarVerificacionRut(input);
    input.value = formatRut(input.value);
    input.addEventListener('input', function() {
        var cursor = input.selectionStart;
        var caracteresAntesDelCursor = input.value
            .slice(0, cursor)
            .replace(/[^0-9K]/gi, '').length;

        input.value = formatRut(input.value);

        var nuevoCursor = 0;
        var caracteresContados = 0;
        while (nuevoCursor < input.value.length && caracteresContados < caracteresAntesDelCursor) {
            if (/[0-9K]/i.test(input.value.charAt(nuevoCursor))) caracteresContados++;
            nuevoCursor++;
        }
        input.setSelectionRange(nuevoCursor, nuevoCursor);
    });
});
