<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - Coopuertos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #1e40af;
            margin: 0;
            font-size: 28px;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #1e40af;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #1e3a8a;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 30px;
        }
        .url-fallback {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            font-size: 12px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(isset($logoUrl))
            <img src="{{ $logoUrl }}" alt="Coopuertos" style="max-width: 200px; height: auto; margin-bottom: 20px;">
        @endif
        <h1>Coopuertos</h1>
    </div>

    <div class="content">
        <p><strong>¡Hola!</strong></p>
        
        <p>Estás recibiendo este correo porque recibimos una solicitud de restablecimiento de contraseña para tu cuenta.</p>

        <div style="text-align: center;">
            <a href="{{ $url }}" class="button">Restablecer Contraseña</a>
        </div>

        <p>Este enlace de restablecimiento de contraseña expirará en <strong>60 minutos</strong>.</p>

        <p>Si no solicitaste un restablecimiento de contraseña, no se requiere ninguna acción adicional.</p>
    </div>

    <div class="url-fallback">
        <p><strong>¿Tienes problemas para hacer clic en el botón "Restablecer Contraseña"?</strong></p>
        <p>Copia y pega la siguiente URL en tu navegador web:</p>
        <p style="color: #1e40af;">{{ $url }}</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} Coopuertos. Todos los derechos reservados.</p>
    </div>
</body>
</html>
