<?php
namespace Riemann;

use DrSlump\Protobuf;

class Client
{
    /**
     * @var Event[]
     */
    private $events;

    private $eventBuilderFactory;

    private $host;
    private $port;

    private $sockets;
    private $defaultProtocol;

    public function __construct($host = 'localhost', $port = 5555, $defaultProtocol = 'udp')
    {
        $this->host = $host;
        $this->port = $port;
        $this->defaultProtocol = $defaultProtocol;
        $this->eventBuilderFactory = new EventBuilderFactory();
    }    

    public function getEventBuilder($service)
    {
        $builder = $this->eventBuilderFactory->create($service);
        $builder->setClient($this);
        return $builder;
    }

    public function sendEvent(Event $event, $flush = true)
    {
        $this->events[] = $event;

        if ($flush) {
            $this->flush();
        }
    }

    private function getSocket($requestedProtocol, $size) {
        // Over a certain size we send TCP
        if ($size > 1024*4) {
            $protocol = 'tcp';
        } elseif ($requestedProtocol) {
            $protocol = $requestedProtocol;
        } else {
            $protocol = $this->defaultProtocol;
        }

        // do we have already have a socket created?
        if (isset($this->sockets[$protocol])) {
            return $this->sockets[$protocol];
        }

        $socket = @fsockopen("{$protocol}://{$this->host}", $this->port, $errno, $errstr, 1);
        if (!$socket) {
            error_log("Failed opening socket to Riemann: $errstr [$errno]");
            return false;
        }
        $this->sockets[$protocol] = $socket;

        return $socket;
    }

    public function flush($protocol = null)
    {
        $message = new Msg();
        $message->ok = true;
        $message->events = $this->events;
        $this->events = array();
        $data = Protobuf::encode($message);
        $size = strlen($data);

        // get socket based on protocol
        if (!($socket = $this->getSocket($protocol, $size))) {
            return false;
        }

        if ($protocol === 'tcp') {
            // TCP requires the length to be sent first
            fwrite($socket, pack('N', $size));
        }
        
        fwrite($socket, $data);      

        return true;
    }
}
