<?php

namespace QuickDRY\Connectors\oauth;


/**
 * The RSA-SHA1 signature method uses the RSASSA-PKCS1-v1_5 signature algorithm as defined in
 * [RFC3447] section 8.2 (more simply known as PKCS#1), using SHA-1 as the hash function for
 * EMSA-PKCS1-v1_5. It is assumed that the Consumer has provided its RSA public key in a
 * verified way to the Service Provider, in a manner which is beyond the scope of this
 * specification.
 *   - Chapter 9.3 ("RSA-SHA1")
 */
abstract class OAuthSignatureMethod_RSA_SHA1 extends OAuthSignatureMethod
{
    /**
     * @return string
     */
    public function get_name(): string
    {
        return 'RSA-SHA1';
    }

    // Up to the SP to implement this lookup of keys. Possible ideas are:
    // (1) do a lookup in a table of trusted certs keyed off of consumer
    // (2) fetch via http using a url provided by the requester
    // (3) some sort of specific discovery code based on request
    //
    // Either way should return a string representation of the certificate
    /**
     * @param $request
     * @return mixed
     */
    abstract protected function fetch_public_cert(&$request): mixed;

    // Up to the SP to implement this lookup of keys. Possible ideas are:
    // (1) do a lookup in a table of trusted certs keyed off of consumer
    //
    // Either way should return a string representation of the certificate
    /**
     * @param $request
     * @return mixed
     */
    abstract protected function fetch_private_cert(&$request): mixed;

    /**
     * @param OAuthRequest $request
     * @param OAuthConsumer $consumer
     * @param OAuthToken $token
     * @return string
     */
    public function build_signature(
        OAuthRequest  $request,
        OAuthConsumer $consumer,
        OAuthToken    $token): string
    {
        $base_string = $request->get_signature_base_string();
        $request->base_string = $base_string;

        // Fetch the private key cert based on the request
        $cert = $this->fetch_private_cert($request);

        // Pull the private key ID from the certificate
        $privatekeyid = openssl_get_privatekey($cert);

        // Sign using the key
        openssl_sign($base_string, $signature, $privatekeyid);

        return base64_encode($signature);
    }

    /**
     * @param OAuthRequest $request
     * @param OAuthConsumer $consumer
     * @param OAuthToken $token
     * @param string $signature
     * @return bool
     */
    public function check_signature(OAuthRequest $request, OAuthConsumer $consumer, OAuthToken $token, string $signature): bool
    {
        $decoded_sig = base64_decode($signature);

        $base_string = $request->get_signature_base_string();

        // Fetch the public key cert based on the request
        $cert = $this->fetch_public_cert($request);

        // Pull the public key ID from the certificate
        $publickeyid = openssl_get_publickey($cert);

        // Check the computed signature against the one passed in the query
        $ok = openssl_verify($base_string, $decoded_sig, $publickeyid);

        return $ok == 1;
    }
}