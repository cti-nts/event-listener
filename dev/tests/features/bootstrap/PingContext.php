<?php

declare(strict_types=1);

use Assert\Assert;
use Behat\Behat\Context\Context;
use Curl\Curl;

/**
 * Defines application features from the specific context.
 */
class PingContext implements Context
{
    private int $port;

    private string $host;

    private Curl $curl;

    /**
     * @Given The service port is defined
     */
    public function theServicePortIsDefined(): void
    {
        $this->port = (int)getenv('HTTP_PORT');
        $this->host = getenv('HTTP_HOST') ?: 'localhost';
    }

    /**
     * @When I do http get
     */
    public function iDoHttpGet(): void
    {
        $this->curl = new Curl();
        $this->curl->get('http://' . $this->host . ':' . $this->port);
    }

    /**
     * @Then I should get an ack response
     */
    public function iShouldGetAnAckResponse(): void
    {
        Assert::that($this->curl->error)->noContent();
        Assert::that($this->curl->response)->notEmpty();
        Assert::that('Content-Type: application/json')->inArray($this->curl->response_headers);
        Assert::that($this->curl->response)->isJsonString();
        Assert::that(json_decode($this->curl->response))->propertyExists('ack');
        Assert::that(json_decode($this->curl->response)->ack)->notEmpty();
    }
}
