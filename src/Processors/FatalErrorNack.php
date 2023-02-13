<?php

namespace Puzzle\AMQP\Processors;

use Psr\Log\LoggerInterface;
use Swarrot\Broker\MessageProvider\MessageProviderInterface;
use Swarrot\Processor\ProcessorInterface;
use Swarrot\Broker\Message;

class FatalErrorNack implements ProcessorInterface
{
    public function __construct(ProcessorInterface $processor, MessageProviderInterface $messageProvider)
    {
        $this->processor = $processor;
        $this->messageProvider = $messageProvider;
    }

    public function process(Message $message, array $options)
    {
        $messageProvider = $this->messageProvider;

        register_shutdown_function(function() use($messageProvider, $message) {
            $error = error_get_last();

            if(null !== $error)
            {
                if($error["type"] === E_ERROR)
                {
                    $messageProvider->nack($message);
                }
            }
        });

        return $this->processor->process($message, $options);
    }
}
