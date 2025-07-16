<?php
/**
 * Pagina 404 - File non trovato
 * 
 * @package ControlDomini
 * @author G Tech Group
 */

// Set 404 header
http_response_code(404);

// Per richieste di risorse, ritorna vuoto
$requested_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_resource = preg_match('/\.(css|js|jpg|jpeg|png|gif|ico|svg|webp|woff|woff2|ttf|eot)$/i', $requested_uri);

if ($is_resource) {
    // Per risorse mancanti, non mostrare HTML
    exit;
}

// Per pagine, mostra errore 404 user-friendly
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Pagina non trovata | Controllo Domini</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 20px;
        }
        
        .error-container {
            max-width: 600px;
            animation: fadeIn 0.6s ease-out;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .error-title {
            font-size: 32px;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .error-message {
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
        }
        
        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-secondary:hover {
            background: white;
            color: #667eea;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .decoration {
            position: fixed;
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            animation: float 20s infinite ease-in-out;
        }
        
        .decoration-1 {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .decoration-2 {
            bottom: 10%;
            right: 10%;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -30px) scale(1.1); }
            50% { transform: translate(-20px, 20px) scale(0.9); }
            75% { transform: translate(20px, 30px) scale(1.05); }
        }
        
        @media (max-width: 600px) {
            .error-code {
                font-size: 80px;
            }
            
            .error-title {
                font-size: 24px;
            }
            
            .error-message {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="decoration decoration-1"></div>
    <div class="decoration decoration-2"></div>
    
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Pagina non trovata</h1>
        <p class="error-message">
            La pagina che stai cercando non esiste o è stata spostata. 
            Potresti aver digitato male l'indirizzo o seguito un link non più valido.
        </p>
        
        <div class="error-actions">
            <a href="/" class="btn">Torna alla Home</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Torna Indietro</a>
        </div>
    </div>
</body>
</html>
