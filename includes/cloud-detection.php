<?php
/**
 * Funzioni per il rilevamento servizi Cloud - Controllo Domini
 * 
 * @package ControlDomini
 * @subpackage CloudDetection
 * @author G Tech Group
 * @website https://controllodomini.it
 */

/**
 * Identifica i servizi cloud utilizzati da un dominio
 * 
 * @param array $dns_results Risultati DNS completi
 * @param string $domain Dominio analizzato
 * @return array Servizi cloud identificati
 */
function identifyCloudServices($dns_results, $domain = '') {
    $services = array(
        'detected_services' => array(),
        'email_provider' => null,
        'hosting_provider' => null,
        'cdn_provider' => null,
        'security_provider' => null,
        'analytics' => array(),
        'other_services' => array(),
        'confidence_scores' => array(),
        'raw_indicators' => array()
    );
    
    // Rileva servizi email
    $services = detectEmailServices($dns_results, $services);
    
    // Rileva hosting e cloud providers
    $services = detectHostingProviders($dns_results, $services, $domain);
    
    // Rileva CDN
    $services = detectCDNProviders($dns_results, $services);
    
    // Rileva servizi di sicurezza
    $services = detectSecurityServices($dns_results, $services);
    
    // Rileva analytics e tracking
    $services = detectAnalyticsServices($dns_results, $services);
    
    // Rileva altri servizi cloud
    $services = detectOtherCloudServices($dns_results, $services);
    
    // Calcola confidence scores
    $services = calculateConfidenceScores($services);
    
    // Genera sommario
    $services['summary'] = generateCloudServicesSummary($services);
    
    return $services;
}

/**
 * Rileva servizi email cloud
 * 
 * @param array $dns_results Risultati DNS
 * @param array $services Array servizi
 * @return array Servizi aggiornati
 */
function detectEmailServices($dns_results, $services) {
    // Definizione indicatori per servizi email
    $email_indicators = array(
        'microsoft365' => array(
            'name' => 'Microsoft 365',
            'category' => 'email',
            'indicators' => array(
                'mx' => array(
                    'outlook.com' => 10,
                    'mail.protection.outlook.com' => 10,
                    'eo.outlook.com' => 8
                ),
                'txt' => array(
                    'MS=' => 8,
                    'v=spf1 include:spf.protection.outlook.com' => 9,
                    'v=msv1' => 7
                ),
                'cname' => array(
                    'autodiscover.outlook.com' => 8,
                    'enterpriseregistration.windows.net' => 7,
                    'enterpriseenrollment.manage.microsoft.com' => 7,
                    'lyncdiscover.outlook.com' => 6,
                    'sip.outlook.com' => 6
                ),
                'srv' => array(
                    '_sipfederationtls._tcp' => 5,
                    '_sip._tls' => 5
                )
            )
        ),
        'google_workspace' => array(
            'name' => 'Google Workspace',
            'category' => 'email',
            'indicators' => array(
                'mx' => array(
                    'aspmx.l.google.com' => 10,
                    'alt1.aspmx.l.google.com' => 9,
                    'alt2.aspmx.l.google.com' => 9,
                    'alt3.aspmx.l.google.com' => 8,
                    'alt4.aspmx.l.google.com' => 8,
                    'googlemail.com' => 8
                ),
                'txt' => array(
                    'google-site-verification=' => 7,
                    'v=spf1 include:_spf.google.com' => 9,
                    'v=spf1 include:_netblocks.google.com' => 8
                ),
                'cname' => array(
                    'ghs.google.com' => 6,
                    'googlehosted.com' => 6,
                    'google.com' => 5
                )
            )
        ),
        'zoho_mail' => array(
            'name' => 'Zoho Mail',
            'category' => 'email',
            'indicators' => array(
                'mx' => array(
                    'zoho.com' => 10,
                    'zohomail.com' => 10,
                    'zoho.eu' => 9
                ),
                'txt' => array(
                    'zoho-verification=' => 8,
                    'v=spf1 include:zoho.com' => 9,
                    'v=spf1 include:zoho.eu' => 8
                ),
                'cname' => array(
                    'business.zoho.com' => 7,
                    'mail.zoho.com' => 7
                )
            )
        ),
        'protonmail' => array(
            'name' => 'ProtonMail',
            'category' => 'email',
            'indicators' => array(
                'mx' => array(
                    'protonmail.ch' => 10,
                    'proton.me' => 10
                ),
                'txt' => array(
                    'protonmail-verification=' => 9,
                    'v=spf1 include:_spf.protonmail.ch' => 9
                )
            )
        ),
        'fastmail' => array(
            'name' => 'FastMail',
            'category' => 'email',
            'indicators' => array(
                'mx' => array(
                    'fastmail.com' => 10,
                    'messagingengine.com' => 10
                ),
                'txt' => array(
                    'v=spf1 include:spf.messagingengine.com' => 9
                )
            )
        ),
        'amazon_workmail' => array(
            'name' => 'Amazon WorkMail',
            'category' => 'email',
            'indicators' => array(
                'mx' => array(
                    'awsapps.com' => 10,
                    'amazonworkmail.com' => 10
                ),
                'txt' => array(
                    'amazonses:' => 7,
                    'v=spf1 include:amazonses.com' => 8
                )
            )
        )
    );
    
    // Controlla ogni servizio
    foreach ($email_indicators as $service_id => $service_config) {
        $score = 0;
        $found_indicators = array();
        
        // Controlla MX records
        if (isset($dns_results['MX']) && isset($service_config['indicators']['mx'])) {
            foreach ($dns_results['MX'] as $mx) {
                foreach ($service_config['indicators']['mx'] as $pattern => $points) {
                    if (stripos($mx['target'], $pattern) !== false) {
                        $score += $points;
                        $found_indicators[] = "MX: " . $mx['target'];
                        $services['raw_indicators'][$service_id]['mx'][] = $mx['target'];
                    }
                }
            }
        }
        
        // Controlla TXT records
        if (isset($dns_results['TXT']) && isset($service_config['indicators']['txt'])) {
            foreach ($dns_results['TXT'] as $txt) {
                foreach ($service_config['indicators']['txt'] as $pattern => $points) {
                    if (stripos($txt['txt'], $pattern) !== false) {
                        $score += $points;
                        $found_indicators[] = "TXT: " . substr($txt['txt'], 0, 50) . "...";
                        $services['raw_indicators'][$service_id]['txt'][] = $txt['txt'];
                    }
                }
            }
        }
        
        // Controlla CNAME records
        if (isset($dns_results['CNAME']) && isset($service_config['indicators']['cname'])) {
            foreach ($dns_results['CNAME'] as $cname) {
                foreach ($service_config['indicators']['cname'] as $pattern => $points) {
                    if (stripos($cname['target'], $pattern) !== false) {
                        $score += $points;
                        $found_indicators[] = "CNAME: " . $cname['host'] . " -> " . $cname['target'];
                        $services['raw_indicators'][$service_id]['cname'][] = $cname;
                    }
                }
            }
        }
        
        // Controlla SRV records
        if (isset($dns_results['SRV']) && isset($service_config['indicators']['srv'])) {
            foreach ($dns_results['SRV'] as $srv) {
                foreach ($service_config['indicators']['srv'] as $pattern => $points) {
                    if (stripos($srv['host'], $pattern) !== false) {
                        $score += $points;
                        $found_indicators[] = "SRV: " . $srv['host'];
                        $services['raw_indicators'][$service_id]['srv'][] = $srv;
                    }
                }
            }
        }
        
        // Se il punteggio è significativo, aggiungi il servizio
        if ($score >= 7) {
            $confidence = calculateServiceConfidence($score);
            
            $services['detected_services'][$service_id] = array(
                'name' => $service_config['name'],
                'category' => $service_config['category'],
                'confidence' => $confidence,
                'score' => $score,
                'indicators' => $found_indicators,
                'details' => getServiceDetails($service_id)
            );
            
            // Imposta come email provider principale se ha il punteggio più alto
            if ($service_config['category'] == 'email' && 
                ($services['email_provider'] == null || 
                 $score > $services['detected_services'][$services['email_provider']]['score'])) {
                $services['email_provider'] = $service_id;
            }
        }
    }
    
    return $services;
}

/**
 * Rileva hosting providers
 * 
 * @param array $dns_results Risultati DNS
 * @param array $services Array servizi
 * @param string $domain Dominio
 * @return array Servizi aggiornati
 */
function detectHostingProviders($dns_results, $services, $domain) {
    $hosting_indicators = array(
        'aws' => array(
            'name' => 'Amazon Web Services (AWS)',
            'patterns' => array(
                'a' => array('amazonaws.com', 'aws.com', 'elasticbeanstalk.com', 'awsdns-'),
                'cname' => array('cloudfront.net', 's3.amazonaws.com', 'elb.amazonaws.com'),
                'ns' => array('awsdns-')
            )
        ),
        'azure' => array(
            'name' => 'Microsoft Azure',
            'patterns' => array(
                'a' => array('azure.com', 'azurewebsites.net', 'cloudapp.azure.com'),
                'cname' => array('azurefd.net', 'azureedge.net', 'blob.core.windows.net'),
                'txt' => array('MS=ms')
            )
        ),
        'google_cloud' => array(
            'name' => 'Google Cloud Platform',
            'patterns' => array(
                'a' => array('googleusercontent.com', 'googleplex.com'),
                'cname' => array('googlehosted.com', 'appspot.com'),
                'ns' => array('googledomains.com')
            )
        ),
        'cloudflare' => array(
            'name' => 'Cloudflare',
            'patterns' => array(
                'ns' => array('cloudflare.com'),
                'a' => array('104.', '172.', '141.101.', '190.93.'),
                'txt' => array('cloudflare-verify')
            )
        ),
        'digitalocean' => array(
            'name' => 'DigitalOcean',
            'patterns' => array(
                'a' => array('digitalocean.com', 'digitaloceanspaces.com'),
                'ns' => array('digitalocean.com')
            )
        ),
        'ovh' => array(
            'name' => 'OVH',
            'patterns' => array(
                'ns' => array('ovh.net', 'ovh.com'),
                'mx' => array('ovh.net')
            )
        ),
        'godaddy' => array(
            'name' => 'GoDaddy',
            'patterns' => array(
                'ns' => array('domaincontrol.com', 'godaddy.com'),
                'mx' => array('secureserver.net')
            )
        ),
        'aruba' => array(
            'name' => 'Aruba',
            'patterns' => array(
                'ns' => array('aruba.it', 'arubadom.net'),
                'mx' => array('aruba.it', 'arubapec.it')
            )
        )
    );
    
    foreach ($hosting_indicators as $provider_id => $config) {
        $found = false;
        $indicators = array();
        
        // Controlla A records
        if (isset($dns_results['A']) && isset($config['patterns']['a'])) {
            foreach ($dns_results['A'] as $a_record) {
                // Controlla per IP pattern
                foreach ($config['patterns']['a'] as $pattern) {
                    if (strpos($pattern, '.') === strlen($pattern) - 1) {
                        // È un pattern IP
                        if (strpos($a_record['ip'], $pattern) === 0) {
                            $found = true;
                            $indicators[] = "IP: " . $a_record['ip'];
                        }
                    }
                }
                
                // Reverse DNS lookup per identificare provider
                $ptr = gethostbyaddr($a_record['ip']);
                if ($ptr && $ptr != $a_record['ip']) {
                    foreach ($config['patterns']['a'] as $pattern) {
                        if (stripos($ptr, $pattern) !== false) {
                            $found = true;
                            $indicators[] = "PTR: " . $ptr;
                        }
                    }
                }
            }
        }
        
        // Controlla altri record types
        foreach (array('cname', 'ns', 'mx', 'txt') as $record_type) {
            $dns_type = strtoupper($record_type);
            if (isset($dns_results[$dns_type]) && isset($config['patterns'][$record_type])) {
                foreach ($dns_results[$dns_type] as $record) {
                    $value = isset($record['target']) ? $record['target'] : 
                            (isset($record['txt']) ? $record['txt'] : '');
                    
                    foreach ($config['patterns'][$record_type] as $pattern) {
                        if (stripos($value, $pattern) !== false) {
                            $found = true;
                            $indicators[] = "{$dns_type}: " . substr($value, 0, 50);
                        }
                    }
                }
            }
        }
        
        if ($found) {
            $services['detected_services'][$provider_id] = array(
                'name' => $config['name'],
                'category' => 'hosting',
                'confidence' => 'high',
                'indicators' => $indicators
            );
            
            if ($services['hosting_provider'] == null) {
                $services['hosting_provider'] = $provider_id;
            }
        }
    }
    
    return $services;
}

/**
 * Rileva CDN providers
 * 
 * @param array $dns_results Risultati DNS
 * @param array $services Array servizi
 * @return array Servizi aggiornati
 */
function detectCDNProviders($dns_results, $services) {
    $cdn_indicators = array(
        'cloudflare' => array(
            'name' => 'Cloudflare CDN',
            'patterns' => array(
                'cname' => array('cloudflare.net', 'cloudflare.com'),
                'header' => array('cf-ray', 'cf-cache-status')
            )
        ),
        'cloudfront' => array(
            'name' => 'Amazon CloudFront',
            'patterns' => array(
                'cname' => array('cloudfront.net', 'd[0-9a-z]+.cloudfront.net')
            )
        ),
        'akamai' => array(
            'name' => 'Akamai',
            'patterns' => array(
                'cname' => array('akamai.net', 'akamaiedge.net', 'akamaitechnologies.com')
            )
        ),
        'fastly' => array(
            'name' => 'Fastly',
            'patterns' => array(
                'cname' => array('fastly.net', 'fastlylb.net')
            )
        ),
        'maxcdn' => array(
            'name' => 'MaxCDN/StackPath',
            'patterns' => array(
                'cname' => array('maxcdn.com', 'stackpath.net', 'stackpathcdn.com')
            )
        ),
        'azure_cdn' => array(
            'name' => 'Azure CDN',
            'patterns' => array(
                'cname' => array('azureedge.net', 'vo.msecnd.net')
            )
        ),
        'bunnycdn' => array(
            'name' => 'BunnyCDN',
            'patterns' => array(
                'cname' => array('b-cdn.net', 'bunnycdn.com')
            )
        )
    );
    
    foreach ($cdn_indicators as $cdn_id => $config) {
        $found = false;
        $indicators = array();
        
        // Controlla CNAME records
        if (isset($dns_results['CNAME'])) {
            foreach ($dns_results['CNAME'] as $cname) {
                foreach ($config['patterns']['cname'] as $pattern) {
                    if (stripos($cname['target'], $pattern) !== false) {
                        $found = true;
                        $indicators[] = $cname['host'] . " -> " . $cname['target'];
                    }
                }
            }
        }
        
        if ($found) {
            $services['detected_services'][$cdn_id] = array(
                'name' => $config['name'],
                'category' => 'cdn',
                'confidence' => 'high',
                'indicators' => $indicators
            );
            
            if ($services['cdn_provider'] == null) {
                $services['cdn_provider'] = $cdn_id;
            }
        }
    }
    
    return $services;
}

/**
 * Rileva servizi di sicurezza
 * 
 * @param array $dns_results Risultati DNS
 * @param array $services Array servizi
 * @return array Servizi aggiornati
 */
function detectSecurityServices($dns_results, $services) {
    $security_indicators = array(
        'cloudflare_security' => array(
            'name' => 'Cloudflare Security',
            'patterns' => array(
                'txt' => array('cloudflare-verify', '_cf-'),
                'caa' => array('cloudflare.com')
            )
        ),
        'sucuri' => array(
            'name' => 'Sucuri',
            'patterns' => array(
                'a' => array('sucuri.net'),
                'txt' => array('sucuri-verification')
            )
        ),
        'incapsula' => array(
            'name' => 'Incapsula/Imperva',
            'patterns' => array(
                'cname' => array('incapdns.net', 'imperva.com')
            )
        ),
        'wordfence' => array(
            'name' => 'Wordfence',
            'patterns' => array(
                'txt' => array('wordfence-verification')
            )
        )
    );
    
    foreach ($security_indicators as $security_id => $config) {
        $found = checkServicePatterns($dns_results, $config['patterns']);
        
        if ($found['detected']) {
            $services['detected_services'][$security_id] = array(
                'name' => $config['name'],
                'category' => 'security',
                'confidence' => 'medium',
                'indicators' => $found['indicators']
            );
            
            if ($services['security_provider'] == null) {
                $services['security_provider'] = $security_id;
            }
        }
    }
    
    return $services;
}

/**
 * Rileva servizi analytics
 * 
 * @param array $dns_results Risultati DNS
 * @param array $services Array servizi
 * @return array Servizi aggiornati
 */
function detectAnalyticsServices($dns_results, $services) {
    $analytics_indicators = array(
        'google_analytics' => array(
            'name' => 'Google Analytics',
            'patterns' => array(
                'txt' => array('google-analytics', 'UA-', 'G-', 'GTM-')
            )
        ),
        'facebook_pixel' => array(
            'name' => 'Facebook Pixel',
            'patterns' => array(
                'txt' => array('facebook-domain-verification')
            )
        ),
        'hotjar' => array(
            'name' => 'Hotjar',
            'patterns' => array(
                'txt' => array('hotjar-verification')
            )
        ),
        'matomo' => array(
            'name' => 'Matomo Analytics',
            'patterns' => array(
                'cname' => array('matomo.cloud', 'piwik')
            )
        )
    );
    
    foreach ($analytics_indicators as $analytics_id => $config) {
        $found = checkServicePatterns($dns_results, $config['patterns']);
        
        if ($found['detected']) {
            $services['analytics'][] = array(
                'id' => $analytics_id,
                'name' => $config['name'],
                'indicators' => $found['indicators']
            );
        }
    }
    
    return $services;
}

/**
 * Rileva altri servizi cloud
 * 
 * @param array $dns_results Risultati DNS
 * @param array $services Array servizi
 * @return array Servizi aggiornati
 */
function detectOtherCloudServices($dns_results, $services) {
    $other_services = array(
        'shopify' => array(
            'name' => 'Shopify',
            'category' => 'ecommerce',
            'patterns' => array(
                'a' => array('23.227.38.', '23.227.39.'),
                'cname' => array('myshopify.com', 'shopify.com')
            )
        ),
        'wordpress_com' => array(
            'name' => 'WordPress.com',
            'category' => 'cms',
            'patterns' => array(
                'cname' => array('wordpress.com', 'wpengine.com'),
                'ns' => array('wordpress.com')
            )
        ),
        'wix' => array(
            'name' => 'Wix',
            'category' => 'website_builder',
            'patterns' => array(
                'a' => array('185.230.60.', '185.230.61.'),
                'cname' => array('wixdns.net')
            )
        ),
        'squarespace' => array(
            'name' => 'Squarespace',
            'category' => 'website_builder',
            'patterns' => array(
                'a' => array('198.185.159.', '198.49.23.'),
                'cname' => array('squarespace.com')
            )
        ),
        'github_pages' => array(
            'name' => 'GitHub Pages',
            'category' => 'hosting',
            'patterns' => array(
                'a' => array('185.199.108.', '185.199.109.', '185.199.110.', '185.199.111.'),
                'cname' => array('github.io')
            )
        ),
        'netlify' => array(
            'name' => 'Netlify',
            'category' => 'hosting',
            'patterns' => array(
                'cname' => array('netlify.com', 'netlify.app'),
                'ns' => array('nsone.net')
            )
        ),
        'vercel' => array(
            'name' => 'Vercel',
            'category' => 'hosting',
            'patterns' => array(
                'cname' => array('vercel.app', 'now.sh'),
                'a' => array('76.76.21.')
            )
        ),
        'mailchimp' => array(
            'name' => 'Mailchimp',
            'category' => 'marketing',
            'patterns' => array(
                'txt' => array('mailchimp', 'mc1.', 'mc2.'),
                'cname' => array('mailchimp.com', 'mandrillapp.com')
            )
        ),
        'sendgrid' => array(
            'name' => 'SendGrid',
            'category' => 'email_marketing',
            'patterns' => array(
                'txt' => array('sendgrid.net'),
                'cname' => array('sendgrid.net', 'em.', 'u[0-9]+.wl[0-9]+.sendgrid.net')
            )
        ),
        'hubspot' => array(
            'name' => 'HubSpot',
            'category' => 'crm',
            'patterns' => array(
                'cname' => array('hs-sites.com', 'hubspot.com', 'hubspotemail.net')
            )
        ),
        'salesforce' => array(
            'name' => 'Salesforce',
            'category' => 'crm',
            'patterns' => array(
                'cname' => array('salesforce.com', 'force.com', 'exacttarget.com')
            )
        ),
        'zendesk' => array(
            'name' => 'Zendesk',
            'category' => 'support',
            'patterns' => array(
                'cname' => array('zendesk.com', 'zdassets.com')
            )
        ),
        'intercom' => array(
            'name' => 'Intercom',
            'category' => 'support',
            'patterns' => array(
                'cname' => array('intercom.io', 'intercomcdn.com')
            )
        ),
        'stripe' => array(
            'name' => 'Stripe',
            'category' => 'payment',
            'patterns' => array(
                'txt' => array('stripe-verification')
            )
        ),
        'paypal' => array(
            'name' => 'PayPal',
            'category' => 'payment',
            'patterns' => array(
                'txt' => array('paypal-verification')
            )
        )
    );
    
    foreach ($other_services as $service_id => $config) {
        $found = checkServicePatterns($dns_results, $config['patterns']);
        
        if ($found['detected']) {
            $services['other_services'][] = array(
                'id' => $service_id,
                'name' => $config['name'],
                'category' => $config['category'],
                'confidence' => $found['confidence'],
                'indicators' => $found['indicators']
            );
        }
    }
    
    return $services;
}

/**
 * Controlla pattern di servizio nei record DNS
 * 
 * @param array $dns_results Risultati DNS
 * @param array $patterns Pattern da cercare
 * @return array Risultato controllo
 */
function checkServicePatterns($dns_results, $patterns) {
    $result = array(
        'detected' => false,
        'indicators' => array(),
        'confidence' => 'low',
        'score' => 0
    );
    
    foreach ($patterns as $record_type => $pattern_list) {
        $dns_type = strtoupper($record_type);
        
        if (isset($dns_results[$dns_type])) {
            foreach ($dns_results[$dns_type] as $record) {
                // Estrai il valore appropriato dal record
                $value = '';
                switch ($dns_type) {
                    case 'A':
                        $value = $record['ip'];
                        break;
                    case 'CNAME':
                    case 'MX':
                    case 'NS':
                        $value = $record['target'];
                        break;
                    case 'TXT':
                        $value = $record['txt'];
                        break;
                    case 'CAA':
                        $value = isset($record['value']) ? $record['value'] : '';
                        break;
                }
                
                foreach ($pattern_list as $pattern) {
                    if (stripos($value, $pattern) !== false) {
                        $result['detected'] = true;
                        $result['score'] += 5;
                        $result['indicators'][] = "{$dns_type}: " . substr($value, 0, 100);
                    }
                }
            }
        }
    }
    
    // Calcola confidence basata sullo score
    if ($result['score'] >= 10) {
        $result['confidence'] = 'high';
    } elseif ($result['score'] >= 5) {
        $result['confidence'] = 'medium';
    }
    
    return $result;
}

/**
 * Calcola confidence score per un servizio
 * 
 * @param int $score Punteggio raw
 * @return string Livello confidence
 */
function calculateServiceConfidence($score) {
    if ($score >= 15) {
        return 'very_high';
    } elseif ($score >= 10) {
        return 'high';
    } elseif ($score >= 7) {
        return 'medium';
    } elseif ($score >= 4) {
        return 'low';
    }
    return 'very_low';
}

/**
 * Calcola confidence scores complessivi
 * 
 * @param array $services Servizi rilevati
 * @return array Servizi con scores
 */
function calculateConfidenceScores($services) {
    foreach ($services['detected_services'] as $service_id => $service) {
        if (!isset($service['confidence'])) {
            $service['confidence'] = 'medium';
        }
        
        // Converti confidence in percentuale
        $confidence_map = array(
            'very_high' => 95,
            'high' => 85,
            'medium' => 70,
            'low' => 50,
            'very_low' => 30
        );
        
        $services['confidence_scores'][$service_id] = 
            isset($confidence_map[$service['confidence']]) ? 
            $confidence_map[$service['confidence']] : 50;
    }
    
    return $services;
}

/**
 * Ottieni dettagli specifici del servizio
 * 
 * @param string $service_id ID servizio
 * @return array Dettagli servizio
 */
function getServiceDetails($service_id) {
    $service_details = array(
        'microsoft365' => array(
            'description' => 'Suite completa di produttività cloud Microsoft',
            'includes' => array('Exchange Online', 'Teams', 'SharePoint', 'OneDrive'),
            'website' => 'https://www.microsoft.com/microsoft-365',
            'support' => 'https://support.microsoft.com'
        ),
        'google_workspace' => array(
            'description' => 'Suite di collaborazione e produttività Google',
            'includes' => array('Gmail', 'Drive', 'Docs', 'Meet', 'Calendar'),
            'website' => 'https://workspace.google.com',
            'support' => 'https://support.google.com/a'
        ),
        'aws' => array(
            'description' => 'Piattaforma cloud leader di Amazon',
            'includes' => array('EC2', 'S3', 'CloudFront', 'Route 53'),
            'website' => 'https://aws.amazon.com',
            'support' => 'https://aws.amazon.com/support'
        ),
        'cloudflare' => array(
            'description' => 'CDN e servizi di sicurezza web',
            'includes' => array('CDN', 'DDoS Protection', 'WAF', 'DNS'),
            'website' => 'https://www.cloudflare.com',
            'support' => 'https://support.cloudflare.com'
        )
    );
    
    return isset($service_details[$service_id]) ? $service_details[$service_id] : array();
}

/**
 * Genera sommario servizi cloud
 * 
 * @param array $services Servizi rilevati
 * @return array Sommario
 */
function generateCloudServicesSummary($services) {
    $summary = array(
        'total_services' => count($services['detected_services']) + 
                          count($services['analytics']) + 
                          count($services['other_services']),
        'categories' => array(),
        'primary_stack' => array(),
        'recommendations' => array()
    );
    
    // Conta servizi per categoria
    $categories = array();
    
    foreach ($services['detected_services'] as $service) {
        $cat = $service['category'];
        if (!isset($categories[$cat])) {
            $categories[$cat] = 0;
        }
        $categories[$cat]++;
    }
    
    foreach ($services['other_services'] as $service) {
        $cat = $service['category'];
        if (!isset($categories[$cat])) {
            $categories[$cat] = 0;
        }
        $categories[$cat]++;
    }
    
    $summary['categories'] = $categories;
    
    // Identifica stack principale
    if ($services['email_provider']) {
        $summary['primary_stack']['email'] = $services['detected_services'][$services['email_provider']]['name'];
    }
    if ($services['hosting_provider']) {
        $summary['primary_stack']['hosting'] = $services['detected_services'][$services['hosting_provider']]['name'];
    }
    if ($services['cdn_provider']) {
        $summary['primary_stack']['cdn'] = $services['detected_services'][$services['cdn_provider']]['name'];
    }
    
    // Genera raccomandazioni
    if (!$services['cdn_provider']) {
        $summary['recommendations'][] = 'Considera l\'uso di un CDN per migliorare le performance globali';
    }
    
    if (!$services['security_provider'] && $summary['total_services'] > 3) {
        $summary['recommendations'][] = 'Con diversi servizi cloud, considera un WAF per maggiore sicurezza';
    }
    
    return $summary;
}

/**
 * Genera report servizi cloud per export
 * 
 * @param array $services Servizi rilevati
 * @return array Report formattato
 */
function generateCloudServicesReport($services) {
    $report = array(
        'overview' => array(
            'total_services' => $services['summary']['total_services'],
            'categories' => $services['summary']['categories'],
            'primary_stack' => $services['summary']['primary_stack']
        ),
        'services' => array(),
        'technology_stack' => array(),
        'integration_map' => array()
    );
    
    // Dettagli servizi principali
    foreach ($services['detected_services'] as $service_id => $service) {
        $report['services'][] = array(
            'name' => $service['name'],
            'category' => $service['category'],
            'confidence' => $services['confidence_scores'][$service_id] . '%',
            'indicators' => $service['indicators']
        );
    }
    
    // Stack tecnologico
    $stack_categories = array(
        'Frontend' => array('cdn', 'website_builder'),
        'Backend' => array('hosting', 'cms'),
        'Communication' => array('email', 'support'),
        'Business' => array('crm', 'payment', 'ecommerce'),
        'Security' => array('security'),
        'Analytics' => array('analytics', 'marketing')
    );
    
    foreach ($stack_categories as $stack_name => $categories) {
        $report['technology_stack'][$stack_name] = array();
        
        foreach ($services['detected_services'] as $service) {
            if (in_array($service['category'], $categories)) {
                $report['technology_stack'][$stack_name][] = $service['name'];
            }
        }
        
        foreach ($services['other_services'] as $service) {
            if (in_array($service['category'], $categories)) {
                $report['technology_stack'][$stack_name][] = $service['name'];
            }
        }
    }
    
    return $report;
}
?>
