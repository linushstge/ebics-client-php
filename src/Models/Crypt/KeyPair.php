<?php

namespace EbicsApi\Ebics\Models\Crypt;

/**
 * Key pair.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class KeyPair
{
    private string $publicKey;
    /**
     * Private key null to represent bank signature.
     */
    private ?string $privateKey;

    public function __construct(string $publicKey, ?string $privateKey)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }
}
