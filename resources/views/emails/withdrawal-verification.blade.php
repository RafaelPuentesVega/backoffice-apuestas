<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Retiro</title>
</head>
<body>
    <h2>Verificación de Retiro</h2>
    <p>Has solicitado realizar un retiro. Tu código de verificación es:</p>
    <p style="font-size: 24px; font-weight: bold;">{{ $token }}</p>
    <p>Este código expirará en 15 minutos.</p>
    <p>Si no has solicitado esta acción, por favor ignora este correo.</p>
</body>
</html>
