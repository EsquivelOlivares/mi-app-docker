<?php
// app/utils/ResponseHelper.php

class ResponseHelper {
    
    // Enviar respuesta JSON
    public static function sendJson($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    // Respuesta de éxito
    public static function success($message, $data = null, $statusCode = 200) {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::sendJson($response, $statusCode);
    }
    
    // Respuesta de error
    public static function error($message, $errors = null, $statusCode = 400) {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        self::sendJson($response, $statusCode);
    }
    
    // Respuesta de error de autenticación
    public static function unauthorized($message = 'Acceso no autorizado') {
        self::error($message, null, 401);
    }
    
    // Respuesta de error de permisos
    public static function forbidden($message = 'No tienes permisos para esta acción') {
        self::error($message, null, 403);
    }
}
