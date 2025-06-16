<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Auth;

use Psr\Http\Message\RequestInterface;

interface SharedAccessKeyInterface
{
    public function getAuthorization(RequestInterface $request): string;
}
