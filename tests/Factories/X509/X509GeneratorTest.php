<?php

namespace AndrewSvirin\Ebics\Tests\Factories\X509;

use AndrewSvirin\Ebics\Factories\CertificateFactory;
use AndrewSvirin\Ebics\Tests\AbstractEbicsTestCase;
use DateTime;

/**
 * Legacy X509 certificate generator @see X509GeneratorInterface.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Guillaume Sainthillier, Andrew Svirin
 *
 * @group x509-generator
 */
class X509GeneratorTest extends AbstractEbicsTestCase
{
    public function testGenerateLegacyCertificateContent()
    {
        $privateKey = $this->getPrivateKey();
        $publicKey = $this->getPublicKey();

        //Certificate generated the 22/03/2020 (1 year validity)
        $generator = new WeBankX509Generator();
        $generator->setCertificateStartDate(new DateTime('2020-03-21'));
        $generator->setCertificateEndDate(new DateTime('2021-03-22'));
        $generator->setSerialNumber('539453510852155194065233908413342789156542395956670254476154968597583055940');

        $certificateFactory = new CertificateFactory();
        $certificate = $certificateFactory->generateCertificateAFromKeys([
            'publickey' => $publicKey,
            'privatekey' => $privateKey,
        ], $generator);

        $this->assertEquals($certificate->getPrivateKey(), $privateKey);
        $this->assertEquals($certificate->getPublicKey(), $publicKey);
        $this->assertCertificateEquals($certificate->getContent(), $this->getCertificateContent('legacy-signed.csr'));
    }

    public function testGenerateSilarhiCertificateContent()
    {
        $privateKey = $this->getPrivateKey();
        $publicKey = $this->getPublicKey();

        //Certificate generated with https://certificatetools.com/ the 22/03/2020 (1 year validity)
        $generator = new SilarhiX509Generator();
        $generator->setCertificateStartDate(new DateTime('2020-03-22'));
        $generator->setCertificateEndDate(new DateTime('2021-03-22'));
        $generator->setSerialNumber('37376365613564393736653364353135633333333932376336366134393663336133663135323432');

        $certificateFactory = new CertificateFactory();
        $certificate = $certificateFactory->generateCertificateAFromKeys([
            'publickey' => $publicKey,
            'privatekey' => $privateKey,
        ], $generator);

        $this->assertEquals($certificate->getPrivateKey(), $privateKey);
        $this->assertEquals($certificate->getPublicKey(), $publicKey);
        $this->assertCertificateEquals(
            $certificate->getContent(),
            $this->getCertificateContent('silarhi-self-signed.csr')
        );
    }

    /**
     * @param string $generatedContent
     * @param string $fileContent
     */
    private function assertCertificateEquals(string $generatedContent, string $fileContent)
    {
        $generatedInfos = openssl_x509_parse($generatedContent);
        $certificateInfos = openssl_x509_parse($fileContent);

        $this->assertEquals($generatedInfos['subject'], $certificateInfos['subject']);
        $this->assertEquals($generatedInfos['issuer'], $certificateInfos['issuer']);
        $this->assertEquals(
            DateTime::createFromFormat(
                'U',
                $generatedInfos['validFrom_time_t']
            )->format('d/m/Y'),
            DateTime::createFromFormat('U', $certificateInfos['validFrom_time_t'])->format('d/m/Y')
        );
        $this->assertEquals(
            DateTime::createFromFormat(
                'U',
                $generatedInfos['validTo_time_t']
            )->format('d/m/Y'),
            DateTime::createFromFormat(
                'U',
                $certificateInfos['validTo_time_t']
            )->format('d/m/Y'));
        $this->assertEquals($generatedInfos['extensions'], $certificateInfos['extensions']);
    }

    /**
     * @param string $name
     *
     * @return false|string
     */
    private function getCertificateContent(string $name)
    {
        return file_get_contents($this->data . '/certificates/' . $name);
    }

    /**
     * @return string
     */
    private function getPrivateKey()
    {
        return file_get_contents($this->data . '/private_key.rsa');
    }

    /**
     * @return string
     */
    private function getPublicKey()
    {
        return file_get_contents($this->data . '/public_key.rsa');
    }
}
