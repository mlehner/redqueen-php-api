<?php

declare(strict_types=1);

namespace BLInc\Model;

final class CardSerialNumber
{
    /**
     * @var int
     */
    private $facilityCode;

    /**
     * @var int
     */
    private $cardNumber;

    public static function createFromHex(string $hexCsn): self
    {
        if (!preg_match('/^[\da-f]{6}$/i', $hexCsn)) {
            throw new \InvalidArgumentException('Invalid CSN provided.');
        }

        $parts = unpack('Ccode/nnumber', hex2bin($hexCsn));

        if (!isset($parts['code'], $parts['number'])) {
            throw new \InvalidArgumentException('Failed to unpack CSN provided.');
        }

        return new self($parts['code'], $parts['number']);
    }

    public static function createFromStrings(string $facilityNumber, string $cardNumber): self
    {
        return new self((int)$facilityNumber, (int)$cardNumber);
    }

    public function __construct(int $facilityCode, int $cardNumber)
    {
        $this->facilityCode = $facilityCode;
        $this->cardNumber = $cardNumber;
    }

    public function getFacilityCode(): int
    {
        return $this->facilityCode;
    }

    public function getCardNumber(): int
    {
        return $this->cardNumber;
    }

    public function getHexCsn(): string
    {
        return strtoupper(bin2hex(pack('Cn', $this->facilityCode, $this->cardNumber)));
    }
}
