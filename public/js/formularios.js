/* ==========================================================================
   Validación y normalización de formularios (portal y panel administrativo)
   - Convierte a MAYÚSCULAS el valor real de los campos .text-uppercase
     (la clase CSS solo cambia la apariencia, no lo que se envía).
   - Valida RFC y CURP en el navegador con el mismo formato que el servidor,
     para que un dato mal capturado no provoque un envío fallido que obligue
     a volver a adjuntar los documentos.
   ========================================================================== */
(function () {
    'use strict';

    var PATRONES = {
        rfc: {
            regex: /^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/,
            mensaje: 'El RFC debe tener 12 caracteres (persona moral) o 13 (persona física). Ej. GODE561231GR8.'
        },
        curp: {
            regex: /^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/,
            mensaje: 'La CURP debe tener 18 caracteres con el formato oficial. Ej. GODE561231HGTRZR09.'
        }
    };

    function tipoCampo(input) {
        var nombre = (input.name || input.id || '').toLowerCase();
        if (/(^|_)rfc$/.test(nombre)) return 'rfc';
        if (/(^|_)curp$/.test(nombre)) return 'curp';
        return null;
    }

    function aMayusculas(input) {
        var inicio = input.selectionStart;
        var fin = input.selectionEnd;
        var mayus = input.value.toUpperCase();
        if (mayus === input.value) return;
        input.value = mayus;
        try { input.setSelectionRange(inicio, fin); } catch (e) { /* campos sin selección */ }
    }

    function validarFormato(input) {
        var tipo = tipoCampo(input);
        if (!tipo) return;
        var valor = input.value.trim();
        if (valor === '' || PATRONES[tipo].regex.test(valor)) {
            input.setCustomValidity('');
        } else {
            input.setCustomValidity(PATRONES[tipo].mensaje);
        }
    }

    document.addEventListener('input', function (e) {
        var el = e.target;
        if (!(el instanceof HTMLInputElement)) return;
        if (el.classList.contains('text-uppercase') || tipoCampo(el)) aMayusculas(el);
        validarFormato(el);
        if (el.classList.contains('is-invalid') && el.checkValidity()) el.classList.remove('is-invalid');
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input').forEach(function (el) {
            if (el.classList.contains('text-uppercase') || tipoCampo(el)) aMayusculas(el);
            validarFormato(el);
        });

        // Tras un error del servidor, avisar que los archivos adjuntos deben seleccionarse de nuevo.
        if (document.querySelector('form input[type="file"]')) {
            document.querySelectorAll('[data-aviso-archivos]').forEach(function (aviso) {
                aviso.classList.remove('d-none');
            });
        }
    });

    // Al intentar enviar, se marcan los campos inválidos y se lleva al usuario al primero.
    document.addEventListener('invalid', function (e) {
        var el = e.target;
        if (el && el.classList) el.classList.add('is-invalid');
    }, true);

    document.addEventListener('change', function (e) {
        var el = e.target;
        if (el && el.classList && el.classList.contains('is-invalid') && el.checkValidity && el.checkValidity()) {
            el.classList.remove('is-invalid');
        }
    });
})();
