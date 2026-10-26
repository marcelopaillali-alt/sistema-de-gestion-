// Valida una IPv4 y quita ceros a la izquierda; devuelve null si es inválida.
function normalizarIpv4(valor) {
    var octetos = valor.split('.');
    if (octetos.length !== 4 || !octetos.every(function(octeto) {
        return /^\d{1,3}$/.test(octeto) && Number(octeto) <= 255;
    })) return null;

    return octetos.map(function(octeto) {
        return String(Number(octeto));
    }).join('.');
}

document.querySelectorAll('input[name="ip_asignada"]').forEach(function(input) {
    // Solo permite números y puntos, y marca el campo inválido si la IP no es correcta.
    function validarIpv4() {
        input.value = input.value.replace(/[^0-9.]/g, '');
        input.setCustomValidity(
            input.value === '' || normalizarIpv4(input.value) !== null
                ? ''
                : 'Ingrese una dirección IPv4 válida, por ejemplo 192.168.1.10.'
        );
    }

    input.addEventListener('input', validarIpv4);
    input.addEventListener('blur', function() {
        var ipNormalizada = normalizarIpv4(input.value);
        if (ipNormalizada !== null) input.value = ipNormalizada;
        validarIpv4();
    });
    validarIpv4();
});

document.querySelectorAll('input[name="mac"]').forEach(function(input) {
    // Da formato AA:BB:CC:DD:EE:FF a la MAC mientras se escribe.
    function formatearMac() {
        var cursor = input.selectionStart || 0;
        var caracteresAntesDelCursor = input.value
            .slice(0, cursor)
            .replace(/[^0-9A-F]/gi, '').length;
        var mac = input.value.toUpperCase().replace(/[^0-9A-F]/g, '').slice(0, 12);
        var grupos = mac.match(/.{1,2}/g) || [];

        input.value = grupos.join(':');

        var nuevoCursor = 0;
        var caracteresContados = 0;
        while (nuevoCursor < input.value.length && caracteresContados < caracteresAntesDelCursor) {
            if (/[0-9A-F]/i.test(input.value.charAt(nuevoCursor))) caracteresContados++;
            nuevoCursor++;
        }
        if (document.activeElement === input) {
            input.setSelectionRange(nuevoCursor, nuevoCursor);
        }
    }

    input.addEventListener('input', formatearMac);
    if (/^(?:[0-9A-F]{2}[:-]?){5}[0-9A-F]{2}$/i.test(input.value)) {
        formatearMac();
    }
});
