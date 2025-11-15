<?php
/**
 *  PHP Mikrotik Billing (https://github.com/hotspotbilling/phpnuxbill/)
 *  by https://t.me/ibnux
 **/

$maintenance_mode = $config['maintenance_mode'];
if ($maintenance_mode == true) {
    displayMaintenanceMessage();
}

if (User::getID()) {
    r2(getUrl('home'));
}

if (isset($routes['1'])) {
    $do = $routes['1'];
} else {
    $do = 'login-display';
}

global $isApi;
/**
 * Perform user login with enhanced security and validation
 * 
 * @param string $username User's username
 * @param string $password User's password
 * @param bool $isApiCall Whether this is an API call (affects response format)
 * @return array Result array with success status, message, and data
 */
function performLogin($username, $password, $isApiCall = false) {
    global $config;
    
    // Input validation
    if (empty($username) || empty($password)) {
        return [
            'success' => false,
            'message' => Lang::T('Please fill in all fields'),
            'data' => []
        ];
    }
    
    /**
     * Get client IP address securely, handling proxy headers
     * @return string Valid IP address or fallback
     */
    function getClientIP() {
        // Use $GLOBALS to reliably access server superglobals and avoid undefined variable issues
        $server = isset($GLOBALS['_SERVER']) && is_array($GLOBALS['_SERVER']) ? $GLOBALS['_SERVER'] : [];

        // Common proxy headers to check (in order of priority)
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_X_CLIENT_IP',
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($server[$header])) {
                $ips = explode(',', $server[$header]);
                
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    
                    // Validate IP and exclude private/reserved ranges for forwarded headers
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                    
                    // For REMOTE_ADDR, allow private IPs (internal networks)
                    if ($header === 'REMOTE_ADDR' && filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        
        // Final fallback
        return '127.0.0.1';
    }
    
    // Sanitize inputs
    $username = trim($username);
    
    // Rate limiting check with secure IP detection
    $clientIP = getClientIP();
    $rateLimitKey = 'login_attempts_' . md5($clientIP);
    
    if (!isset($_SESSION[$rateLimitKey])) {
        $_SESSION[$rateLimitKey] = [];
    }
    
    // Remove attempts older than 15 minutes
    $_SESSION[$rateLimitKey] = array_filter($_SESSION[$rateLimitKey], function($time) {
        return (time() - $time) < 900; // 15 minutes
    });
    
    // Check if too many attempts (max 5 per 15 minutes)
    if (count($_SESSION[$rateLimitKey]) >= 5) {
        _log("Too many login attempts from IP: $clientIP", 'Security');
        return [
            'success' => false,
            'message' => Lang::T('Too many login attempts. Please try again later.'),
            'data' => []
        ];
    }
    
    try {
        run_hook('customer_login'); #HOOK
        
        // Find user by username
        $user = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
        
        // Security: Always perform password verification to prevent timing attacks
        // This ensures consistent execution time regardless of whether user exists
        $passwordValid = false;
        $userExists = ($user !== false);
        
        if ($userExists) {
            // User exists - verify against real password hash
            $passwordValid = Password::_uverify($password, $user['password']);
        } else {
            // User doesn't exist - verify against dummy hash to maintain consistent timing
            // Using a realistic bcrypt hash to ensure same computation time
            $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // bcrypt hash of "dummy"
            Password::_uverify($password, $dummyHash);
            $passwordValid = false; // Always false for non-existent users
        }
        
        // Add small random delay to further normalize timing (1-5ms)
        usleep(rand(1000, 5000));
        
        // Now evaluate results after consistent timing
        if (!$userExists) {
            // Add failed attempt
            $_SESSION[$rateLimitKey][] = time();
            _log("Login attempt with non-existent username: $username from IP: $clientIP", 'Security');
            return [
                'success' => false,
                'message' => Lang::T('Invalid username or password'),
                'data' => []
            ];
        }

        // Check account status
        if ($user['status'] == 'Banned') {
            _log("Banned user $username attempted login from IP: $clientIP", 'Security');
            return [
                'success' => false,
                'message' => Lang::T('This account status') . ' : ' . Lang::T($user['status']),
                'data' => []
            ];
        }

        // Check password verification result
        if (!$passwordValid) {
            // Add failed attempt
            $_SESSION[$rateLimitKey][] = time();
            _log("Failed login attempt for user: $username from IP: $clientIP", 'Security');
            return [
                'success' => false,
                'message' => Lang::T('Invalid username or password'),
                'data' => []
            ];
        }

        // Successful login
        session_regenerate_id(true); // Regenerate session ID and delete old session
        $_SESSION['uid'] = $user['id'];
        $token = User::setCookie($user['id']);
        
        // Update last login time
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();
        
        // Clear failed attempts on successful login
        unset($_SESSION[$rateLimitKey]);
        
        // Log successful login
        _log($username . ' ' . Lang::T('Login Successful'), 'User', $user['id']);
        
        $responseData = [];
        if ($token) {
            $responseData['token'] = "u." . $token;
        }
        
        // Add user info for API responses
        if ($isApiCall) {
            $responseData['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'status' => $user['status'],
                'last_login' => $user['last_login']
            ];
        }
        
        return [
            'success' => true,
            'message' => Lang::T('Login Successful'),
            'data' => $responseData
        ];
        
    } catch (Exception $e) {
        _log("Login error: " . $e->getMessage(), 'Error');
        return [
            'success' => false,
            'message' => Lang::T('An error occurred during login. Please try again.'),
            'data' => []
        ];
    }
}

switch ($do) {
    case 'api':
        // Handle AJAX/API login requests
        header('Content-Type: application/json');
        
        $username = _post('username');
        $password = _post('password');
        $csrf_token = _post('csrf_token');
        
        // Verify CSRF token for API requests
        if (!Csrf::check($csrf_token)) {
            echo json_encode([
                'success' => false,
                'message' => Lang::T('Invalid or Expired CSRF Token'),
                'data' => []
            ]);
            exit;
        }
        
        $result = performLogin($username, $password, true);
        
        echo json_encode($result);
        exit;
        
    case 'post':
        $username = _post('username');
        $password = _post('password');
        $csrf_token = _post('csrf_token');
        if (!Csrf::check($csrf_token)) {
            _msglog('e', Lang::T('Invalid or Expired CSRF Token'));
            r2(getUrl('login'));
        }
        
        $result = performLogin($username, $password, false);
        
        if ($result['success']) {
            if ($isApi) {
                showResult(true, $result['message'], $result['data']);
            } else {
                _alert($result['message'], 'success', "home");
            }
        } else {
            if ($isApi) {
                showResult(false, $result['message']);
            } else {
                _msglog('e', $result['message']);
                r2(getUrl('login'));
            }
        }
        break;

    case 'activation':
        if (!empty(_post('voucher_only'))) {
            $csrf_token = _post('csrf_token');
            if (!Csrf::check($csrf_token)) {
                _msglog('e', Lang::T('Invalid or Expired CSRF Token'));
                r2(getUrl('login'));
            }
            $voucher = Text::alphanumeric(_post('voucher_only'), "-_.,");
            $tur = ORM::for_table('tbl_user_recharges')
                ->where('username', $voucher)
                ->where('customer_id', '0') // Voucher Only will make customer ID as 0
                ->find_one();
            if ($tur) {
                if ($tur['status'] == 'off') {
                    _alert(Lang::T('Internet Voucher Expired'), 'danger', "login");
                }
                $p = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                if ($p) {
                    $dvc = Package::getDevice($p);
                    if ($_app_stage != 'demo') {
                        if (file_exists($dvc)) {
                            if (file_exists($dvc)) {
                                require_once $dvc;
                                $c = [
                                    'fullname' => "Voucher",
                                    'email' => '',
                                    'username' => $voucher,
                                    'password' => $voucher,
                                ];
                                (new $p['device'])->add_customer($c, $p);
                            } else {
                                new Exception(Lang::T("Devices Not Found"));
                            }
                            if (!empty($config['voucher_redirect'])) {
                                r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                            } else {
                                r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                            }
                        } else {
                            new Exception(Lang::T("Devices Not Found"));
                        }
                    }
                    if (!empty($config['voucher_redirect'])) {
                        _alert(Lang::T("Voucher activation success, now you can login"), 'danger', $config['voucher_redirect']);
                    } else {
                        r2(getUrl('login'), 's', Lang::T("Voucher activation success, you are connected to internet"));
                    }
                } else {
                    _alert(Lang::T('Internet Plan Expired'), 'danger', "login");
                }
            } else {
                $v = ORM::for_table('tbl_voucher')->whereRaw("BINARY code = '$voucher'")->find_one();
                if (!$v) {
                    _alert(Lang::T('Voucher invalid'), 'danger', "login");
                }
                if ($v['status'] == 0) {
                    if (Package::rechargeUser(0, $v['routers'], $v['id_plan'], "Voucher", $voucher)) {
                        $v->status = "1";
                        $v->save();
                        $tur = ORM::for_table('tbl_user_recharges')->where('username', $voucher)->find_one();
                        if ($tur) {
                            $p = ORM::for_table('tbl_plans')->where('id', $tur['plan_id'])->find_one();
                            if ($p) {
                                $dvc = Package::getDevice($p);
                                if ($_app_stage != 'demo') {
                                    if (file_exists($dvc)) {
                                        if (file_exists($dvc)) {
                                            require_once $dvc;
                                            $c = [
                                                'fullname' => "Voucher",
                                                'email' => '',
                                                'username' => $voucher,
                                                'password' => $voucher,
                                            ];
                                            (new $p['device'])->add_customer($c, $p);
                                        } else {
                                            new Exception(Lang::T("Devices Not Found"));
                                        }
                                        if (!empty($config['voucher_redirect'])) {
                                            r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                        } else {
                                            r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                        }
                                    } else {
                                        new Exception(Lang::T("Devices Not Found"));
                                    }
                                }
                                if (!empty($config['voucher_redirect'])) {
                                    _alert(Lang::T("Voucher activation success, now you can login"), 'danger', $config['voucher_redirect']);
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, you are connected to internet"));
                                }
                            } else {
                                _alert(Lang::T('Internet Plan Expired'), 'danger', "login");
                            }
                        } else {
                            _alert(Lang::T('Voucher activation failed'), 'danger', "login");
                        }
                    } else {
                        _alert(Lang::T('Voucher activation failed'), 'danger', "login");
                    }
                } else {
                    _alert(Lang::T('Internet Voucher Expired'), 'danger', "login");
                }
            }
        } else {
            $voucher = Text::alphanumeric(_post('voucher'), "-_.,");
            $username = _post('username');
            $v1 = ORM::for_table('tbl_voucher')->whereRaw("BINARY code = '$voucher'")->find_one();
            if ($v1) {
                // voucher exists, check customer exists or not
                $user = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
                if (!$user) {
                    $d = ORM::for_table('tbl_customers')->create();
                    $d->username = alphanumeric($username, "+_.@-");
                    $d->password = $voucher;
                    $d->fullname = '';
                    $d->address = '';
                    $d->email = '';
                    $d->phonenumber = (strlen($username) < 21) ? $username : '';
                    if ($d->save()) {
                        $user = ORM::for_table('tbl_customers')->where('username', $username)->find_one($d->id());
                        if (!$user) {
                            r2(getUrl('login'), 'e', Lang::T('Voucher activation failed'));
                        }
                    } else {
                        _alert(Lang::T('Login Successful'), 'success', "dashboard");
                        r2(getUrl('login'), 'e', Lang::T('Voucher activation failed') . '.');
                    }
                }
                if ($v1['status'] == 0) {
                    $oldPass = $user['password'];
                    // change customer password to voucher code
                    $user->password = $voucher;
                    $user->save();
                    // voucher activation
                    if (Package::rechargeUser($user['id'], $v1['routers'], $v1['id_plan'], "Voucher", $voucher)) {
                        $v1->status = "1";
                        $v1->used_date = date('Y-m-d H:i:s');
                        $v1->user = $user['username'];
                        $v1->save();
                        $user->last_login = date('Y-m-d H:i:s');
                        $user->save();
                        // add customer to mikrotik
                        if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'])) {
                            try {
                                $p = ORM::for_table('tbl_plans')->where('id', $v1['id_plan'])->find_one();
                                $dvc = Package::getDevice($p);
                                if ($_app_stage != 'demo') {
                                    if (file_exists($dvc)) {
                                        require_once $dvc;
                                        (new $p['device'])->connect_customer($user, $_SESSION['nux-ip'], $_SESSION['nux-mac'], $v1['routers']);
                                        if (!empty($config['voucher_redirect'])) {
                                            r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                        } else {
                                            r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                        }
                                    } else {
                                        new Exception(Lang::T("Devices Not Found"));
                                    }
                                }
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, you are connected to internet"));
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, you are connected to internet"));
                                }
                            } catch (Exception $e) {
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                }
                            }
                        }
                        if (!empty($config['voucher_redirect'])) {
                            r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                        } else {
                            r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                        }
                    } else {
                        // if failed to recharge, restore old password
                        $user->password = $oldPass;
                        $user->save();
                        r2(getUrl('login'), 'e', Lang::T("Failed to activate voucher"));
                    }
                } else {
                    // used voucher
                    // check if voucher used by this username
                    if ($v1['user'] == $user['username']) {
                        $user->last_login = date('Y-m-d H:i:s');
                        $user->save();
                        if (!empty($_SESSION['nux-mac']) && !empty($_SESSION['nux-ip'])) {
                            try {
                                $p = ORM::for_table('tbl_plans')->where('id', $v1['id_plan'])->find_one();
                                $dvc = Package::getDevice($p);
                                if ($_app_stage != 'demo') {
                                    if (file_exists($dvc)) {
                                        require_once $dvc;
                                        (new $p['device'])->connect_customer($user, $_SESSION['nux-ip'], $_SESSION['nux-mac'], $v1['routers']);
                                        if (!empty($config['voucher_redirect'])) {
                                            r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                        } else {
                                            r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                        }
                                    } else {
                                        new Exception(Lang::T("Devices Not Found"));
                                    }
                                }
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, you are connected to internet"));
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                }
                            } catch (Exception $e) {
                                if (!empty($config['voucher_redirect'])) {
                                    r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, now you can login"));
                                } else {
                                    r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                                }
                            }
                        } else {
                            if (!empty($config['voucher_redirect'])) {
                                r2($config['voucher_redirect'], 's', Lang::T("Voucher activation success, you are connected to internet"));
                            } else {
                                r2(getUrl('login'), 's', Lang::T("Voucher activation success, now you can login"));
                            }
                        }
                    } else {
                        // voucher used by other customer
                        r2(getUrl('login'), 'e', Lang::T('Voucher Not Valid'));
                    }
                }
            } else {
                _msglog('e', Lang::T('Invalid Username or Password'));
                r2(getUrl('login'));
            }
        }
    default:
        run_hook('customer_view_login'); #HOOK
        $csrf_token = Csrf::generateAndStoreToken();
        if ($config['disable_registration'] == 'yes') {
            $ui->assign('csrf_token', $csrf_token);
            $ui->assign('_title', Lang::T('Activation'));
            $ui->assign('code', alphanumeric(_get('code'), "-"));
            $ui->display('customer/login-noreg.tpl');
        } else {
            $UPLOAD_URL_PATH = str_replace($root_path, '',  $UPLOAD_PATH);
            if (!empty($config['login_page_logo']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'])) {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_logo'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png')) {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.png';
            } else {
                $login_logo = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'login-logo.default.png';
            }

            if (!empty($config['login_page_wallpaper']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'])) {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_wallpaper'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png')) {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.png';
            } else {
                $wallpaper = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'wallpaper.default.png';
            }

            if (!empty($config['login_page_favicon']) && file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'])) {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . $config['login_page_favicon'];
            } elseif (file_exists($UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png')) {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.png';
            } else {
                $favicon = $UPLOAD_URL_PATH . DIRECTORY_SEPARATOR . 'favicon.default.png';
            }

            $ui->assign('login_logo', $login_logo);
            $ui->assign('wallpaper', $wallpaper);
            $ui->assign('favicon', $favicon);
            $ui->assign('csrf_token', $csrf_token);
            $ui->assign('_title', Lang::T('Login'));
            switch ($config['login_page_type']) {
                case 'custom':
                    $ui->display('customer/login-custom-' . $config['login_Page_template'] . '.tpl');
                    break;
                default:
                    $ui->display('customer/login.tpl');
                    break;
            }
        }

        break;
}
