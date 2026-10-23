#!/usr/bin/env bash
# Check unsigned refusal against a disposable local site using shipped XML-RPC defaults.
# Usage: tests/integration/assert-multicall-refusal.sh http://localhost:8890
set -euo pipefail
site_url=${1:?Pass the URL of a disposable local WordPress site}
response_file=$(mktemp)
trap 'rm -f "$response_file"' EXIT
for query in '' '?for=jetpack'; do
  http_status=$(curl --silent --show-error --max-time 30 --output "$response_file" --write-out '%{http_code}' \
    --header 'Content-Type: text/xml' --data-binary '<?xml version="1.0"?><methodCall><methodName>system.multicall</methodName><params><param><value><array><data></data></array></value></param></params></methodCall>' \
    "${site_url%/}/xmlrpc.php${query}")
  test "$http_status" = 200 || { echo "Expected HTTP 200, got $http_status" >&2; exit 1; }
  php -r '$xml = simplexml_load_file($argv[1]); $codes = $xml ? $xml->xpath("/methodResponse/fault/value/struct/member[name=\"faultCode\"]/value/int") : array(); if (count($codes) !== 1 || (int) $codes[0] !== -32601) { fwrite(STDERR, "Expected XML-RPC fault -32601\n"); exit(1); }' "$response_file"
  echo "Unsigned multicall${query}: HTTP 200, fault -32601"
done
