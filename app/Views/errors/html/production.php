<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Algo falló de nuestro lado · Ventanilla Única Municipal</title>
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .tarjeta {
            max-width: 560px;
            width: 100%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }
        .icono { font-size: 56px; line-height: 1; margin-bottom: 12px; }
        h1 { font-size: 22px; font-weight: 700; margin: 0 0 12px; }
        p { font-size: 16px; line-height: 1.6; color: #475569; margin: 0 0 28px; }
        .acciones { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
        .boton {
            display: inline-block;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            padding: 12px 22px;
            border-radius: 10px;
            transition: opacity .15s ease;
        }
        .boton:hover { opacity: .9; }
        .boton-primario { background: #1d4ed8; color: #ffffff; }
        .boton-secundario { background: #eef2ff; color: #1e293b; }
        .pie { margin-top: 32px; font-size: 13px; color: #94a3b8; }
    </style>
</head>
<body>
    <main class="tarjeta">
        <div class="icono">🛠️</div>
        <h1>Algo falló de nuestro lado</h1>
        <p>
            Estamos trabajando para resolverlo. Por favor, intenta de nuevo en unos minutos.
            Si el problema persiste, comunica a la ventanilla para revisarlo.
        </p>
        <div class="acciones">
            <a class="boton boton-primario" href="/">Ir al inicio</a>
            <a class="boton boton-secundario" href="javascript:location.reload()">Volver a intentar</a>
        </div>
        <div class="pie">Ventanilla Única Municipal · Municipio de Uriangato</div>
    </main>
</body>
</html>
