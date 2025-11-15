/**
 * Enhanced Input Validation Functions
 * Controllo Domini v4.2.1
 *
 * Security-first validation with detailed error messages
 */

/**
 * Valida e sanitizza un indirizzo email
 *
 * @param string $email Email da validare
 * @param array $options Opzioni di validazione
 * @return array ['valid' => bool, 'sanitized' => string, 'error' => string]
 */
function validateEmail($email, $options = []) {
    $defaults = [
        'check_mx' => false,
        'allow_disposable' => true,
        'max_length' => 254
    ];
    $options = array_merge($defaults, $options);

    $result = [
        'valid' => false,
        'sanitized' => '',
        'error' => ''
    ];

    // Trim e lowercase
    $email = strtolower(trim($email));

    // Length check
    if (strlen($email) > $options['max_length']) {
        $result['error'] = 'Email troppo lunga (max 254 caratteri)';
        return $result;
    }

    // Empty check
    if (empty($email)) {
        $result['error'] = 'Email richiesta';
        return $result;
    }

    // Filter validation
    $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
    if ($sanitized !== $email) {
        $result['error'] = 'Email contiene caratteri non validi';
        return $result;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result['error'] = 'Formato email non valido';
        return $result;
    }

    // Extract domain
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        $result['error'] = 'Formato email non valido';
        return $result;
    }

    $domain = $parts[1];

    // Check MX records if requested
    if ($options['check_mx']) {
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            $result['error'] = 'Dominio email non raggiungibile';
            return $result;
        }
    }

    // Check disposable email if not allowed
    if (!$options['allow_disposable']) {
        $disposable_domains = [
            'tempmail.com', 'throwaway.email', '10minutemail.com',
            'guerrillamail.com', 'mailinator.com', 'trashmail.com'
        ];

        foreach ($disposable_domains as $disp_domain) {
            if (strpos($domain, $disp_domain) !== false) {
                $result['error'] = 'Email temporanea non consentita';
                return $result;
            }
        }
    }

    $result['valid'] = true;
    $result['sanitized'] = $email;
    return $result;
}

/**
 * Valida password con requisiti di sicurezza
 *
 * @param string $password Password da validare
 * @param array $options Opzioni di validazione
 * @return array ['valid' => bool, 'error' => string, 'strength' => int]
 */
function validatePassword($password, $options = []) {
    $defaults = [
        'min_length' => 8,
        'max_length' => 128,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_number' => true,
        'require_special' => true,
        'check_common' => true
    ];
    $options = array_merge($defaults, $options);

    $result = [
        'valid' => false,
        'error' => '',
        'strength' => 0,
        'feedback' => []
    ];

    // Length check
    $len = strlen($password);
    if ($len < $options['min_length']) {
        $result['error'] = "Password troppo corta (minimo {$options['min_length']} caratteri)";
        return $result;
    }

    if ($len > $options['max_length']) {
        $result['error'] = "Password troppo lunga (massimo {$options['max_length']} caratteri)";
        return $result;
    }

    $strength = 0;

    // Check uppercase
    if (preg_match('/[A-Z]/', $password)) {
        $strength += 20;
    } elseif ($options['require_uppercase']) {
        $result['error'] = 'Password deve contenere almeno una lettera maiuscola';
        $result['feedback'][] = 'Aggiungi lettere maiuscole';
        return $result;
    }

    // Check lowercase
    if (preg_match('/[a-z]/', $password)) {
        $strength += 20;
    } elseif ($options['require_lowercase']) {
        $result['error'] = 'Password deve contenere almeno una lettera minuscola';
        $result['feedback'][] = 'Aggiungi lettere minuscole';
        return $result;
    }

    // Check numbers
    if (preg_match('/[0-9]/', $password)) {
        $strength += 20;
    } elseif ($options['require_number']) {
        $result['error'] = 'Password deve contenere almeno un numero';
        $result['feedback'][] = 'Aggiungi numeri';
        return $result;
    }

    // Check special characters
    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $strength += 20;
    } elseif ($options['require_special']) {
        $result['error'] = 'Password deve contenere almeno un carattere speciale (!@#$%^&*)';
        $result['feedback'][] = 'Aggiungi caratteri speciali';
        return $result;
    }

    // Length bonus
    if ($len >= 12) $strength += 10;
    if ($len >= 16) $strength += 10;

    // Check common passwords
    if ($options['check_common']) {
        $common = ['password', '12345678', 'qwerty', 'admin', 'letmein', 'welcome'];
        $lower = strtolower($password);
        foreach ($common as $common_pwd) {
            if (strpos($lower, $common_pwd) !== false) {
                $result['error'] = 'Password troppo comune, scegliere una password più sicura';
                return $result;
            }
        }
    }

    $result['valid'] = true;
    $result['strength'] = min(100, $strength);

    // Feedback on strength
    if ($strength < 40) {
        $result['feedback'][] = 'Password debole';
    } elseif ($strength < 60) {
        $result['feedback'][] = 'Password media';
    } elseif ($strength < 80) {
        $result['feedback'][] = 'Password buona';
    } else {
        $result['feedback'][] = 'Password forte';
    }

    return $result;
}

/**
 * Valida un URL
 *
 * @param string $url URL da validare
 * @param array $options Opzioni
 * @return array ['valid' => bool, 'sanitized' => string, 'error' => string]
 */
function validateUrl($url, $options = []) {
    $defaults = [
        'require_https' => false,
        'allow_localhost' => false,
        'max_length' => 2048
    ];
    $options = array_merge($defaults, $options);

    $result = [
        'valid' => false,
        'sanitized' => '',
        'error' => ''
    ];

    $url = trim($url);

    if (strlen($url) > $options['max_length']) {
        $result['error'] = 'URL troppo lungo';
        return $result;
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $result['error'] = 'URL non valido';
        return $result;
    }

    $parsed = parse_url($url);

    if (!isset($parsed['scheme']) || !isset($parsed['host'])) {
        $result['error'] = 'URL malformato';
        return $result;
    }

    // Check HTTPS
    if ($options['require_https'] && $parsed['scheme'] !== 'https') {
        $result['error'] = 'Solo URL HTTPS consentiti';
        return $result;
    }

    // Check localhost
    if (!$options['allow_localhost']) {
        $localhost_patterns = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
        foreach ($localhost_patterns as $pattern) {
            if (strpos($parsed['host'], $pattern) !== false) {
                $result['error'] = 'URL localhost non consentito';
                return $result;
            }
        }
    }

    $result['valid'] = true;
    $result['sanitized'] = $url;
    return $result;
}

/**
 * Sanitizza input generico
 *
 * @param string $input Input da sanitizzare
 * @param string $type Tipo di sanitizzazione
 * @return string Input sanitizzato
 */
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'string':
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');

        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);

        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);

        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);

        case 'alphanumeric':
            return preg_replace('/[^a-zA-Z0-9]/', '', $input);

        case 'filename':
            // Safe filename
            $input = preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
            return substr($input, 0, 255);

        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Valida lunghezza stringa
 *
 * @param string $string Stringa da validare
 * @param int $min Lunghezza minima
 * @param int $max Lunghezza massima
 * @param string $name Nome campo (per error message)
 * @return array ['valid' => bool, 'error' => string]
 */
function validateLength($string, $min, $max, $name = 'Campo') {
    $len = mb_strlen($string, 'UTF-8');

    if ($len < $min) {
        return [
            'valid' => false,
            'error' => "$name troppo corto (minimo $min caratteri)"
        ];
    }

    if ($len > $max) {
        return [
            'valid' => false,
            'error' => "$name troppo lungo (massimo $max caratteri)"
        ];
    }

    return ['valid' => true, 'error' => ''];
}
