<?php

if (! function_exists('asset_v')) {
    /**
     * asset(), with the file's own timestamp on the end.
     *
     * The project has no build step, so a changed .js or .css keeps the same URL
     * and browsers go on serving the copy they already hold — a fix can be live
     * on the server and invisible to everyone who has been on the site before.
     */
    function asset_v(string $path): string
    {
        $file = public_path($path);
        $stamp = is_file($file) ? filemtime($file) : null;

        return $stamp ? asset($path).'?v='.$stamp : asset($path);
    }
}
