<?php

namespace Plausible\Analytics\WP\Client\Lib\GuzzleHttp;

use Plausible\Analytics\WP\Client\Lib\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
