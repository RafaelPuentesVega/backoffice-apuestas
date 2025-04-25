<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de Membresía Recibido</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
            background-color: #f9f9f9;
        }
        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Pago de Membresía Recibido</h1>
        </div>
        <div class="content">
            <p>Hola {{ $user->name }},</p>
            
            <p>Hemos recibido tu pago para la membresía <strong>{{ $membresia->nombre }}</strong>.</p>
            
            <p>Detalles del pago:</p>
            <table>
                <tr>
                    <th>Membresía:</th>
                    <td>{{ $membresia->nombre }}</td>
                </tr>
                <tr>
                    <th>Monto:</th>
                    <td>{{ number_format($membresia->precio, 2, ',', '.') }} USD</td>
                </tr>
                <tr>
                    <th>Fecha:</th>
                    <td>{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                <tr>
                    <th>Estado:</th>
                    <td>Pendiente de aprobación</td>
                </tr>
            </table>
            
            <p>Un administrador revisará tu pago y lo aprobará en breve. Recibirás una notificación cuando esto ocurra.</p>
            
            <p>Si tienes alguna pregunta, no dudes en contactarnos.</p>
            
            <p>¡Gracias por confiar en nosotros!</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} UUNSE. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html> 