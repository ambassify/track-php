<?php

namespace Ambassify\Track;

use Ambassify\Track\Arr;
use Ambassify\Track\Exception\ConfigException;
use Ambassify\Track\Exception\InvalidArgumentException;
use Ambassify\Track\Exception\RequestException;
use Ambassify\Track\Exception\ResponseException;
use Ambassify\Track\Exception\UnauthorizedException;

class Client {

    const PARAM_URL = 'u';

    const LINK_RE = '#^(https?):\/\/([^/]+)\/r\/([0-9a-z]+)(?:\/([0-9a-z-_=]+))?#i';
    const CODE_RE = '#^[0-9a-z]+$#i';

    protected $strict;
    protected $endpoint;
    protected $baseurl;
    protected $apikey;

    public static function factory($options = [])
    {
        return new static($options);
    }

    public function __construct(array $options = [])
    {
        $this->strict = !empty($options['strict']);
        $this->endpoint = Arr::get($options, 'endpoint', null);
        $this->baseurl = Arr::get($options, 'baseurl', $this->endpoint);
        $this->apikey = Arr::get($options, 'apikey', null);

        if ($this->endpoint)
            $this->endpoint = rtrim($this->endpoint, '/');

        if ($this->baseurl)
            $this->baseurl = rtrim($this->baseurl, '/');

        if ($this->strict && !$this->baseurl)
            throw new ConfigException('baseurl or endpoint required in strict mode');
    }

    public function shorten(array $params)
    {
        if (!$this->baseurl)
            throw new ConfigException('baseurl or endpoint required');

        $url = Arr::get($params, self::PARAM_URL);

        if (!$url)
            throw new InvalidArgumentException('url required');

        $req = curl_init();
        curl_setopt($req, CURLOPT_URL, $this->endpoint . '/api/r');
        curl_setopt($req, CURLOPT_POST, 1);
        curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($req, CURLOPT_FAILONERROR, false);
        curl_setopt($req, CURLOPT_RETURNTRANSFER, true);

        if ($this->apikey) {
            curl_setopt($req, CURLOPT_HTTPHEADER, [
                "X-API-KEY: $this->apikey"
            ]);
        }

        $res = curl_exec($req);

        if ($res === false) {
            $err = curl_error($req);
            $err = $err ?: 'Unknown';
            throw new RequestException($err);
        }

        $code = intval(curl_getinfo($req, CURLINFO_HTTP_CODE));
        $data = json_decode($res, true);

        if ($code == 401 || $code == 403)
            throw new UnauthorizedException();

        $shortlink = Arr::get($data, 'shortlink');

        if (!$shortlink)
            throw new ResponseException($res);

        return $shortlink;
    }

    public function override($short, array $params)
    {
        $new_url = '';
        $base_params = [];
        $parsed = $this->parse_shortcode($short);

        if ($parsed && !$this->baseurl) {
            throw new ConfigException('baseurl or endpoint required');
        } else if ($parsed) {
            $new_url = $this->baseurl . '/r/' . $parsed['shortcode'];
        } else {
            $parsed = $this->strict ?
                $this->parse_shortlink($short, $this->baseurl) :
                $this->parse_shortlink($short);

            if ($parsed) {
                $base = $this->baseurl ?
                    $this->baseurl :
                    $parsed['protocol'] . '://' . $parsed['domain'];

                $shortcode = $parsed['shortcode'];
                $new_url = "$base/r/$shortcode";
                $base_params = $parsed['override'];
            } else {
                throw new InvalidArgumentException("Not a valid shortlink or shortcode: $short");
            }
        }

        $params += $base_params;

        if (count($params)) {
            $params = $this->encode_parameters($params);
            $new_url .= "/$params";
        }

        return $new_url;
    }

    public function encode_parameters(array $params)
    {
        return trim(strtr(base64_encode(json_encode($params)), '+/', '-_'), '=');
    }

    public function decode_parameters($encoded)
    {
        return json_decode(base64_decode(strtr($encoded, '-_~', '+/=')), true);
    }

    protected function parse_shortcode($c)
    {
        return preg_match(self::CODE_RE, $c) ? [ 'shortcode' => $c ] : false;
    }

    protected function parse_shortlink($url, $base = null)
    {
        $r = preg_match(self::LINK_RE, $url, $m);

        if (!$r || ($base && preg_replace('/(https?:)?\/\//', '', $base) !== $m[2]))
            return false;

        return [
            'protocol' => $m[1],
            'domain' => $m[2],
            'shortcode' => $m[3],
            'override' => count($m) > 4 ? $this->decode_parameters($m[4]) : []
        ];
    }
}
