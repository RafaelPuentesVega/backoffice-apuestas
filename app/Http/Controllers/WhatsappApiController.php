<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappApiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // No middleware needed here
    }

    /**
     * Enviar mensaje a través de la API de WhatsApp.
     */
    public function sendMessage($data)
    {
        try {
            // Agregar prefijo al número
            $data['number'] = ltrim($data['number'], '+'); 

            // Configurar el payload según el tipo
            $payload = [
                'type' => $data['type'],
                'number' => $data['number'],
            ];

            switch ($data['type']) {
                case 'texto':
                    $payload['message'] = $data['message'];
                    break;

                case 'imagen':
                    $payload['message'] = $data['message'];
                    $payload['imageUrl'] = $data['imageUrl'];
                    break;

                case 'pdf':
                    $payload['pdfBase64'] = $data['pdfBase64'];
                    $payload['nameFile'] = $data['nameFile'];
                    break;
            }
            
            // Configurar el token de autorización (Bearer Token)
            $token = new JwtWhatsappController();
            $token = $token->createToken(); 

            if($token['success'] == false){
                throw new Exception("Ocurrió un error generando el token");            
            }
            
            $url = env('ENDPOINT_API_WHATSAPP_NODE');
            if (!$url) {
                throw new Exception("La URL del endpoint de WhatsApp no está configurada");
            }

            Log::info('Enviando mensaje a WhatsApp', [
                'number' => $data['number'],
                'type' => $data['type'],
                'url' => $url
            ]);

            $client = new Client();
            $response = $client->post($url.'/send-message', [
                'json' => $payload,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token['token']
                ]
            ]);

            // Obtener el cuerpo de la respuesta
            $responseBody = $response->getBody();
            $responseData = json_decode($responseBody, true);
            
            Log::info('Respuesta de WhatsApp API', [
                'response' => $responseData
            ]);
            
            return [
                'success' => true,
                'message' => 'Se envió correctamente el mensaje.',
                'data' => $responseData,
            ];

        } catch (Exception $e) {
            Log::error('Error al enviar mensaje a WhatsApp', [
                'error' => $e->getMessage(),
                'number' => $data['number'] ?? 'no disponible'
            ]);
            
            return [
                'success' => false,
                'message' => 'Ocurrió un error al enviar el mensaje: ' . $e->getMessage(),
                'details' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Verificar si un número de WhatsApp es válido.
     */
    public function verifyNumber($countryCode, $number)
    {
        try {
            // Limpiar el número
            $countryCode = ltrim($countryCode, '+');
            $number = ltrim($number, '+');
            $fullNumber = $countryCode . $number;
            
            // Generar un código de verificación simple
            $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Enviar mensaje de prueba
            $message = "Tu código de verificación para UUNSE es: *{$verificationCode}*\n\nEste código expirará en 10 minutos.";
            
            $data = [
                'type' => 'texto',
                'number' => $fullNumber,
                'message' => $message,
            ];
            
            $result = $this->sendMessage($data);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Número de WhatsApp verificado correctamente.',
                    'verification_code' => $verificationCode
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se pudo verificar el número de WhatsApp.',
                    'details' => $result['message']
                ];
            }
            
        } catch (Exception $e) {
            Log::error('Error al verificar número de WhatsApp', [
                'error' => $e->getMessage(),
                'number' => $countryCode . $number
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al verificar número de WhatsApp: ' . $e->getMessage(),
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Enviar código de verificación a un número de WhatsApp.
     * 
     * @param string $countryCode Código de país (con o sin +)
     * @param string $phone Número de teléfono
     * @param string $code Código de verificación
     * @return array Resultado de la operación
     */
    public function sendVerificationCode($countryCode, $phone, $code)
    {
        try {
            // Limpiar el número
            $countryCode = ltrim($countryCode, '+');
            $phone = ltrim($phone, '+');
            $fullNumber = $countryCode . $phone;
            
            // Preparar el mensaje
            $message = "Tu código de verificación para UUNSE es: *{$code}*\n\nEste código expirará en 10 minutos.";
            
            $data = [
                'type' => 'texto',
                'number' => $fullNumber,
                'message' => $message,
            ];
            
            $result = $this->sendMessage($data);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Código de verificación enviado correctamente.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'No se pudo enviar el código de verificación.',
                    'details' => $result['message']
                ];
            }
            
        } catch (Exception $e) {
            Log::error('Error al enviar código de verificación por WhatsApp', [
                'error' => $e->getMessage(),
                'number' => $countryCode . $phone
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al enviar código de verificación: ' . $e->getMessage(),
                'details' => $e->getMessage()
            ];
        }
    }
}
