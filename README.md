# EBICS-CLIENT-PHP

[![CI](https://github.com/ebics-api/ebics-client-php/actions/workflows/ci.yml/badge.svg)](https://github.com/ebics-api/ebics-client-php/actions/workflows/ci.yml)
[![Latest Stable Version](https://poser.pugx.org/ebics-api/ebics-client-php/v/stable)](https://packagist.org/packages/ebics-api/ebics-client-php)
[![Total Downloads](https://img.shields.io/packagist/dt/ebics-api/ebics-client-php.svg)](https://packagist.org/packages/ebics-api/ebics-client-php)
[![License](https://poser.pugx.org/ebics-api/ebics-client-php/license)](https://packagist.org/packages/ebics-api/ebics-client-php)

<img src="https://www.ebics.org/typo3conf/ext/siz_ebicsorg_base/Resources/Public/Images/ebics-logo.png" width="300">

PHP library to communicate with a bank through <a href="https://en.wikipedia.org/wiki/Electronic_Banking_Internet_Communication_Standard" target="_blank">EBICS</a> protocol.  
PHP EBICS Client - https://ebics-api.github.io/ebics-client-php/  
Supported PHP versions - PHP 7.2 and higher  
Supported EBICS versions: 2.4, 2.5, 3.0; Encryption versions: E002, X002, A005, A006; Switching EBICS T/TS

# 💥 (Premium) EBICS API Client

<img src="./doc/ebics_api_client.png" align="left" width="210" style="padding-right:20px">

EBICS API Client - https://sites.google.com/view/ebics-api-client  
**For quick integration** EBICS Client can be deployed as a standalone service on a webserver or within a Docker container and provides:
<br clear="left"/>
- :100: Support for EBICS Integration
- :white_check_mark: RESTful API to operate with orders, connections, keyrings, access logs, fetched files
- :white_check_mark: Extended Access Policy
- :white_check_mark: Execute order transactions directly from the App
- :white_check_mark: Manage Connections and Monitor access logs
- :white_check_mark: Scheduler Jobs, Fetched files secure storage
<br clear="left"/>

## License

ebics-api/ebics-client-php is licensed under the MIT License, see the LICENSE file for details

## Installation

```bash
$ composer require ebics-api/ebics-client-php
```

## Initialize client

You will need to have this information from your Bank: `HostID`, `HostURL`, `PartnerID`, `UserID`

```php
<?php

use EbicsApi\Ebics\Services\FileKeyringManager;
use EbicsApi\Ebics\Models\Bank;
use EbicsApi\Ebics\Models\User;
use EbicsApi\Ebics\EbicsClient;
use EbicsApi\Ebics\Models\X509\BankX509Generator;

// Prepare `workspace` dir in the __PATH_TO_WORKSPACES_DIR__ manually.
// "__EBICS_VERSION__" should have value "VERSION_30" for EBICS 3.0
$keyringPath = __PATH_TO_WORKSPACES_DIR__ . '/workspace/keyring.json';
$keyringManager = new FileKeyringManager();
if (is_file($keyringPath)) {
    $keyring = $keyringManager->loadKeyring($keyringPath, __PASSWORD__, __EBICS_VERSION__);
} else {
    $keyring = $keyringManager->createKeyring(__EBICS_VERSION__);
    $keyring->setPassword(__PASSWORD__);
}
$bank = new Bank(__HOST_ID__, __HOST_URL__);
// Use __IS_CERTIFIED__ true for EBICS 3.0 and/or French banks, otherwise use false.
if(__IS_CERTIFIED__) {
    $certificateGenerator = (new BankX509Generator());
    $certificateGenerator->setCertificateOptionsByBank($bank);
    $keyring->setCertificateGenerator($certificateGenerator);
}
$user = new User(__PARTNER_ID__, __USER_ID__);
$client = new EbicsClient($bank, $user, $keyring);
if (!is_file($keyringPath)) {
    $client->createUserSignatures();
    $keyringManager->saveKeyring($client->getKeyring(), $keyringPath);
}
```

## Global process and interaction with Bank Department

### 1. Create and store your 3 keys and send initialization request.

```php
<?php

use EbicsApi\Ebics\Contracts\EbicsResponseExceptionInterface;

/* @var \EbicsApi\Ebics\EbicsClient $client */

try {
    $client->INI();
    /* @var \EbicsApi\Ebics\Services\FileKeyringManager $keyringManager */
    /* @var \EbicsApi\Ebics\Models\Keyring $keyring */
    $keyringManager->saveKeyring($keyring, $keyringRealPath);
} catch (EbicsResponseExceptionInterface $exception) {
    echo sprintf(
        "INI request failed. EBICS Error code : %s\nMessage : %s\nMeaning : %s",
        $exception->getResponseCode(),
        $exception->getMessage(),
        $exception->getMeaning()
    );
}

try {
    $client->HIA();
    $keyringManager->saveKeyring($keyring, $keyringRealPath);
} catch (EbicsResponseExceptionInterface $exception) {
    echo sprintf(
        "HIA request failed. EBICS Error code : %s\nMessage : %s\nMeaning : %s",
        $exception->getResponseCode(),
        $exception->getMessage(),
        $exception->getMeaning()
    );
}
```

### 2. Generate a EBICS letter

```php
/* @var \EbicsApi\Ebics\EbicsClient $client */
$ebicsBankLetter = new \EbicsApi\Ebics\EbicsBankLetter();

$bankLetter = $ebicsBankLetter->prepareBankLetter(
    $client->getBank(),
    $client->getUser(),
    $client->getKeyring()
);

$pdf = $ebicsBankLetter->formatBankLetter($bankLetter, $ebicsBankLetter->createPdfBankLetterFormatter());
```

### 3. Wait for the bank validation and access activation.

### 4. Fetch the bank keys.

```php

try {
    /* @var \EbicsApi\Ebics\EbicsClient $client */
    $client->HPB();
    /* @var \EbicsApi\Ebics\Services\FileKeyringManager $keyringManager */
    /* @var \EbicsApi\Ebics\Models\Keyring $keyring */
    $keyringManager->saveKeyring($keyring, $keyringRealPath);
} catch (EbicsResponseExceptionInterface $exception) {
    echo sprintf(
        "HPB request failed. EBICS Error code : %s\nMessage : %s\nMeaning : %s",
        $exception->getResponseCode(),
        $exception->getMessage(),
        $exception->getMeaning()
    );
}
```

### 5. Play with other transactions!

| Transaction | Description                                                                                                       |
|-------------|-------------------------------------------------------------------------------------------------------------------|
| HEV         | Download supported protocol versions for the Bank.                                                                |
| INI         | Send to the bank public signature of signature A005.                                                              |
| HIA         | Send to the bank public signatures of authentication (X002) and encryption (E002).                                |
| H3K         | Send to the bank public signatures of signature (A005), authentication (X002) and encryption (E002).              |
| HPB         | Download the Bank public signatures authentication (X002) and encryption (E002).                                  |
| SPR         | Suspend activated keyring.                                                                                        |
| HPD         | Download the bank server parameters.                                                                              |
| HKD         | Download customer's customer and subscriber information.                                                          |
| HTD         | Download subscriber's customer and subscriber information.                                                        |
| HAA         | Download Bank available order types.                                                                              |
| PTK         | Download transaction status.                                                                                      |
| FDL         | Download the files from the bank.                                                                                 |
| FUL         | Upload the files to the bank.                                                                                     |
| VMK         | Download the interim transaction report in SWIFT format (MT942).                                                  |
| STA         | Download the bank account statement.                                                                              |
| BKA         | Download electronic account statement in pdf format.                                                              |
| C52         | Download the bank account report in Camt.052 format.                                                              |
| C53         | Download the bank account statement in Camt.053 format.                                                           |
| C54         | Download Debit Credit Notification (DTI).                                                                         |
| Z52         | Download the bank account report in Camt.052 format (i.e Switzerland financial services).                         |
| Z53         | Download the bank account statement in Camt.053 format (i.e Switzerland financial services).                      |
| Z54         | Download the bank account statement in Camt.054 format (i.e available in Switzerland).                            |
| ZSR         | Download Order/Payment Status report.                                                                             |
| XEK         | Download account information as PDF-file.                                                                         |
| CCT         | Upload initiation of the credit transfer per Single Euro Payments Area.                                           |
| CIP         | Upload initiation of the instant credit transfer per Single Euro Payments Area.                                   |
| XE2         | Upload initiation of the Swiss credit transfer (i.e available in Switzerland).                                    |
| XE3         | Upload SEPA Direct Debit Initiation, CH definitions, CORE (i.e available in Switzerland).                         |
| YCT         | Upload Credit transfer CGI (SEPA & non SEPA).                                                                     |
| CDD         | Upload initiation of the direct debit transaction.                                                                |
| CDB         | Upload initiation of the direct debit transaction for business.                                                   |
| BTD         | Download request files of any BTF structure.                                                                      |
| BTU         | Upload the files to the bank.                                                                                     |
| HVU         | Download List the orders for which the user is authorized as a signatory.                                         |
| HVZ         | Download VEU overview with additional information.                                                                |
| HVE         | Upload VEU signature for order.                                                                                   |
| HVD         | Download the state of a VEU order.                                                                                |
| HVT         | Download detailed information about an order from VEU processing for which the user is authorized as a signatory. |

If you need to parse Cfonb 120, 240, 360 use [ebics-api/cfonb-php](https://github.com/ebics-api/cfonb-php)  
If you need to parse MT942 use [ebics-api/mt942-php](https://github.com/ebics-api/mt942-php)  

## Keyring schema
```json
{
    "VERSION": "VERSION_24|VERSION_25|VERSION_30",
    "USER": {
        "A": {
            "VERSION": "A005|A006",
            "CERTIFICATE": "null|string",
            "PUBLIC_KEY": "string",
            "PRIVATE_KEY": "string"
        },
        "E": {
            "CERTIFICATE": "null|string",
            "PUBLIC_KEY": "string",
            "PRIVATE_KEY": "string"
        },
        "X": {
            "CERTIFICATE": "null|string",
            "PUBLIC_KEY": "string",
            "PRIVATE_KEY": "string"
        }
    },
    "BANK": {
        "E": {
            "CERTIFICATE": "null|string",
            "PUBLIC_KEY": "null|string",
            "PRIVATE_KEY": null
        },
        "X": {
            "CERTIFICATE": null,
            "PUBLIC_KEY": "null|string",
            "PRIVATE_KEY": null
        }
    }
}
```

## Backlog
 - Format validators for all available ISO 20022 formats:
   - upload: PAIN.001, PAIN.008, MT101, TA875, CFONB320, CFONB160 with different versions.
   - download: PAIN.002, CAMT.052, CAMT.053, CAMT.054, MT199, MT900, MT910, MT940, MT942, CFONB240,CFONB245, CFONB120
 - Country's Bank specific order types
 - Refactor by abstraction Download and Upload Order types
