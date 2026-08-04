<?php

if (! function_exists('uuid_v4')) {
    /**
     * RFC 4122 version 4 UUID from random bytes. Used for every primary key;
     * MySQL cannot default CHAR(36) columns to UUID(), so IDs are minted here.
     */
    function uuid_v4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
