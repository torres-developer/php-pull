<?php

/**
 *      Pull - An wrapper for the curl extension using PHP 8 \Fiber class
 *      Copyright (C) 2023  João Torres
 *
 *      This program is free software: you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation, either version 3 of the License, or
 *      (at your option) any later version.
 *
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *
 *      You should have received a copy of the GNU General Public License
 *      along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @package TorresDeveloper\\Pull
 * @author João Torres <torres.dev@disroot.org>
 * @copyright Copyright (C) 2023 João Torres
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License version 3
 *
 * @since 0.0.1
 * @version 0.0.1
 */

declare(strict_types=1);

namespace TorresDeveloper\Pull;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TorresDeveloper\HTTPMessage\Headers;
use TorresDeveloper\HTTPMessage\HTTPVerb;
use TorresDeveloper\HTTPMessage\URI;

function pull(
    string|URI $resource,
    HTTPVerb $method = HTTPVerb::GET,
    StreamInterface|\SplFileObject|string|null|array $body = null,
    Headers|iterable $headers = []
): ResponseInterface {
    $req = new Request($resource, $method, $body, $headers);

    return Pull::fetch()->start($req);
}
