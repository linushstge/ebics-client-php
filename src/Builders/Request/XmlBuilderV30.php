<?php

namespace EbicsApi\Ebics\Builders\Request;

use Closure;
use EbicsApi\Ebics\Handlers\Traits\H005Trait;

/**
 * Ebics 3.0 XmlBuilder.
 *
 * @license http://www.opensource.org/licenses/mit-license.html  MIT License
 * @author Andrew Svirin
 */
final class XmlBuilderV30 extends XmlBuilder
{
    use H005Trait;

    public function addHeader(Closure $callback): XmlBuilder
    {
        $headerBuilder = new HeaderBuilderV3($this->cryptService, $this->dom);
        $header = $headerBuilder->createInstance()->getInstance();
        $this->instance->appendChild($header);

        call_user_func($callback, $headerBuilder);

        return $this;
    }

    public function addBody(Closure $callback = null): XmlBuilder
    {
        $bodyBuilder = new BodyBuilderV3($this->zipService, $this->cryptService, $this->dom);
        $body = $bodyBuilder->createInstance()->getInstance();
        $this->instance->appendChild($body);

        if (null !== $callback) {
            call_user_func($callback, $bodyBuilder);
        }

        return $this;
    }
}
