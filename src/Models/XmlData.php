<?php

namespace EbicsApi\Ebics\Models;

use EbicsApi\Ebics\Contracts\OrderDataInterface;

/**
 * Class OrderData represents OrderData model.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
abstract class XmlData extends DOMDocument implements OrderDataInterface
{
    public function __construct()
    {
        parent::__construct('1.0', 'utf-8');
        $this->preserveWhiteSpace = false;
    }

    public function getContent(): string
    {
        $content = (string)$this->saveXML();
        $content = str_replace(
            '<?xml version="1.0" encoding="utf-8"?>',
            "<?xml version='1.0' encoding='utf-8'?>",
            $content
        );
        $content = str_replace(["\n", "\r", "\t"], '', $content);
        $content = trim($content);

        return $content;
    }

    public function getFormattedContent(): string
    {
        $this->formatOutput = true;

        return (string)$this->saveXML();
    }
}
