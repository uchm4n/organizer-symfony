<?php

declare(strict_types=1);

namespace App\Shared\Logging;

use Monolog\Formatter\LineFormatter;

final class SlimLineFormatter extends LineFormatter
{
    public function __construct()
    {
        parent::__construct(
            format: "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            dateFormat: 'Y-m-d H:i:s',
            allowInlineLineBreaks: false,
            ignoreEmptyContextAndExtra: true,
        );

        $this->setBasePath(dirname(__DIR__, 3));
    }
}
