<?php
namespace Riemann;

class EventBuilder
{
    private $host;
    private $tags;
    private $service;
    private $metric = 1;

    /**
     * @var Client
     */
    private $client;

    public function __construct(
        $service,
        $host,
        array $initialTags = array()
    ) {
        $this->service = $service;
        $this->host = $host;
        $this->tags = $initialTags;
    }

    public function setService($service)
    {
        $this->service = $service;
        return $this;
    }

    public function setMetric($metric)
    {
        $this->metric = $metric;
        return $this;
    }

    public function addTag($tag)
    {
        $this->tags[] = $tag;
        return $this;
    }

    public function build()
    {
        if (!$this->service) {
            throw new \RuntimeException('A service has to be set.');
        }
        $event = new Event();
        $event->host = $this->host;
        $event->time = (new \DateTime())->getTimestamp();
        $event->service = $this->service;
        $event->tags = $this->tags;

        $floatMetric = (float)$this->metric;
        $event->metric_f = $floatMetric;
        if (is_int($this->metric)) {
            $event->metric_sint64 = $this->metric;
        } else {
            $event->metric_d = $floatMetric;
        }

        return $event;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function sendEvent($flush = true)
    {
        $this->client->sendEvent($this->build(), $flush);
    }
}
