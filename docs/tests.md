# Test inventory for this package

Generated automatically by `bin/generate-test-inventory`. Edit tests to update this list.

## tests/Feature/ServiceProviderTest.php

- test config is merged
  - Assertions: assertIsArray, assertArrayHasKey

- test auto registered settings are singletons
  - Assertions: assertSame

## tests/Unit/EnvironmentSettingsTest.php

- test resolve returns production when env is production
  - Assertions: assertSame, assertTrue

- test resolve returns development when env is local
  - Assertions: assertSame, assertFalse

- test resolve returns staging when env is staging
  - Assertions: assertSame

- test resolve maps dev to development
  - Assertions: assertSame

- test resolve falls back to development for unknown env
  - Assertions: assertSame

- test staging defaults to development when not overridden
  - Assertions: assertSame

- test testing defaults to development when not overridden
  - Assertions: assertSame

- test resolve respects custom environment map
  - Assertions: assertSame

- test resolve respects fallback environment config
  - Assertions: assertSame

- test settings properties are typed
  - Assertions: assertIsString, assertIsInt, assertIsBool

## tests/Unit/HelpersTest.php

- test env settings helper resolves class
  - Assertions: assertInstanceOf, assertSame
