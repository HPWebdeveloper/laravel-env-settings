# Test inventory for hpwebdeveloper/laravel-env-settings

Generated automatically by `bin/generate-test-inventory`. Edit tests to update this list.

## tests/Commands/DiffEnvSettingsCommandInteractiveTest.php

- test it prompts for the class when none is given

- test it maps a later choice back to the right class

- test it prompts for both environments when only a class is given

- test the second environment prompt excludes the first choice

- test an invalid class argument falls back to the prompt

- test it reports when nothing is registered to choose from

- test it reports when the register config is not an array

- test registered entries that are not settings classes are skipped

## tests/Commands/DiffEnvSettingsCommandTest.php

- test diff shows differences between environments
  - Assertions: assertSame, assertStringContainsString

- test diff shows no differences when same env
  - Assertions: assertSame, assertStringContainsString

- test diff works with staging
  - Assertions: assertSame, assertStringContainsString

- test diff fails for invalid class
  - Assertions: assertSame

- test diff fails for invalid environment method
  - Assertions: assertSame

- test diff marks different values
  - Assertions: assertSame, assertStringContainsString

## tests/Commands/MakeEnvSettingsCommandTest.php

- test it creates a settings class file
  - Assertions: assertFileExists

- test generated file contains correct class name
  - Assertions: assertStringContainsString

- test generated file has development and production methods
  - Assertions: assertStringContainsString

- test generated file has default example property when no properties given
  - Assertions: assertStringContainsString

- test properties option generates typed constructor
  - Assertions: assertStringContainsString

- test properties option generates correct defaults in factory methods
  - Assertions: assertStringContainsString

- test it fails if file already exists

- test generated file has correct use statement
  - Assertions: assertStringContainsString

- test it uses the configured namespace when no path is given
  - Assertions: assertSame

- test it derives the namespace from a path inside the app root
  - Assertions: assertSame

- test derived namespace collapses relative path segments
  - Assertions: assertSame

- test it warns and falls back when path is outside the app root
  - Assertions: assertSame

- test explicit namespace option wins over derivation
  - Assertions: assertSame

- test explicit namespace option is not warned about outside the app root
  - Assertions: assertSame

- test it fails on an invalid explicit namespace
  - Assertions: assertFileDoesNotExist

- test it ignores an invalid configured namespace
  - Assertions: assertSame

- test it registers a class whose name matches a commented out example
  - Assertions: assertStringContainsString

- test it does not duplicate a class that is already registered
  - Assertions: assertSame

- test it appends to an empty register array
  - Assertions: assertStringContainsString

- test it warns when the config has not been published
  - Assertions: assertFileDoesNotExist

- test it warns when no register array is present

- test it does not corrupt a config whose register array contains a bracket
  - Assertions: assertSame

- test it finds existing entries beyond a bracket in a comment
  - Assertions: assertSame

- test it does not corrupt a config with a block comment in the register array
  - Assertions: assertSame

## tests/Commands/ShowEnvSettingsCommandTest.php

- test show displays resolved settings for a class
  - Assertions: assertSame, assertStringContainsString

- test show displays all registered classes
  - Assertions: assertSame, assertStringContainsString

- test show warns when no classes registered
  - Assertions: assertSame, assertStringContainsString

- test show fails for invalid class
  - Assertions: assertSame

- test show displays boolean values correctly
  - Assertions: assertSame, assertStringContainsString

## tests/Feature/OverrideTest.php

- test resolve uses override class when enabled
  - Assertions: assertSame

- test override class still resolves environment correctly
  - Assertions: assertSame

- test resolve ignores override when disabled
  - Assertions: assertSame

- test resolve falls back to normal when no override file
  - Assertions: assertSame

- test resolve falls back when override path missing
  - Assertions: assertSame

- test resolve falls back when override namespace is null
  - Assertions: assertSame

- test relative override path is resolved against app path
  - Assertions: assertSame

- test null override path uses the app settings overrides convention
  - Assertions: assertSame

- test empty override path uses the app settings overrides convention
  - Assertions: assertSame

- test absolute override path is used as is
  - Assertions: assertSame

- test resolve falls back when override path is not a string
  - Assertions: assertSame

## tests/Feature/RegistrationTest.php

- test auto registered class resolves for current environment
  - Assertions: assertSame, assertTrue

- test auto registered class resolves development for local
  - Assertions: assertSame, assertFalse

- test multiple classes can be auto registered
  - Assertions: assertSame

- test auto registered classes are singletons
  - Assertions: assertSame

- test invalid class in register array is ignored
  - Assertions: assertInstanceOf

- test manual singleton registration works
  - Assertions: assertSame

- test manual registration is singleton
  - Assertions: assertSame

- test root settings resolves nested sub settings
  - Assertions: assertInstanceOf, assertSame

- test root settings resolves development nested
  - Assertions: assertSame

- test root settings resolves staging nested
  - Assertions: assertSame

- test root settings can be auto registered
  - Assertions: assertSame

- test env settings helper works with auto registration
  - Assertions: assertSame

- test env settings helper works with nested root
  - Assertions: assertSame

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

## tests/Unit/HelpersTest.php

- test env settings helper resolves class
  - Assertions: assertInstanceOf, assertSame
