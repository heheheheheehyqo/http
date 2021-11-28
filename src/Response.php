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

    public function json(): Response
    {
        $this->contentType(ContentType::JSON);

        return $this;
    }

    /**
     * @return \Generator|string[]
     */
    private function packHeaders(): \Generator
    {
        foreach ($this->headers as $name => $value) {
            yield sprintf('%s: %s', $name, $value);
        }
    }

    private function packContent($data): string
    {
        if (is_array($data)) {
            $this->json();

            return json_encode($data);
        }

        return $data;
    }

    private function sendHeaders(): void
    {
        foreach ($this->packHeaders() as $header) {
            header($header);
        }
    }

    public function send($data = null): void
    {
        if ($data === null) {
            $this->sendHeaders();
            return;
        }

        $data = $this->packContent($data);

        $this->sendHeaders();
        echo $data;
    }
}
