# Codeception TestRail Integration Module

This [Codeception](https://codeception.com) module allow for tests to report their results to
[TestRail](https://testrail.com) using the [TestRail API v2](http://docs.gurock.com/testrail-api2/start).

**Note:** The module currently only supports the `Cest` or `Cept` test format.  It cannot report PHPUnit tests.

## Theory of Operation
The module hooks the following Codeception actions: `_initialize()`, `_beforeSuite()`, `_before()`, `_after()`.  The
module currently makes no attempts to close the test plan.

During the `_initialize()` step, the module will fetch the project details and create a new plan for the run.  The plan
name can be procedurally generated but by default it will be the date and time formatted as *Y-m-d H:i;s*.

During `_beforeSuite()` the module will register the current suite as an entry in the test plan.  It will check to see
if the suite already has an entry and will reuse that one.  The suite entry will use all of the Test Cases from the
TestRail Test Suite.  There are currently no facilities to include only specific Test Cases from a TestRail Test Suite.

During `_before()` the module clears the test state and other prep work.

During `_after()` the module checks that a Test Case ID was set and then delegates to a helper method to log the result
in TestRail using the other details previously gathered.  This table illustrates the default status mapping for TestRail:

| Codeception Status | TestRail Status |
|:------------------ |:---------------:|
| SUCCESS            | 1               |
| FAILURE            | 5               |
| ERROR              | 5               |
| INCOMPLETE         | 5               |
| SKIPPED            | 5               |

# Configuration

The module requires four configuration parameters to be set (`user`, `apikey`, `project`, `suite`).  Out of the box the
module will work with any project type, including multi-suite projects.

## Setup the Access Credentials
The easiest way to setup access credentials is to add it to the global module configuration in the `codeception.yml` in
your projects root directory.

```yaml
modules:
    config:
        \BookIt\Codeception\TestRail\Module:
            user: 'mark.randles@bookit.com'
            apikey: 'thequickbrownfoxjumpsoverthelazydog'
```

We prefer to use an API key for access.  To generate an API key see the TestRail documents.  If you want, you can
also your password here.  But remember, it will be stored in plain text as part of your repository.

## Setup a Codeception Test Suite for Reporting
The easiest way to set the `project` and `suite` config keys is to add them to the module config for the test suite
for which they're used.  If you're using a multi-suite project, you may want to set the project in the core
`codeception.yml` config in your project root directory.

```yaml
modules:
    enabled:
        - Asserts
        - \Helper\Test
        - \BookIt\Codeception\TestRail\Module:
            project: 9
            suite: 57
```

# Usage
Add a test to report in TestRail is straightforward.  The module will only attempt to report results when a test case id
is available and the test is either a `Cest` or a `Cept`.

## Create the Test Case in TestRail
Before you can report the results, you must manually create the test case in TestRail.  Creation of these test cases is
no different then if you were using TestRail as a manual testing platform.  The actual contents of the test case doesn't
matter and the module will not change the contents of the test case.

## Register the TestRail Test Case with the Codeception Test
To connect the TestRail Test Case with the Codeception Test, you must call the `setTestCase` method provided from the
module.  This method takes the integer id of the TestRail Test Case as a parameter.  The last call to `setTestCase` in a
given Codeception test will be the TestRail Test Case id used for reporting.

When the Codeception test completes, the results will be reported to TestRail.
