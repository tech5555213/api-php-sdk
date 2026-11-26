#!/bin/sh
# Starts the fake API, runs the tests, stops the server.
cd "$(dirname "$0")/.."
php -S 127.0.0.1:8765 tests/fake-api.php >/dev/null 2>&1 &
PID=$!
sleep 1
LOAMHILL_FAKE_API=http://127.0.0.1:8765 php tests/run.php
CODE=$?
kill $PID
exit $CODE
