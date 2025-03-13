<?php

namespace EbicsApi\Ebics\Builders\Request;

use Closure;
use DOMDocument;
use DOMElement;
use EbicsApi\Ebics\Contracts\SignatureDataInterface;
use EbicsApi\Ebics\Services\CryptService;
use EbicsApi\Ebics\Services\ZipService;

/**
 * Class DataTransferBuilder builder for request container.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class DataTransferBuilder
{
    protected DOMElement $instance;
    protected ?DOMDocument $dom;
    protected ZipService $zipService;
    protected CryptService $cryptService;

    public function __construct(ZipService $zipService, CryptService $cryptService, ?DOMDocument $dom = null)
    {
        $this->dom = $dom;
        $this->zipService = $zipService;
        $this->cryptService = $cryptService;
    }

    public function createInstance(): DataTransferBuilder
    {
        $this->instance = $this->dom->createElement('DataTransfer');

        return $this;
    }

    public function addOrderData(?string $orderData = null, ?string $transactionKey = null): DataTransferBuilder
    {
        $xmlDataTransfer = $this->dom->createElement('OrderData');
        $this->instance->appendChild($xmlDataTransfer);

        if (null !== $orderData) {
            $orderDataCompressed = $this->zipService->compress($orderData);

            if (null !== $transactionKey) {
                $orderDataCompressedEncrypted = $this->cryptService->encryptByKey(
                    $transactionKey,
                    $orderDataCompressed
                );
                $orderDataNodeValue = base64_encode($orderDataCompressedEncrypted);
            } else {
                $orderDataNodeValue = base64_encode($orderDataCompressed);
            }

            $xmlDataTransfer->nodeValue = $orderDataNodeValue;
        }

        return $this;
    }

    public function addDataEncryptionInfo(?Closure $callable = null): DataTransferBuilder
    {
        $dataEncryptionInfoBuilder = new DataEncryptionInfoBuilder($this->cryptService, $this->dom);
        $this->instance->appendChild($dataEncryptionInfoBuilder->createInstance()->getInstance());

        call_user_func($callable, $dataEncryptionInfoBuilder);

        return $this;
    }

    public function addSignatureData(SignatureDataInterface $userSignature, string $transactionKey): DataTransferBuilder
    {
        $userSignatureCompressed = $this->zipService->compress($userSignature->getContent());
        $userSignatureCompressedEncrypted = $this->cryptService->encryptByKey(
            $transactionKey,
            $userSignatureCompressed
        );
        $signatureDataNodeValue = base64_encode($userSignatureCompressedEncrypted);

        $xmlSignatureData = $this->dom->createElement('SignatureData');
        $xmlSignatureData->setAttribute('authenticate', 'true');
        $xmlSignatureData->nodeValue = $signatureDataNodeValue;
        $this->instance->appendChild($xmlSignatureData);

        return $this;
    }

    abstract public function addDataDigest(string $signatureVersion, ?string $digest = null): DataTransferBuilder;

    abstract public function addAdditionalOrderInfo(): DataTransferBuilder;

    public function getInstance(): DOMElement
    {
        return $this->instance;
    }
}
