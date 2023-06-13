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

use Psr\Http\Message\RequestInterface;
use TorresDeveloper\HTTPMessage\Headers;
use TorresDeveloper\HTTPMessage\HTTPVerb;
use TorresDeveloper\HTTPMessage\Stream;

/**
 * The class that gives an API to use the curl extension
 *
 * @author João Torres <torres.dev@disroot.org>
 *
 * @since 0.0.1
 * @version 0.0.2
 */
class Pull
{
    final private function __construct()
    {
    }

    public static function fetch(): \Fiber
    {
        return new \Fiber(static::pull(...));
    }

    private static function pull(
        RequestInterface $req,
        array $opts = [],
        bool $verbose = false
    ): void {
        $handle = curl_init((string) $req->getUri());

        $headers = $req->getHeaders();
        $headers_opts = [];
        foreach ($headers as $h => $v) {
            $headers_opts[] = "$h: " . implode(", ", $v);
        }

        curl_setopt_array($handle, [
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers_opts,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_VERBOSE => $verbose,
        ]);

        curl_setopt_array($handle, $opts);

        try {
            $contents = $req->getBody()->getContents();
        } catch (\RuntimeException) {
            $contents = null;
        }

        if ($req instanceof Request && is_array($req->getBodyIsArray())) {
            $contents = $req->getBodyIsArray();
        }

        switch ($req->getMethod()) {
            case HTTPVerb::GET->value:
                break;
            case HTTPVerb::POST->value:
                curl_setopt_array($handle, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $contents,
                ]);
                break;
            default:
                curl_setopt_array($handle, [
                    CURLOPT_CUSTOMREQUEST => HTTPVerb::from($req->getMethod())->value,
                    CURLOPT_POSTFIELDS => $contents,
                ]);
                break;
        }

        $buf = curl_exec($handle);

        if ($buf === false) {
            throw new \RuntimeException(sprintf("[%d]: %s", curl_errno($handle), curl_error($handle)));
        }

        $parts = explode("\r\n\r\n", $buf);
        $responseHeadersRaw = $parts[count($parts) - 2] ?? null;
        $body = $parts[count($parts) - 1] ?? null;

        if ($body === null) {
            $body = $responseHeadersRaw;
            $responseHeadersRaw = null;
        }
        $info = curl_getinfo($handle);
        $status = $info["http_code"];

        curl_close($handle);

        $responseHeaders = new Headers();
        if ($responseHeadersRaw !== null) {
            $responseHeadersRaw = explode("\r\n", $responseHeadersRaw);
            foreach ($responseHeadersRaw as $h) {
                @[$k, $v] = explode(":", $h, 2);
                if ($k && $v) {
                    $responseHeaders->{trim($k)} = trim($v);
                }
            }
        }

        $res = new Response(
            $status,
            Response::STATUS[$status] ?? "",
            new Stream((string) $body),
            $responseHeaders,
        );

        \Fiber::suspend($res);
    }
}
