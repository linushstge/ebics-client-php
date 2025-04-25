<?php

namespace EbicsApi\Ebics\Tests;

use EbicsApi\Ebics\Builders\CustomerCreditTransfer\CustomerCreditTransferBuilder;
use EbicsApi\Ebics\Builders\CustomerDirectDebit\CustomerDirectDebitBuilder;
use EbicsApi\Ebics\Contracts\EbicsClientInterface;
use EbicsApi\Ebics\EbicsClient;
use EbicsApi\Ebics\Factories\KeyringFactory;
use EbicsApi\Ebics\Factories\SignatureFactory;
use EbicsApi\Ebics\Models\Bank;
use EbicsApi\Ebics\Models\CustomerCreditTransfer;
use EbicsApi\Ebics\Models\CustomerDirectDebit;
use EbicsApi\Ebics\Models\Keyring;
use EbicsApi\Ebics\Models\StructuredPostalAddress;
use EbicsApi\Ebics\Models\UnstructuredPostalAddress;
use EbicsApi\Ebics\Models\User;
use EbicsApi\Ebics\Models\X509\BankX509Generator;
use EbicsApi\Ebics\Services\DebuggerHttpClient;
use EbicsApi\Ebics\Services\FakerHttpClient;
use EbicsApi\Ebics\Services\FileKeyringManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Class TestCase extends basic TestCase for add extra setups.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class AbstractEbicsTestCase extends TestCase
{
    protected $data = __DIR__ . '/_data';

    protected $fixtures = __DIR__ . '/_fixtures';

    protected function setupClientV24(
        int $credentialsId,
        bool $fake = false,
        bool $debug = false
    ): EbicsClientInterface {
        return $this->setupClient(Keyring::VERSION_24, $credentialsId, $fake, $debug);
    }

    protected function setupClientV25(
        int $credentialsId,
        bool $fake = false,
        bool $debug = false
    ): EbicsClientInterface {
        return $this->setupClient(Keyring::VERSION_25, $credentialsId, $fake, $debug);
    }

    protected function setupClientV30(
        int $credentialsId,
        bool $fake = false,
        bool $debug = false
    ): EbicsClientInterface {
        return $this->setupClient(Keyring::VERSION_30, $credentialsId, $fake, $debug);
    }

    private function setupClient(
        string $version,
        int $credentialsId,
        bool $fake = false,
        bool $debug = false
    ): EbicsClientInterface {
        $credentials = $this->credentialsDataProvider($credentialsId);

        $bank = new Bank($credentials['hostId'], $credentials['hostURL']);
        $bank->setServerName(sprintf('Server %d', $credentialsId));
        $bank->setCountryCode($credentials['countryCode']);
        $user = new User($credentials['partnerId'], $credentials['userId']);

        $keyringManager = new FileKeyringManager(new KeyringFactory);

        $keyringPath = sprintf('%s/workspace/keyring_%d.json', $this->data, $credentialsId);
        if (is_file($keyringPath)) {
            $keyring = $keyringManager->loadKeyring($keyringPath, $credentials['password'], $version);
        } else {
            $keyring = $keyringManager->createKeyring($version);
            $keyring->setPassword($credentials['password']);
        }

        $options = [];
        if (true === $fake) {
            $options['http_client'] = new FakerHttpClient($this->fixtures);
        }
        if (true === $debug) {
            $options['http_client'] = new DebuggerHttpClient();
        }

        $ebicsClient = new EbicsClient($bank, $user, $keyring, $options);

        if ($credentials['hostIsCertified']) {
            $x509Generator = new BankX509Generator();
            $x509Generator->setCertificateOptionsByBank($bank);
            $keyring->setCertificateGenerator($x509Generator);
        }

        if (!is_file($keyringPath)) {
            $ebicsClient->createUserSignatures($credentials['aVersion']);
            $this->saveKeyring($credentialsId, $ebicsClient->getKeyring());
        }

        return $ebicsClient;
    }

    protected function loadKeyring(string $keyringPath, string $password, string $version): Keyring
    {
        $keyringManager = new FileKeyringManager(new KeyringFactory);

        return $keyringManager->loadKeyring($keyringPath, $password, $version);
    }

    protected function saveKeyring(string $credentialsId, Keyring $keyring): void
    {
        $keyringRealPath = sprintf('%s/workspace/keyring_%d.json', $this->data, $credentialsId);
        $keyringManager = new FileKeyringManager(new KeyringFactory);
        $keyringManager->saveKeyring($keyring, $keyringRealPath);
    }

    protected function setupKeys(Keyring $keyring)
    {
        $keys = json_decode(file_get_contents($this->fixtures . '/keys.json'));
        $keyring->setPassword('mysecret');
        $signatureFactory = new SignatureFactory();

        $userSignatureA = $signatureFactory->createSignatureA(
            $keyring->getUserSignatureA()->getPublicKey(),
            $keys->A006
        );
        $userSignatureA->setCertificateContent($keyring->getUserSignatureA()->getCertificateContent());
        $keyring->setUserSignatureA($userSignatureA);

        $userSignatureE = $signatureFactory->createSignatureE(
            $keyring->getUserSignatureE()->getPublicKey(),
            $keys->E002
        );
        $userSignatureE->setCertificateContent($keyring->getUserSignatureE()->getCertificateContent());
        $keyring->setUserSignatureE($userSignatureE);

        $userSignatureX = $signatureFactory->createSignatureX(
            $keyring->getUserSignatureX()->getPublicKey(),
            $keys->X002
        );
        $userSignatureX->setCertificateContent($keyring->getUserSignatureX()->getCertificateContent());
        $keyring->setUserSignatureX($userSignatureX);
    }

    /**
     * Validate response data is Ok.
     *
     * @param string $code
     * @param string $reportText
     *
     * @return void
     */
    protected function assertResponseOk(string $code, string $reportText)
    {
        self::assertEquals('000000', $code, $reportText);
    }

    /**
     * Validate response data is Done.
     *
     * @param string $code
     * @param string $reportText
     *
     * @return void
     */
    protected function assertResponseDone(string $code, string $reportText)
    {
        self::assertEquals('011000', $code, $reportText);
    }

    protected function assertExceptionCode(?string $code = null)
    {
        if (null !== $code) {
            $code = (int)$code;
            $this->expectExceptionCode($code);
        }
    }

    /**
     * Client credentials data provider.
     *
     * @param int $credentialsId
     *
     * @return array
     */
    public function credentialsDataProvider(int $credentialsId): array
    {
        $path = sprintf('%s/credentials/credentials_%d.json', $this->data, $credentialsId);

        if (!file_exists($path)) {
            throw new RuntimeException('Credentials missing');
        }

        $credentialsEnc = json_decode(file_get_contents($path), true);

        return [
            'hostId' => $credentialsEnc['hostId'],
            'hostURL' => $credentialsEnc['hostURL'],
            'countryCode' => $credentialsEnc['countryCode'],
            'hostIsCertified' => (bool)$credentialsEnc['hostIsCertified'],
            'partnerId' => $credentialsEnc['partnerId'],
            'userId' => $credentialsEnc['userId'],
            'aVersion' => $credentialsEnc['aVersion'],
            'password' => $credentialsEnc['password'],
        ];
    }

    /**
     * Create simple instance of CustomerCreditTransfer.
     *
     * @param string $schema
     *
     * @return CustomerCreditTransfer
     * @throws \DOMException
     */
    protected function buildCustomerCreditTransfer(string $schema): CustomerCreditTransfer
    {
        $builder = new CustomerCreditTransferBuilder();
        $customerCreditTransfer = $builder
            ->createInstance(
                $schema,
                'ZKBKCHZZ80A',
                'SE7500800000000000001123',
                'Debitor Name'
            )
            ->addBankTransaction(
                'MARKDEF1820',
                'DE09820000000083001503',
                new StructuredPostalAddress('CH', 'Triesen', '9495'),
                100.10,
                'EUR',
                'Test payment  1'
            )
            ->addSEPATransaction(
                'GIBASKBX',
                'SK4209000000000331819272',
                'Creditor Name 4',
                null, // new UnstructuredPostalAddress(),
                200.02,
                'EUR',
                'Test payment  2'
            )
            ->addForeignTransaction(
                'NWBKGB2L',
                'GB29 NWBK 6016 1331 9268 19',
                'United Development Ltd',
                new UnstructuredPostalAddress('GB', 'George Street', 'BA1 2FJ Bath'),
                65.10,
                'CHF',
                'Test payment 3'
            )
            ->popInstance();

        return $customerCreditTransfer;
    }

    /**
     * Create simple instance of CustomerDirectDebit.
     *
     * @param string $schema
     *
     * @return CustomerDirectDebit
     * @throws \DOMException
     */
    protected function buildCustomerDirectDebit(string $schema): CustomerDirectDebit
    {
        $builder = new CustomerDirectDebitBuilder();
        $customerDirectDebit = $builder
            ->createInstance(
                $schema,
                'ZKBKCHZZ80A',
                'SE7500800000000000001123',
                'Creditor Name'
            )
            ->addTransaction(
                'MARKDEF1820',
                'DE09820000000083001503',
                'Debitor Name 1',
                100.10,
                'EUR',
                'Test payment  1'
            )
            ->addTransaction(
                'GIBASKBX',
                'SK4209000000000331819272',
                'Debitor Name 2',
                200.02,
                'EUR',
                'Test payment  2'
            )
            ->popInstance();

        return $customerDirectDebit;
    }
}
