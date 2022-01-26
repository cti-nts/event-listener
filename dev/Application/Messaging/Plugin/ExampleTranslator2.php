<?php

namespace Application\Messaging\Plugin;

use Application\Messaging\Message;
use Application\Messaging\Translator;

class ExampleTranslator2 implements Translator
{
    public function translate(Message $message): Message
    {
        $body = $message->getBody();
        $body.= "? (translated by ExampleTranslator2)";
        return $message
            ->withBody($body)
            ->withHeader(name: 'translated', value: true)
            ->withProperty(name: 'addedProperty', value: 'aValue');
    }
}
