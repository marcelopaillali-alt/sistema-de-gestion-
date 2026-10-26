document.querySelectorAll('[data-confirm]').forEach(function(el){
    el.addEventListener('click', function(e){
        if(!window.confirm(el.getAttribute('data-confirm'))) {
            e.preventDefault();
            return;
        }

        var confirmacionFinal = el.getAttribute('data-confirm-second');
        if(confirmacionFinal && !window.confirm(confirmacionFinal)) {
            e.preventDefault();
        }
    });
});

document.querySelectorAll('[data-sidebar-toggle]').forEach(function(button) {
    var sidebar = button.closest('.sidebar');
    var layout = sidebar ? sidebar.parentElement : null;
    if (!sidebar || !layout) return;

    button.addEventListener('click', function() {
        var minimizada = layout.classList.toggle('sidebar-collapsed');
        button.setAttribute('aria-expanded', minimizada ? 'false' : 'true');
        button.setAttribute('aria-label', minimizada ? 'Expandir menú lateral' : 'Minimizar menú lateral');
        button.setAttribute('title', minimizada ? 'Expandir menú lateral' : 'Minimizar menú lateral');
        var icono = button.querySelector('i');
        if (icono) {
            icono.classList.toggle('bi-three-dots', !minimizada);
            icono.classList.toggle('bi-three-dots-vertical', minimizada);
        }
    });
});
document.addEventListener('change', function (evento) {
    var selector = evento.target.closest('[data-usuario-lugar]');
    if (!selector) { return; }
    var formulario = selector.form;
    var campoLugar = formulario ? formulario.querySelector('[data-lugar-equipo]') : null;
    var campoArea = formulario ? formulario.querySelector('[data-area-equipo]') : null;
    var opcion = selector.options[selector.selectedIndex];
    if (campoArea) {
        campoArea.value = selector.value === '' ? 'Sin asignar' : ((opcion && opcion.getAttribute('data-area')) || 'Sin área');
    }
    if (!campoLugar) { return; }
    var ubicacion = opcion ? (opcion.getAttribute('data-ubicacion') || '') : '';
    if (selector.value === '') {
        campoLugar.value = 'Bodega';
    } else if (ubicacion !== '') {
        campoLugar.value = ubicacion;
    }
});

document.querySelectorAll('[data-tipo-equipo]').forEach(function (selector) {
    var campoNuevo = selector.form ? selector.form.querySelector('[data-tipo-equipo-nuevo]') : null;
    if (!campoNuevo) { return; }
    selector.addEventListener('change', function () {
        var agregarTipo = selector.value === '__nuevo__';
        campoNuevo.hidden = !agregarTipo;
        campoNuevo.disabled = !agregarTipo;
        campoNuevo.required = agregarTipo;
        if (agregarTipo) { campoNuevo.focus(); }
    });
});

document.querySelectorAll('[data-tipos-equipo-admin] [data-accion-tipo]').forEach(function (button) {
    button.addEventListener('click', function () {
        var lista = button.closest('[data-tipos-equipo-admin]');
        var nombre = button.getAttribute('data-nombre-tipo') || '';
        var accion = button.getAttribute('data-accion-tipo');
        var nombreNuevo = '';

        if (accion === 'editar') {
            nombreNuevo = window.prompt('Nuevo nombre para "' + nombre + '" (máximo 50 caracteres):', nombre);
            if (nombreNuevo === null) { return; }
            nombreNuevo = nombreNuevo.trim();
            if (!nombreNuevo || nombreNuevo.length > 50) {
                window.alert('El nombre debe tener entre 1 y 50 caracteres.');
                return;
            }
            if (nombreNuevo === nombre) { return; }
        } else if (accion === 'eliminar' && !window.confirm('¿Eliminar el tipo "' + nombre + '"? Solo se puede eliminar si ningún equipo lo está usando.')) {
            return;
        }

        var form = document.createElement('form');
        form.method = 'POST';
        var rutaPagina = window.location.pathname.match(/\/admin\/(.*)$/);
        form.action = '../'.repeat(rutaPagina ? rutaPagina[1].split('/').length : 1) + 'admin/equipos/tipo_equipo_gestionar.php';
        var datos = {
            csrf: lista.getAttribute('data-csrf'),
            accion: accion,
            nombre: nombre,
            nombre_nuevo: nombreNuevo,
            volver: lista.getAttribute('data-volver'),
            equipo_id: lista.getAttribute('data-equipo-id')
        };
        Object.keys(datos).forEach(function (clave) {
            var campo = document.createElement('input');
            campo.type = 'hidden';
            campo.name = clave;
            campo.value = datos[clave] || '';
            form.appendChild(campo);
        });
        document.body.appendChild(form);
        form.submit();
    });
});

// Valida que ningún campo de los formularios con data-validar-vacios esté vacío.
document.addEventListener('submit', function (evento) {
    const form = evento.target;
    if (!form.matches || !form.matches('form[data-validar-vacios]')) {
        return;
    }
    const campos = form.querySelectorAll('input, select, textarea');
    for (const campo of campos) {
        if (!campo.name || campo.disabled || campo.readOnly || campo.type === 'hidden' ||
            campo.type === 'submit' || campo.type === 'button' || campo.type === 'checkbox' ||
            campo.type === 'radio' || campo.type === 'file' || campo.hasAttribute('data-opcional') || campo.offsetParent === null) {
            continue;
        }
        if (campo.value.trim() === '') {
            const etiqueta = (campo.id && document.querySelector('label[for="' + campo.id + '"]')) ||
                (campo.closest('div') && campo.closest('div').querySelector('label'));
            const nombre = etiqueta ? etiqueta.textContent.replace(/\(.*?\)/g, '').replace(/\*/g, '').trim() : campo.name;
            evento.preventDefault();
            evento.stopImmediatePropagation();
            alert('El campo "' + nombre + '" no puede estar vacío.');
            campo.focus();
            return;
        }
    }
}, true);
