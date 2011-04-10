<?php
    /**
     * Validate an action token, returning true if valid and false if not
     *
     * @return unknown
     */
    function validate_security_token($require_session = false)
    {
        $token = get_input('__token');
        $ts = get_input('__ts');
        $session_id = Session::id();
        
        if (!$require_session && !$token && $ts && !$session_id)
        {
            // user does not have a session; expect an empty token
            return;
        }

        if ($token && $ts && $session_id)
        {
            // generate token, check with input and forward if invalid
            $generated_token = generate_security_token($ts);

            // Validate token
            if (strcmp($token, $generated_token)==0)
            {
                $day = 60*60*24;
                $now = time();

                // Validate time to ensure its not crazy
                if (($ts>$now-$day) && ($ts<$now+$day))
                {
                    return;
                }
            }
        }
        throw new SecurityException(__('actiongatekeeper:timeerror'));        
    }

    function action_error($msg)
    {
        register_error($msg);
        Session::save_input();
        forward_to_referrer();
    }

    /**
     * Generate a token for the current user suitable for being placed in a hidden field in action forms.
     *
     * @param int $timestamp Unix timestamp
     */
    function generate_security_token($timestamp)
    {
        // Get input values
        $site_secret = get_site_secret();

        // Current session id
        $session_id = session_id();

        // Get user agent
        $ua = $_SERVER['HTTP_USER_AGENT'];

        // Session token
        $st = Session::get('__elgg_session');

        if (($site_secret) && ($session_id))
            return md5($site_secret.$timestamp.$session_id.$ua.$st);

        return false;
    }

    /**
     * Initialise the site secret.
     *
     */
    function init_site_secret()
    {
        $secret = md5(rand().microtime());
        if (Datalist::set('__site_secret__', $secret))
            return $secret;

        return false;
    }

    /**
     * Retrieve the site secret.
     *
     */
    function get_site_secret()
    {
        $secret = Datalist::get('__site_secret__');
        if (!$secret) $secret = init_site_secret();

        return $secret;
    }
