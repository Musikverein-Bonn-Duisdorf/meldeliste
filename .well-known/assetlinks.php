<?php
/**
 * Fallback if the webserver does not serve /.well-known/assetlinks.json as a static file.
 * Prefer deploying .well-known/assetlinks.json directly at the site root.
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
readfile(__DIR__.'/assetlinks.json');
