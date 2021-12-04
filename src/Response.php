<?php

namespace Hyqo\HTTP;

class Response
{
    /** @var array */
    private $headers = [];

    public function header(string $name, string $value): Response
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function contentType(string $value): Response
    {
        return $this->header(Header::CONTENT_TYPE, $value);
    }

    private function sendHeaders(): void
    {
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
    }

    public function send(string $data = ''): void
    {
        $this->sendHeaders();

        echo $data;
    }

    public function sendJSON(array $data = []): void
    {
        $this->contentType(ContentType::JSON);
        $this->sendHeaders();

        echo json_encode($data);
    }
}
