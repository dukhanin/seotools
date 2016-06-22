<?php

if ( !function_exists('st_set_marker') )
{
    function st_set_marker()
    {
        setcookie('show_me_the_money', 1, time() + (3600 * 24 * 7), '/');
        $_COOKIE['show_me_the_money'] = 1;
    }
}

if ( !function_exists('st_auto_marker') )
{
    function st_auto_marker()
    {
        if ( @$_GET['show_me_the_money'] )
        {
            st_set_marker();
        }
    }
}

if ( !function_exists('st_is_marker') )
{
    function st_is_marker()
    {
        return (bool)$_COOKIE['show_me_the_money'];
    }
}

if ( !function_exists('st_redirect') )
{
    function st_redirect($location)
    {
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $location);
        exit();
    }
}

if ( !function_exists('st_smart_redirect') )
{
    function st_smart_redirect($file = null)
    {
        if ( is_null($file) )
        {
            $file = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/redirect.txt';
        }

        if ( !file_exists($file) )
        {
            return false;
        }

        $default = null;
        foreach ( (array)@file($file) as $row )
        {
            if ( !($row = trim($row)) || preg_match('/^#/', $row) )
            {
                continue;
            }

            $row = preg_replace('/\s+/', ' ', $row);
            list($old, $new) = explode(' ', $row);

            if ( empty($new) )
            {
                if ( !empty($old) && empty($default) )
                {
                    $host = $_SERVER['HTTP_HOST'];
                    $host = preg_replace('/www\./', '', $host);

                    $redirects[ $host ] = array(
                        ':default' => array(
                            'host' => $_SERVER['HTTP_HOST'],
                            'location'  => $old
                        )
                    );
                }

                continue;
            }

            $old = parse_url($old);

            $request_uri = $old['path'] . ($old['query'] ? '?' . $old['query'] : '');
            $request_uri = '/' . trim($request_uri, '/');

            $host = empty($old['host']) ? $_SERVER['HTTP_HOST'] : $old['host'];
            $host = preg_replace('/www\./', '', $host);

            if ( !isset($redirects[ $host ]) )
            {
                $redirects[ $host ] = array();
            }

            $redirects[ $host ][ $request_uri ] = array(
                'location' => $new
            );
        }

        ksort($redirects);

        $current = parse_url($_SERVER['REQUEST_URI']);
        $current['uri'] = '/' . trim($current['path'] . ($current['query'] ? '?' . $current['query'] : ''), '/');
        $current['host'] = $_SERVER['HTTP_HOST'];

        if ( @$_GET['show_redirects'] )
        {
            echo '<b>current: </b><pre>';
            print_r($current);
            echo '</pre>';

            echo '<b>redirects: </b><pre>';
            print_r($redirects);
            echo '</pre>';

            exit;
        }

        if(st_is_marker())
        {
            return false;
        }

        if ( isset($redirects[ $current['host'] ]) )
        {
            if ( isset($redirects[ $current['host'] ][ $current['uri'] ]) )
            {
                $url = $redirects[ $current['host'] ][ $current['uri'] ]['location'];
            }
            elseif ( isset($redirects[ $current['host'] ][':default']) )
            {
                $url = $redirects[ $current['host'] ][':default']['location'];
            }

            if(!empty($url))
            {
                st_redirect($url);
            }
        }
    }
}