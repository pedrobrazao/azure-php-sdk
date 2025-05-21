<?php

declare(strict_types=1);

namespace AzurePhp\Storage\Common\Factory;

final class DateFactory
{
    public static function toRfc8601Zulu(?\DateTimeInterface $date = null): string
    {
        if (null === $date) {
            $date = new \DateTime();
        }

        return \DateTime::createFromInterface($date)
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s\Z')
        ;
    }

    public static function fromRfc1123(string $date): \DateTimeImmutable
    {
        return  \DateTimeImmutable::createFromFormat(\DateTimeInterface::RFC1123, $date);
    }
}
