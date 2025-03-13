<?php

namespace EbicsApi\Ebics\Factories;

use EbicsApi\Ebics\Contracts\Crypt\BigIntegerInterface;
use EbicsApi\Ebics\Contracts\SignatureInterface;
use EbicsApi\Ebics\Contracts\X509GeneratorInterface;
use EbicsApi\Ebics\Factories\Crypt\RSAFactory;
use EbicsApi\Ebics\Models\Crypt\KeyPair;
use EbicsApi\Ebics\Models\Crypt\RSA;
use EbicsApi\Ebics\Models\Signature;
use LogicException;
use RuntimeException;

/**
 * Class SignatureFactory represents producers for the @see Signature.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin, Guillaume Sainthillier
 */
final class SignatureFactory
{
    private RSAFactory $rsaFactory;

    public function __construct()
    {
        $this->rsaFactory = new RSAFactory();
    }

    /**
     * @param string $type
     * @param string $publicKey
     * @param string|null $privateKey
     *
     * @return SignatureInterface
     */
    public function create(string $type, string $publicKey, ?string $privateKey = null): SignatureInterface
    {
        switch ($type) {
            case SignatureInterface::TYPE_A:
                $signature = $this->createSignatureA($publicKey, $privateKey);
                break;
            case SignatureInterface::TYPE_E:
                $signature = $this->createSignatureE($publicKey, $privateKey);
                break;
            case SignatureInterface::TYPE_X:
                $signature = $this->createSignatureX($publicKey, $privateKey);
                break;
            default:
                throw new LogicException('Unpredictable case.');
        }

        return $signature;
    }

    /**
     * @param string $publicKey
     * @param string $privateKey
     *
     * @return SignatureInterface
     */
    public function createSignatureA(string $publicKey, string $privateKey): SignatureInterface
    {
        return new Signature(SignatureInterface::TYPE_A, $publicKey, $privateKey);
    }

    /**
     * @param string $publicKey
     * @param string|null $privateKey
     *
     * @return SignatureInterface
     */
    public function createSignatureE(string $publicKey, ?string $privateKey = null): SignatureInterface
    {
        return new Signature(SignatureInterface::TYPE_E, $publicKey, $privateKey);
    }

    /**
     * @param string $publicKey
     * @param string|null $privateKey
     *
     * @return SignatureInterface
     */
    public function createSignatureX(string $publicKey, ?string $privateKey = null): SignatureInterface
    {
        return new Signature(SignatureInterface::TYPE_X, $publicKey, $privateKey);
    }

    /**
     * @param KeyPair $keyPair
     * @param string $password
     * @param X509GeneratorInterface|null $x509Generator
     *
     * @return SignatureInterface
     */
    public function createSignatureAFromKeys(
        KeyPair $keyPair,
        string $password,
        ?X509GeneratorInterface $x509Generator = null
    ): SignatureInterface {
        return $this->createSignatureFromKeys($keyPair, $password, SignatureInterface::TYPE_A, $x509Generator);
    }

    /**
     * @param KeyPair $keyPair
     * @param string $password
     * @param X509GeneratorInterface|null $x509Generator
     *
     * @return SignatureInterface
     */
    public function createSignatureEFromKeys(
        KeyPair $keyPair,
        string $password,
        ?X509GeneratorInterface $x509Generator = null
    ): SignatureInterface {
        return $this->createSignatureFromKeys($keyPair, $password, SignatureInterface::TYPE_E, $x509Generator);
    }

    /**
     * @param KeyPair $keyPair
     * @param string $password
     * @param X509GeneratorInterface|null $x509Generator
     *
     * @return SignatureInterface
     */
    public function createSignatureXFromKeys(
        KeyPair $keyPair,
        string $password,
        ?X509GeneratorInterface $x509Generator = null
    ): SignatureInterface {
        return $this->createSignatureFromKeys($keyPair, $password, SignatureInterface::TYPE_X, $x509Generator);
    }

    /**
     * @param BigIntegerInterface $exponent
     * @param BigIntegerInterface $modulus
     *
     * @return SignatureInterface
     */
    public function createSignatureEFromDetails(
        BigIntegerInterface $exponent,
        BigIntegerInterface $modulus
    ): SignatureInterface {
        return $this->createCertificateFromDetails(SignatureInterface::TYPE_E, $exponent, $modulus);
    }

    /**
     * @param BigIntegerInterface $exponent
     * @param BigIntegerInterface $modulus
     *
     * @return SignatureInterface
     */
    public function createSignatureXFromDetails(
        BigIntegerInterface $exponent,
        BigIntegerInterface $modulus
    ): SignatureInterface {
        return $this->createCertificateFromDetails(SignatureInterface::TYPE_X, $exponent, $modulus);
    }

    /**
     * @param KeyPair $keyPair
     * @param string $password
     * @param string $type
     * @param X509GeneratorInterface|null $x509Generator
     *
     * @return SignatureInterface
     */
    private function createSignatureFromKeys(
        KeyPair $keyPair,
        string $password,
        string $type,
        ?X509GeneratorInterface $x509Generator = null
    ): SignatureInterface {
        $signature = new Signature($type, $keyPair->getPublicKey(), $keyPair->getPrivateKey());

        if (null !== $x509Generator) {
            $certificateContent = $this->generateCertificateContent($keyPair, $password, $type, $x509Generator);
            $signature->setCertificateContent($certificateContent);
        }

        return $signature;
    }

    /**
     * @param KeyPair $keyPair
     * @param string $password
     * @param string $type
     * @param X509GeneratorInterface $x509Generator
     *
     * @return string
     */
    private function generateCertificateContent(
        KeyPair $keyPair,
        string $password,
        string $type,
        X509GeneratorInterface $x509Generator
    ): string {
        $rsaPrivateKey = $this->rsaFactory->createPrivate($keyPair->getPrivateKey(), $password);

        $rsaPublicKey = $this->rsaFactory->createPublic($keyPair->getPublicKey());

        switch ($type) {
            case SignatureInterface::TYPE_A:
                $x509 = $x509Generator->generateAX509($rsaPrivateKey, $rsaPublicKey);
                break;
            case SignatureInterface::TYPE_E:
                $x509 = $x509Generator->generateEX509($rsaPrivateKey, $rsaPublicKey);
                break;
            case SignatureInterface::TYPE_X:
                $x509 = $x509Generator->generateXX509($rsaPrivateKey, $rsaPublicKey);
                break;
            default:
                throw new RuntimeException('Unpredictable type.');
        }

        if (!($currentCert = $x509->saveX509CurrentCert())) {
            throw new RuntimeException('Can not save current certificate.');
        }

        return $currentCert;
    }

    /**
     * @param string $type
     * @param BigIntegerInterface $exponent
     * @param BigIntegerInterface $modulus
     *
     * @return SignatureInterface
     */
    private function createCertificateFromDetails(
        string $type,
        BigIntegerInterface $exponent,
        BigIntegerInterface $modulus
    ): SignatureInterface {
        $details = [
            'modulus' => $modulus,
            'exponent' => $exponent,
        ];
        $rsa = $this->rsaFactory->createPublic($details);
        $publicKey = $rsa->getPublicKey(RSA::PUBLIC_FORMAT_PKCS1);

        return new Signature($type, $publicKey, null);
    }
}
