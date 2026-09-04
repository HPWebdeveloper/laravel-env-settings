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

- test sensitive option marks the named properties
  - Assertions: assertStringContainsString

- test sensitive import is absent when the option is not used
  - Assertions: assertStringNotContainsString

- test sensitive option rejects unknown property names
  - Assertions: assertFileDoesNotExist

- test a generated sensitive class parses as valid php
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

## tests/Feature/CheckCommandTest.php

- test it fails when a factory was left at its placeholders
  - Assertions: assertSame, assertStringContainsString

- test it says which environment supplies a real value
  - Assertions: assertStringContainsString

- test it reports a todo left in the value
  - Assertions: assertStringContainsString

- test a value that is empty everywhere is not reported
  - Assertions: assertStringNotContainsString

- test an allow empty property is never reported
  - Assertions: assertStringNotContainsString

- test it passes when every class is complete
  - Assertions: assertSame, assertStringContainsString

- test the environment being checked passes when it is the filled one
  - Assertions: assertStringNotContainsString

- test it defaults to the current environment
  - Assertions: assertSame, assertStringContainsString

- test it can check a single class without registration
  - Assertions: assertSame, assertStringContainsString

- test it fails on a class that is not a settings class
  - Assertions: assertSame, assertStringContainsString

- test it warns when nothing is registered
  - Assertions: assertSame, assertStringContainsString

- test a non array register config does not crash
  - Assertions: assertSame

## tests/Feature/EnumValuedSettingsTest.php

- test an enum property resolves per environment
  - Assertions: assertSame

- test a backed enum is unwrapped to its value
  - Assertions: assertSame

- test a pure enum is unwrapped to its name
  - Assertions: assertSame

- test enums inside arrays are unwrapped
  - Assertions: assertSame

- test the whole payload survives json encode
  - Assertions: assertIsString, assertJson, assertStringContainsString

- test non enum values are untouched
  - Assertions: assertSame

- test show prints enum values rather than the object
  - Assertions: assertStringContainsString, assertStringNotContainsString

- test diff prints enum values and marks the difference
  - Assertions: assertStringNotContainsString, assertStringContainsString, assertMatchesRegularExpression

- test an identical enum value is not flagged as differing
  - Assertions: assertStringContainsString

- test enum cases are identical across environments
  - Assertions: assertNotSame, assertSame

## tests/Feature/EnvironmentAttributeTest.php

- test it resolves an environment named by the attribute
  - Assertions: assertSame

- test one method can serve several environments
  - Assertions: assertSame

- test the method name need not match the environment
  - Assertions: assertSame

- test an unmarked environment still falls back
  - Assertions: assertSame

- test the attribute wins over the environment map
  - Assertions: assertSame

- test classes without attributes are unaffected
  - Assertions: assertSame

- test the environment map still applies to unmarked environments
  - Assertions: assertSame

- test a subclass inherits the mapping when it redeclares a marked method
  - Assertions: assertSame

- test a marked method that is not a usable factory is ignored
  - Assertions: assertSame

- test repeated resolution is consistent
  - Assertions: assertSame

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

## tests/Feature/SensitiveMaskingTest.php

- test show masks a marked property the name heuristic would miss
  - Assertions: assertStringNotContainsString, assertStringContainsString

- test diff masks marked properties
  - Assertions: assertStringNotContainsString

- test diff still reports a masked property as differing
  - Assertions: assertMatchesRegularExpression, assertStringContainsString

- test unmarked property matching the name heuristic is still masked
  - Assertions: assertStringNotContainsString

- test diff masks unmarked properties matching the heuristic
  - Assertions: assertStringNotContainsString

- test ordinary values are not masked
  - Assertions: assertStringContainsString

- test an empty marked property is not masked
  - Assertions: assertMatchesRegularExpression

- test numeric properties matching the name heuristic are not masked
  - Assertions: assertStringContainsString

- test a marked non string property is masked
  - Assertions: assertStringNotContainsString

- test numeric heuristic properties are not masked in diff
  - Assertions: assertStringContainsString

- test to array returns real values
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
