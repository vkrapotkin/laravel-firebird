<?php

namespace Firebird\Support;

class Version
{
    public static const FIREBIRD_15 = '1.5';
    public static const FIREBIRD_25 = '2.5';

    public static const SUPPORTED_VERSIONS = [
        self::FIREBIRD_15,
        self::FIREBIRD_25,
    ];
}
