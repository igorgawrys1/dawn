# Security Policy

Dawn is a development/testing dependency and must never run in production -
its service provider refuses to register routes in the `production`
environment, mirroring Laravel Dusk.

If you discover a security vulnerability (for example a way for the
`/_dawn/*` authentication routes to be exposed outside local/testing
environments), please email **igor@gawrys.me** instead of opening a public
issue. You will receive a response within 72 hours.
