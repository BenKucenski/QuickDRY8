<?php

namespace QuickDRY\API;


use DateTimeImmutable;
use Exception;
use Firebase\JWT\JWT;
use models\APIUser;
use models\APIUserLog;
use QuickDRY\Utilities\Dates;
use QuickDRY\Utilities\HTTP;
use QuickDRY\Utilities\Strings;
use QuickDRY\Utilities\strongType;
use QuickDRY\Web\Server;
use stdClass;

/**
 *
 */
class Security extends strongType
{
    public static string $cipher = 'AES-256-CFB';

    public static string $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_';

    /**
     * @param string $token
     * @return string
     */
    public static function convertToken(string $token): string
    {
        $m = strlen(self::$chars);
        $base10 = Strings::Base16to10($token);
        $new_token = '';
        while (strlen($base10) > 1) {
            $base10_part = substr(strval($base10), -3);
            $new_token .= self::$chars[(intval($base10_part) % $m)];
            $base10 = substr($base10, 0, -1);
        }
        return $new_token;
    }

    /**
     * @return string
     */
    public static function generateToken(): string
    {
        try {
            return self::convertToken(bin2hex(random_bytes(64)));
        } catch (Exception $ex) {
            Debug($ex);
        }
        return '';
    }

    /**
     * @param $plaintext
     * @return string
     */
    public static function encrypt($plaintext): string
    {
        $ivlen = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext = openssl_encrypt($plaintext, self::$cipher, MASTER_SECRET_KEY, 0, $iv, $tag);
        //store $cipher, $iv, and $tag for decryption later

        return $ciphertext . '::' . base64_encode($iv) . '::' . base64_encode($tag);
    }

    /**
     * @param $ciphertext
     * @return false|string
     */
    public static function decrypt($ciphertext): bool|string
    {
        $parts = explode('::', $ciphertext);
        return openssl_decrypt(
            $parts[0],
            self::$cipher,
            MASTER_SECRET_KEY,
            0,
            base64_decode($parts[1] ?? ''),
            base64_decode($parts[2] ?? '')
        );
    }

    /**
     * @param array $data
     * @param int $expire_seconds
     * @return string
     */
    public static function createBearerToken(array $data, int $expire_seconds): string
    {
        $issuedAt = new DateTimeImmutable();
        try {
            $expire = $issuedAt->modify('+' . $expire_seconds . ' seconds')->getTimestamp();
        } catch (Exception $e) {
            Debug($e->getMessage());
        }
        $serverName = $_SERVER['HTTP_HOST'];

        $data = [
            'iat'  => $issuedAt->getTimestamp(),         // Issued at: time when the token was generated
            'iss'  => $serverName,                       // Issuer
            'nbf'  => $issuedAt->getTimestamp(),         // Not before
            'exp'  => $expire,                           // Expire
            'data' => $data,
        ];

        return JWT::encode(
            $data,
            MASTER_SECRET_KEY,
            'HS512'
        );
    }

    /**
     * @param string $token
     * @return stdClass|null
     */
    public static function decodeBearerToken(string $token): ?stdClass
    {
        // https://stackoverflow.com/questions/72278051/why-is-jwtdecode-returning-status-kid-empty-unable-to-lookup-corr
        try {
            return JWT::decode($token, new Key(MASTER_SECRET_KEY, 'HS512'));
        } catch (Exception $ex) {
            switch ($ex->getMessage()) {
                case 'Expired token':
                    HTTP::ExitJSON(['error' => 'token expired'], HTTP::HTTP_STATUS_UNAUTHORIZED);
                    break;

                case 'Signature verification failed':
                case 'Wrong number of segments':
                    HTTP::ExitJSON(['error' => 'invalid token'], HTTP::HTTP_STATUS_UNAUTHORIZED);
                    break;
            }
            HTTP::ExitJSON(['error' => $ex->getMessage()]);
        }
        return null;
    }

    /**
     * @param string $token
     * @return int
     */
    public static function getExpirationTimestamp(string $token): int
    {
        $data = self::decodeBearerToken($token);
        return intval($data->exp);
    }

    /**
     * @return string|null
     */
    public static function validateHeaders(): ?string
    {
        $headers = getallheaders();
        $client_id = $headers['X-Client-Id'] ?? ($_REQUEST['client_id'] ?? null);
        $client_secret = $headers['X-Client-Secret'] ?? ($_REQUEST['client_secret'] ?? null);;
        if ($client_id && $client_secret) {
            return self::getBearer($client_id, $client_secret);
        }
        $bearer = $_REQUEST['bearer'] ?? null;
        if ($bearer) {
            return $bearer;
        }
        return null;
    }

    /**
     * @param callable|null $checkCount
     * @return array|null
     */
    public static function validateRequest(callable $checkCount = null): ?array
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_REQUEST['HTTP_AUTHORIZATION'] ?? null);
        if (!$token) {
            $token = self::validateHeaders();
            if (!$token) {
                return null;
            }
        }

        $token = explode(' ', $token);
        $token = trim($token[sizeof($token) - 1] ?? null);

        if (!$token) {
            return null;
        }

        $jwt = self::decodeBearerToken($token);

        $data = json_decode(json_encode($jwt->data ?? null), true);

        $expires = intval($jwt->exp);
        if ($expires < time()) {
            HTTP::ExitJSON(['error' => 'token expired'], HTTP::HTTP_STATUS_UNAUTHORIZED);
        }

        $ip = Server::REMOTE_ADDR();
        if (in_array($ip, [
            '127.0.0.1', // localhost
            'localhost',
        ])) {
            return $data;
        }

        if ($checkCount) {
            $cnt = $checkCount($ip);
            if ($cnt > 1200) {
                HTTP::ExitJSON(['error' => 'slow down', 'queries' => $cnt], HTTP::HTTP_STATUS_CALM_DOWN);
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
        int    $expire = 3600
    ): string
    {
        $check = APIUser::Get($client_id);

        if (!$check) {
            HTTP::ExitJSON([
                'error' => 'unauthorized',
                'code'  => 1
            ], HTTP::HTTP_STATUS_UNAUTHORIZED);
        }

        $log = new APIUserLog();
        $log->client_id = $check->client_id;
        $log->created_at = Dates::Timestamp();
        $log->remote_addr = Server::REMOTE_ADDR();

        if (!$check->validate($client_secret)) {
            $log->is_success = 0;
            $log->Save();
            HTTP::ExitJSON([
                'error' => 'unauthorized',
                'code'  => 2
            ], HTTP::HTTP_STATUS_UNAUTHORIZED);
        }

        $log->is_success = 1;
        $log->Save();

        $expire = $expire ?? 3600;
        if ($expire > 3600) {
            $expire = 3600;
        }

        return Security::createBearerToken([
            'email'     => $check->email_address,
            'client_id' => $check->client_id,
        ], $expire);
    }

    public static function encryptURL(?string $data): ?string
    {
        if (!$data) {
            return $data;  //to preserve empty string, null, or zero
        }

        if (!defined('URL_SECRET_KEY')) {
            Debug('URL_SECRET_KEY is not in the env');
        }

        // https://stackoverflow.com/questions/48017856/correct-way-to-use-php-openssl-encrypt
        $passphrase = hash('sha256', URL_SECRET_KEY);
        $iv = substr(hash('sha256', $passphrase), 0, 16);

        // base64 encode to make it URL safe
        // add a leading "_" to indicate OpenSSL
        return base64_encode(openssl_encrypt(
            $data,
            self::$cipher,
            $passphrase,
            0,
            $iv
        ));
    }

    /**
     * @param string $data
     * @return string
     * @throws Exception
     */
    public static function decryptURL(string $data): string
    {
        if (!$data) {
            return $data;  //to preserve empty string, null, or zero
        }

        if (!defined('URL_SECRET_KEY')) {
            Debug('URL_SECRET_KEY is not in the env');
        }

        $data = base64_decode($data);

        $passphrase = hash('sha256', URL_SECRET_KEY);
        $iv = substr(hash('sha256', $passphrase), 0, 16);

        $check = openssl_decrypt(
            $data,
            self::$cipher,
            $passphrase,
            0,
            $iv
        );

        if (!$check) {
            throw new Exception('Invalid Key');
        }

        return $check;
    }
}
