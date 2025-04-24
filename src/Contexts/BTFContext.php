<?php

namespace EbicsApi\Ebics\Contexts;

/**
 * Business transactions & formats.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Geoffroy de Corbiac
 */
abstract class BTFContext extends ServiceContext
{
    private ?string $containerFlag = null;
    private ?string $msgNameVariant = null;
    private ?string $msgNameVersion = null;
    private ?string $msgNameFormat = null;
    private bool $signatureFlag = false;
    private bool $signatureFlagEds = false;

    public function setContainerFlag(string $containerFlag): self
    {
        $this->containerFlag = $containerFlag;

        return $this;
    }

    public function getContainerFlag(): ?string
    {
        return $this->containerFlag;
    }

    public function setMsgNameVariant(string $msgNameVariant): self
    {
        $this->msgNameVariant = $msgNameVariant;

        return $this;
    }

    public function getMsgNameVariant(): ?string
    {
        return $this->msgNameVariant;
    }

    public function setMsgNameVersion(?string $msgNameVersion): self
    {
        $this->msgNameVersion = $msgNameVersion;

        return $this;
    }

    public function getMsgNameVersion(): ?string
    {
        return $this->msgNameVersion;
    }

    public function setMsgNameFormat(string $msgNameFormat): self
    {
        $this->msgNameFormat = $msgNameFormat;

        return $this;
    }

    public function getMsgNameFormat(): ?string
    {
        return $this->msgNameFormat;
    }

    public function setSignatureFlag(bool $signatureFlag): self
    {
        $this->signatureFlag = $signatureFlag;

        return $this;
    }

    public function getSignatureFlag(): bool
    {
        return $this->signatureFlag;
    }

    public function setSignatureFlagEds(bool $signatureFlagEds): self
    {
        $this->signatureFlagEds = $signatureFlagEds;

        return $this;
    }

    public function getSignatureFlagEds(): bool
    {
        return $this->signatureFlagEds;
    }
}
