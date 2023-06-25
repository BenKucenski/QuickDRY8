<?php

namespace QuickDRY\API;


use DateTimeImmutable;
use Exception;
use Firebase\JWT\JWT;
use QuickDRY\Utilities\HTTP;
use QuickDRY\Utilities\Log;
use QuickDRY\Utilities\Strings;
use QuickDRY\Utilities\strongType;
use stdClass;
use const QuickDRY\Utilities\HTTP_STATUS_CALM_DOWN;
use const QuickDRY\Utilities\HTTP_STATUS_UNAUTHORIZED;

class Security extends strongType
{
    public static string $cipher = 'AES-256-CFB';

    public static string $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';
    public static function convertToken(string $token): string
    {
        $m = strlen(self::$chars);
        $base10 = Strings::Base16to10($token);
        $new_token = '';
        while(strlen($base10) > 1) {
            $base10_part = substr(strval($base10), -3);
            $new_token .= self::$chars[(intval($base10_part) % $m)];
            $base10 = substr($base10, 0, -1);
        }
        return $new_token;
    }

    public static function generateToken(): string
    {
        try {
            return self::convertToken(bin2hex(random_bytes(64)));
        } catch (Exception $ex) {
            Debug($ex);
        }
        return '';
    }

    public static function encrypt($plaintext): string
    {
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($plaintext, self::$cipher, MASTER_SECRET_KEY, 0, $iv, $tag);
        //store $cipher, $iv, and $tag for decryption later

        return $ciphertext . '::' . base64_encode($iv) . '::' . base64_encode($tag);
    }

    public static function decryptBase64(string $data)
    {
        $parts = explode('::', $data);
        return self::decrypt(
            $parts[0],
            base64_decode($parts[1] ?? ''),
            base64_decode($parts[2] ?? '')
        );
    }

    public static function decrypt($ciphertext, $iv, $tag)
    {
        return openssl_decrypt($ciphertext, self::$cipher, MASTER_SECRET_KEY, 0, $iv, $tag);
    }

    public static function createBearerToken(array $data, int $expire_seconds): string
    {
        $issuedAt = new DateTimeImmutable();
        $expire = $issuedAt->modify('+' . $expire_seconds .  ' seconds')->getTimestamp();
        $serverName = $_SERVER['HTTP_HOST'];

        $data = [
            'iat' => $issuedAt->getTimestamp(),         // Issued at: time when the token was generated
            'iss' => $serverName,                       // Issuer
            'nbf' => $issuedAt->getTimestamp(),         // Not before
            'exp' => $expire,                           // Expire
            'data' => $data,
        ];

        return JWT::encode(
            $data,
            MASTER_SECRET_KEY,
            'HS512'
        );
    }

    public static function decodeBearerToken(string $token): ?stdClass
    {
        // https://stackoverflow.com/questions/72278051/why-is-jwtdecode-returning-status-kid-empty-unable-to-lookup-corr
        try {
            return JWT::decode($token, new Key(MASTER_SECRET_KEY, 'HS512'));
        } catch (Exception $ex) {
            switch($ex->getMessage()) {
                case 'Expired token':
                    HTTP::ExitJSON(['error' => 'token expired'], HTTP_STATUS_UNAUTHORIZED);
                    break;
                case 'Signature verification failed':
                case 'Wrong number of segments':
                    HTTP::ExitJSON(['error' => 'invalid token'], HTTP_STATUS_UNAUTHORIZED);
                    break;
            }
            HTTP::ExitJSON(['error' => $ex->getMessage()]);
        }
        return null;
    }

    public static function getExpirationTimestamp(string $token): int
    {
        $data = self::decodeBearerToken($token);
        return intval($data->exp);
    }

    public static function validateHeaders(): ?string
    {
        Log::Insert('validateHeaders');
        $headers = getallheaders();
        $client_id = $headers['X-Client-Id'] ?? null;
        $client_secret = $headers['X-Client-Secret'] ?? null;
        Log::Insert('Client-Id: ' . $client_id);
        if($client_id && $client_secret) {
            return self::getBearer($client_id, $client_secret);
        }
        $bearer = $_REQUEST['bearer'] ?? null;
        if($bearer) {
            return $bearer;
        }
        return null;
    }

    public static function validateRequest(callable $checkCount = null): ?array
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        Log::Insert('Token: ' . $token);
        if (!$token) {
            $token = self::validateHeaders();
            Log::Insert('Token(2): ' . $token);
            if(!$token) {
                return null;
            }
        }

        $token = explode(' ', $token);
        $token = trim($token[sizeof($token) - 1] ?? null);

        Log::Insert('Split Token: ' . $token);
        if (!$token) {
            return null;
        }

        $jwt = self::decodeBearerToken($token);

        Log::Insert($jwt);

        $data = json_decode(json_encode($jwt->data ?? null), true);

        $expires = intval($jwt->exp);
        if ($expires < time()) {
            HTTP::ExitJSON(['error' => 'token expired'], HTTP_STATUS_UNAUTHORIZED);
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        if(in_array($ip, [
            '127.0.0.1', // localhost
            'localhost',
        ])) {
            return $data;
        }

        if($checkCount) {
            $cnt = $checkCount($ip);
            if($cnt > 1200) {
                HTTP::ExitJSON(['error' => 'slow down', 'queries' => $cnt], HTTP_STATUS_CALM_DOWN);
            }
        }

        return $data;
    }

    /**
     * @param string $client_id
     * @param string $client_secret
     * @param int $expire
     * @return string
     */
    public static function getBearer(
        string $client_id,
        string $client_secret,
        int $expire = 3600
    ): string
    {
//        $check = api_user::Get([
//            'client_id' => $client_id,
//        ]);
//
//        if (!$check) {
//            HTTP::ExitJSON(['error' => 'unauthorized'], HTTP_STATUS_UNAUTHORIZED);
//        }
//
//        $log = new api_user_log();
//        $log->api_user_id = $check->client_id;
//        $log->created_at = Dates::Timestamp();
//        $log->remote_addr = $_SERVER['REMOTE_ADDR'];
//
//        if (!$check->validate($client_secret)) {
//            $log->is_success = 0;
//            $log->Save();
//            HTTP::ExitJSON(['error' => 'unauthorized'], HTTP_STATUS_UNAUTHORIZED);
//        }
//
//        $log->is_success = 1;
//        $log->Save();
//
//        $expire = $expire ?? 3600;
//        if($expire > 3600) {
//            $expire = 3600;
//        }
//
//        return Security::createBearerToken([
//            'email' => $check->email_address,
//            'client_id' => $check->client_id,
//        ], $expire);

        return '';
    }
}
